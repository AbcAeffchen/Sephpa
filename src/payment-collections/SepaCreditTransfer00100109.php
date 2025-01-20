<?php
/*
 * Sephpa
 *
 * @license   GNU LGPL v3.0 - For details have a look at the LICENSE file
 * @copyright ©2025 Alexander Schickedanz
 * @link      https://github.com/AbcAeffchen/Sephpa
 *
 * @author  Alexander Schickedanz <abcaeffchen@gmail.com>
 */

namespace AbcAeffchen\Sephpa\PaymentCollections;

use AbcAeffchen\SepaUtilities\SepaUtilities;
use AbcAeffchen\Sephpa\SephpaInputException;

/**
 * Manages credit transfers
 */
class SepaCreditTransfer00100109 extends SepaCreditTransferCollection
{
    /**
     * @type int VERSION The SEPA file version of this collection
     */
    const VERSION = SepaUtilities::SEPA_PAIN_001_001_09;

    /**
     * @param mixed[] $transferInfo     needed keys: 'pmtInfId', 'dbtr', 'iban';
     *                                  optional keys: 'ccy', 'btchBookg', 'ctgyPurp', 'reqdExctnDt',
     *                                  'ultmtDbtr', 'bic', 'pstlAdr'
     * @param bool    $checkAndSanitize All inputs will be checked and sanitized before creating the
     *                                  collection. If you check the inputs yourself you can set this
     *                                  to false.
     * @param int     $flags            The flags used for sanitizing
     * @throws SephpaInputException
     */
    public function __construct(array $transferInfo, $checkAndSanitize = true, $flags = 0)
    {
        $this->today            = (int) date('Ymd');
        $this->checkAndSanitize = $checkAndSanitize;
        $this->sanitizeFlags    = $flags;

        if(!SepaUtilities::checkRequiredCollectionKeys($transferInfo, self::VERSION))
            throw new SephpaInputException('The values of \'pmtInfId\', \'dbtr\', \'iban\' must not be empty.');

        // backward compatibility / deprecated
        if(isset($transferInfo['dbtrPstlAdr']) && !isset($transferInfo['pstlAdr']))
        {
            $transferInfo['pstlAdr'] = $transferInfo['dbtrPstlAdr'];
        }

        if($this->checkAndSanitize)
        {
            // All fields contain valid information?
            $checkResult = SepaUtilities::checkAndSanitizeAll($transferInfo, $this->sanitizeFlags, ['version' => self::VERSION, 'allowEmptyBic' => true]);

            if($checkResult !== true)
                throw new SephpaInputException('The values of ' . implode(', ', $checkResult) . ' are invalid.');

            $this->dbtrIban = $transferInfo['iban'];

            if(!empty($transferInfo['bic']) && !SepaUtilities::crossCheckIbanBic($transferInfo['iban'], $transferInfo['bic']))
                throw new SephpaInputException('IBAN and BIC do not belong to each other.');
        }

        $this->transferInfo = $transferInfo;
    }

    /**
     * Adds a payment to the payment collection
     *
     * @param array $paymentInfo   needed keys: 'pmtId', 'instdAmt', 'iban', 'bic', 'cdtr';
     *                             optional keys: 'ultmtCdrt', 'purp', 'rmtInf', 'ultmtDbtr', 'ultmtDbtrId'
     * @return void
     * @throws SephpaInputException
     */
    public function addPayment(array $paymentInfo)
    {
        if($this->checkAndSanitize)
        {
            if(!SepaUtilities::checkRequiredPaymentKeys($paymentInfo, self::VERSION))
                throw new SephpaInputException('One of the required inputs \'pmtId\', \'instdAmt\', \'iban\', \'cdtr\' is missing.');

            $bicRequired = (!SepaUtilities::isEEATransaction($this->dbtrIban, $paymentInfo['iban']));

            $checkResult = SepaUtilities::checkAndSanitizeAll($paymentInfo, $this->sanitizeFlags,
                                                              ['version' => self::VERSION, 'allowEmptyBic' => !$bicRequired]);

            if($checkResult !== true)
                throw new SephpaInputException('The values of ' . implode(', ', $checkResult) . ' are invalid.');

            // IBAN and BIC can belong to each other?
            if(!empty($paymentInfo['bic']) && !SepaUtilities::crossCheckIbanBic($paymentInfo['iban'], $paymentInfo['bic']))
                throw new SephpaInputException('IBAN and BIC do not belong to each other.');
        }

        $this->payments[] = $paymentInfo;
    }

    /**
     * Generates the xml for the collection using generatePaymentXml
     * @param \SimpleXMLElement $pmtInf The PmtInf-Child of the xml object
     * @return void
     */
    public function generateCollectionXml(\SimpleXMLElement $pmtInf)
    {
        $ccy = (empty($this->transferInfo['ccy']))
            ? self::CCY
            : $this->transferInfo['ccy'];

        $datetime       = new \DateTime();
        $reqdExctnType  = isset($this->transferInfo['reqdExctnDtTm']) ? 'DtTm' : 'Dt';
        $reqdExctnValue = $this->transferInfo['reqdExctnDtTm'] ?? $datetime->format('Y-m-d') ?? $this->transferInfo['reqdExctnDt'];

        $pmtInf->addChild('PmtInfId', $this->transferInfo['pmtInfId']);
        $pmtInf->addChild('PmtMtd', 'TRF');
        if(!empty($this->transferInfo['btchBookg']))
            $pmtInf->addChild('BtchBookg', $this->transferInfo['btchBookg']);
        $pmtInf->addChild('NbOfTxs', $this->getNumberOfTransactions());
        $pmtInf->addChild('CtrlSum', sprintf("%01.2F", $this->getCtrlSum()));

        $pmtTpInf = $pmtInf->addChild('PmtTpInf');
        $pmtTpInf->addChild('InstrPrty', 'NORM');
        $pmtTpInf->addChild('SvcLvl')->addChild('Cd', 'SEPA');
        if(!empty($this->transferInfo['ctgyPurp']))
            $pmtTpInf->addChild('CtgyPurp')->addChild('Cd', $this->transferInfo['ctgyPurp']);

        $pmtInf->addChild('ReqdExctnDt')->addChild($reqdExctnType, $reqdExctnValue);
        $pmtInf->addChild('Dbtr')->addChild('Nm', $this->transferInfo['dbtr']);

        if(isset($this->transferInfo['pstlAdr']))
            $this->writePstlAdr($this->transferInfo['pstlAdr'], $pmtInf->Dbtr);

        $dbtrAcct = $pmtInf->addChild('DbtrAcct');
        $dbtrAcct->addChild('Id')->addChild('IBAN', $this->transferInfo['iban']);
        $dbtrAcct->addChild('Ccy', $ccy);

        if(!empty($this->transferInfo['bic']))
            $pmtInf->addChild('DbtrAgt')->addChild('FinInstnId')
                   ->addChild('BICFI', $this->transferInfo['bic']);
        else
            $pmtInf->addChild('DbtrAgt')->addChild('FinInstnId')->addChild('Othr')
                   ->addChild('Id', 'NOTPROVIDED');

        if(isset($this->transferInfo['ultmtDbtr']))
            $pmtInf->addChild('UltmtDbtr')->addChild('Nm', $this->transferInfo['ultmtDbtr']);

        $pmtInf->addChild('ChrgBr', 'SLEV');

        foreach($this->payments as $payment)
        {
            $cdtTrfTxInf = $pmtInf->addChild('CdtTrfTxInf');
            $this->generatePaymentXml($cdtTrfTxInf, $payment, $ccy);
        }
    }

    /**
     * generates the xml for a single payment
     * @param \SimpleXMLElement $cdtTrfTxInf
     * @param mixed[]           $payment one of the payments in $this->payments
     * @param string            $ccy     currency
     * @return void
     */
    private function generatePaymentXml(\SimpleXMLElement $cdtTrfTxInf, $payment, $ccy)
    {
        $cdtTrfTxInf->addChild('PmtId')->addChild('EndToEndId', $payment['pmtId']);
        $cdtTrfTxInf->addChild('Amt')->addChild('InstdAmt', sprintf("%01.2F", $payment['instdAmt']))
                    ->addAttribute('Ccy', $ccy);

        if(isset($payment['ultmtDbtr']) || isset($payment['ultmtDbtrId']))
        {
            $cdtTrfTxInf->addChild('UltmtDbtr');

            if(isset($payment['ultmtDbtr']))
                $cdtTrfTxInf->UltmtDbtr->addChild('Nm', $payment['ultmtDbtr']);

            if(isset($payment['ultmtDbtrId']))
                $cdtTrfTxInf->UltmtDbtr->addChild('Id')->addChild('OrgId')->addChild('Othr')->addChild('Id', $payment['ultmtDbtrId']);
        }

        if(!empty($payment['bic']))
            $cdtTrfTxInf->addChild('CdtrAgt')->addChild('FinInstnId')
                        ->addChild('BICFI', $payment['bic']);

        $cdtTrfTxInf->addChild('Cdtr')->addChild('Nm', $payment['cdtr']);

        if(isset($payment['pstlAdr']))
            $this->writePstlAdr($payment['pstlAdr'], $cdtTrfTxInf->Cdtr);

        $cdtTrfTxInf->addChild('CdtrAcct')->addChild('Id')->addChild('IBAN', $payment['iban']);

        if(isset($payment['ultmtCdtr']))
            $cdtTrfTxInf->addChild('UltmtCdtr')->addChild('Nm', $payment['ultmtCdtr']);
        if(isset($payment['purp']))
            $cdtTrfTxInf->addChild('Purp')->addChild('Cd', $payment['purp']);
        if(isset($payment['rmtInf']))
            $cdtTrfTxInf->addChild('RmtInf')->addChild('Ustrd', $payment['rmtInf']);
    }

    private function writePstlAdr(array $data, \SimpleXMLElement $xmlObj)
    {
        $pstlAdr = $xmlObj->addChild('PstlAdr');

        foreach(['Dept',
                 'SubDept',
                 'StrtNm',
                 'BldgNb',
                 'BldgNm',
                 'Flr',
                 'PstBx',
                 'Room',
                 'PstCd',
                 'TwnNm',
                 'TwnLctnNm',
                 'DstrctNm',
                 'CtrySubDvsn',
                 'Ctry'] as $key)
        {
            if(isset($data[lcfirst($key)]))
                $pstlAdr->addChild($key, $data[lcfirst($key)]);
        }
    }

}
