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

namespace AbcAeffchen\Sephpa\PaymentCollections;
use AbcAeffchen\SepaUtilities\SepaUtilities;

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

        parent::addPayment($paymentInfo);
    }

}