<?php
/**
 * Sephpa
 *
 * @license   GNU LGPL v3.0 - For details have a look at the LICENSE file
 * @copyright ©2018 Alexander Schickedanz
 * @link      https://github.com/AbcAeffchen/Sephpa
 *
 * @author  Alexander Schickedanz <abcaeffchen@gmail.com>
 */

require __DIR__ . '/../vendor/autoload.php';

use AbcAeffchen\Sephpa\Sephpa;
use AbcAeffchen\Sephpa\SephpaCreditTransfer;
use AbcAeffchen\SepaUtilities\SepaUtilities;
use AbcAeffchen\Sephpa\SephpaDirectDebit;
use AbcAeffchen\Sephpa\SephpaInputException;
use AbcAeffchen\Sephpa\SephpaMultiFile;

class SephpaTest extends PHPUnit\Framework\TestCase
{
    /**
     * Calls the protected method `generateXml()` of the provided Sephpa object.
     * @param Sephpa $object
     * @return mixed
     */
    private function invokeGenerateXml(Sephpa &$object)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod('generateXml');
        $method->setAccessible(true);

        return $method->invokeArgs($object, []);
    }

    /**
     * Generates test data for all test of the Credit Transfer tests.
     * @param int  $version Use SephpaCreditTransfer::SEPA_PAIN_001_* constants
     * @param bool $addBIC
     * @param bool $addOptionalData
     * @param bool $checkAndSanitize
     * @return SephpaCreditTransfer
     * @throws SephpaInputException
     */
    private function getCreditTransferFile($version, $addBIC, $addOptionalData, $checkAndSanitize)
    {
        $transferInformation = [
            'pmtInfId'      => 'PaymentID-1234',            // ID of the payment collection
            'dbtr'          => 'Name of Debtor2',           // (max 70 characters)
            'iban'          => 'DE21500500001234567897',    // IBAN of the Debtor
        ];

        if($addBIC)
            $transferInformation['bic'] = 'BELADEBEXXX';

        if($addOptionalData)
        {
            $transferInformation['ccy']         = 'EUR';                     // Currency. Default is 'EUR'
            $transferInformation['btchBookg']   = 'true';                    // BatchBooking, only 'true' or 'false'
            $transferInformation['reqdExctnDt'] = '2013-11-25';              // Date: YYYY-MM-DD
            $transferInformation['ultmtDebtr']  = 'Ultimate Debtor Name';    // just an information, this do not affect the payment (max 70 characters)
        }

        $creditTransferFile = new SephpaCreditTransfer('Initiator Name', 'MessageID-1234',
                                                       $version, $transferInformation, $checkAndSanitize);


        $paymentData = [
            'pmtId'     => 'TransferID-1234-1',     // ID of the payment (EndToEndId)
            'instdAmt'  => 1.14,                    // amount,
            'iban'      => 'DE21500500009876543210',// IBAN of the Creditor
            'cdtr'      => 'Name of Creditor',      // (max 70 characters)
        ];

        if($addBIC)
            $paymentData['bic'] = 'SPUEDE2UXXX';

        if($addOptionalData)
        {
            $paymentData['ultmtCdrt'] = 'Ultimate Creditor Name';   // just an information, this do not affect the payment (max 70 characters)
            $paymentData['rmtInf']    = 'Remittance Information';   // unstructured information about the remittance (max 140 characters)
        }

        $creditTransferFile->addPayment($paymentData);

        return $creditTransferFile;
    }

    /**
     * Generates test data for all tests of the Direct Debit classes.
     * @param int  $version Use SephpaDirectDebit::SEPA_PAIN_008_* constants
     * @param bool $addBIC
     * @param bool $addOptionalData
     * @param bool $checkAndSanitize
     * @return SephpaDirectDebit
     * @throws SephpaInputException
     */
    private function getDirectDebitFile($version, $addBIC, $addOptionalData, $checkAndSanitize)
    {

        $directDebitInformation = [
            'pmtInfId'      => 'PaymentID-1235',        // ID of the payment collection
            'lclInstrm'     => SepaUtilities::LOCAL_INSTRUMENT_CORE_DIRECT_DEBIT,
            'seqTp'         => SepaUtilities::SEQUENCE_TYPE_FIRST,
            'cdtr'          => 'Name of Creditor',      // (max 70 characters)
            'iban'          => 'DE87200500001234567890',// IBAN of the Creditor
            'ci'            => 'DE98ZZZ09999999999',    // Creditor-Identifier
        ];

        if($addBIC)
            $directDebitInformation['bic'] = 'BELADEBEXXX';

        if($addOptionalData)
        {
            $directDebitInformation['ccy']           = 'EUR';                   // Currency. Default is 'EUR'
            $directDebitInformation['btchBookg']     = 'true';                  // BatchBooking, only 'true' or 'false'
            $directDebitInformation['ultmtCdtr']     = 'Ultimate Creditor Name';// just an information, this do not affect the payment (max 70 characters)
            $directDebitInformation['reqdColltnDt']  = '2013-11-25';            // Date: YYYY-MM-DD
        }

        $paymentData = [
            'pmtId'               => 'TransferID-1235-1',       // ID of the payment (EndToEndId)
            'instdAmt'            => 2.34,                      // amount
            'mndtId'              => 'Mandate-Id',              // Mandate ID
            'dtOfSgntr'           => '2010-04-12',              // Date of signature
            'dbtr'                => 'Name of Debtor',          // (max 70 characters)
            'iban'                => 'DE87200500001234567890',  // IBAN of the Debtor
        ];

        if($addBIC)
            $paymentData['bic'] = 'BELADEBEXXX';

        if($addOptionalData)
        {
            $paymentData['amdmntInd']           = 'true';                    // Did the mandate change
            $paymentData['ultmtDbtr']           = 'Ultimate Debtor Name';    // just an information, this do not affect the payment (max 70 characters)
            $paymentData['rmtInf']              = 'Remittance Information';  // unstructured information about the remittance (max 140 characters)
            // only use this if 'amdmntInd' is 'true'. at least one must be used
            $paymentData['orgnlMndtId']         = 'Original-Mandat-ID';
            $paymentData['orgnlCdtrSchmeId_nm'] = 'Creditor-Identifier Name';
            $paymentData['orgnlCdtrSchmeId_id'] = 'DE98AAA09999999999';
            $paymentData['orgnlDbtrAcct_iban']  = 'DE87200500001234567890';  // Original Debtor Account
            $paymentData['orgnlDbtrAgt']        = 'SMNDA';                   // only 'SMNDA' allowed if used
        }

        // generate a SepaDirectDebit object (pain.008.002.02).
        $directDebitFile = new SephpaDirectDebit('Initiator Name', 'MessageID-1235',
                                                 $version, $directDebitInformation, $checkAndSanitize);

        $directDebitFile->addPayment($paymentData);

        return $directDebitFile;
    }

    /**
     * Tests if File Routing Slip and Control List are generated. This tests creates an output
     * folder in the tests folder containing two zip files, that contain the xml and pdf files.
     * @throws SephpaInputException
     * @throws \Mpdf\MpdfException
     */
    public function testAdditionalDocuments()
    {
        // according to https://github.com/sebastianbergmann/phpunit-documentation/issues/171#issuecomment-337854895
        // assertNull is the way to test if a function that returns nothing was executed without errors or exceptions.

        $version = SephpaCreditTransfer::SEPA_PAIN_001_001_03;
        $file = $this->getCreditTransferFile($version, true, true, true);
        $this->assertNull($file->store(__DIR__ . DIRECTORY_SEPARATOR . 'output',
                                       ['addFileRoutingSlip' => true, 'addControlList' => true]));

        $version = SephpaDirectDebit::SEPA_PAIN_008_001_02;
        $file = $this->getDirectDebitFile($version, true, true, true);
        $this->assertNull($file->store(__DIR__ . DIRECTORY_SEPARATOR . 'output',
                                       ['addFileRoutingSlip' => true, 'addControlList' => true]));
    }

    /**
     * @throws SephpaInputException
     */
    public function testMultiFile()
    {
        // according to https://github.com/sebastianbergmann/phpunit-documentation/issues/171#issuecomment-337854895
        // assertNull is the way to test if a function that returns nothing was executed without errors or exceptions.

        $transferInformation = [
            'pmtInfId'      => 'PaymentID-1234',            // ID of the payment collection
            'dbtr'          => 'Name of Debtor2',           // (max 70 characters)
            'iban'          => 'DE21500500001234567897',    // IBAN of the Debtor
        ];

        $directDebitInformation = [
            'pmtInfId'      => 'PaymentID-1235',        // ID of the payment collection
            'lclInstrm'     => SepaUtilities::LOCAL_INSTRUMENT_CORE_DIRECT_DEBIT,
            'seqTp'         => SepaUtilities::SEQUENCE_TYPE_FIRST,
            'cdtr'          => 'Name of Creditor',      // (max 70 characters)
            'iban'          => 'DE87200500001234567890',// IBAN of the Creditor
            'ci'            => 'DE98ZZZ09999999999',    // Creditor-Identifier
        ];

        $sephpaMultiFile = new SephpaMultiFile();
        $creditTransferFile = $sephpaMultiFile->addCreditTransferFile('Initiator Name', 'MessageID-1234',
                                                                      SephpaCreditTransfer::SEPA_PAIN_001_001_03,
                                                                      $transferInformation, true);
        $directDebitFile = $sephpaMultiFile->addDirectDebitFile('Initiator Name',
                                                                'MessageID-1235',
                                                                SephpaDirectDebit::SEPA_PAIN_008_001_02,
                                                                $directDebitInformation, true);

        $paymentData = [
            'pmtId'     => 'TransferID-1234-1',     // ID of the payment (EndToEndId)
            'instdAmt'  => 1.14,                    // amount,
            'iban'      => 'DE21500500009876543210',// IBAN of the Creditor
            'cdtr'      => 'Name of Creditor',      // (max 70 characters)
        ];

        $creditTransferFile->addPayment($paymentData);

        $paymentData = [
            'pmtId'               => 'TransferID-1235-1',       // ID of the payment (EndToEndId)
            'instdAmt'            => 2.34,                      // amount
            'mndtId'              => 'Mandate-Id',              // Mandate ID
            'dtOfSgntr'           => '2010-04-12',              // Date of signature
            'dbtr'                => 'Name of Debtor',          // (max 70 characters)
            'iban'                => 'DE87200500001234567890',  // IBAN of the Debtor
        ];

        $directDebitFile->addPayment($paymentData);

        $this->assertNull($sephpaMultiFile->store(__DIR__ . DIRECTORY_SEPARATOR . 'output',
                                                  ['addFileRoutingSlip' => true,
                                                   'addControlList'     => true]));
    }
}