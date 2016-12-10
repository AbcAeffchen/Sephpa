<?php
/**
 * Sephpa
 *
 * @license   GNU LGPL v3.0 - For details have a look at the LICENSE file
 * @copyright ©2016 Alexander Schickedanz
 * @link      https://github.com/AbcAeffchen/Sephpa
 *
 * @author  Alexander Schickedanz <abcaeffchen@gmail.com>
 */

require __DIR__ . '/../vendor/autoload.php';

use AbcAeffchen\Sephpa\SephpaCreditTransfer;
use AbcAeffchen\SepaUtilities\SepaUtilities;
use AbcAeffchen\Sephpa\SephpaDirectDebit;
use AbcAeffchen\Sephpa\SephpaInputException;

class SephpaTest extends PHPUnit\Framework\TestCase
{
    private function invokeGenerateXml(&$object, $dateTime)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod('generateXml');
        $method->setAccessible(true);

        return $method->invokeArgs($object, [$dateTime]);
    }

    /**
     * @param int $version  Use SephpaCreditTransfer::SEPA_PAIN_001_* constants
     * @param bool $addBIC
     * @param bool $addOptionalData
     * @param bool $checkAndSanitize
     * @return DOMDocument
     */
    private function getCreditTransferFile($version, $addBIC, $addOptionalData, $checkAndSanitize)
    {
        $creditTransferFile = new SephpaCreditTransfer('Initiator Name', 'MessageID-1234',
                                                       $version, $checkAndSanitize);

        $collectionData = array(
            'pmtInfId'      => 'PaymentID-1234',            // ID of the payment collection
            'dbtr'          => 'Name of Debtor2',           // (max 70 characters)
            'iban'          => 'DE21500500001234567897',    // IBAN of the Debtor
        );

        if($addBIC)
            $collectionData['bic'] = 'BELADEBEXXX';

        if($addOptionalData)
        {
            $collectionData['ccy']         = 'EUR';                     // Currency. Default is 'EUR'
            $collectionData['btchBookg']   = 'true';                    // BatchBooking, only 'true' or 'false'
            $collectionData['reqdExctnDt'] = '2013-11-25';              // Date: YYYY-MM-DD
            $collectionData['ultmtDebtr']  = 'Ultimate Debtor Name';    // just an information, this do not affect the payment (max 70 characters)
        }

        $paymentData = array(
            'pmtId'     => 'TransferID-1234-1',     // ID of the payment (EndToEndId)
            'instdAmt'  => 1.14,                    // amount,
            'iban'      => 'DE21500500009876543210',// IBAN of the Creditor
            'cdtr'      => 'Name of Creditor',      // (max 70 characters)
        );

        if($addBIC)
            $paymentData['bic'] = 'SPUEDE2UXXX';

        if($addOptionalData)
        {
            $paymentData['ultmtCdrt'] = 'Ultimate Creditor Name';   // just an information, this do not affect the payment (max 70 characters)
            $paymentData['rmtInf']    = 'Remittance Information';   // unstructured information about the remittance (max 140 characters)
        }

        $creditTransferCollection = $creditTransferFile->addCollection($collectionData);
        $creditTransferCollection->addPayment($paymentData);

        $domDoc = new DOMDocument();
        $domDoc->loadXML($this->invokeGenerateXml($creditTransferFile,'2014-10-19T00:38:44'));

        return $domDoc;
    }

    /**
     * @param int $version  Use SephpaDirectDebit::SEPA_PAIN_008_* constants
     * @param bool $addBIC
     * @param bool $addOptionalData
     * @param bool $checkAndSanitize
     * @return DOMDocument
     */
    private function getDirectDebitFile($version, $addBIC, $addOptionalData, $checkAndSanitize)
    {

        $collectionData = array(
            'pmtInfId'      => 'PaymentID-1235',        // ID of the payment collection
            'lclInstrm'     => SepaUtilities::LOCAL_INSTRUMENT_CORE_DIRECT_DEBIT,
            'seqTp'         => SepaUtilities::SEQUENCE_TYPE_FIRST,
            'cdtr'          => 'Name of Creditor',      // (max 70 characters)
            'iban'          => 'DE87200500001234567890',// IBAN of the Creditor
            'ci'            => 'DE98ZZZ09999999999',    // Creditor-Identifier
        );

        if($addBIC)
            $collectionData['bic'] = 'BELADEBEXXX';

        if($addOptionalData)
        {
            $collectionData['ccy']           = 'EUR';                   // Currency. Default is 'EUR'
            $collectionData['btchBookg']     = 'true';                  // BatchBooking, only 'true' or 'false'
            $collectionData['ultmtCdtr']     = 'Ultimate Creditor Name';// just an information, this do not affect the payment (max 70 characters)
            $collectionData['reqdColltnDt']  = '2013-11-25';            // Date: YYYY-MM-DD
        }

        $paymentData = array(
            'pmtId'               => 'TransferID-1235-1',       // ID of the payment (EndToEndId)
            'instdAmt'            => 2.34,                      // amount
            'mndtId'              => 'Mandate-Id',              // Mandate ID
            'dtOfSgntr'           => '2010-04-12',              // Date of signature
            'dbtr'                => 'Name of Debtor',          // (max 70 characters)
            'iban'                => 'DE87200500001234567890',  // IBAN of the Debtor
        );

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
        $directDebitFile = new SephpaDirectDebit('Initiator Name', 'MessageID-1235', $version, $checkAndSanitize);

        $directDebitCollection = $directDebitFile->addCollection($collectionData);
        $directDebitCollection->addPayment($paymentData);

        $domDoc = new DOMDocument();
        $domDoc->loadXML($this->invokeGenerateXml($directDebitFile,'2014-10-19T00:38:44'));

        return $domDoc;
    }

    public function testCreditTransfer00100203()
    {
        $version = SephpaCreditTransfer::SEPA_PAIN_001_002_03;
        $xsdFile = __DIR__ . '/schemata/pain.001.002.03.xsd';
        static::assertTrue($this->getCreditTransferFile($version,true,true,true)
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getCreditTransferFile($version,true,false,true)
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getCreditTransferFile($version,true,true,false)
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getCreditTransferFile($version,true,false,false)
                                ->schemaValidate($xsdFile));

        // check for behavior about missing BIC
        $exceptionCounter = 0;

        try { $this->getCreditTransferFile($version, false, false, false); }
        catch(SephpaInputException $e) { $exceptionCounter++; }

        try { $this->getCreditTransferFile($version, false, true, false); }
        catch(SephpaInputException $e) { $exceptionCounter++; }

        try { $this->getCreditTransferFile($version, false, false, true); }
        catch(SephpaInputException $e) { $exceptionCounter++; }

        try { $this->getCreditTransferFile($version, false, true, true); }
        catch(SephpaInputException $e) { $exceptionCounter++; }

        static::assertSame(4, $exceptionCounter);

        // check if file contend is independent from checkAndSanitize
        static::assertSame($this->getCreditTransferFile($version,true,false,false)->saveXML(),
                           $this->getCreditTransferFile($version,true,false,true)->saveXML());
        static::assertSame($this->getCreditTransferFile($version,true,true,false)->saveXML(),
                           $this->getCreditTransferFile($version,true,true,true)->saveXML());
    }

    public function testCreditTransfer00100303()
    {
        $version = SephpaCreditTransfer::SEPA_PAIN_001_003_03;
        $xsdFile = __DIR__ . '/schemata/pain.001.003.03.xsd';
        static::assertTrue($this->getCreditTransferFile($version,true,true,true)
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getCreditTransferFile($version,true,false,true)
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getCreditTransferFile($version,true,true,false)
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getCreditTransferFile($version,true,false,false)
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getCreditTransferFile($version,false,true,true)
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getCreditTransferFile($version,false,false,true)
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getCreditTransferFile($version,false,true,false)
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getCreditTransferFile($version,false,false,false)
                                ->schemaValidate($xsdFile));

        // check if file contend is independent from checkAndSanitize
        static::assertSame($this->getCreditTransferFile($version,true,false,false)->saveXML(),
                           $this->getCreditTransferFile($version,true,false,true)->saveXML());
        static::assertSame($this->getCreditTransferFile($version,true,true,false)->saveXML(),
                           $this->getCreditTransferFile($version,true,true,true)->saveXML());
        static::assertSame($this->getCreditTransferFile($version,false,false,false)->saveXML(),
                           $this->getCreditTransferFile($version,false,false,true)->saveXML());
        static::assertSame($this->getCreditTransferFile($version,false,true,false)->saveXML(),
                           $this->getCreditTransferFile($version,false,true,true)->saveXML());
    }

    public function testCreditTransfer00100103()
    {
        $version = SephpaCreditTransfer::SEPA_PAIN_001_001_03;
        foreach(array('pain.001.001.03','pain.001.001.03_GBIC') as $xsdFileVersion)
        {
            $xsdFile = __DIR__ . '/schemata/' . $xsdFileVersion . '.xsd';
            static::assertTrue($this->getCreditTransferFile($version, true, true, true)
                                    ->schemaValidate($xsdFile));
            static::assertTrue($this->getCreditTransferFile($version, true, false, true)
                                    ->schemaValidate($xsdFile));
            static::assertTrue($this->getCreditTransferFile($version, true, true, false)
                                    ->schemaValidate($xsdFile));
            static::assertTrue($this->getCreditTransferFile($version, true, false, false)
                                    ->schemaValidate($xsdFile));
            static::assertTrue($this->getCreditTransferFile($version, false, true, true)
                                    ->schemaValidate($xsdFile));
            static::assertTrue($this->getCreditTransferFile($version, false, false, true)
                                    ->schemaValidate($xsdFile));
            static::assertTrue($this->getCreditTransferFile($version, false, true, false)
                                    ->schemaValidate($xsdFile));
            static::assertTrue($this->getCreditTransferFile($version, false, false, false)
                                    ->schemaValidate($xsdFile));
        }

        // check if file contend is independent from checkAndSanitize
        static::assertSame($this->getCreditTransferFile($version, true, false, false)->saveXML(),
                           $this->getCreditTransferFile($version, true, false, true)->saveXML());
        static::assertSame($this->getCreditTransferFile($version, true, true, false)->saveXML(),
                           $this->getCreditTransferFile($version, true, true, true)->saveXML());
        static::assertSame($this->getCreditTransferFile($version, false, false, false)->saveXML(),
                           $this->getCreditTransferFile($version, false, false, true)->saveXML());
        static::assertSame($this->getCreditTransferFile($version, false, true, false)->saveXML(),
                           $this->getCreditTransferFile($version, false, true, true)->saveXML());
    }

    public function testDirectDebit00800202()
    {
        $version = SephpaDirectDebit::SEPA_PAIN_008_002_02;
        $xsdFile = __DIR__ . '/schemata/pain.008.002.02.xsd';
        static::assertTrue($this->getDirectDebitFile($version,true,true,true)
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDirectDebitFile($version,true,false,true)
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDirectDebitFile($version,true,true,false)
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDirectDebitFile($version,true,false,false)
                                ->schemaValidate($xsdFile));

        // check for behavior about missing BIC
        $exceptionCounter = 0;

        try { $this->getDirectDebitFile($version, false, false, false); }
        catch(SephpaInputException $e) { $exceptionCounter++; }

        try { $this->getDirectDebitFile($version, false, true, false); }
        catch(SephpaInputException $e) { $exceptionCounter++; }

        try { $this->getDirectDebitFile($version, false, false, true); }
        catch(SephpaInputException $e) { $exceptionCounter++; }

        try { $this->getDirectDebitFile($version, false, true, true); }
        catch(SephpaInputException $e) { $exceptionCounter++; }

        static::assertSame(4, $exceptionCounter);

        // check if file contend is independent from checkAndSanitize
        static::assertSame($this->getDirectDebitFile($version,true,false,false)->saveXML(),
                           $this->getDirectDebitFile($version,true,false,true)->saveXML());
        static::assertSame($this->getDirectDebitFile($version,true,true,false)->saveXML(),
                           $this->getDirectDebitFile($version,true,true,true)->saveXML());
    }

    public function testDirectDebit00800302()
    {
        $version = SephpaDirectDebit::SEPA_PAIN_008_003_02;
        $xsdFile = __DIR__ . '/schemata/pain.008.003.02.xsd';
        static::assertTrue($this->getDirectDebitFile($version,true,true,true)
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDirectDebitFile($version,true,false,true)
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDirectDebitFile($version,true,true,false)
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDirectDebitFile($version,true,false,false)
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDirectDebitFile($version,false,true,true)
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDirectDebitFile($version,false,false,true)
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDirectDebitFile($version,false,true,false)
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDirectDebitFile($version,false,false,false)
                                ->schemaValidate($xsdFile));

        // check if file contend is independent from checkAndSanitize
        static::assertSame($this->getDirectDebitFile($version,true,false,false)->saveXML(),
                           $this->getDirectDebitFile($version,true,false,true)->saveXML());
        static::assertSame($this->getDirectDebitFile($version,true,true,false)->saveXML(),
                           $this->getDirectDebitFile($version,true,true,true)->saveXML());
        static::assertSame($this->getDirectDebitFile($version,false,false,false)->saveXML(),
                           $this->getDirectDebitFile($version,false,false,true)->saveXML());
        static::assertSame($this->getDirectDebitFile($version,false,true,false)->saveXML(),
                           $this->getDirectDebitFile($version,false,true,true)->saveXML());
    }

    public function testDirectDebit00800102()
    {
        $version = SephpaDirectDebit::SEPA_PAIN_008_001_02;
        foreach(array('pain.008.001.02','pain.008.001.02_GBIC') as $xsdFileVersion)
        {
            $xsdFile = __DIR__ . '/schemata/' . $xsdFileVersion . '.xsd';
            static::assertTrue($this->getDirectDebitFile($version, true, true, true)
                                    ->schemaValidate($xsdFile));
            static::assertTrue($this->getDirectDebitFile($version, true, false, true)
                                    ->schemaValidate($xsdFile));
            static::assertTrue($this->getDirectDebitFile($version, true, true, false)
                                    ->schemaValidate($xsdFile));
            static::assertTrue($this->getDirectDebitFile($version, true, false, false)
                                    ->schemaValidate($xsdFile));
            static::assertTrue($this->getDirectDebitFile($version, false, true, true)
                                    ->schemaValidate($xsdFile));
            static::assertTrue($this->getDirectDebitFile($version, false, false, true)
                                    ->schemaValidate($xsdFile));
            static::assertTrue($this->getDirectDebitFile($version, false, true, false)
                                    ->schemaValidate($xsdFile));
            static::assertTrue($this->getDirectDebitFile($version, false, false, false)
                                    ->schemaValidate($xsdFile));
        }

        // check if file contend is independent from checkAndSanitize
        static::assertSame($this->getDirectDebitFile($version, true, false, false)->saveXML(),
                           $this->getDirectDebitFile($version, true, false, true)->saveXML());
        static::assertSame($this->getDirectDebitFile($version, true, true, false)->saveXML(),
                           $this->getDirectDebitFile($version, true, true, true)->saveXML());
        static::assertSame($this->getDirectDebitFile($version, false, false, false)->saveXML(),
                           $this->getDirectDebitFile($version, false, false, true)->saveXML());
        static::assertSame($this->getDirectDebitFile($version, false, true, false)->saveXML(),
                           $this->getDirectDebitFile($version, false, true, true)->saveXML());
    }

    public function testDirectDebit00800102Austrian003()
    {
        $version = SephpaDirectDebit::SEPA_PAIN_008_001_02_AUSTRIAN_003;
        $xsdFile = __DIR__ . '/schemata/pain.008.001.02.austrian.003.xsd';
        static::assertTrue($this->getDirectDebitFile($version, true, true, true)
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDirectDebitFile($version, true, false, true)
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDirectDebitFile($version, true, true, false)
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDirectDebitFile($version, true, false, false)
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDirectDebitFile($version, false, true, true)
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDirectDebitFile($version, false, false, true)
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDirectDebitFile($version, false, true, false)
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDirectDebitFile($version, false, false, false)
                                ->schemaValidate($xsdFile));

        // check if file contend is independent from checkAndSanitize
        static::assertSame($this->getDirectDebitFile($version, true, false, false)->saveXML(),
                           $this->getDirectDebitFile($version, true, false, true)->saveXML());
        static::assertSame($this->getDirectDebitFile($version, true, true, false)->saveXML(),
                           $this->getDirectDebitFile($version, true, true, true)->saveXML());
        static::assertSame($this->getDirectDebitFile($version, false, false, false)->saveXML(),
                           $this->getDirectDebitFile($version, false, false, true)->saveXML());
        static::assertSame($this->getDirectDebitFile($version, false, true, false)->saveXML(),
                           $this->getDirectDebitFile($version, false, true, true)->saveXML());
    }

    /**
     * check if cloning SimpleXML objects works as expected
     */
    public function testSimpleXMLClone()
    {
        $xml1 = simplexml_load_string('<Doc></Doc>');
        $child1 = $xml1->addChild('child1');

        $xml2 = clone $xml1;
        $xml2->addChild('child2');

        $child1->addChild('subChild');

        static::assertNotSame($xml1->asXML(), $xml2->asXML());
    }
}