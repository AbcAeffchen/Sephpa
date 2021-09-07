<?php
/*
 * Sephpa
 *
 * @license   GNU LGPL v3.0 - For details have a look at the LICENSE file
 * @copyright ©2021 Alexander Schickedanz
 * @link      https://github.com/AbcAeffchen/Sephpa
 *
 * @author  Alexander Schickedanz <abcaeffchen@gmail.com>
 */

namespace AbcAeffchen\Sephpa;

use AbcAeffchen\SepaUtilities\SepaUtilities;

class TestDataProvider
{
    private static function isCreditTransfer(int $version)
    {
        return SepaUtilities::version2transactionType($version) === SepaUtilities::SEPA_TRANSACTION_TYPE_CT;
    }

    /**
     * @param int|null $adrType 0 = string,
     *                          1 = array with one element,
     *                          2 = array with two elements,
     *                          null = random
     * @return array
     */
    private static function getPstlAdrData(?int $adrType = null) : array
    {
        if($adrType === null)
            $adrType = random_int(0, 2);

        return ['ctry' => 'DE',
                'adrLine' => $adrType === 0 ? 'test 1' : ($adrType === 1 ? ['test 1'] : ['test 1', 'test 2'])];
    }

    public static function getCreditTransferData(bool $addBIC, bool $addOptionalData) : array
    {
        $transferInformation = [
            'pmtInfId'      => 'PaymentCollectionID-1234',  // ID of the payment collection
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
            $transferInformation['ultmtDbtr']   = 'Ultimate Debtor Name';    // just an information, this do not affect the payment (max 70 characters)
            $transferInformation['ctgyPurp']    = 'BONU';
            $transferInformation['pstlAdr']     = self::getPstlAdrData(2);
        }

        return $transferInformation;
    }

    public static function getCreditTransferPaymentData(bool $addBIC, bool $addOptionalData, int $id = 1) : array
    {
        $paymentData = [
            'pmtId'     => 'PaymentID-1234-' . $id,     // ID of the payment (EndToEndId)
            'instdAmt'  => 1.14,                        // amount,
            'iban'      => 'DE21500500009876543210',    // IBAN of the Creditor
            'cdtr'      => 'Name of Creditor',          // (max 70 characters)
        ];

        if($addBIC)
            $paymentData['bic'] = 'SPUEDE2UXXX';
        else
            $paymentData['bic'] = '';

        if($addOptionalData)
        {
            $paymentData['ultmtDbtr'] = 'Ultimate Debtor Name';
            $paymentData['ultmtDbtrId'] = 'Ultimate Debtor ID';
            $paymentData['ultmtCdtr'] = 'Ultimate Creditor Name';   // just an information, this do not affect the payment (max 70 characters)
            $paymentData['rmtInf']    = 'Remittance Information';   // unstructured information about the remittance (max 140 characters)
            $paymentData['purp']      = 'BONU';                     // Four letter code
            $paymentData['pstlAdr']   = self::getPstlAdrData(($id ?? 2) % 3);
        }

        return $paymentData;
    }

    public static function getDirectDebitData(bool $addBIC, bool $addOptionalData) : array
    {
        $directDebitInformation = [
            'pmtInfId'      => 'PaymentCollectionID-1235',  // ID of the payment collection
            'lclInstrm'     => SepaUtilities::LOCAL_INSTRUMENT_CORE_DIRECT_DEBIT,
            'seqTp'         => SepaUtilities::SEQUENCE_TYPE_FIRST,
            'cdtr'          => 'Name of Creditor',          // (max 70 characters)
            'iban'          => 'DE87200500001234567890',    // IBAN of the Creditor
            'ci'            => 'DE98ZZZ09999999999',        // Creditor-Identifier
        ];

        if($addBIC)
            $directDebitInformation['bic'] = 'BELADEBEXXX';

        if($addOptionalData)
        {
            $directDebitInformation['ccy']           = 'EUR';                   // Currency. Default is 'EUR'
            $directDebitInformation['btchBookg']     = 'true';                  // BatchBooking, only 'true' or 'false'
            $directDebitInformation['ultmtCdtr']     = 'Ultimate Creditor Name';// just an information, this do not affect the payment (max 70 characters)
            $directDebitInformation['ctgyPurp']      = 'BONU';
            $directDebitInformation['reqdColltnDt']  = '2013-11-25';            // Date: YYYY-MM-DD
            $directDebitInformation['pstlAdr']       = self::getPstlAdrData(2);
        }

        return $directDebitInformation;
    }

    public static function getDirectDebitPaymentData(bool $addBIC, bool $addOptionalData, int $id = 1) : array
    {
        $paymentData = [
            'pmtId'               => 'PaymentID-1235-' . $id,        // ID of the payment (EndToEndId)
            'instdAmt'            => 2.34,                      // amount
            'mndtId'              => 'Mandate-Id',              // Mandate ID
            'dtOfSgntr'           => '2010-04-12',              // Date of signature
            'dbtr'                => 'Name of Debtor',          // (max 70 characters)
            'iban'                => 'DE87200500001234567890',  // IBAN of the Debtor
        ];

        if($addBIC)
            $paymentData['bic'] = 'BELADEBEXXX';
        else
            $paymentData['bic'] = '';

        if($addOptionalData)
        {
            $paymentData['amdmntInd']           = 'true';                    // Did the mandate change
            $paymentData['ultmtDbtr']           = 'Ultimate Debtor Name';    // just an information, this do not affect the payment (max 70 characters)
            $paymentData['rmtInf']              = 'Remittance Information';  // unstructured information about the remittance (max 140 characters)
            $paymentData['purp']                = 'BONU';                    // Four letter code
            $paymentData['pstlAdr']             = self::getPstlAdrData(($id ?? 2) % 3);
            // only use this if 'amdmntInd' is 'true'. at least one must be used
            $paymentData['orgnlMndtId']         = 'Original-Mandat-ID';
            $paymentData['orgnlCdtrSchmeId_nm'] = 'Creditor-Identifier Name';
            $paymentData['orgnlCdtrSchmeId_id'] = 'DE98AAA09999999999';
            $paymentData['orgnlDbtrAcct_iban']  = 'DE87200500001234567890';  // Original Debtor Account
            $paymentData['orgnlDbtrAgt']        = 'SMNDA';                   // only 'SMNDA' allowed if used
            $paymentData['orgnlDbtrAgt_bic']    = 'BELADEBEXXX';
        }

        return $paymentData;
    }

    public static function getCollectionData(int $version, bool $addBIC, bool $addOptionalData)
    {
        return self::isCreditTransfer($version)
            ? self::getCreditTransferData($addBIC, $addOptionalData)
            : self::getDirectDebitData($addBIC, $addOptionalData);
    }

    public static function getPaymentData(int $version, bool $addBIC, bool $addOptionalData, int $id = 1)
    {
        return self::isCreditTransfer($version)
            ? self::getCreditTransferPaymentData($addBIC, $addOptionalData, $id)
            : self::getDirectDebitPaymentData($addBIC, $addOptionalData, $id);
    }

    /**
     * @param int         $version Use SephpaCreditTransfer::SEPA_PAIN_001_* and SephpaDirectDebit::SEPA_PAIN_008_* constants.
     * @param bool        $addBIC
     * @param bool        $addOptionalData
     * @param bool        $checkAndSanitize
     * @param array       $orgId
     * @param string|null $initgPtyId
     * @param int         $numCollections
     * @param int         $numPayments
     * @return SephpaCreditTransfer|SephpaDirectDebit
     * @throws SephpaInputException
     */
    public static function getFile(int $version, bool $addBIC, bool $addOptionalData, bool $checkAndSanitize, array $orgId = [], ?string $initgPtyId = null, int $numCollections = 1, int $numPayments = 3)
    {
        $fileClass = self::isCreditTransfer($version)
            ? 'AbcAeffchen\Sephpa\SephpaCreditTransfer'
            : 'AbcAeffchen\Sephpa\SephpaDirectDebit';

        $file = new $fileClass('Initiator Name', 'MessageID-1234', $version, $orgId,
                               $initgPtyId, $checkAndSanitize);

        for($i = 0; $i < $numCollections; $i++)
        {
            $collection = $file->addCollection(self::getCollectionData($version, $addBIC, $addOptionalData));

            for($j = 0; $j < $numPayments; $j++)
            {
                $collection->addPayment(self::getPaymentData($version, $addBIC, $addOptionalData, $j));
            }
        }

        return $file;
    }
}