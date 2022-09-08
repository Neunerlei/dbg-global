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

## Postcardware

You're free to use this package, but if it makes it to your production environment I highly appreciate you sending me a postcard from your hometown, mentioning
which of our package(s) you are using.

You can find my address [here](https://www.neunerlei.eu/).

Thank you :D 
