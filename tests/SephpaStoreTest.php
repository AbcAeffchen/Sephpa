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

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/TestDataProvider.php';

use Mpdf\MpdfException;
use AbcAeffchen\Sephpa\{SephpaCreditTransfer, SephpaDirectDebit, SephpaInputException, SephpaMultiFile};

use AbcAeffchen\Sephpa\TestDataProvider as TDP;

class SephpaStoreTest extends PHPUnit\Framework\TestCase
{
    /**
     * Tests if File Routing Slip and Control List are generated. This tests creates an output
     * folder in the tests folder containing two zip files, that contain the xml and pdf files.
     *
     * @throws SephpaInputException
     * @throws MpdfException
     * @noinspection PhpVoidFunctionResultUsedInspection*/
    public function testAdditionalDocuments()
    {
        // according to https://github.com/sebastianbergmann/phpunit-documentation/issues/171#issuecomment-337854895
        // assertNull is the way to test if a function that returns nothing was executed without errors or exceptions.

        $version = SephpaCreditTransfer::SEPA_PAIN_001_001_03;
        $file = TDP::getCreditTransferFile($version, true, true, true);
        $this->assertNull($file->store(__DIR__ . DIRECTORY_SEPARATOR . 'output',
                                       ['addFileRoutingSlip' => true,
                                        'addControlList'     => true]));

        $version = SephpaDirectDebit::SEPA_PAIN_008_001_02;
        $file = TDP::getDirectDebitFile($version, true, true, true);
        $this->assertNull($file->store(__DIR__ . DIRECTORY_SEPARATOR . 'output',
                                       ['addFileRoutingSlip' => true,
                                        'addControlList'     => true]));
    }

    /**
     * @throws SephpaInputException
     * @throws MpdfException
     * @noinspection PhpVoidFunctionResultUsedInspection
     */
    public function testMultiFile()
    {
        // according to https://github.com/sebastianbergmann/phpunit-documentation/issues/171#issuecomment-337854895
        // assertNull is the way to test if a function that returns nothing was executed without errors or exceptions.

        $sephpaMultiFile = new SephpaMultiFile();
        $creditTransferFile = $sephpaMultiFile->addCreditTransferFile('Initiator Name', 'MessageID-1234',
                                                                      SephpaCreditTransfer::SEPA_PAIN_001_001_03, [], null, true);
        $directDebitFile = $sephpaMultiFile->addDirectDebitFile('Initiator Name',
                                                                'MessageID-1235',
                                                                SephpaDirectDebit::SEPA_PAIN_008_001_02, [], null, true);

        $creditTransferFile->addCollection(TDP::getCreditTransferData(false, false))
                           ->addPayment(TDP::getCreditTransferPaymentData(false, false));
        $directDebitFile->addCollection(TDP::getDirectDebitData(false, false))
                        ->addPayment(TDP::getDirectDebitPaymentData(false, false));

        $this->assertNull($sephpaMultiFile->store(__DIR__ . DIRECTORY_SEPARATOR . 'output',
                                                  ['addFileRoutingSlip' => true,
                                                   'addControlList'     => true]));
    }
}