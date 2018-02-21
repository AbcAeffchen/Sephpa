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

class TestClass
{
    public $testArray = [];

    /**
     * TestClass constructor.
     * This class is for testing the return method.
     */
    public function __construct()
    {
        $this->testArray = [0,0,0,0,0];
    }

    public function &getEnd()
    {
        return $this->testArray[count($this->testArray)-1];
    }
}

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
     * Get a DOMDocument object from a Sephpa Object. This is used to check the xml format.
     * @param Sephpa $sephpaFile    A Sephpa object (SephpaCreditTransfer or SephpaDirectDebit)
     * @return DOMDocument
     */
    private function getDomDoc(Sephpa $sephpaFile)
    {
        $domDoc = new DOMDocument();
        $domDoc->loadXML($this->invokeGenerateXml($sephpaFile));

        return $domDoc;
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
    private function getCreditTransferFile($version, $addBIC, $addOptionalData, $checkAndSanitize, $orgId = [])
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
                                                       $version, $transferInformation, $orgId, $checkAndSanitize);


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
    private function getDirectDebitFile($version, $addBIC, $addOptionalData, $checkAndSanitize, $orgId = [])
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
                                                 $version, $directDebitInformation, $orgId, $checkAndSanitize);

        $directDebitFile->addPayment($paymentData);

        return $directDebitFile;
    }

    public function testOrgId()
    {
        $version = SephpaCreditTransfer::SEPA_PAIN_001_002_03;
        $xsdFile = __DIR__ . '/schemata/pain.001.002.03.xsd';
        static::assertTrue($this->getDomDoc($this->getCreditTransferFile($version,true,true,true,[]))
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDomDoc($this->getCreditTransferFile($version,true,true,true,['id' => 'testID']))
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDomDoc($this->getCreditTransferFile($version,true,true,true,['bob' => 'BELADEBEXXX']))
                                ->schemaValidate($xsdFile));
    }

    public function testCreditTransfer00100203()
    {
        $version = SephpaCreditTransfer::SEPA_PAIN_001_002_03;
        $xsdFile = __DIR__ . '/schemata/pain.001.002.03.xsd';
        static::assertTrue($this->getDomDoc($this->getCreditTransferFile($version,true,true,true))
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDomDoc($this->getCreditTransferFile($version,true,false,true))
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDomDoc($this->getCreditTransferFile($version,true,true,false))
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDomDoc($this->getCreditTransferFile($version,true,false,false))
                                ->schemaValidate($xsdFile));

        // check for behavior about missing BIC
        $exceptionCounter = 0;

        try { $this->getDomDoc($this->getCreditTransferFile($version, false, false, false)); }
        catch(SephpaInputException $e) { $exceptionCounter++; }

        try { $this->getDomDoc($this->getCreditTransferFile($version, false, true, false)); }
        catch(SephpaInputException $e) { $exceptionCounter++; }

        try { $this->getDomDoc($this->getCreditTransferFile($version, false, false, true)); }
        catch(SephpaInputException $e) { $exceptionCounter++; }

        try { $this->getDomDoc($this->getCreditTransferFile($version, false, true, true)); }
        catch(SephpaInputException $e) { $exceptionCounter++; }

        static::assertSame(4, $exceptionCounter);

        // check if file contend is independent from checkAndSanitize
        static::assertSame($this->getDomDoc($this->getCreditTransferFile($version,true,false,false))->saveXML(),
                           $this->getDomDoc($this->getCreditTransferFile($version,true,false,true))->saveXML());
        static::assertSame($this->getDomDoc($this->getCreditTransferFile($version,true,true,false))->saveXML(),
                           $this->getDomDoc($this->getCreditTransferFile($version,true,true,true))->saveXML());
    }

    public function testCreditTransfer00100303()
    {
        $version = SephpaCreditTransfer::SEPA_PAIN_001_003_03;
        $xsdFile = __DIR__ . '/schemata/pain.001.003.03.xsd';
        static::assertTrue($this->getDomDoc($this->getCreditTransferFile($version,true,true,true))
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDomDoc($this->getCreditTransferFile($version,true,false,true))
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDomDoc($this->getCreditTransferFile($version,true,true,false))
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDomDoc($this->getCreditTransferFile($version,true,false,false))
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDomDoc($this->getCreditTransferFile($version,false,true,true))
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDomDoc($this->getCreditTransferFile($version,false,false,true))
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDomDoc($this->getCreditTransferFile($version,false,true,false))
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDomDoc($this->getCreditTransferFile($version,false,false,false))
                                ->schemaValidate($xsdFile));

        // check if file contend is independent from checkAndSanitize
        static::assertSame($this->getDomDoc($this->getCreditTransferFile($version,true,false,false))->saveXML(),
                           $this->getDomDoc($this->getCreditTransferFile($version,true,false,true))->saveXML());
        static::assertSame($this->getDomDoc($this->getCreditTransferFile($version,true,true,false))->saveXML(),
                           $this->getDomDoc($this->getCreditTransferFile($version,true,true,true))->saveXML());
        static::assertSame($this->getDomDoc($this->getCreditTransferFile($version,false,false,false))->saveXML(),
                           $this->getDomDoc($this->getCreditTransferFile($version,false,false,true))->saveXML());
        static::assertSame($this->getDomDoc($this->getCreditTransferFile($version,false,true,false))->saveXML(),
                           $this->getDomDoc($this->getCreditTransferFile($version,false,true,true))->saveXML());
    }

    public function testCreditTransfer00100103()
    {
        $version = SephpaCreditTransfer::SEPA_PAIN_001_001_03;
        foreach(['pain.001.001.03', 'pain.001.001.03_GBIC'] as $xsdFileVersion)
        {
            $xsdFile = __DIR__ . '/schemata/' . $xsdFileVersion . '.xsd';
            static::assertTrue($this->getDomDoc($this->getCreditTransferFile($version, true, true, true))
                                    ->schemaValidate($xsdFile));
            static::assertTrue($this->getDomDoc($this->getCreditTransferFile($version, true, false, true))
                                    ->schemaValidate($xsdFile));
            static::assertTrue($this->getDomDoc($this->getCreditTransferFile($version, true, true, false))
                                    ->schemaValidate($xsdFile));
            static::assertTrue($this->getDomDoc($this->getCreditTransferFile($version, true, false, false))
                                    ->schemaValidate($xsdFile));
            static::assertTrue($this->getDomDoc($this->getCreditTransferFile($version, false, true, true))
                                    ->schemaValidate($xsdFile));
            static::assertTrue($this->getDomDoc($this->getCreditTransferFile($version, false, false, true))
                                    ->schemaValidate($xsdFile));
            static::assertTrue($this->getDomDoc($this->getCreditTransferFile($version, false, true, false))
                                    ->schemaValidate($xsdFile));
            static::assertTrue($this->getDomDoc($this->getCreditTransferFile($version, false, false, false))
                                    ->schemaValidate($xsdFile));
        }

        // check if file contend is independent from checkAndSanitize
        static::assertSame($this->getDomDoc($this->getCreditTransferFile($version, true, false, false))->saveXML(),
                           $this->getDomDoc($this->getCreditTransferFile($version, true, false, true))->saveXML());
        static::assertSame($this->getDomDoc($this->getCreditTransferFile($version, true, true, false))->saveXML(),
                           $this->getDomDoc($this->getCreditTransferFile($version, true, true, true))->saveXML());
        static::assertSame($this->getDomDoc($this->getCreditTransferFile($version, false, false, false))->saveXML(),
                           $this->getDomDoc($this->getCreditTransferFile($version, false, false, true))->saveXML());
        static::assertSame($this->getDomDoc($this->getCreditTransferFile($version, false, true, false))->saveXML(),
                           $this->getDomDoc($this->getCreditTransferFile($version, false, true, true))->saveXML());
    }

    public function testDirectDebit00800202()
    {
        $version = SephpaDirectDebit::SEPA_PAIN_008_002_02;
        $xsdFile = __DIR__ . '/schemata/pain.008.002.02.xsd';
        static::assertTrue($this->getDomDoc($this->getDirectDebitFile($version,true,true,true))
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDomDoc($this->getDirectDebitFile($version,true,false,true))
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDomDoc($this->getDirectDebitFile($version,true,true,false))
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDomDoc($this->getDirectDebitFile($version,true,false,false))
                                ->schemaValidate($xsdFile));

        // check for behavior about missing BIC
        $exceptionCounter = 0;

        try { $this->getDomDoc($this->getDirectDebitFile($version, false, false, false)); }
        catch(SephpaInputException $e) { $exceptionCounter++; }

        try { $this->getDomDoc($this->getDirectDebitFile($version, false, true, false)); }
        catch(SephpaInputException $e) { $exceptionCounter++; }

        try { $this->getDomDoc($this->getDirectDebitFile($version, false, false, true)); }
        catch(SephpaInputException $e) { $exceptionCounter++; }

        try { $this->getDomDoc($this->getDirectDebitFile($version, false, true, true)); }
        catch(SephpaInputException $e) { $exceptionCounter++; }

        static::assertSame(4, $exceptionCounter);

        // check if file contend is independent from checkAndSanitize
        static::assertSame($this->getDomDoc($this->getDirectDebitFile($version,true,false,false))->saveXML(),
                           $this->getDomDoc($this->getDirectDebitFile($version,true,false,true))->saveXML());
        static::assertSame($this->getDomDoc($this->getDirectDebitFile($version,true,true,false))->saveXML(),
                           $this->getDomDoc($this->getDirectDebitFile($version,true,true,true))->saveXML());
    }

    public function testDirectDebit00800302()
    {
        $version = SephpaDirectDebit::SEPA_PAIN_008_003_02;
        $xsdFile = __DIR__ . '/schemata/pain.008.003.02.xsd';
        static::assertTrue($this->getDomDoc($this->getDirectDebitFile($version,true,true,true))
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDomDoc($this->getDirectDebitFile($version,true,false,true))
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDomDoc($this->getDirectDebitFile($version,true,true,false))
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDomDoc($this->getDirectDebitFile($version,true,false,false))
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDomDoc($this->getDirectDebitFile($version,false,true,true))
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDomDoc($this->getDirectDebitFile($version,false,false,true))
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDomDoc($this->getDirectDebitFile($version,false,true,false))
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDomDoc($this->getDirectDebitFile($version,false,false,false))
                                ->schemaValidate($xsdFile));

        // check if file contend is independent from checkAndSanitize
        static::assertSame($this->getDomDoc($this->getDirectDebitFile($version,true,false,false))->saveXML(),
                           $this->getDomDoc($this->getDirectDebitFile($version,true,false,true))->saveXML());
        static::assertSame($this->getDomDoc($this->getDirectDebitFile($version,true,true,false))->saveXML(),
                           $this->getDomDoc($this->getDirectDebitFile($version,true,true,true))->saveXML());
        static::assertSame($this->getDomDoc($this->getDirectDebitFile($version,false,false,false))->saveXML(),
                           $this->getDomDoc($this->getDirectDebitFile($version,false,false,true))->saveXML());
        static::assertSame($this->getDomDoc($this->getDirectDebitFile($version,false,true,false))->saveXML(),
                           $this->getDomDoc($this->getDirectDebitFile($version,false,true,true))->saveXML());
    }

    public function testDirectDebit00800102()
    {
        $version = SephpaDirectDebit::SEPA_PAIN_008_001_02;
        foreach(['pain.008.001.02', 'pain.008.001.02_GBIC'] as $xsdFileVersion)
        {
            $xsdFile = __DIR__ . '/schemata/' . $xsdFileVersion . '.xsd';
            static::assertTrue($this->getDomDoc($this->getDirectDebitFile($version, true, true, true))
                                    ->schemaValidate($xsdFile));
            static::assertTrue($this->getDomDoc($this->getDirectDebitFile($version, true, false, true))
                                    ->schemaValidate($xsdFile));
            static::assertTrue($this->getDomDoc($this->getDirectDebitFile($version, true, true, false))
                                    ->schemaValidate($xsdFile));
            static::assertTrue($this->getDomDoc($this->getDirectDebitFile($version, true, false, false))
                                    ->schemaValidate($xsdFile));
            static::assertTrue($this->getDomDoc($this->getDirectDebitFile($version, false, true, true))
                                    ->schemaValidate($xsdFile));
            static::assertTrue($this->getDomDoc($this->getDirectDebitFile($version, false, false, true))
                                    ->schemaValidate($xsdFile));
            static::assertTrue($this->getDomDoc($this->getDirectDebitFile($version, false, true, false))
                                    ->schemaValidate($xsdFile));
            static::assertTrue($this->getDomDoc($this->getDirectDebitFile($version, false, false, false))
                                    ->schemaValidate($xsdFile));
        }

        // check if file contend is independent from checkAndSanitize
        static::assertSame($this->getDomDoc($this->getDirectDebitFile($version, true, false, false))->saveXML(),
                           $this->getDomDoc($this->getDirectDebitFile($version, true, false, true))->saveXML());
        static::assertSame($this->getDomDoc($this->getDirectDebitFile($version, true, true, false))->saveXML(),
                           $this->getDomDoc($this->getDirectDebitFile($version, true, true, true))->saveXML());
        static::assertSame($this->getDomDoc($this->getDirectDebitFile($version, false, false, false))->saveXML(),
                           $this->getDomDoc($this->getDirectDebitFile($version, false, false, true))->saveXML());
        static::assertSame($this->getDomDoc($this->getDirectDebitFile($version, false, true, false))->saveXML(),
                           $this->getDomDoc($this->getDirectDebitFile($version, false, true, true))->saveXML());
    }

    public function testDirectDebit00800102Austrian003()
    {
        $version = SephpaDirectDebit::SEPA_PAIN_008_001_02_AUSTRIAN_003;
        $xsdFile = __DIR__ . '/schemata/pain.008.001.02.austrian.003.xsd';
        static::assertTrue($this->getDomDoc($this->getDirectDebitFile($version, true, true, true))
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDomDoc($this->getDirectDebitFile($version, true, false, true))
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDomDoc($this->getDirectDebitFile($version, true, true, false))
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDomDoc($this->getDirectDebitFile($version, true, false, false))
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDomDoc($this->getDirectDebitFile($version, false, true, true))
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDomDoc($this->getDirectDebitFile($version, false, false, true))
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDomDoc($this->getDirectDebitFile($version, false, true, false))
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDomDoc($this->getDirectDebitFile($version, false, false, false))
                                ->schemaValidate($xsdFile));

        // check if file contend is independent from checkAndSanitize
        static::assertSame($this->getDomDoc($this->getDirectDebitFile($version, true, false, false))->saveXML(),
                           $this->getDomDoc($this->getDirectDebitFile($version, true, false, true))->saveXML());
        static::assertSame($this->getDomDoc($this->getDirectDebitFile($version, true, true, false))->saveXML(),
                           $this->getDomDoc($this->getDirectDebitFile($version, true, true, true))->saveXML());
        static::assertSame($this->getDomDoc($this->getDirectDebitFile($version, false, false, false))->saveXML(),
                           $this->getDomDoc($this->getDirectDebitFile($version, false, false, true))->saveXML());
        static::assertSame($this->getDomDoc($this->getDirectDebitFile($version, false, true, false))->saveXML(),
                           $this->getDomDoc($this->getDirectDebitFile($version, false, true, true))->saveXML());
    }

    public function testEndReference()
    {
        $testObj = new TestClass();
        $end = &$testObj->getEnd();
        $end = 1;
        $this->assertSame(1, end($testObj->testArray));
    }
}