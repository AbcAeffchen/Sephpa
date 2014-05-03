<?php
/**
 * SEPA XML FILE GENERATOR
 *  
 * @license MIT License
 * @copyright © 2013 Alexander Schickedanz 
 * @link      http://abcaeffchen.net
 *
 * @author  Alexander Schickedanz <alex@abcaeffchen.net>
 */

require_once 'SepaPaymentCollection.php';
 
/**
 manages the debits
*/
class SepaDirectDebit extends SepaPaymentCollection
{
    
    /**
      @var $pmtInfId string the uniquie id of the collection
     */
    private $pmtInfId;
    /**
      @var $payments mixed[] saves all payments
     */
    private $payments = array();
    /**
      @var $debitInfo saves the transfer information for the collection.
     */
    private $debitInfo;
    /**
      @var CCY string default currency
     */
    const CCY = 'EUR';
    
    
    /**
      calculates the sum of all payments in this collection
      @param $debitInfo mixed[] needed keys: 'pmtInfId', 'lclInstrm', 'seqTp', 'cdtr', 'iban', 'bic', 'ci'; optional keys: 'ccy', 'btchBookg', 'ctgyPurp', 'ultmtCdtr', 'reqdColltnDt'
     */
    public function __construct(array $debitInfo)
    {
       
        // allready checkt for needed keys in SepaXmlFile
        $this->debitInfo = $debitInfo;
       
    }
    
    /**
      calculates the sum of all payments in this collection
      @param $paymentInfo mixed[] needes keys: 'pmtId', 'instdAmt', 'mndtId', 'dtOfSgntr', 'bic', 'dbtr', 'iban'; optional keys: 'amdmntInd', 'orgnlMndtId', 'orgnlCdtrSchmeId_nm', 'orgnlCdtrSchmeId_id', 'orgnlDbtrAcct_iban', 'orgnlDbtrAgt', 'elctrncSgntr', 'ultmtDbtr', 'purp', 'rmtInf'
      @return boolean
     */
    public function addPayment(array $paymentInfo)
    {
        $needed = array(
            'pmtId', 'instdAmt', 'mndtId', 'dtOfSgntr', 'bic', 'dbtr', 'iban'  // TODO: Check this
        );
        
        foreach ($needed as $key) {
            if (!isset($paymentInfo[$key]))
            return false;
        }
        
        if(isset($paymentInfo['amdmntInd']) && strcmp($paymentInfo['amdmntInd'], 'true') == 0){
        
            $alsoNeedsAtLeastOne = array(
                'orgnlMndtId', 'orgnlCdtrSchmeId_nm', 'orgnlCdtrSchmeId_id', 'orgnlDbtrAcct_iban', 'orgnlDbtrAgt'
            );
            
            $counter = 0;
            
            foreach ($alsoNeedsAtLeastOne as $key) {
                if (isset($paymentInfo[$key]))
                    $counter++;
            }
            
            if($counter == 0)
                return false;

        }else{
            $paymentInfo['amdmntInd'] = 'false';
        }
        
        
        $this->payments[] = array_map(array('self','removeUmlauts'), $paymentInfo);
        
        return true;
        
    }
    
    /**
      calculates the sum of all payments in this collection
      @return float
     */
    public function getCtrlSum()
    {
        $sum = 0;
        foreach($this->payments as $payment){
            $sum += $payment['instdAmt'];
        }
        return $sum;
    }
    
    /**
      counts the payments in this collection
      @return int
     */
    public function getNumberOfTransactions()
    {
        return count($this->payments);
    }
    
    /**
      generates the xml for the collection using generatePaymentXml
      @param pmtInf xml the PmtInf-Child of the xml object
      @return void
     */
    public function generateCollectionXml($pmtInf)
    {
        
        $ccy = (isset($this->debitInfo['ccy']) && strlen($this->debitInfo['ccy']) == 3) ? strtoupper($this->debitInfo['ccy']) : self::CCY;
        
        $datetime = new DateTime();
		$reqdColltnDt = (isset($this->debitInfo['reqdColltnDt'])) ? $this->debitInfo['reqdColltnDt'] : $datetime->format('Y-m-d');
        
        $pmtInf->addChild('PmtInfId', $this->debitInfo['pmtInfId']);
        $pmtInf->addChild('PmtMtd', 'DD');
        if(isset($this->debitInfo['btchBookg']) && (strcmp($this->debitInfo['btchBookg'],'false') == 0 || strcmp($this->debitInfo['btchBookg'],'true') == 0))
            $pmtInf->addChild('BtchBookg', $this->debitInfo['btchBookg']);
        $pmtInf->addChild('NbOfTxs', $this->getNumberOfTransactions());
        $pmtInf->addChild('CtrlSum', sprintf("%01.2f", $this->getCtrlSum()));
        
        $pmtTpInf = $pmtInf->addChild('PmtTpInf');
        $pmtTpInf->addChild('SvcLvl')->addChild('Cd', 'SEPA');
        $pmtTpInf->addChild('LclInstrm')->addChild('Cd', strtoupper($this->debitInfo['lclInstrm']));
        $pmtTpInf->addChild('SeqTp', $this->debitInfo['seqTp']);
        if(isset($this->debitInfo['ctgyPurp']))
            $pmtTpInf->addChild('CtgyPurp')->addChild('Cd', $this->debitInfo['ctgyPurp']);
        
        $pmtInf->addChild('ReqdColltnDt', $reqdColltnDt);
        $pmtInf->addChild('Cdtr')->addChild('Nm', $this->debitInfo['cdtr']);
        
        $cdtrAcct = $pmtInf->addChild('CdtrAcct');
        $cdtrAcct->addChild('Id')->addChild('IBAN', $this->debitInfo['iban']);
        $cdtrAcct->addChild('Ccy', $ccy);
        
        $pmtInf->addChild('CdtrAgt')->addChild('FinInstnId')->addChild('BIC', $this->debitInfo['bic']);
        
        if(isset($this->debitInfo['ultmtCdtr']))
            $pmtInf->addChild('UltmtCdtr')->addChild('Nm', $this->shorten(70, $this->debitInfo['ultmtCdtr']));
        
        $pmtInf->addChild('ChrgBr', 'SLEV');
        
        $ci = $pmtInf->addChild('CdtrSchmeId')->addChild('Id')->addChild('PrvtId')->addChild('Othr');
        $ci->addChild('Id', $this->debitInfo['ci']);
        $ci->addChild('SchmeNm')->addChild('Prtry', 'SEPA');
        
        foreach($this->payments as $payment){
            $drctDbtTxInf = $pmtInf->addChild('DrctDbtTxInf');
            $this->generatePaymentXml($drctDbtTxInf, $payment, $ccy);
        }
    
    }
    
    /**
      generates the xml for a single payment
      @param $drctDbtTxInf xml
      @param $payment mixed[] one of the payments in $this->payments
      @param $ccy string currency
      @return void
     */
    private function generatePaymentXml($drctDbtTxInf, $payment, $ccy)
    {
        $drctDbtTxInf->addChild('PmtId')->addChild('EndToEndId', $payment['pmtId']);
        $drctDbtTxInf->addChild('InstdAmt', sprintf("%01.2f", $payment['instdAmt']))->addAttribute('Ccy', $ccy);
        
        $mndtRltdInf = $drctDbtTxInf->addChild('DrctDbtTx')->addChild('MndtRltdInf');
        $mndtRltdInf->addChild('MndtId', $payment['mndtId']);
        $mndtRltdInf->addChild('DtOfSgntr', $payment['dtOfSgntr']);
        $mndtRltdInf->addChild('AmdmntInd', $payment['amdmntInd']);        
        if(strcmp($payment['amdmntInd'], 'true') == 0){
            $amdmntInd = $mndtRltdInf->addChild('AmdmntInfDtls');
            if(isset($payment['orgnlMndtId']))
                $amdmntInd->addChild('OrgnlMndtId', $payment['orgnlMndtId']);
            if(isset($payment['orgnlCdtrSchmeId_Nm']) || isset($payment['orgnlCdtrSchmeId_Nm'])){
                $orgnlCdtrSchmeId = $amdmntInd->addChild('OrgnlCdtrSchmeId');
                if(isset($payment['orgnlCdtrSchmeId_Nm']))
                    $orgnlCdtrSchmeId->addChild('Nm', $this->shorten(70, $payment['orgnlCdtrSchmeId_Nm']));
                if(isset($payment['orgnlCdtrSchmeId_Id'])){
                    $othr = $orgnlCdtrSchmeId->addChild('Id')->addChild('PrvtId')->addChild('Othr');
                    $othr->addChild('Id', $payment['orgnlCdtrSchmeId_Id']);
                    $othr->addChild('SchmeNm')->addChild('Prtry', 'SEPA');
                }
            }
            if(isset($payment['orgnlDbtrAcct_iban']))
                $amdmntInd->addChild('OrgnlDbtrAcct')->addChild('Id')->addChild('IBAN', $payment['orgnlDbtrAcct_iban']);
            if(isset($payment['orgnlDbtrAgt']))
                $amdmntInd->addChild('OrgnlDbtrAgt')->addChild('FinInstnId')->addChild('Othr')->addChild('Id', 'SMNDA');
        }
        if(isset($payment['elctrncSgntr']))
            $mndtRltdInf->addChild('ElctrncSgntr', $payment['elctrncSgntr']);
        
        $drctDbtTxInf->addChild('DbtrAgt')->addChild('FinInstnId')->addChild('BIC', $payment['bic']);
        $drctDbtTxInf->addChild('Dbtr')->addChild('Nm', $this->shorten(70, $payment['dbtr']));
        $drctDbtTxInf->addChild('DbtrAcct')->addChild('Id')->addChild('IBAN', $payment['iban']);
        if(isset($payment['ultmtDbtr']))
            $drctDbtTxInf->addChild('UltmtDbtr')->addChild('Nm', $payment['ultmtDbtr']);
        if(isset($payment['purp']))
            $drctDbtTxInf->addChild('Purp')->addChild('Cd', $payment['purp']);
        if(isset($payment['rmtInf']))
            $drctDbtTxInf->addChild('RmtInf')->addChild('Ustrd', $this->shorten(140, $payment['rmtInf']));
    }

    private function removeUmlauts($str)
    {
        $umlauts = array('Ä', 'ä', 'Ü', 'ü', 'Ö', 'ö', 'ß');
        $umlautReplacements = array('Ae', 'ae', 'Ue', 'ue', 'Oe', 'oe', 'ss');
        
        return str_replace($umlauts, $umlautReplacements, $str);
    }

}

?>