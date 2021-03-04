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

namespace AbcAeffchen\Sephpa\PaymentCollections;
use AbcAeffchen\SepaUtilities\SepaUtilities;
use AbcAeffchen\Sephpa\SephpaInputException;

class SepaDirectDebit00800102Austrian003 extends SepaDirectDebit00800102
{
    /**
     * @type int VERSION The SEPA file version of this collection
     */
    const VERSION = SepaUtilities::SEPA_PAIN_008_001_02_AUSTRIAN_003;

    const NO_BIC_PROVIDED = 'NOTAVAIL';

    public function __construct(array $debitInfo, $checkAndSanitize = true, $flags = 0)
    {
        if(empty($debitInfo['bic']))
            $debitInfo['bic'] = self::NO_BIC_PROVIDED;

        parent::__construct($debitInfo, $checkAndSanitize, $flags);
    }

    public function addPayment(array $paymentInfo)
    {
        if(empty($paymentInfo['bic']))
            $paymentInfo['bic'] = self::NO_BIC_PROVIDED;

        if(isset($paymentInfo['orgnlDbtrAgt_bic']))
            unset($paymentInfo['orgnlDbtrAgt_bic']);

        if( SepaDirectDebit00800102Austrian003::class === get_called_class()
            && !empty( $paymentInfo['amdmntInd'] ) && $paymentInfo['amdmntInd'] === 'true' )
        {

            if( SepaUtilities::containsNotAnyKey($paymentInfo, ['orgnlMndtId',
                                                                'orgnlCdtrSchmeId_nm',
                                                                'orgnlCdtrSchmeId_id',
                                                                'orgnlDbtrAcct_iban',
                                                                'orgnlDbtrAgt'])
            )
                throw new SephpaInputException('You set \'amdmntInd\' to \'true\', so you have to set also at least one of the following inputs: \'orgnlMndtId\', \'orgnlCdtrSchmeId_nm\', \'orgnlCdtrSchmeId_id\', \'orgnlDbtrAcct_iban\', \'orgnlDbtrAgt\'.');

            // It is not clear if this holds for this version, so the check is deactivated.
            // if( !empty( $paymentInfo['orgnlDbtrAgt'] ) && $paymentInfo['orgnlDbtrAgt'] === 'SMNDA' && $this->debitInfo['seqTp'] !== SepaUtilities::SEQUENCE_TYPE_FIRST )
            //     throw new SephpaInputException('You set \'amdmntInd\' to \'true\' and \'orgnlDbtrAgt\' to \'SMNDA\', \'seqTp\' has to be \'' . SepaUtilities::SEQUENCE_TYPE_FIRST . '\'.');
        }

        parent::addPayment($paymentInfo);
    }

}