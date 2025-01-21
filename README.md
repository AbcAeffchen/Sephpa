![Sephpa Logo](https://user-images.githubusercontent.com/20192194/81567624-d838be00-939c-11ea-91e2-ee5178840da6.png)

Sephpa - A PHP class to export SEPA files
===============

[![Unit Tests](https://github.com/AbcAeffchen/Sephpa/actions/workflows/php.yml/badge.svg)](https://github.com/AbcAeffchen/Sephpa/actions/workflows/php.yml)
[![Latest Stable Version](https://poser.pugx.org/abcaeffchen/sephpa/v/stable.svg)](https://packagist.org/packages/abcaeffchen/sephpa) 
[![Total Downloads](https://poser.pugx.org/abcaeffchen/sephpa/downloads.svg)](https://packagist.org/packages/abcaeffchen/sephpa) 
[![License](https://poser.pugx.org/abcaeffchen/sephpa/license.svg)](https://packagist.org/packages/abcaeffchen/sephpa)
[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/AbcAeffchen/Sephpa?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge)

## General
**Sephpa** [sefa] is a PHP class that creates SEPA XML files. The created XML files fulfill
the specifications of Electronic Banking Internet Communication Standard (EBICS).

## Supported file versions
- SEPA Credit Transfer
    - pain.001.001.09
    - pain.001.001.03
    - pain.001.002.03
    - pain.001.003.03
- SEPA Direct Debit
    - pain.008.001.08
    - pain.008.001.02
    - pain.008.001.02.austrian.003
    - pain.008.002.02
    - pain.008.003.02

## Requirements
Sephpa was created for PHP >=8.1 and requires [SepaUtilities 2.0.0+](https://github.com/AbcAeffchen/SepaUtilities) and [SimpleXML](http://php.net/manual/en/book.simplexml.php).
Sephpa should also work with PHP <=8.0, but since these versions are not officially supported anymore, 
it is strongly recommended not to use PHP older than 8.1.

If you want to download correctly sorted files, you also need the zip library and for documentation
files you need [SepaDocumentor](https://github.com/AbcAeffchen/SepaDocumentor).

## Installation

### Composer
Just add

```json
{
    "require": {
        "abcaeffchen/sephpa": "^3.0"
    }
}
```

to your `composer.json` and include the Composer autoloader to your script.

### Direct download
You can download Sephpa from this GitHub page. Make sure you also download [SepaUtilities](https://github.com/AbcAeffchen/SepaUtilities).
You should store the files in a structure that looks like this:
```
your project root
├── your_code
│   └── ...
└── vendor
    ├── Sephpa          (the Sephpa project go here)
    ├── SepaUtilities   (the SepaUtilities files go here)
    └── ...
```

In your code you can include the Sephpa autoloader by including the file
```
vendor/Sephpa/src/autoloader.php
```
You also need to include the SepaUtilities file which should be
```
vendor/SepaUtilities/src/SepaUtilities.php
```

In total your code should look something like this:
```
require PROJECT_ROOT . '/vendor/Sephpa/src/autoloader.php';
require PROJECT_ROOT . '/vendor/abcaeffchen/sepa-utilities/src/SepaUtilities.php';
```
You need to define `PROJECT_ROOT` by yourself.

### Documentation Module
Sephpa uses [SepaDocumentor](https://github.com/AbcAeffchen/SepaDocumentor) to create File
Routing Slips and Control Lists. If you are interested in these files, you need to add

```
{
    "require": {
        "abcaeffchen/sepa-documentor": "^3.0"
    }
}
```

to your composer file or download it from the website and make it available to Sephpa.

## Disclaimer
Sephpa is not meant to teach you SEPA. If you want to learn more about SEPA or SEPA files,
you should ask your bank for help. You use this library at your own risk and I assume no liability
if anything goes wrong. You are supposed to check the files **before** handing them to your bank.

## Documentation

Have a look at the [wiki pages](https://github.com/AbcAeffchen/Sephpa/wiki) for the 
documentation and examples.

## Credits
Thanks to [Hermann Herz](https://github.com/Heart1010) who supported me debugging and with great 
ideas to improve Sephpa and SepaUtilities.  
Thanks to [sargac](https://github.com/sargac) for the help with the wiki pages and for creating 
the nice logo.

## Support Sephpa
If you use and like Sephpa, drop me a note on what project you use it. I'm really curious. 
If you like it a lot, consider [buying me a coffee](https://www.buymeacoffee.com/schickedanz) :)

## License
Licensed under the LGPL v3.0 License.
