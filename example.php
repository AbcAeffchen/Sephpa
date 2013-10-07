<?php
/*
 This is just an example. Please read the dokumentation
 of SEPA to learn more about using SEPA.
*/

require_once 'SepaXmlFile.php';

// generate a SepaCreditTranfer object. Here you can not use direct debit! (pain.001.002.03)
$creditTransferFile = new SepaXmlFile('Initiator Name', 'MessageID-1234', 'CT');

// at least one in every SepaXmlFile (of type CT). No limit.
$creditTransferCollection = $creditTransferFile->addCreditTransferCollection(array(
                    // needed information about the payer
                        'pmtInfId'      => 'PaymentID-1234',    // ID of the paymentcollection
                        'dbtr'          => 'Name of Debtor2',   // (max 70 characters)
                        'iban'          => 'DE87200500001234567890',// IBAN of the Debtor
                        'bic'           => 'BELADEBEXXX',       // BIC of the Debtor
                    // optional
                        'ccy'           => 'EUR',               // Currency. Default is 'EUR'
                        'btchBookg'     => 'true',              // BatchBooking, only 'true' or 'false'
                        //'ctgyPurp'      => ,                  // Do not use this if you not know how. For further information read the SEPA documentation
                        'reqdExctnDt'   => '2013-11-25',        // Date: YYYY-MM-DD
                        'ultmtDebtr'    => 'Ultimate Debtor Name'   // just an information, this do not affect the payment (max 70 characters)
                    ));
                    
// at least one in every CreditTransferCollection. No limit.
$creditTransferCollection->addPayment(array(
                    // needed information about the one who gets payed
                        'pmtId'     => 'TransferID-1234-1',     // ID of the payment (EndToEndId)
                        'instdAmt'  => 1.14,                    // amount, 
                        'iban'      => 'DE87200500001234567890',// IBAN of the Creditor
                        'bic'       => 'BELADEBEXXX',           // BIC of the Creditor
                        'cdtr'      => 'Name of Creditor',      // (max 70 characters)
                    // optional
                        'ultmtCdrt' => 'Ultimate Creditor Name',// just an information, this do not affect the payment (max 70 characters)
                        //'purp'      => ,                      // Do not use this if you not know how. For further information read the SEPA documentation
                        'rmtInf'    => 'Remittance Information' // unstructured information about the remittance (max 140 characters)
                    ));



// save the file on the server
$ct = fopen('credit_transfer.xml', 'w');
fwrite($ct, $creditTransferFile->generateXml());
fclose($ct);
// or download the file without saving it on the server
header('Content-Disposition: attachment; filename="credit_transfer.xml"');
print $creditTransferFile->generateXml();


// generate a SepaDirectDebit object. Here you can not use credit transfer! (pain.008.002.02)
$directDebitFile = new SepaXmlFile('Initiator Name', 'MessageID-1235', 'DD');

// at least one in every SepaXmlFile (of type DD). No limit.
$directDebitCollection = $directDebitFile->addDirectDebitCollection(array(
                    // needed information about the payer
                        'pmtInfId'      => 'PaymentID-1235',        // ID of the paymentcollection
                        'lclInstrm'     => 'CORE',                  // only 'CORE' or 'B2B'
                        'seqTp'         => 'RCUR',                  // only 'FRST', 'RCUR', 'OOFF' or 'FNAL'
                        'cdtr'          => 'Name of Creditor',      // (max 70 characters)
                        'iban'          => 'DE87200500001234567890',// IBAN of the Creditor
                        'bic'           => 'BELADEBEXXX',           // BIC of the Creditor
                        'ci'            => 'DE00ZZZ00099999999',    // Creditor-Identifier
                    // optional
                        'ccy'           => 'EUR',                   // Currency. Default is 'EUR'
                        'btchBookg'     => 'true',                  // BatchBooking, only 'true' or 'false'
                        //'ctgyPurp'      => ,                      // Do not use this if you not know how. For further information read the SEPA documentation
                        'ultmtCdtr'     => 'Ultimate Creditor Name',// just an information, this do not affect the payment (max 70 characters)
                        'reqdColltnDt'  => '2013-11-25'             // Date: YYYY-MM-DD
                    ));
                    
// at least one in every DirectDebitCollection. No limit.
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
                        'amdmntInd'     => 'true',                  // Did the mandate change
                        'elctrncSgntr'  => 'test',                  // do not use this if there is a paper-based mandate
                        'ultmtDbtr'     => 'Ultimate Debtor Name',  // just an information, this do not affect the payment (max 70 characters)
                        //'purp'        => ,                        // Do not use this if you not know how. For further information read the SEPA documentation
                        'rmtInf'        => 'Remittance Information',// unstructured information about the remittance (max 140 characters)
                        // only use this if 'amdmntInd' is 'true'. at least one must be used
                        'orgnlMndtId'           => 'Original-Mandat-ID',
                        'orgnlCdtrSchmeId_nm'   => 'Creditor-Identifier Name',
                        'orgnlCdtrSchmeId_id'   => 'Creditor-Identifier ID',
                        'orgnlDbtrAcct_iban'    => 'DE87200500001234567890',// Original Debtor Account
                        'orgnlDbtrAgt'          => 'SMNDA'          // only 'SMNDA' allowed if used
));

// save the file on the server
$dd = fopen('direct_debit.xml', 'w');
fwrite($dd, $directDebitFile->generateXml());
fclose($dd);
// or download the file without saving it on the server
header('Content-Disposition: attachment; filename="credit_transfer.xml"');
print $directDebitFile->generateXml();


?>