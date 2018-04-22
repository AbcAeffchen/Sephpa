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

use AbcAeffchen\Sephpa\SephpaCreditTransfer;

require __DIR__ . '/../src/autoloader.php'; // path to Sephpa autoloader
require __DIR__ . '/../vendor/abcaeffchen/sepa-utilities/src/SepaUtilities.php';    // path to sepa-utilities

// load files that exist
$test = new AbcAeffchen\Sephpa\SephpaDirectDebit('test','test',AbcAeffchen\Sephpa\SephpaDirectDebit::SEPA_PAIN_008_001_02);

// load file without fully qualified name
$test = new SephpaCreditTransfer('test', 'test', SephpaCreditTransfer::SEPA_PAIN_001_001_03);

// load files that not exist. Make sure the autoloader does not try to require them (fails, but just because "Bla" does not exist. An other autoloader could load it)
$test = new Bla();