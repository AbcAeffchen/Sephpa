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

use AbcAeffchen\Sephpa\SephpaCreditTransfer;
use AbcAeffchen\Sephpa\TestDataProvider as TDP;

require __DIR__ . '/../src/autoloader.php'; // path to Sephpa autoloader
require __DIR__ . '/../vendor/abcaeffchen/sepa-utilities/src/SepaUtilities.php';    // path to sepa-utilities
require __DIR__ . '/../tests/TestDataProvider.php';     // path to test data
// load files that exist
$test = new AbcAeffchen\Sephpa\SephpaDirectDebit('testParty', 'testMessageID',
                                                 AbcAeffchen\Sephpa\SephpaDirectDebit::SEPA_PAIN_008_001_02,
                                                 TDP::getDirectDebitData(false, false));

// load file without fully qualified name
$test = new SephpaCreditTransfer('testParty', 'testMessageID',
                                 SephpaCreditTransfer::SEPA_PAIN_001_001_03,
                                 TDP::getCreditTransferData(false, false));

// load files that not exist. Make sure the autoloader does not try to require them (fails, but just because "NonExitingClass" does not exist. An other autoloader could load it)
$test = new NonExitingClass();