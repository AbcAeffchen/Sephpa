Sephpa - A PHP class to export SEPA files
===============

[![Build Status](https://travis-ci.org/AbcAeffchen/Sephpa.svg?branch=master)](https://travis-ci.org/AbcAeffchen/Sephpa)
[![Dependency Status](https://www.versioneye.com/php/abcaeffchen:sephpa/badge.svg)](https://www.versioneye.com/php/abcaeffchen:sephpa/1.2.2)
[![Latest Stable Version](https://poser.pugx.org/abcaeffchen/sephpa/v/stable.svg)](https://packagist.org/packages/abcaeffchen/sephpa) 
[![Total Downloads](https://poser.pugx.org/abcaeffchen/sephpa/downloads.svg)](https://packagist.org/packages/abcaeffchen/sephpa) 
[![License](https://poser.pugx.org/abcaeffchen/sephpa/license.svg)](https://packagist.org/packages/abcaeffchen/sephpa)

##General##
**Sephpa** [sefa] is a PHP class that creates SEPA (xml,xsd) files. The created xml files fulfill
the specifications of Electronic Banking Internet Communication Standard (EBICS)

##Supported file versions##
- SEPA Credit Transfer
    - pain.001.002.03
    - pain.001.003.03
- SEPA Direct Debit
    - pain.008.002.02
    - pain.008.003.02

##Requirements##
Sephpa was tested on PHP version 5.3 and requires [SepaUtilities 1.1.0+](https://github.com/AbcAeffchen/SepaUtilities) and [SimpleXML](http://php.net/manual/en/book.simplexml.php).

##Installation##

###Composer###
Just add

```json
{
    "require": {
        "abcaeffchen/sephpa": "~1.3.0"
    }
}
```

to your `composer.json` and include the Composer autoloader to your script.

###Direct download###
You can download it here. Make sure you also download [SepaUtilities](https://github.com/AbcAeffchen/SepaUtilities) 
and make it available. If you put src directory to your project root, you can use the following
snippet to make Sephpa and SepaUtilities available to your project.

```php
function sephpaAutoloader($class) {
    switch($class)
    {
        case 'Sephpa':
        case 'SephpaCreditTransfer':
        case 'SephpaDirectDebit':
        case 'SepaUtilities':
            require __DIR__ . '/src/' . $class . '.php';
        default:
            require __DIR__ . '/src/payment-collections/' . $class . '.php';
    }
}

spl_autoload_register('sephpaAutoloader');
```

Feel free to improve or adapt this to your requirement.
You also have to remove the composer autoloader from `Sephpa.php`.

##Creating a new SEPA file##
**Note:** This is not meant to teach you SEPA. If you want to learn more about SEPA or SEPA files,
you should ask your bank for help. You use this library at your own risk and I assume no liability
if anything goes wrong. You are supposed to check the files **before** handing them to your bank.

###Credit Transfer###
Just include the file `Sephpa.php`. All other files will be included automatically. After that
you can create a new Sephpa object.

```php
    $creditTransferFile = new SephpaCreditTransfer('Initiator Name',
                                                   'MessageID-1234', 
                                                   SephpaCreditTransfer::SEPA_PAIN_001_002_03);
```

You have to input the initiator name, the unique message id and the version of the file. The message
id have to be unique for all sepa files you hand over to your bank. This is one of the things Sephpa
will not check for you. Currently supported credit transfer versions are:

- `SEPA_PAIN_001_002_03`: Credit transfer version pain.001.002.03
- `SEPA_PAIN_001_003_03`: Credit transfer version pain.001.003.03

If you don't know which version to choose, ask your bank which versions they do accept. Normally
banks are not the fastest and so they will most likely accept the older one.

By default `checkAndSanitize` is set to true, which means that Sephpa will check and sanitize every
input it self and throw exceptions if something can not be sanitized. This sounds nagging but if
you turn off this checks and just ignore the exceptions your bank will not accept the files.
But it is **recommended** to check all inputs at input time (using [SepaUtilities](https://github.com/AbcAeffchen/SepaUtilities))
and then only add valid data. Then you can turn off `checkAndSanitize` to prevent double checking everything.

Once you created the SephpaCreditTransfer object, you can add a payment collection. You can add 
as many payment collections as you like.

```php
$creditTransferCollection = $creditTransferFile->addCollection(array(
// required information about the debtor
    'pmtInfId'      => 'PaymentID-1234',        // ID of the payment collection
    'dbtr'          => 'Name of Debtor',        // (max 70 characters)
    'iban'          => 'DE21500500001234567897',// IBAN of the Debtor
    'bic'           => 'SPUEDE2UXXX',           // BIC of the Debtor
// optional
    'ccy'           => 'EUR',                   // Currency. Default is 'EUR'
    'btchBookg'     => 'true',                  // BatchBooking, only 'true' or 'false'
    //'ctgyPurp'      => ,                      // Category Purpose. Do not use this if you do not know how. For further information read the SEPA documentation
    'reqdExctnDt'   => '2013-11-25',            // Date: YYYY-MM-DD
    'ultmtDebtr'    => 'Ultimate Debtor Name'   // just an information, this do not affect the payment (max 70 characters)
));
```

You can add as many payments as you want to each collection.

```php
$creditTransferCollection->addPayment(array(
// needed information about the creditor
    'pmtId'     => 'TransferID-1234-1',     // ID of the payment (EndToEndId)
    'instdAmt'  => 0.42,                    // amount,
    'iban'      => 'DE21500500009876543210',// IBAN of the Creditor
    'bic'       => 'SPUEDE2UXXX',           // BIC of the Creditor (only required for pain.001.002.03)
    'cdtr'      => 'Name of Creditor',      // (max 70 characters)
// optional
    'ultmtCdrt' => 'Ultimate Creditor Name',// just an information, this do not affect the payment (max 70 characters)
    //'purp'      => ,                      // Do not use this if you do not know how. For further information read the SEPA documentation
    'rmtInf'    => 'Remittance Information' // unstructured information about the remittance (max 140 characters)
));
```

###Direct Debits###
Direct debits work the same way as credit transfers, but they have little different inputs.

```php
$directDebitFile = new SephpaDirectDebit('Initiator Name', 
                                         'MessageID-1235', 
                                         SephpaDirectDebit::SEPA_PAIN_008_002_02);

$directDebitCollection = $directDebitFile->addCollection(array(
// needed information about the payer
    'pmtInfId'      => 'PaymentID-1235',        // ID of the payment collection
    'lclInstrm'     => SepaUtilities::LOCAL_INSTRUMENT_CORE_DIRECT_DEBIT,
    'seqTp'         => SepaUtilities::SEQUENCE_TYPE_RECURRING,
    'cdtr'          => 'Name of Creditor',      // (max 70 characters)
    'iban'          => 'DE87200500001234567890',// IBAN of the Creditor
    'bic'           => 'BELADEBEXXX',           // BIC of the Creditor
    'ci'            => 'DE98ZZZ09999999999',    // Creditor-Identifier
// optional
    'ccy'           => 'EUR',                   // Currency. Default is 'EUR'
    'btchBookg'     => 'true',                  // BatchBooking, only 'true' or 'false'
    //'ctgyPurp'      => ,                      // Do not use this if you not know how. For further information read the SEPA documentation
    'ultmtCdtr'     => 'Ultimate Creditor Name',// just an information, this do not affect the payment (max 70 characters)
    'reqdColltnDt'  => '2013-11-25'             // Date: YYYY-MM-DD
));
                    
$directDebitCollection->addPayment(array(
// needed information about the 
    'pmtId'         => 'TransferID-1235-1',     // ID of the payment (EndToEndId)
    'instdAmt'      => 2.34,                    // amount
    'mndtId'        => 'Mandate-Id',            // Mandate ID
    'dtOfSgntr'     => '2010-04-12',            // Date of signature
    'bic'           => 'BELADEBEXXX',           // BIC of the Debtor
    'dbtr'          => 'Name of Debtor',        // (max 70 characters)
    'iban'          => 'DE87200500001234567890',// IBAN of the Debtor
// optional
    'amdmntInd'     => 'false',                 // Did the mandate change
    'elctrncSgntr'  => 'test',                  // do not use this if there is a paper-based mandate
    'ultmtDbtr'     => 'Ultimate Debtor Name',  // just an information, this do not affect the payment (max 70 characters)
    //'purp'        => ,                        // Do not use this if you not know how. For further information read the SEPA documentation
    'rmtInf'        => 'Remittance Information',// unstructured information about the remittance (max 140 characters)
    // only use this if 'amdmntInd' is 'true'. at least one must be used
    'orgnlMndtId'           => 'Original-Mandat-ID',
    'orgnlCdtrSchmeId_nm'   => 'Creditor-Identifier Name',
    'orgnlCdtrSchmeId_id'   => 'DE98AAA09999999999',
    'orgnlDbtrAcct_iban'    => 'DE87200500001234567890',// Original Debtor Account
    'orgnlDbtrAgt'          => 'SMNDA'          // only 'SMNDA' allowed if used
));
```

###Get the Sepa file###
After you have added some payments to your payment collection you can save the finished file by

```php
$creditTransferCollection->storeSepaFile();
```

or get it directly without saving it on the server by

```php
$creditTransferCollection->downloadSepaFile();
```

Notice that you can hand over a filename you like, but you should only use the file extension  
`.xsd` or `.xml`.

##Credits##
Thanks to [Herrmann Herz](https://github.com/Heart1010) who supported me debugging and with great 
ideas to improve Sephpa and SepaUtilities.

##License##
Licensed under the LGPL v3.0 License.