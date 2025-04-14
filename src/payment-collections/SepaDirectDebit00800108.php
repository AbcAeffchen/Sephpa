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
 * Manages direct debits
 */
class SepaDirectDebit00800108 extends SepaDirectDebitCollection
{
    /**
     * @type int VERSION The SEPA file version of this collection
     */
    const VERSION = SepaUtilities::SEPA_PAIN_008_001_08;

    /**
     * @param array $debitInfo        Needed keys: 'pmtInfId', 'lclInstrm', 'seqTp', 'cdtr',
     *                                'iban', 'bic', 'ci'; optional keys: 'ccy', 'btchBookg',
     *                                'ctgyPurp', 'ultmtCdtr', 'reqdColltnDt', 'pstlAdr'
     * @param bool  $checkAndSanitize All inputs will be checked and sanitized before creating
     *                                the collection. If you check the inputs yourself you can
     *                                set this to false.
     * @param int   $flags            The flags used for sanitizing
     * @throws SephpaInputException
     */
    public function __construct(array $debitInfo, $checkAndSanitize = true, $flags = 0)
    {
        $this->today = (int) date('Ymd');
        $this->checkAndSanitize = $checkAndSanitize;
        $this->sanitizeFlags = $flags;

        if(!SepaUtilities::checkRequiredCollectionKeys($debitInfo, self::VERSION) )
            throw new SephpaInputException('One of the required inputs \'pmtInfId\', \'lclInstrm\', \'seqTp\', \'cdtr\', \'iban\', \'ci\' is missing.');

        if($this->checkAndSanitize)
        {
            $checkResult = SepaUtilities::checkAndSanitizeAll($debitInfo, $this->sanitizeFlags, ['version' => self::VERSION]);

            if($checkResult !== true)
                throw new SephpaInputException('The values of ' . implode(', ', $checkResult) . ' are invalid.');

            // IBAN and BIC can belong to each other?
            if(!empty($debitInfo['bic']) && !SepaUtilities::crossCheckIbanBic($debitInfo['iban'], $debitInfo['bic']))
                throw new SephpaInputException('IBAN and BIC do not belong to each other.');
        }

        $this->debitInfo = $debitInfo;
    }

    /**
     * calculates the sum of all payments in this collection
     *
     * @param array $paymentInfo Needed keys: 'pmtId', 'instdAmt', 'mndtId', 'dtOfSgntr', 'bic',
     *                           'dbtr', 'iban';
     *                           Optional keys: 'amdmntInd', 'orgnlMndtId', 'orgnlCdtrSchmeId_nm',
     *                           'orgnlCdtrSchmeId_id', 'orgnlDbtrAcct_iban', 'orgnlDbtrAgt_bic',
     *                           'elctrncSgntr', 'ultmtDbtr', 'purp', 'rmtInf', 'pstlAdr'
     * @throws SephpaInputException
     * @return void
     */
    public function addPayment(array $paymentInfo)
    {
        if($this->checkAndSanitize)
        {
            if(!SepaUtilities::checkRequiredPaymentKeys($paymentInfo, self::VERSION) )
                throw new SephpaInputException('One of the required inputs \'pmtId\', \'instdAmt\', \'mndtId\', \'dtOfSgntr\', \'dbtr\', \'iban\' is missing.');

            $bicRequired = (!SepaUtilities::isEEATransaction($this->debitInfo['iban'], $paymentInfo['iban']));

            $checkResult = SepaUtilities::checkAndSanitizeAll($paymentInfo, $this->sanitizeFlags,
                                                              ['allowEmptyBic' => !$bicRequired, 'version' => self::VERSION]);

            if($checkResult !== true)
                throw new SephpaInputException('The values of ' . implode(', ', $checkResult) . ' are invalid.');

            if( SepaDirectDebit00800108::class === get_called_class()
                && !empty( $paymentInfo['amdmntInd'] ) && $paymentInfo['amdmntInd'] === 'true' )
            {
                if( SepaUtilities::containsNotAnyKey($paymentInfo, ['orgnlMndtId',
                                                                    'orgnlCdtrSchmeId_nm',
                                                                    'orgnlCdtrSchmeId_id',
                                                                    'orgnlDbtrAcct_iban',
                                                                    'orgnlDbtrAgt_bic'])
                )
                    throw new SephpaInputException('You set \'amdmntInd\' to \'true\', so you have to set also at least one of the following inputs: \'orgnlMndtId\', \'orgnlCdtrSchmeId_nm\', \'orgnlCdtrSchmeId_id\', \'orgnlDbtrAcct_iban\', \'orgnlDbtrAgt\'.');

                if( !empty( $paymentInfo['orgnlDbtrAgt'] ) && $paymentInfo['orgnlDbtrAgt'] === 'SMNDA' && $this->debitInfo['seqTp'] !== SepaUtilities::SEQUENCE_TYPE_RECURRING && $this->debitInfo['seqTp'] !== SepaUtilities::SEQUENCE_TYPE_FIRST)
                    throw new SephpaInputException('You set \'amdmntInd\' to \'true\' and \'orgnlDbtrAgt\' to \'SMNDA\', \'seqTp\' has to be \'' . SepaUtilities::SEQUENCE_TYPE_FIRST . '\' or \'' . SepaUtilities::SEQUENCE_TYPE_RECURRING . '\'.');

            }

            // IBAN and BIC can belong to each other?
            if(!empty($paymentInfo['bic']) && !SepaUtilities::crossCheckIbanBic($paymentInfo['iban'],$paymentInfo['bic']))
                throw new SephpaInputException('IBAN and BIC do not belong to each other.');
        }

        // adjustments
        // local instrument COR1 got included into CORE.
        if($this->debitInfo['lclInstrm'] === SepaUtilities::LOCAL_INSTRUMENT_CORE_DIRECT_DEBIT_D_1)
            $this->debitInfo['lclInstrm'] = SepaUtilities::LOCAL_INSTRUMENT_CORE_DIRECT_DEBIT;

        // it is no longer required to use FRST as sequence type for the first direct debit
        // instead it is recommended to use RCUR
        if($this->debitInfo['seqTp'] === SepaUtilities::SEQUENCE_TYPE_FIRST)
            $this->debitInfo['seqTp'] = SepaUtilities::SEQUENCE_TYPE_RECURRING;

        $this->payments[] = $paymentInfo;
    }

    /**
     * Generates the xml for the collection using generatePaymentXml
     *
     * @param \SimpleXMLElement $pmtInf The PmtInf-Child of the xml object
     * @return void
     */
    public function generateCollectionXml(\SimpleXMLElement $pmtInf)
    {
        $ccy = empty( $this->debitInfo['ccy'] ) ? self::CCY : $this->debitInfo['ccy'];

        $datetime     = new \DateTime();
        $reqdColltnDt = ( !empty( $this->debitInfo['reqdColltnDt'] ) )
            ? $this->debitInfo['reqdColltnDt'] : $datetime->format('Y-m-d');

        $pmtInf->addChild('PmtInfId', $this->debitInfo['pmtInfId']);
        $pmtInf->addChild('PmtMtd', 'DD');
        if( !empty( $this->debitInfo['btchBookg'] ) )
            $pmtInf->addChild('BtchBookg', $this->debitInfo['btchBookg']);
        $pmtInf->addChild('NbOfTxs', $this->getNumberOfTransactions());
        $pmtInf->addChild('CtrlSum', sprintf('%01.2F', $this->getCtrlSum()));

        $pmtTpInf = $pmtInf->addChild('PmtTpInf');
        $pmtTpInf->addChild('SvcLvl')->addChild('Cd', 'SEPA');
        $pmtTpInf->addChild('LclInstrm')->addChild('Cd', $this->debitInfo['lclInstrm']);
        $pmtTpInf->addChild('SeqTp', $this->debitInfo['seqTp']);
        if( !empty( $this->debitInfo['ctgyPurp'] ) )
            $pmtTpInf->addChild('CtgyPurp')->addChild('Cd', $this->debitInfo['ctgyPurp']);

        $pmtInf->addChild('ReqdColltnDt', $reqdColltnDt);
        $pmtInf->addChild('Cdtr')->addChild('Nm', $this->debitInfo['cdtr']);

        if(isset($this->debitInfo['pstlAdr']))
            $this->writePstlAdr($this->debitInfo['pstlAdr'], $pmtInf->Cdtr);

        $cdtrAcct = $pmtInf->addChild('CdtrAcct');
        $cdtrAcct->addChild('Id')->addChild('IBAN', $this->debitInfo['iban']);
        $cdtrAcct->addChild('Ccy', $ccy);

        if( !empty( $this->debitInfo['bic'] ) )
            $pmtInf->addChild('CdtrAgt')->addChild('FinInstnId')
                   ->addChild('BICFI', $this->debitInfo['bic']);
        else
            $pmtInf->addChild('CdtrAgt')->addChild('FinInstnId')->addChild('Othr')
                   ->addChild('Id', 'NOTPROVIDED');

        if( !empty( $this->debitInfo['ultmtCdtr'] ) )
            $pmtInf->addChild('UltmtCdtr')->addChild('Nm', $this->debitInfo['ultmtCdtr']);

        $pmtInf->addChild('ChrgBr', 'SLEV');

        $ci = $pmtInf->addChild('CdtrSchmeId')->addChild('Id')->addChild('PrvtId')
                     ->addChild('Othr');
        $ci->addChild('Id', $this->debitInfo['ci']);
        $ci->addChild('SchmeNm')->addChild('Prtry', 'SEPA');

        foreach($this->payments as $payment)
        {
            $drctDbtTxInf = $pmtInf->addChild('DrctDbtTxInf');
            $this->generatePaymentXml($drctDbtTxInf, $payment, $ccy);
        }
    }

    /**
     * Generates the xml for a single payment
     *
     * @param \SimpleXMLElement $drctDbtTxInf
     * @param array             $payment One of the payments in $this->payments
     * @param string            $ccy     currency
     * @return void
     */
    private function generatePaymentXml(\SimpleXMLElement $drctDbtTxInf, $payment, $ccy)
    {
        $drctDbtTxInf->addChild('PmtId')->addChild('EndToEndId', $payment['pmtId']);
        $drctDbtTxInf->addChild('InstdAmt', sprintf('%01.2F', $payment['instdAmt']))
                     ->addAttribute('Ccy', $ccy);

        $mndtRltdInf = $drctDbtTxInf->addChild('DrctDbtTx')->addChild('MndtRltdInf');
        $mndtRltdInf->addChild('MndtId', $payment['mndtId']);
        $mndtRltdInf->addChild('DtOfSgntr', $payment['dtOfSgntr']);
        if(!empty($payment['amdmntInd']))
        {
            $mndtRltdInf->addChild('AmdmntInd', $payment['amdmntInd']);
            if( $payment['amdmntInd'] === 'true' )
            {
                $amdmntInd = $mndtRltdInf->addChild('AmdmntInfDtls');
                if( !empty( $payment['orgnlMndtId'] ) )
                    $amdmntInd->addChild('OrgnlMndtId', $payment['orgnlMndtId']);
                if( !empty( $payment['orgnlCdtrSchmeId_nm'] ) || !empty( $payment['orgnlCdtrSchmeId_id'] ) )
                {
                    $orgnlCdtrSchmeId = $amdmntInd->addChild('OrgnlCdtrSchmeId');
                    if( !empty( $payment['orgnlCdtrSchmeId_nm'] ) )
                        $orgnlCdtrSchmeId->addChild('Nm', $payment['orgnlCdtrSchmeId_nm']);
                    if( !empty( $payment['orgnlCdtrSchmeId_id'] ) )
                    {
                        $othr = $orgnlCdtrSchmeId->addChild('Id')->addChild('PrvtId')
                                                 ->addChild('Othr');
                        $othr->addChild('Id', $payment['orgnlCdtrSchmeId_id']);
                        $othr->addChild('SchmeNm')->addChild('Prtry', 'SEPA');
                    }
                }
                if( !empty( $payment['orgnlDbtrAcct_iban'] ) )
                    $amdmntInd->addChild('OrgnlDbtrAcct')->addChild('Id')
                              ->addChild('IBAN', $payment['orgnlDbtrAcct_iban']);
                else
                    $amdmntInd->addChild('OrgnlDbtrAcct')->addChild('Othr')
                              ->addChild('Id', 'SMNDA');

                if( !empty( $payment['orgnlDbtrAgt_bic'] ) )
                    $amdmntInd->addChild('OrgnlDbtrAgt')->addChild('FinInstnId')
                              ->addChild('BICFI', $payment['orgnlDbtrAgt_bic']);
            }
        }
        if( !empty( $payment['elctrncSgntr'] ) )
            $mndtRltdInf->addChild('ElctrncSgntr', $payment['elctrncSgntr']);

        if( !empty( $payment['bic'] ) )
            $drctDbtTxInf->addChild('DbtrAgt')->addChild('FinInstnId')
                   ->addChild('BICFI', $payment['bic']);
        else
            $drctDbtTxInf->addChild('DbtrAgt')->addChild('FinInstnId')->addChild('Othr')
                   ->addChild('Id', 'NOTPROVIDED');

        $drctDbtTxInf->addChild('Dbtr')->addChild('Nm', $payment['dbtr']);

        if(isset($payment['pstlAdr']))
            $this->writePstlAdr($payment['pstlAdr'], $drctDbtTxInf->Dbtr);

        $drctDbtTxInf->addChild('DbtrAcct')->addChild('Id')
                     ->addChild('IBAN', $payment['iban']);
        if( !empty( $payment['ultmtDbtr'] ) )
            $drctDbtTxInf->addChild('UltmtDbtr')->addChild('Nm', $payment['ultmtDbtr']);
        if( !empty( $payment['purp'] ) )
            $drctDbtTxInf->addChild('Purp')->addChild('Cd', $payment['purp']);
        if( !empty( $payment['rmtInf'] ) )
            $drctDbtTxInf->addChild('RmtInf')->addChild('Ustrd', $payment['rmtInf']);
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
