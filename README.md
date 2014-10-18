Sephpa - A PHP class to export SEPA files
===============

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
Sephpa was tested on PHP version 5.3 and requires SepaUtilities 1.0.1+ and [SimpleXML](http://php.net/manual/en/book.simplexml.php).

###Get it###
You can download it here (make sure you also download [SepaUtilities](https://github.com/AbcAeffchen/SepaUtilities) and make it available) or
you can use composer. Just add

    {
        "require": {
            "abcaeffchen/sephpa": "~1.2"
        }
    }

to your `composer.json`.

###Creating a new SEPA file###

Note: This is not meant to teach you SEPA. If you want to learn more about SEPA or SEPA files,
you should ask your bank for help. Everyone uses this class at his/her own risk and I assume no liability
if anything goes wrong. You are supposed to check the files after handing them to your bank.


Just include the file `Sephpa.php`. All other files will be included automatically. After that
you can create a new Sephpa object.

    $creditTransferFile = new Sephpa('Initiator Name', 'MessageID-1234', SEPA_PAIN_001_002_03);

You have to input the initiator name, the unique message id and the type of the file. The message
id have to be unique for all sepa files you hand over to your bank. As type you can choose one of
the following:

- `SEPA_PAIN_001_002_03`: Credit transfer version pain.001.002.03
- `SEPA_PAIN_001_003_03`: Credit transfer version pain.001.003.03
- `SEPA_PAIN_008_002_02`: Direct debit version pain.008.002.02
- `SEPA_PAIN_008_003_02`: Direct debit version pain.008.003.02

If you don't know which version to choose ask your bank which version they do accept. Normally
banks are not the fastest and so they will most likely accept the older one.

Once you created the Sephpa object you can add a payment collection. You can add as many payment
collections as you like, but they have to be all of the same type.

    $creditTransferCollection = $creditTransferFile->addCreditTransferCollection(array(
    // required information about the payer
        'pmtInfId'      => 'PaymentID-1234',        // ID of the payment collection
        'dbtr'          => 'Name of Debtor2',       // (max 70 characters)
        'iban'          => 'DE21500500001234567897',// IBAN of the Debtor
        'bic'           => 'SPUEDE2UXXX',           // BIC of the Debtor
    // optional
        'ccy'           => 'EUR',                   // Currency. Default is 'EUR'
        'btchBookg'     => 'true',                  // BatchBooking, only 'true' or 'false'
        //'ctgyPurp'      => ,                      // Do not use this if you do not know how. For further information read the SEPA documentation
        'reqdExctnDt'   => '2013-11-25',            // Date: YYYY-MM-DD
        'ultmtDebtr'    => 'Ultimate Debtor Name'   // just an information, this do not affect the payment (max 70 characters)
    ));

You can use methods from [SepaUtilities](https://github.com/AbcAeffchen/SepaUtilities) to 
validate and sanitize the inputs.

To each collection you can add as many payments as you want

    $creditTransferCollection->addPayment(array(
    // needed information about the one who gets payed
        'pmtId'     => 'TransferID-1234-1',     // ID of the payment (EndToEndId)
        'instdAmt'  => 1.14,                    // amount,
        'iban'      => 'DE21500500009876543210',// IBAN of the Creditor
        'bic'       => 'SPUEDE2UXXX',           // BIC of the Creditor (only required for pain.001.002.03)
        'cdtr'      => 'Name of Creditor',      // (max 70 characters)
    // optional
        'ultmtCdrt' => 'Ultimate Creditor Name',// just an information, this do not affect the payment (max 70 characters)
        //'purp'      => ,                      // Do not use this if you do not know how. For further information read the SEPA documentation
        'rmtInf'    => 'Remittance Information' // unstructured information about the remittance (max 140 characters)
    ));

After you have added some payments to your payment collection you can save the finished file by

    $ct = fopen('credit_transfer.xml', 'w');
    fwrite($ct, $creditTransferFile->generateXml());
    fclose($ct);

or get it directly without saving it on the server by

    header('Content-Disposition: attachment; filename="credit_transfer.xml"');
    print $creditTransferFile->generateXml();

Notice that you can change the `.xml` file to a `.xsd` file by changing the name.

This works for direct debits the same way. Please have a look at the example.

###Licence###
Licensed under the MIT Licence.

###
