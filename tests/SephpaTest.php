<?php
/**
 * Sephpa
 *
 * @license   GNU LGPL v3.0 - For details have a look at the LICENSE file
 * @copyright ©2020 Alexander Schickedanz
 * @link      https://github.com/AbcAeffchen/Sephpa
 *
 * @author  Alexander Schickedanz <abcaeffchen@gmail.com>
 */

/** @noinspection PhpUnhandledExceptionInspection */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/TestDataProvider.php';

use AbcAeffchen\SepaUtilities\SepaUtilities;
use AbcAeffchen\Sephpa\{Sephpa, SephpaCreditTransfer, SephpaDirectDebit, SephpaInputException};
use AbcAeffchen\Sephpa\TestDataProvider as TDP;

class ReturnReferenceTestClass
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
        return $this->testArray[count($this->testArray) - 1];
    }
}

class SephpaTest extends PHPUnit\Framework\TestCase
{
    public function ctVersionProvider()
    {
        return [
            'ct2' => [SephpaCreditTransfer::SEPA_PAIN_001_002_03, __DIR__ . '/schemata/pain.001.002.03.xsd'],
            'ct3' => [SephpaCreditTransfer::SEPA_PAIN_001_003_03, __DIR__ . '/schemata/pain.001.003.03.xsd'],
            'ct1' => [SephpaCreditTransfer::SEPA_PAIN_001_001_03, __DIR__ . '/schemata/pain.001.001.03.xsd'],
            'ct1_gbic' => [SephpaCreditTransfer::SEPA_PAIN_001_001_03, __DIR__ . '/schemata/pain.001.001.03_GBIC.xsd']
        ];
    }

    public function ddVersionProvider()
    {
        return [
            'dd2' => [SephpaDirectDebit::SEPA_PAIN_008_002_02, __DIR__ . '/schemata/pain.008.002.02.xsd'],
            'dd3' => [SephpaDirectDebit::SEPA_PAIN_008_003_02, __DIR__ . '/schemata/pain.008.003.02.xsd'],
            'dd1' => [SephpaDirectDebit::SEPA_PAIN_008_001_02, __DIR__ . '/schemata/pain.008.001.02.xsd'],
            'dd1_gbic' => [SephpaDirectDebit::SEPA_PAIN_008_001_02, __DIR__ . '/schemata/pain.008.001.02_GBIC.xsd'],
            'dd1_austrian' => [SephpaDirectDebit::SEPA_PAIN_008_001_02_AUSTRIAN_003, __DIR__ . '/schemata/pain.008.001.02.austrian.003.xsd']
        ];
    }

    public function testEndReference()
    {
        $testObj = new ReturnReferenceTestClass();
        $end = &$testObj->getEnd();
        $end = 1;
        $this->assertSame(1, end($testObj->testArray));
    }

    /**
     * Generates all combinations of a boolean array with $n entries.
     *
     * @param int $n
     * @return Generator
     */
    private function generateBooleanCombinations(int $n)
    {
        assert($n > 0);
        $booleans = array_fill(0, $n, false);

        yield $booleans;

        $max = 2 ** $n;
        for($i = 1; $i < $max; $i++)
        {
            for($j = 0; $j < $n; $j++)
                $booleans[$j] = (bool) ($i & (2 ** $j));

            yield $booleans;
        }
    }

    public function testBooleanGenerator()
    {
        for($i = 1; $i <= 3; $i++)
        {
            $allArrays = [];
            foreach($this->generateBooleanCombinations($i) as $booleanArray)
            {
                static::assertSame($i, count($booleanArray));
                $allArrays[] = $booleanArray;
            }

            static::assertSame(2 ** $i, count($allArrays));
            static::assertSame(2 ** $i, count(array_unique($allArrays, SORT_REGULAR)));
        }
    }

    /**
     * Calls the protected method `generateXml()` of the provided Sephpa object.
     *
     * @param Sephpa $object
     * @return mixed
     * @throws ReflectionException
     */
    private function invokeGenerateXml(Sephpa &$object)
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod('generateXml');
        $method->setAccessible(true);

        return $method->invokeArgs($object, []);
    }

    /**
     * Get a DOMDocument object from a Sephpa Object. This is used to check the xml format.
     *
     * @param Sephpa $sephpaFile A Sephpa object (SephpaCreditTransfer or SephpaDirectDebit)
     * @return DOMDocument
     * @throws ReflectionException
     */
    private function getDomDoc(Sephpa $sephpaFile)
    {
        $domDoc = new DOMDocument();
        $domDoc->loadXML($this->invokeGenerateXml($sephpaFile));

        return $domDoc;
    }

    /**
     * @dataProvider ctVersionProvider
     */
    public function testOrgId($version, $xsdFile)
    {
        static::assertTrue($this->getDomDoc(TDP::getCreditTransferFile($version,true,true,true,[]))
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDomDoc(TDP::getCreditTransferFile($version,true,true,true,['id' => 'testID']))
                                ->schemaValidate($xsdFile));
        static::assertTrue($this->getDomDoc(TDP::getCreditTransferFile($version,true,true,true,['bob' => 'BELADEBEXXX']))
                                ->schemaValidate($xsdFile));
    }

    /**
     * @dataProvider ctVersionProvider
     * @dataProvider ddVersionProvider
     */
    public function testInitgPtyId($version, $xsdFile)
    {
        // InitgPtyId is not supported by pain.008.001.02.austrian.003
        if($version === SepaUtilities::SEPA_PAIN_008_001_02_AUSTRIAN_003)
            static::expectException(SephpaInputException::class);

        if(SepaUtilities::version2transactionType($version) === SepaUtilities::SEPA_TRANSACTION_TYPE_CT)
            static::assertTrue($this->getDomDoc(TDP::getCreditTransferFile($version,true,true,true,[],'InitgPtyId-123'))
                                    ->schemaValidate($xsdFile));
        else
            static::assertTrue($this->getDomDoc(TDP::getDirectDebitFile($version,true,true,true,[],'InitgPtyId-123'))
                                    ->schemaValidate($xsdFile));
    }

    public function testCreditTransfer00100203()
    {
        $version = SephpaCreditTransfer::SEPA_PAIN_001_002_03;
        $xsdFile = __DIR__ . '/schemata/pain.001.002.03.xsd';
        foreach($this->generateBooleanCombinations(2) as $b)
            static::assertTrue($this->getDomDoc(TDP::getCreditTransferFile($version, true, $b[1], $b[0]))
                                    ->schemaValidate($xsdFile));

        // check for behavior about missing BIC
        $exceptionCounter = 0;

        foreach($this->generateBooleanCombinations(2) as $b)
            try { $this->getDomDoc(TDP::getCreditTransferFile($version, false, $b[1], $b[0])); }
            catch(SephpaInputException $e) { $exceptionCounter++; }

        static::assertSame(4, $exceptionCounter);

        // check if file contend is independent from checkAndSanitize
        foreach([true, false] as $b)
            static::assertSame($this->getDomDoc(TDP::getCreditTransferFile($version, true, $b, false))->saveXML(),
                               $this->getDomDoc(TDP::getCreditTransferFile($version, true, $b, true))->saveXML());
    }

    public function testCreditTransfer00100303()
    {
        $version = SephpaCreditTransfer::SEPA_PAIN_001_003_03;
        $xsdFile = __DIR__ . '/schemata/pain.001.003.03.xsd';
        foreach($this->generateBooleanCombinations(3) as $b)
            static::assertTrue($this->getDomDoc(TDP::getCreditTransferFile($version, $b[2], $b[1], $b[0]))
                                    ->schemaValidate($xsdFile));

        // check if file content is independent from checkAndSanitize
        foreach($this->generateBooleanCombinations(2) as $b)
            static::assertSame($this->getDomDoc(TDP::getCreditTransferFile($version, $b[1], $b[0], false))->saveXML(),
                               $this->getDomDoc(TDP::getCreditTransferFile($version, $b[1], $b[0], true))->saveXML());
    }

    public function testCreditTransfer00100103()
    {
        $version = SephpaCreditTransfer::SEPA_PAIN_001_001_03;
        foreach(['pain.001.001.03', 'pain.001.001.03_GBIC'] as $xsdFileVersion)
        {
            $xsdFile = __DIR__ . '/schemata/' . $xsdFileVersion . '.xsd';
            foreach($this->generateBooleanCombinations(3) as $b)
                static::assertTrue($this->getDomDoc(TDP::getCreditTransferFile($version, $b[2], $b[1], $b[0]))
                                        ->schemaValidate($xsdFile));
        }

        // check if file contend is independent from checkAndSanitize
        foreach($this->generateBooleanCombinations(2) as $b)
            static::assertSame($this->getDomDoc(TDP::getCreditTransferFile($version, $b[1], $b[0], false))->saveXML(),
                               $this->getDomDoc(TDP::getCreditTransferFile($version, $b[1], $b[0], true))->saveXML());
    }

    public function testDirectDebit00800202()
    {
        $version = SephpaDirectDebit::SEPA_PAIN_008_002_02;
        $xsdFile = __DIR__ . '/schemata/pain.008.002.02.xsd';

        foreach($this->generateBooleanCombinations(2) as $b)
            static::assertTrue($this->getDomDoc(TDP::getDirectDebitFile($version, true, $b[1], $b[0]))
                                    ->schemaValidate($xsdFile));

        // check for behavior about missing BIC
        $exceptionCounter = 0;

        foreach($this->generateBooleanCombinations(2) as $b)
            try { $this->getDomDoc(TDP::getDirectDebitFile($version, false, $b[1], $b[0])); }
            catch(SephpaInputException $e) { $exceptionCounter++; }

        static::assertSame(4, $exceptionCounter);

        // check if file contend is independent from checkAndSanitize
        foreach([true, false] as $b)
            static::assertSame($this->getDomDoc(TDP::getDirectDebitFile($version,true,$b,false))->saveXML(),
                               $this->getDomDoc(TDP::getDirectDebitFile($version,true,$b,true))->saveXML());
    }

    public function testDirectDebit00800302()
    {
        $version = SephpaDirectDebit::SEPA_PAIN_008_003_02;
        $xsdFile = __DIR__ . '/schemata/pain.008.003.02.xsd';

        foreach($this->generateBooleanCombinations(3) as $b)
            static::assertTrue($this->getDomDoc(TDP::getDirectDebitFile($version, $b[2], $b[1], $b[0]))
                                    ->schemaValidate($xsdFile));

        // check if file contend is independent from checkAndSanitize
        foreach($this->generateBooleanCombinations(2) as $b)
            static::assertSame($this->getDomDoc(TDP::getDirectDebitFile($version, $b[1], $b[0], false))->saveXML(),
                               $this->getDomDoc(TDP::getDirectDebitFile($version, $b[1], $b[0], true))->saveXML());
    }

    public function testDirectDebit00800102()
    {
        $version = SephpaDirectDebit::SEPA_PAIN_008_001_02;
        foreach(['pain.008.001.02', 'pain.008.001.02_GBIC'] as $xsdFileVersion)
        {
            $xsdFile = __DIR__ . '/schemata/' . $xsdFileVersion . '.xsd';
            foreach($this->generateBooleanCombinations(3) as $b)
                static::assertTrue($this->getDomDoc(TDP::getDirectDebitFile($version, $b[2], $b[1], $b[0]))
                                        ->schemaValidate($xsdFile));
        }

        // check if file contend is independent from checkAndSanitize
        foreach($this->generateBooleanCombinations(2) as $b)
            static::assertSame($this->getDomDoc(TDP::getDirectDebitFile($version, $b[1], $b[0], false))->saveXML(),
                               $this->getDomDoc(TDP::getDirectDebitFile($version, $b[1], $b[0], true))->saveXML());
    }

    public function testDirectDebit00800102Austrian003()
    {
        $version = SephpaDirectDebit::SEPA_PAIN_008_001_02_AUSTRIAN_003;
        $xsdFile = __DIR__ . '/schemata/pain.008.001.02.austrian.003.xsd';

        foreach($this->generateBooleanCombinations(3) as $b)
            static::assertTrue($this->getDomDoc(TDP::getDirectDebitFile($version, $b[2], $b[1], $b[0]))
                                    ->schemaValidate($xsdFile));

        // check if file contend is independent from checkAndSanitize
        foreach($this->generateBooleanCombinations(2) as $b)
            static::assertSame($this->getDomDoc(TDP::getDirectDebitFile($version, $b[1], $b[0], false))->saveXML(),
                               $this->getDomDoc(TDP::getDirectDebitFile($version, $b[1], $b[0], true))->saveXML());
    }
}