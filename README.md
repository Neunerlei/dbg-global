# DBG - Debug Helpers - Global Installer

This is a companion plugin to [neunerlei/dbg](https://github.com/Neunerlei/dbg) which allows it to be installed globally,
without actually adding it to your `composer.json` file. This is an edge case scenario for your work environment in bigger teams.

## Installation

Install the plugin *globally* with:

```composer global require neunerlei/dbg-global```

After you did that you can simply run ```composer install``` in the actual project you want to use dbg in.
This will not modify your `composer.json` or `composer.lock` but you will have all the helper functions
accessible in your code.

Happy debugging.

## Shims and disabling them

The plugin will create a shim of the neunerlei/dbg methods in the vendor directory of your local project, so that your IDE can find them
for its autocompletion.

If you don't want that to happen you can either disable the shim generation globally:
```composer global config extra.neunerleiDevGlobal.noShim true```

Alternatively you can disable the shim generation for specific directories as well:
```composer global config extra.neunerleiDevGlobal.noShimDirs.0 /work/project```
For multiple directories, raise the number after "noShimDirs" for each new directory.

Use: ```composer global config extra.neunerleiDevGlobal``` to see the current configuration

## Postcardware

You're free to use this package, but if it makes it to your production environment I highly appreciate you sending me a postcard from your hometown, mentioning
which of our package(s) you are using.

You can find my address [here](https://www.neunerlei.eu/).

Thank you :D 

