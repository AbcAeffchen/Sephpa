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

require_once 'sepaLib/SepaCreditTransfer.php';
require_once 'sepaLib/SepaDirectDebit.php';

/**
 Base class for both credit transfer and direkt debit
*/
class SepaXmlFile
{
    
    /**
      @var $xml SimpleXMLElement xml object
     */
    private $xml;
    /**
      @var $type sting saves the type of the object 'CT' (transfer) or 'DD' (debit)
     */
    private $type;
    /**
      @var $initgPty string either 'CstmrCdtTrfInitn' or 'CstmrDrctDbtInitn'
     */
    private $xmlType;
    /**
      @var $initgPty string Name of the party that initiates the transfer
     */
    private $initgPty;
    /**
      @var $msgId identify the Sepafile (unique id for all files)
     */
    private $msgId;
    /**
      @var $paymentCollections SepaPaymentCollection[] saves all payment objects
     */
    private $paymentCollections = array();
    /**
      @var INITIAL_STRING_CT initial sting for credit transfer (Überweisung)
     */    
    const INITIAL_STRING_CT = '<?xml version="1.0" encoding="UTF-8"?><Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.002.03" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:iso:std:iso:20022:tech:xsd:pain.001.002.03 pain.001.002.03.xsd"></Document>';
    /**
      @var INITIAL_STRING_DD initial sting for direct debit (Lastschrift)
     */
    const INITIAL_STRING_DD = '<?xml version="1.0" encoding="UTF-8"?><Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.002.02" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:iso:std:iso:20022:tech:xsd:pain.008.002.02 pain.008.002.02.xsd"></Document>';
    
    
    /**
      Creates a SepaXmlFile object and sets the head data
      @param $initgPty string the name of the initiating party (max. 70 characters)
      @param $msgId string the unique id of the file
      @param $type string sets the type of the sepa file 'CT' or 'DD' (default = 'CT')
    */
    public function __construct($initgPty, $msgId, $type = 'CT')
    {
        if( strcasecmp ( $type , 'CT' ) == 0 ){
            $this->xml = simplexml_load_string(self::INITIAL_STRING_CT);
            $this->xmlType = 'CstmrCdtTrfInitn';            
            $this->type = 'CT';
        }else{
            $this->xml = simplexml_load_string(self::INITIAL_STRING_DD);
            $this->xmlType = 'CstmrDrctDbtInitn';
            $this->type = 'DD';
        }
        $this->initgPty = $this->shorten(70, $initgPty);
        $this->msgId = $msgId;
    }
    
    /**
      adds a new collection of transfers and sets main data
      @param $transferInfo mixed[] needed keys: 'pmtInfId', 'dbtr', 'iban', 'bic'; optional keys: 'ccy', 'btchBookg', 'ctgyPurp', 'reqdExctnDt', 'ultmtDbtr'
      @return false|SepaCreditTransfer
     */
    public function addCreditTransferCollection(array $transferInfo)
    {
        if(strcasecmp($this->type, 'CT') != 0)
            return false;
        
        $needed = array(
            'pmtInfId', 'dbtr', 'iban', 'bic'
        );
        
        foreach ($needed as $key) {
            if (!isset($transferInfo[$key]))
                return false;
        }
        
        $paymentCollection = new SepaCreditTransfer(array_map(array('self','removeUmlauts'), $transferInfo));
        $this->paymentCollections[] = $paymentCollection;
        
        return $paymentCollection;
    }
    
    /**
      adds a new collection of transfers and sets main data
      @param $debitInfo mixed[] needed keys: 'pmtInfId', 'lclInstrm', 'seqTp', 'reqdColltnDt', 'cdtr', 'iban', 'bic', 'ci'; optional keys: 'ccy', 'btchBookg', 'ctgyPurp', 'ultmtCdtr', 'reqdColltnDt'
      @return false|SepaDirectDebit
     */
    public function addDirectDebitCollection(array $debitInfo)
    {
        if(strcasecmp($this->type, 'DD') != 0)
            return false;
        
        $needed = array(
            'pmtInfId', 'lclInstrm', 'seqTp', 'cdtr', 'iban', 'bic', 'ci'
        );
        foreach ($needed as $key) {
            if (!isset($debitInfo[$key]))
                return false;
        }
        
        if(strcasecmp($debitInfo['lclInstrm'], 'CORE') != 0 && strcasecmp($debitInfo['lclInstrm'], 'B2B') != 0)
            return false;
        
        $allowed = array(
            'FRST', 'RCUR', 'OOFF', 'FNAL'
        );
        if(!in_array(strtoupper($debitInfo['seqTp']), $allowed))
            return false;
        
        $paymentCollection = new SepaDirectDebit(array_map(array('self','removeUmlauts'), $debitInfo));
        $this->paymentCollections[] = $paymentCollection;
        
        return $paymentCollection;
    }

    
    /**
      generates the XML file from the given data
      @return simplexml
     */
    public function generateXml()
    {
        
        $datetime = new DateTime();
		$creDtTm = $datetime->format('Y-m-d\TH:i:s');
        
        
        $fileHead = $this->xml->addChild($this->xmlType);
        
        $grpHdr = $fileHead->addChild('GrpHdr');
        $grpHdr->addChild('MsgId', $this->msgId);
        $grpHdr->addChild('CreDtTm', $creDtTm);
        $grpHdr->addChild('NbOfTxs', $this->getNumberOfTransactions());
        $grpHdr->addChild('CtrlSum', sprintf("%01.2f", $this->getCtrlSum()));
        $grpHdr->addChild('InitgPty')->addChild('Nm', $this->shorten(70, $this->initgPty));
        
        foreach($this->paymentCollections as $paymentCollection){
            $pmtInf = $fileHead->addChild('PmtInf');
            $paymentCollection->generateCollectionXml($pmtInf);
        }
        
        return $this->xml->asXML();
        
    }

       
    /**
      shortens a string $str down to a lenght of $len
      @param $len int 
      @param $str string
      @return string
     */
    private function shorten($len, $str)
    {
        return (strlen($str) < $len) ? $str : substr($str, 0, $len);
    }
    
    /**
      calulates the sum of all payments
      @return float
     */
    private function getCtrlSum()
    {
        $ctrlSum = 0;
        foreach($this->paymentCollections as $collection){
            $ctrlSum += $collection->getCtrlSum();
        }
        
        return $ctrlSum;
    }
    
    /**
      calulates the number payments in all collections
      @return int
     */
    private function getNumberOfTransactions()
    {
        $nbOfTxs = 0;
        foreach($this->paymentCollections as $collection){
            $nbOfTxs += $collection->getNumberOfTransactions();
        }
        
        return $nbOfTxs;
    }
    
    private function removeUmlauts($str)
    {
        $umlauts = array('Ä', 'ä', 'Ü', 'ü', 'Ö', 'ö', 'ß');
        $umlautReplacements = array('Ae', 'ae', 'Ue', 'ue', 'Oe', 'oe', 'ss');
        
        return str_replace($umlauts, $umlautReplacements, $str);
    }
    
    
}

?>