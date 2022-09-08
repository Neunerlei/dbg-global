<?php
declare(strict_types=1);


namespace Neunerlei\DbgGlobal\Composer;


use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\RootPackageInterface;
use Composer\Plugin\PluginInterface;
use Neunerlei\FileSystem\Path;

class InstallerPlugin implements PluginInterface, EventSubscriberInterface
{
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
        $installPath = $this->getGlobalInstallPath();
        if (! $this->installWrapper($installPath)) {
            return;
        }
        
        $autoloadPath = $this->getGlobalAutoloadPath();
        $rootPackage = $this->composer->getPackage();
        
        if (! $this->registerAutoloadFile($autoloadPath, $rootPackage)) {
            return;
        }
        
        $this->io->write('<info>Successfully injected neunerlei/dbg into your project!</info>');
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
     * Runs a composer update in the global installation path to load the required dependencies
     *
     * @param   string  $installationPath  The output of "getGlobalInstallPath()"
     *
     * @return bool
     */
    protected function installWrapper(string $installationPath): bool
    {
        if (! function_exists('shell_exec')) {
            throw new \RuntimeException('Can\'t install neunerlei/dev as global dependency, because the required function "shell_exec" was disabled!');
        }
        
        $this->io->write(
            'Trying to install/update neunerlei/dev as a global dependency using a sub-composer call at...',
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
            $this->io->write('<error>There seems to be an issue when installing neunerlei/dbg globally...</error>');
            $this->io->write($res);
            
            return false;
        }
        
        $this->io->write(
            '<info>Global installation of neunerlei/dev is ready to be used!</info>',
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
            $this->io->write('<error>The global autoload file of neunerlei/dbg at "' . $autoloadPath . '" is not readable!</error>');
            
            return false;
        }
        
        if (! is_callable([$package, 'setAutoload'])) {
            $this->io->write('<error>The provided root package does not allow autoload override! That prevents the injection of "neunerlei/dbg" as dependency!</error>');
            
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
     * @inheritDoc
     */
    public function deactivate(Composer $composer, IOInterface $io) { }
    
    /**
     * @inheritDoc
     */
    public function uninstall(Composer $composer, IOInterface $io) { }
    
}