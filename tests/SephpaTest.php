<?php

require __DIR__ . '/../vendor/autoload.php';

use AbcAeffchen\Sephpa\SephpaCreditTransfer;
use AbcAeffchen\SepaUtilities\SepaUtilities;
use AbcAeffchen\Sephpa\SephpaDirectDebit;

/**
 * Project: Sephpa
 * User:    AbcAeffchen
 * Date:    18.10.2014
 */

class SephpaTest extends PHPUnit\Framework\TestCase
{
    public function testCreditTransfer00100203()
    {
        $creditTransferFile = new SephpaCreditTransfer('Initiator Name', 'MessageID-1234',
                                                       SephpaCreditTransfer::SEPA_PAIN_001_002_03);

        // at least one in every SEPA file
        $creditTransferCollection = $creditTransferFile->addCollection(array(
        // needed information about the payer
            'pmtInfId'      => 'PaymentID-1234',    // ID of the payment collection
            'dbtr'          => 'Name of Debtor2',   // (max 70 characters)
            'iban'          => 'DE21500500001234567897',// IBAN of the Debtor
            'bic'           => 'BELADEBEXXX',       // BIC of the Debtor
        // optional
            'ccy'           => 'EUR',               // Currency. Default is 'EUR'
            'btchBookg'     => 'true',              // BatchBooking, only 'true' or 'false'
            //'ctgyPurp'      => ,                  // Do not use this if you do not know how. For further information read the SEPA documentation
            'reqdExctnDt'   => '2013-11-25',        // Date: YYYY-MM-DD
            'ultmtDebtr'    => 'Ultimate Debtor Name'   // just an information, this do not affect the payment (max 70 characters)
        ));

        // at least one in every CreditTransferCollection
        $creditTransferCollection->addPayment(array(
        // needed information about the one who gets payed
            'pmtId'     => 'TransferID-1234-1',     // ID of the payment (EndToEndId)
            'instdAmt'  => 1.14,                    // amount,
            'iban'      => 'DE21500500009876543210',// IBAN of the Creditor
            'bic'       => 'SPUEDE2UXXX',           // BIC of the Creditor
            'cdtr'      => 'Name of Creditor',      // (max 70 characters)
        // optional
            'ultmtCdrt' => 'Ultimate Creditor Name',// just an information, this do not affect the payment (max 70 characters)
            //'purp'      => ,                      // Do not use this if you do not know how. For further information read the SEPA documentation
            'rmtInf'    => 'Remittance Information' // unstructured information about the remittance (max 140 characters)
        ));

        $domDoc = new DOMDocument();
        $domDoc->loadXML($creditTransferFile->generateXml('2014-10-19T00:38:44'));

        static::assertTrue($domDoc->schemaValidate(__DIR__ . '/schemata/pain.001.002.03.xsd'));
    }

    public function testCreditTransfer00100303WithoutBIC()
    {
        $creditTransferFile = new SephpaCreditTransfer('Initiator Name', 'MessageID-1234',
                                                       SephpaCreditTransfer::SEPA_PAIN_001_003_03);

        // at least one in every SEPA file
        $creditTransferCollection = $creditTransferFile->addCollection(array(
        // needed information about the payer
            'pmtInfId'      => 'PaymentID-1234',    // ID of the payment collection
            'dbtr'          => 'Name of Debtor2',   // (max 70 characters)
            'iban'          => 'DE21500500001234567897',// IBAN of the Debtor
        // optional
            //'bic'           => 'BELADEBEXXX',       // BIC of the Debtor
            'ccy'           => 'EUR',               // Currency. Default is 'EUR'
            'btchBookg'     => 'true',              // BatchBooking, only 'true' or 'false'
            //'ctgyPurp'      => ,                  // Do not use this if you do not know how. For further information read the SEPA documentation
            'reqdExctnDt'   => '2013-11-25',        // Date: YYYY-MM-DD
            'ultmtDebtr'    => 'Ultimate Debtor Name'   // just an information, this do not affect the payment (max 70 characters)
        ));

        // at least one in every CreditTransferCollection
        $creditTransferCollection->addPayment(array(
        // needed information about the one who gets payed
            'pmtId'     => 'TransferID-1234-1',     // ID of the payment (EndToEndId)
            'instdAmt'  => 1.14,                    // amount,
            'iban'      => 'DE21500500009876543210',// IBAN of the Creditor
            'cdtr'      => 'Name of Creditor',      // (max 70 characters)
        // optional
            //'bic'       => 'SPUEDE2UXXX',           // BIC of the Creditor
            'ultmtCdrt' => 'Ultimate Creditor Name',// just an information, this do not affect the payment (max 70 characters)
            //'purp'      => ,                      // Do not use this if you do not know how. For further information read the SEPA documentation
            'rmtInf'    => 'Remittance Information' // unstructured information about the remittance (max 140 characters)
        ));

        $domDoc = new DOMDocument();
        $domDoc->loadXML($creditTransferFile->generateXml('2014-10-19T00:38:44'));

        static::assertTrue($domDoc->schemaValidate(__DIR__ . '/schemata/pain.001.003.03.xsd'));
    }

    public function testCreditTransfer00100303WithBIC()
    {
        $creditTransferFile = new SephpaCreditTransfer('Initiator Name', 'MessageID-1234',
                                                       SephpaCreditTransfer::SEPA_PAIN_001_003_03);

        // at least one in every SEPA file
        $creditTransferCollection = $creditTransferFile->addCollection(array(
        // needed information about the payer
            'pmtInfId'      => 'PaymentID-1234',    // ID of the payment collection
            'dbtr'          => 'Name of Debtor2',   // (max 70 characters)
            'iban'          => 'DE21500500001234567897',// IBAN of the Debtor
        // optional
            'bic'           => 'BELADEBEXXX',       // BIC of the Debtor
            'ccy'           => 'EUR',               // Currency. Default is 'EUR'
            'btchBookg'     => 'true',              // BatchBooking, only 'true' or 'false'
            //'ctgyPurp'      => ,                  // Do not use this if you do not know how. For further information read the SEPA documentation
            'reqdExctnDt'   => '2013-11-25',        // Date: YYYY-MM-DD
            'ultmtDebtr'    => 'Ultimate Debtor Name'   // just an information, this do not affect the payment (max 70 characters)
        ));

        // at least one in every CreditTransferCollection
        $creditTransferCollection->addPayment(array(
        // needed information about the one who gets payed
            'pmtId'     => 'TransferID-1234-1',     // ID of the payment (EndToEndId)
            'instdAmt'  => 1.14,                    // amount,
            'iban'      => 'DE21500500009876543210',// IBAN of the Creditor
            'cdtr'      => 'Name of Creditor',      // (max 70 characters)
        // optional
            'bic'       => 'SPUEDE2UXXX',           // BIC of the Creditor
            'ultmtCdrt' => 'Ultimate Creditor Name',// just an information, this do not affect the payment (max 70 characters)
            //'purp'      => ,                      // Do not use this if you do not know how. For further information read the SEPA documentation
            'rmtInf'    => 'Remittance Information' // unstructured information about the remittance (max 140 characters)
        ));

        $domDoc = new DOMDocument();
        $domDoc->loadXML($creditTransferFile->generateXml('2014-10-19T00:38:44'));

        static::assertTrue($domDoc->schemaValidate(__DIR__ . '/schemata/pain.001.003.03.xsd'));
    }

    public function testDirectDebit00800202()
    {
        // generate a SepaDirectDebit object (pain.008.002.02).
        $directDebitFile = new SephpaDirectDebit('Initiator Name', 'MessageID-1235',
                                                 SephpaDirectDebit::SEPA_PAIN_008_002_02);

        // at least one in every SEPA file. No limit.
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
            'amdmntInd'     => 'false',                 // Did the mandate change
            //'elctrncSgntr'  => 'tests',                  // do not use this if there is a paper-based mandate
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

        $domDoc = new DOMDocument();
        $domDoc->loadXML($directDebitFile->generateXml('2014-10-19T00:38:44'));

        static::assertTrue($domDoc->schemaValidate(__DIR__ . '/schemata/pain.008.002.02.xsd'));
    }

    public function testDirectDebit00800302WithBIC()
    {
        // generate a SepaDirectDebit object (pain.008.003.02).
        $directDebitFile = new SephpaDirectDebit('Initiator Name', 'MessageID-1235',
                                                 SephpaDirectDebit::SEPA_PAIN_008_003_02);

        // at least one in every SEPA file. No limit.
        $directDebitCollection = $directDebitFile->addCollection(array(
        // needed information about the payer
            'pmtInfId'      => 'PaymentID-1235',        // ID of the payment collection
            'lclInstrm'     => SepaUtilities::LOCAL_INSTRUMENT_CORE_DIRECT_DEBIT,
            'seqTp'         => SepaUtilities::SEQUENCE_TYPE_RECURRING,
            'cdtr'          => 'Name of Creditor',      // (max 70 characters)
            'iban'          => 'DE87200500001234567890',// IBAN of the Creditor
            'ci'            => 'DE98ZZZ09999999999',    // Creditor-Identifier
        // optional
            'bic'           => 'BELADEBEXXX',           // BIC of the Creditor
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
            'dbtr'          => 'Name of Debtor',        // (max 70 characters)
            'iban'          => 'DE87200500001234567890',// IBAN of the Debtor
        // optional
            'bic'           => 'BELADEBEXXX',           // BIC of the Debtor
            'amdmntInd'     => 'false',                 // Did the mandate change
            //'elctrncSgntr'  => 'tests',                  // do not use this if there is a paper-based mandate
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

        $domDoc = new DOMDocument();
        $domDoc->loadXML($directDebitFile->generateXml('2014-10-19T00:38:44'));

        static::assertTrue($domDoc->schemaValidate(__DIR__ . '/schemata/pain.008.003.02.xsd'));
    }

    public function testDirectDebit00800302WithoutBIC()
    {
        // generate a SepaDirectDebit object (pain.008.003.02).
        $directDebitFile = new SephpaDirectDebit('Initiator Name', 'MessageID-1235',
                                                 SephpaDirectDebit::SEPA_PAIN_008_003_02);

        // at least one in every SEPA file. No limit.
        $directDebitCollection = $directDebitFile->addCollection(array(
        // needed information about the payer
            'pmtInfId'      => 'PaymentID-1235',        // ID of the payment collection
            'lclInstrm'     => SepaUtilities::LOCAL_INSTRUMENT_CORE_DIRECT_DEBIT,
            'seqTp'         => SepaUtilities::SEQUENCE_TYPE_RECURRING,
            'cdtr'          => 'Name of Creditor',      // (max 70 characters)
            'iban'          => 'DE87200500001234567890',// IBAN of the Creditor
            'ci'            => 'DE98ZZZ09999999999',    // Creditor-Identifier
        // optional
//            'bic'           => 'BELADEBEXXX',           // BIC of the Creditor
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
            'dbtr'          => 'Name of Debtor',        // (max 70 characters)
            'iban'          => 'DE87200500001234567890',// IBAN of the Debtor
        // optional
//            'bic'           => 'BELADEBEXXX',           // BIC of the Debtor
            'amdmntInd'     => 'false',                 // Did the mandate change
            //'elctrncSgntr'  => 'tests',                  // do not use this if there is a paper-based mandate
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

        $domDoc = new DOMDocument();
        $domDoc->loadXML($directDebitFile->generateXml('2014-10-19T00:38:44'));

        static::assertTrue($domDoc->schemaValidate(__DIR__ . '/schemata/pain.008.003.02.xsd'));
    }
}