Sephpa - A PHP class to export SEPA files
===============

###General###
**Sephpa** [sefa] is a PHP class that creates SEPA (xml,xsd) files. The created xml files fulfill the specifications of Electronic Banking Internet Communication Standard (EBICS)

###Supported file versions###
- SEPA Credit Transfer
  - pain.001.002.03
  - pain.001.003.03
- SEPA Direct Debit
  - pain.008.002.02
  - pain.008.003.02

###Requirements###
Sephpa was tested on PHP version 5.3 (maybe it runs also on an older version, but this was not tested)

###Using the Utilities###
The SepaUtilities class depends not on Sephpa, so it can also used in any other project.

Since you want to know if the input is valid at input time and not at the moment you create the
file, Sephpa does not check the input itself. So the best would be, you check the inputs at input time
and later just make a file out of the data.

SepaUtilities contains the following checks:
- `checkIBAN($iban)`: Checks if the IBAN is valid by checking the format and by calculating the checksum.
It also removes whitespaces and changes all letters to upper case.
- `checkBIC($bic)`: Checks if the BIC is valid by checking the format. It also removes whitespaces
and changes all letters to upper case.
- `checkCharset($str)`: Checks if the string contains only allowed characters.
- `check($field, $input)`: Checks if the input fits in the field. This function also does little
formatting changes, e.g. correcting letter case. Possible field values are:
  - 'pmtinfid': Payment-Information-ID
  - 'dbtr': Debtor Name
  - 'iban'
  - 'bic'
  - 'ccy': Currency
  - 'btchbookg': Batch Booking (boolean as string)
  - 'ultmtdebtr': Ultimate Debtor
  - 'pmtid': Payment ID
  - 'instdamt': Instructed Amount
  - 'cdtr': Creditor Name
  - 'ultmtcdrt': Ultimate Creditor
  - 'rmtinf': Remittance Information
  - 'ci': Creditor Identifier
- `sanitizeLength($input, $maxLen)`: Shortens the string if it is to long.
- `replaceSpecialChars($str)`: replaces all characters that are not allowed in sepa files by a
allowed one or removes them. Take a look at this [.xls file](See http://www.europeanpaymentscouncil.eu/index.cfm/knowledge-bank/epc-documents/sepa-requirements-for-an-extended-character-set-unicode-subset-best-practices/) for more information
*Notice:* Cyrillic is not supported yet, but greek letters are.
- `sanitize($field, $input)`: tries to sanitize the input so it fits in the field. Possible fields are
  - 'cdtr'
  - 'dbtr'
  - 'rmtInf'
  - 'ultmtCdrt'
  - 'ultmtDebtr'
- `formatDate($date, $inputFormat)`: Returns $date in a Sepa-valid format. You can specify the
input format by using [the table on this site](http://de1.php.net/manual/en/function.date.php).
By default the german date format (DD.MM.YYYY) is used.

Have also a look at utilitiesExample.php

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

You can use methods from SepaUtilities class to check if iban and bic are valid and also if other
text like the debtor name fits into the SEPA charset. You should use the methods of this class to
check and sanitize the inputs. If the inputs are invalid, the file will be created without any
problems, but your bank will reject the file. The easiest way to use SepaUtilities is by using
the check method.

    $isValid = SepaUtilities::check('iban',$input);

You can just insert the field the input is for and the input it self. This method will not work
with dates and data that can only take some different values as e.g. 'btchBookg'.

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
Published under MIT-Licence
