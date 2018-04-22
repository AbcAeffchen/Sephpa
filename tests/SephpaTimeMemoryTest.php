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

use AbcAeffchen\Sephpa\SephpaCreditTransfer;

function testTimeMemory()
{
    $start = microtime(true);

    $collectionData = [
        'pmtInfId'      => 'PaymentID-1234',            // ID of the payment collection
        'dbtr'          => 'Name of Debtor2',           // (max 70 characters)
        'iban'          => 'DE21500500001234567897',    // IBAN of the Debtor
        'bic'           => 'BELADEBEXXX',
        'ccy'           => 'EUR',                       // Currency. Default is 'EUR'
        'btchBookg'     => 'true',                      // BatchBooking, only 'true' or 'false'
        'reqdExctnDt'   => '2013-11-25',                // Date: YYYY-MM-DD
        'ultmtDebtr'    => 'Ultimate Debtor Name'       // just an information, this do not affect the payment (max 70 characters)
    ];

    $creditTransferFile = new SephpaCreditTransfer('Initiator Name', 'MessageID-1234',
                                                   SephpaCreditTransfer::SEPA_PAIN_001_001_03,
                                                   $collectionData, false);

    $paymentData = [
        'pmtId'     => 'TransferID-1234-1',     // ID of the payment (EndToEndId)
        'instdAmt'  => 1.14,                    // amount,
        'iban'      => 'DE21500500009876543210',// IBAN of the Creditor
        'cdtr'      => 'Name of Creditor',      // (max 70 characters)
        'bic'       => 'SPUEDE2UXXX',
        'ultmtCdrt' => 'Ultimate Creditor Name',   // just an information, this do not affect the payment (max 70 characters)
        'rmtInf'    => 'Remittance Information should longer'   // unstructured information about the remittance (max 140 characters)
    ];

    for($i = 0; $i < 100000; $i++)
        $creditTransferFile->addPayment($paymentData);

    $creditTransferFile->store(__DIR__);

    echo memory_get_peak_usage() / 1024.0 / 1024.0 . " MB\n";
    echo (microtime(true) - $start) . ' s';
}

testTimeMemory();
