<?php
declare(strict_types=1);


namespace Neunerlei\DbgGlobal\Composer;


use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Package\RootPackageInterface;
use Composer\Plugin\PluginInterface;
use Neunerlei\FileSystem\Fs;
use Neunerlei\FileSystem\Path;

class InstallerPlugin implements PluginInterface, EventSubscriberInterface
{
    public const TARGET_PACKAGE_NAME = 'neunerlei/dbg';
    
    /**
     * @var Composer
     */
    protected $composer;
    
    /**
     * @var IOInterface
     */
    protected $io;
    
    public static function getSubscribedEvents(): array
    {
        return [
            'pre-autoload-dump' => ['onAutoloadDump', -500],
        ];
    }
    
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
    }
    
    /**
     * Handles the "pre-autoload-dump" by updating the global installation and adding
     * its autoload.php to the autoload list of the current project.
     *
     * @return void
     */
    public function onAutoloadDump(): void
    {
        if ($this->abortOnGlobalInstallation()) {
            return;
        }
        
        if ($this->abortIfPresentInLocalInstallation()) {
            return;
        }
        
        if (! $this->installWrapper($this->getGlobalInstallPath())) {
            return;
        }
        
        if (
            ! $this->registerAutoloadFile(
                $this->getGlobalAutoloadPath(),
                $this->composer->getPackage()
            )
        ) {
            return;
        }
        
        $this->provideShimFile(
            $this->getShimSourcePath(),
            $this->getShimTargetPath()
        );
        
        $this->io->write('<info>Successfully injected "' . static::TARGET_PACKAGE_NAME . '" into your project!</info>');
    }
    
    /**
     * Checks if the current vendor-directory is a subdirectory of the composer home directory.
     * If that is the case, we can safely assume, that the installation is done globally...
     *
     * @return bool
     */
    protected function isGlobalInstallation(): bool
    {
        $config = $this->composer->getConfig();
        
        return Path::isBasePath($config->get('home'), $config->get('vendor-dir'));
    }
    
    /**
     * Checks if the dev package is already present in the local installation,
     * either via "require" or "require-dev", or has been replaced by another package.
     *
     * @return bool
     */
    protected function isPresentInLocalInstallation(): bool
    {
        $packages = $this->composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages();
        foreach ($packages as $package) {
            if ($package->getPrettyName() === static::TARGET_PACKAGE_NAME) {
                return true;
            }
            
            foreach ($package->getReplaces() as $replacement) {
                if ($replacement->getTarget() === static::TARGET_PACKAGE_NAME) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Returns the absolute path to the global installation directory of the package
     *
     * @return string
     */
    protected function getGlobalInstallPath(): string
    {
        return Path::join(__FILE__, '../../../Wrap');
    }
    
    /**
     * Returns the absolute path to the global installation's autoload file to be included
     * in the other packages that should be provided with the debug utilities
     *
     * @return string
     */
    protected function getGlobalAutoloadPath(): string
    {
        return Path::join($this->getGlobalInstallPath(), 'vendor/autoload.php');
    }
    
    /**
     * Returns the path where the shim source file is located
     *
     * @return string
     */
    protected function getShimSourcePath(): string
    {
        return Path::join(
            $this->getGlobalInstallPath(),
            'vendor/' . static::TARGET_PACKAGE_NAME . '/functions.php'
        );
    }
    
    /**
     * Returns the path where the shim file should be placed in the current project
     *
     * @return string
     */
    protected function getShimTargetPath(): string
    {
        return Path::join(
            $this->composer->getConfig()->get('vendor-dir'),
            'neunerlei-dbg-global-function-shim.php'
        );
    }
    
    /**
     * Checks if we are in a global installation, writes a log message and returns true if so.
     *
     * @return bool True if the process should abort, false if not
     */
    protected function abortOnGlobalInstallation(): bool
    {
        if ($this->isGlobalInstallation()) {
            $this->io->write(
                'Ignoring installation of "' . static::TARGET_PACKAGE_NAME . '", because this is a global installation',
                true,
                IOInterface::VERBOSE
            );
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Checks if the package is already in the local installation, writes a log message and returns true if so.
     *
     * @return bool True if the process should abort, false if not
     */
    protected function abortIfPresentInLocalInstallation(): bool
    {
        if ($this->isPresentInLocalInstallation()) {
            $this->io->write(
                'Ignoring installation of "' . static::TARGET_PACKAGE_NAME . '", because it is already present in the dependency list',
                true,
                IOInterface::VERBOSE
            );
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Runs a composer update in the global installation path to load the required dependencies
     *
     * @param   string  $installationPath  The output of "getGlobalInstallPath()"
     *
     * @return bool
     */
    protected function installWrapper(string $installationPath): bool
    {
        if (! function_exists('shell_exec')) {
            $this->io->write('<error>Can\'t install "' . static::TARGET_PACKAGE_NAME . '" as global dependency, because the required function "shell_exec" was disabled!</error>');
            
            return false;
        }
        
        $this->io->write(
            'Trying to install/update "' . static::TARGET_PACKAGE_NAME . '" as a global dependency using a sub-composer call at...',
            true,
            IOInterface::VERBOSE
        );
        
        $composerBinPath = getenv('COMPOSER_BINARY');
        
        $res = shell_exec(
            'cd "' . $installationPath . '" && "' . $composerBinPath . '" update --optimize-autoloader 2>&1'
        );
        
        if (empty($res) || ! is_string($res)) {
            $this->io->write('<error>Failed to install neunerlei/dev as a global dependency!</error>');
        }
        
        $expectedLastLine = 'No security vulnerability advisories found';
        $res = trim($res);
        if (substr($res, -42) !== $expectedLastLine) {
            $this->io->write('<error>There seems to be an issue when installing "' . static::TARGET_PACKAGE_NAME . '" globally...</error>');
            $this->io->write($res);
            
            return false;
        }
        
        $this->io->write(
            '<info>Global installation of "' . static::TARGET_PACKAGE_NAME . '" is ready to be used!</info>',
            true,
            IOInterface::VERBOSE
        );
        
        return true;
    }
    
    /**
     * Registers the global autoload file as a part of the currently installed bundle.
     *
     * @param   string                $autoloadPath  The output of getGlobalAutoloadPath()
     * @param   RootPackageInterface  $package       The package to extend the autoload declaration for
     *
     * @return bool
     */
    protected function registerAutoloadFile(string $autoloadPath, RootPackageInterface $package): bool
    {
        if (! is_readable($autoloadPath)) {
            $this->io->write('<error>The global autoload file of "' . static::TARGET_PACKAGE_NAME . '" at "' . $autoloadPath . '" is not readable!</error>');
            
            return false;
        }
        
        if (! is_callable([$package, 'setAutoload'])) {
            $this->io->write('<error>The provided root package does not allow autoload override! That prevents the injection of "' . static::TARGET_PACKAGE_NAME . '" as dependency!</error>');
            
            return false;
        }
        
        $autoload = $package->getAutoload();
        if (! is_array($autoload['files'] ?? null)) {
            $autoload['files'] = [];
        }
        
        array_unshift($autoload['files'], $autoloadPath);
        $package->setAutoload($autoload);
        
        return true;
    }
    
    /**
     * Provides a copy of the functions.php in neunerlei/dbg as a shim for the current project.
     *
     * @param   string  $sourcePath  the output of getShimSourcePath()
     * @param   string  $targetPath  the output of getShimTargetPath()
     *
     * @return void
     */
    protected function provideShimFile(string $sourcePath, string $targetPath): void
    {
        try {
            Fs::remove($targetPath);
            
            if ($this->isShimGenerationDisabled($targetPath)) {
                $this->io->write('Skipping shim generation, because it was disabled by the config', true, IOInterface::VERBOSE);
                
                return;
            }
            
            Fs::copy($sourcePath, $targetPath);
            $this->io->write(
                'Created "' . static::TARGET_PACKAGE_NAME . '" shim file at: "' . $targetPath . '"',
                true,
                IOInterface::VERBOSE
            );
        } catch (\Throwable $e) {
            $this->io->write(
                '<error>Failed to generate a "' . static::TARGET_PACKAGE_NAME .
                '"shim file, because an error occurred: "' . $e->getMessage() . '"</error>');
        }
    }
    
    /**
     * Checks if a shim file can be generated at the provided target path
     *
     * @param   string  $targetPath  The path to where the shim file should be generated
     *
     * @return bool
     */
    protected function isShimGenerationDisabled(string $targetPath): bool
    {
        $extra = Factory::createGlobal($this->io, true)->getPackage()->getExtra();
        if (! is_array($extra['neunerleiDevGlobal'] ?? null)) {
            return false;
        }
        
        $conf = $extra['neunerleiDevGlobal'];
        
        // Globally disabled
        if (! empty($conf['noShim'])) {
            return true;
        }
        
        // Check if the target path is in the list of disabled directories
        foreach ($conf as $k => $v) {
            if (strpos($k, 'noShimDirs.') === 0 && strpos($targetPath, $v) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * @inheritDoc
     */
    public function deactivate(Composer $composer, IOInterface $io) { }
    
    /**
     * @inheritDoc
     */
    public function uninstall(Composer $composer, IOInterface $io) { }
    
}