GitSynchronizer
==============

Keeps a local folder synchronized with a Git repository on GitHub.


Installation
------------

The easiest way to use GitSynchronizer is to integrate it as a dependency
in your project's [composer.json](http://getcomposer.org/doc/00-intro.md) file:

```json
{
    "require": {
        "ml/git-synchronizer": "@dev"
    }
}
```

Installing is then a matter of running composer

    php composer.phar install

... and including Composer's autoloader to your project

```php
require('vendor/autoload.php');
```

Of course you can also download it as [ZIP archive](https://github.com/lanthaler/GitSynchronizer/archive/master.zip)
from Github.
