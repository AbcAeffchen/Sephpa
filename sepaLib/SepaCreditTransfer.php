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
 manages the transfers
*/
class SepaCreditTransfer extends SepaPaymentCollection
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
      @var $transferInfo saves the transfer information for the collection.
     */
    private $transferInfo;
    /**
      @var CCY string default currency
     */
    const CCY = 'EUR';
    
    
    /**
      calculates the sum of all payments in this collection
      @param transferInfo mixed[] needed keys: pmtInfId, dbtr, iban, bic; optional keys: ccy, btchBookg, ctgyPurp, reqdExctnDt, ultmtDebtr
     */
    public function __construct(array $transferInfo)
    {
       
        // allready checkt for needed keys in SepaXmlFile
        $this->transferInfo = $transferInfo;
       
    }
    
    /**
      calculates the sum of all payments in this collection
      @param $paymentInfo mixed[] needes keys: 'pmtId', 'instdAmt', 'iban', 'bic', 'cdtr'; optional keys: 'ultmtCdrt', 'purp', 'rmtInf'
      @return boolean
     */
    public function addPayment(array $paymentInfo)
    {
        $needed = array(
            'pmtId', 'instdAmt', 'iban', 'bic', 'cdtr'
        );
        
        foreach ($needed as $key) {
            if (!isset($paymentInfo[$key]))
                return false;
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
        
        $ccy = (isset($this->transferInfo['ccy']) && strlen($this->transferInfo['ccy']) == 3) ? strtoupper($this->transferInfo['ccy']) : self::CCY;
        
        $datetime = new DateTime();
		$reqdExctnDt = (isset($this->transferInfo['reqdExctnDt'])) ? $this->transferInfo['reqdExctnDt'] : $datetime->format('Y-m-d');
        
        $pmtInf->addChild('PmtInfId', $this->transferInfo['pmtInfId']);
        $pmtInf->addChild('PmtMtd', 'TRF');
        if(isset($this->transferInfo['btchBookg']) && (strcmp($this->transferInfo['btchBookg'],'false') == 0 || strcmp($this->transferInfo['btchBookg'],'true') == 0))
            $pmtInf->addChild('BtchBookg', $this->transferInfo['btchBookg']);
        $pmtInf->addChild('NbOfTxs', $this->getNumberOfTransactions());
        $pmtInf->addChild('CtrlSum', sprintf("%01.2f", $this->getCtrlSum()));
        
        $pmtTpInf = $pmtInf->addChild('PmtTpInf');
        $pmtTpInf->addChild('InstrPrty','NORM');
        $pmtTpInf->addChild('SvcLvl')->addChild('Cd','SEPA');
        if(isset($this->transferInfo['ctgyPurp']))
            $pmtTpInf->addChild('ctgyPurp')->addChild('Cd', $this->transferInfo['ctgyPurp']);
        
        $pmtInf->addChild('ReqdExctnDt', $reqdExctnDt);
        $pmtInf->addChild('Dbtr')->addChild('Nm', $this->shorten(70, $this->transferInfo['dbtr']));
        
        $dbtrAcct= $pmtInf->addChild('DbtrAcct');
        $dbtrAcct->addChild('Id')->addChild('IBAN', $this->transferInfo['iban']);
        $dbtrAcct->addChild('Ccy', $ccy);
        
        $pmtInf->addChild('DbtrAgt')->addChild('FinInstnId')->addChild('BIC', $this->transferInfo['bic']);
        
        if(isset($this->transferInfo['ultmtDbtr']))
            $pmtInf->addChild('UltmtDbtr')->addChild('Nm', $this->shorten(70, $this->transferInfo['ultmtDbtr']));
        
        $pmtInf->addChild('ChrgBr', 'SLEV');
        
        foreach($this->payments as $payment){
            $cdtTrfTxInf = $pmtInf->addChild('CdtTrfTxInf');
            $this->generatePaymentXml($cdtTrfTxInf, $payment, $ccy);
        }
    
    }
    
    /**
      generates the xml for a single payment
      @param $cdtTrfTxInf xml
      @param $payment mixed[] one of the payments in $this->payments
      @param $ccy string currency
      @return void
     */
    private function generatePaymentXml($cdtTrfTxInf, $payment, $ccy)
    {
        $cdtTrfTxInf->addChild('PmtId')->addChild('EndToEndId', $payment['pmtId']);
        $cdtTrfTxInf->addChild('Amt')->addChild('InstdAmt', $payment['instdAmt'])->addAttribute('Ccy', $ccy);
        $cdtTrfTxInf->addChild('CdtrAgt')->addChild('FinInstnId')->addChild('BIC', $payment['bic']);
        $cdtTrfTxInf->addChild('Cdtr')->addChild('Nm', $payment['cdtr']);
        $cdtTrfTxInf->addChild('CdtrAcct')->addChild('Id')->addChild('IBAN', $payment['iban']);
        
        if(isset($payment['ultmtCdtr']))
            $cdtTrfTxInf->addChild('UltmtCdtr')->addChild('Nm', $this->shorten(70, $payment['ultmtCdtr']));
        if(isset($payment['purp']))
            $cdtTrfTxInf->addChild('Purp')->addChild('Cd', $payment['purp']);
        if(isset($payment['rmtInf']))
            $cdtTrfTxInf->addChild('RmtInf')->addChild('Ustrd', $this->shorten(140, $payment['rmtInf']));
    }
    
    private function removeUmlauts($str)
    {
        $umlauts = array('Ä', 'ä', 'Ü', 'ü', 'Ö', 'ö', 'ß');
        $umlautReplacements = array('Ae', 'ae', 'Ue', 'ue', 'Oe', 'oe', 'ss');
        
        return str_replace($umlauts, $umlautReplacements, $str);
    }



}

?>