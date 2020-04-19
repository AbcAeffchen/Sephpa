<?php /**
 * Sephpa
 *
 * @license   GNU LGPL v3.0 - For details have a look at the LICENSE file
 * @copyright ©2020 Alexander Schickedanz
 * @link      https://github.com/AbcAeffchen/Sephpa
 *
 * @author  Alexander Schickedanz <abcaeffchen@gmail.com>
 */ /**
 * Sephpa
 *
 * @license   GNU LGPL v3.0 - For details have a look at the LICENSE file
 * @copyright ©2020 Alexander Schickedanz
 * @link      https://github.com/AbcAeffchen/Sephpa
 *
 * @author  Alexander Schickedanz <abcaeffchen@gmail.com>
 */ /** @noinspection PhpComposerExtensionStubsInspection */
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
            '001.002.03' => [SephpaCreditTransfer::SEPA_PAIN_001_002_03, __DIR__ . '/schemata/pain.001.002.03.xsd'],
            '001.003.03' => [SephpaCreditTransfer::SEPA_PAIN_001_003_03, __DIR__ . '/schemata/pain.001.003.03.xsd'],
            '001.001.03' => [SephpaCreditTransfer::SEPA_PAIN_001_001_03, __DIR__ . '/schemata/pain.001.001.03.xsd'],
            '001.001.03_GBIC' => [SephpaCreditTransfer::SEPA_PAIN_001_001_03, __DIR__ . '/schemata/pain.001.001.03_GBIC.xsd']
        ];
    }

    public function ddVersionProvider()
    {
        return [
            '008.002.02' => [SephpaDirectDebit::SEPA_PAIN_008_002_02, __DIR__ . '/schemata/pain.008.002.02.xsd'],
            '008.003.02' => [SephpaDirectDebit::SEPA_PAIN_008_003_02, __DIR__ . '/schemata/pain.008.003.02.xsd'],
            '008.001.02' => [SephpaDirectDebit::SEPA_PAIN_008_001_02, __DIR__ . '/schemata/pain.008.001.02.xsd'],
            '008.001.02_GBIC' => [SephpaDirectDebit::SEPA_PAIN_008_001_02, __DIR__ . '/schemata/pain.008.001.02_GBIC.xsd'],
            'pain.008.001.02.austrian.003' => [SephpaDirectDebit::SEPA_PAIN_008_001_02_AUSTRIAN_003, __DIR__ . '/schemata/pain.008.001.02.austrian.003.xsd']
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
     * Get a DOMDocument object from a Sephpa Object. This is used to check the xml format.
     *
     * @param Sephpa $sephpaFile A Sephpa object (SephpaCreditTransfer or SephpaDirectDebit)
     * @return DOMDocument
     * @throws SephpaInputException
     * @throws \Mpdf\MpdfException
     */
    private function getDomDoc(Sephpa $sephpaFile)
    {
        $domDoc = new DOMDocument();
        $domDoc->loadXML($sephpaFile->generateOutput()[0]['data']);

        return $domDoc;
    }

    /**
     * @param Sephpa $sephpaFile
     * @param string $xsdFile
     * @throws SephpaInputException
     * @throws \Mpdf\MpdfException
     */
    private function validateSchema(Sephpa $sephpaFile, string $xsdFile)
    {
        static::assertTrue($this->getDomDoc($sephpaFile)->schemaValidate($xsdFile));
    }

    /**
     * @dataProvider ctVersionProvider
     * @dataProvider ddVersionProvider
     * @param $version
     * @param $xsdFile
     * @throws SephpaInputException
     * @throws \Mpdf\MpdfException
     */
    public function testOrgId($version, $xsdFile)
    {
        $this->validateSchema(TDP::getFile($version, true, true, true, []), $xsdFile);
        $this->validateSchema(TDP::getFile($version, true, true, true, ['id' => 'testID']), $xsdFile);
        $this->validateSchema(TDP::getFile($version, true, true, true, ['bob' => 'BELADEBEXXX']), $xsdFile);

        try
        {
            TDP::getFile($version, true, true, true, ['id'  => 'testID', 'bob' => 'BELADEBEXXX']);
            static::fail('Exception was not thrown...');
        }
        catch(SephpaInputException $e)
        {
            static::assertSame('You cannot use orgid[id] and orgid[bob] simultaneously.', $e->getMessage());
        }
    }

    /**
     * @dataProvider ctVersionProvider
     * @dataProvider ddVersionProvider
     * @param $version
     * @param $xsdFile
     * @throws SephpaInputException
     * @throws \Mpdf\MpdfException
     */
    public function testInitgPtyId($version, $xsdFile)
    {
        // InitgPtyId is not supported by pain.008.001.02.austrian.003
        if($version === SepaUtilities::SEPA_PAIN_008_001_02_AUSTRIAN_003)
            static::expectException(SephpaInputException::class);

        $this->validateSchema(TDP::getFile($version, true, true, true, [], 'InitgPtyId-123'),
                              $xsdFile);
    }

    /**
     * @dataProvider ctVersionProvider
     * @dataProvider ddVersionProvider
     * @param $version
     * @param $xsdFile
     * @throws SephpaInputException
     * @throws \Mpdf\MpdfException
     */
    public function testBasicFileValidity($version, $xsdFile)
    {
        $bicRequired = in_array($version, [SephpaCreditTransfer::SEPA_PAIN_001_002_03,
                                           SephpaDirectDebit::SEPA_PAIN_008_002_02]);

        // validate against schema
        foreach($this->generateBooleanCombinations(3) as [$bic, $opt, $check])
        {
            try
            {
                $this->validateSchema(TDP::getFile($version, $bic, $opt, $check), $xsdFile);
            }
            catch(SephpaInputException $e)
            {
                static::assertTrue($bicRequired && !$bic);
            }
        }

        // check that checking does not change the result.
        foreach($this->generateBooleanCombinations(2) as [$bic, $opt])
        {
            if($bicRequired && !$bic)
                continue;

            static::assertSame($this->getDomDoc(TDP::getFile($version, $bic, $opt, false))->saveXML(),
                               $this->getDomDoc(TDP::getFile($version, $bic, $opt, true))->saveXML());
        }
    }
}