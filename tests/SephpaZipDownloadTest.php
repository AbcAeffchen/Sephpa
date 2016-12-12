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

error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../vendor/autoload.php';

use AbcAeffchen\Sephpa\SephpaDirectDebit;
use AbcAeffchen\SepaUtilities\SepaUtilities;

$directDebitFile = new SephpaDirectDebit('Initiator Name', 'MessageID-1234',
                                         SephpaDirectDebit::SEPA_PAIN_008_001_02,
                                         false);

$collectionData = array(
    'pmtInfId'      => 'PaymentID-1235',        // ID of the payment collection
    'lclInstrm'     => SepaUtilities::LOCAL_INSTRUMENT_CORE_DIRECT_DEBIT,
    'seqTp'         => SepaUtilities::SEQUENCE_TYPE_FIRST,
    'cdtr'          => 'Name of Creditor',      // (max 70 characters)
    'iban'          => 'DE87200500001234567890',// IBAN of the Creditor
    'ci'            => 'DE98ZZZ09999999999',    // Creditor-Identifier
);

$paymentData = array(
    'pmtId'               => 'TransferID-1235-1',       // ID of the payment (EndToEndId)
    'instdAmt'            => 2.34,                      // amount
    'mndtId'              => 'Mandate-Id',              // Mandate ID
    'dtOfSgntr'           => '2010-04-12',              // Date of signature
    'dbtr'                => 'Name of Debtor',          // (max 70 characters)
    'iban'                => 'DE87200500001234567890',  // IBAN of the Debtor
);

$directDebitCollection1 = $directDebitFile->addCollection($collectionData);
$directDebitCollection1->addPayment($paymentData);
$directDebitCollection2 = $directDebitFile->addCollection($collectionData);
$directDebitCollection2->addPayment($paymentData);

$directDebitFile->downloadSepaFile('payment.xml');



