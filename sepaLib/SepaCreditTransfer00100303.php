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

/**
 * Manages credit transfers
 */
class SepaCreditTransfer00100303 extends SepaPaymentCollection
{
    /**
     * @var mixed[] $payments Saves all payments
     */
    private $payments = array();
    /**
     * @var mixed[] $transferInfo Saves the transfer information for the collection.
     */
    private $transferInfo;
    /**
     * @var string CCY Default currency
     */
    const CCY = 'EUR';

    /**
     * Calculates the sum of all payments in this collection
     *
     * @param mixed[] $transferInfo needed keys: 'pmtInfId', 'dbtr', 'iban';
     *                              optional keys: 'ccy', 'btchBookg', 'ctgyPurp', 'reqdExctnDt', 'ultmtDebtr', 'bic'
     */
    public function __construct(array $transferInfo)
    {
        // already checked for needed keys in Sephpa-Class
        $this->transferInfo = $transferInfo;
    }

    /**
     * Adds a payment to the payment collection
     *
     * @param mixed[] $paymentInfo needed keys: 'pmtId', 'instdAmt', 'iban', 'bic', 'cdtr';
     *                             optional keys: 'ultmtCdrt', 'purp', 'rmtInf'
     * @throws SephpaInputException
     * @return void
     */
    public function addPayment(array $paymentInfo)
    {
        if(SepaUtilities::containsNotAllKeys($paymentInfo, array('pmtId', 'instdAmt', 'iban', 'bic', 'cdtr')))
            throw new SephpaInputException('One of the required inputs \'pmtId\', \'instdAmt\', \'iban\', \'bic\', \'cdtr\' is missing.');

        $this->payments[] = $paymentInfo;
    }
    
    /**
     * calculates the sum of all payments in this collection
     * @return float
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
     * counts the payments in this collection
     * @return int
     */
    public function getNumberOfTransactions()
    {
        return count($this->payments);
    }
    
    /**
     * Generates the xml for the collection using generatePaymentXml
     * @param SimpleXMLElement $pmtInf The PmtInf-Child of the xml object
     * @return void
     */
    public function generateCollectionXml(SimpleXMLElement $pmtInf)
    {
        
        $ccy = (isset($this->transferInfo['ccy']) && strlen($this->transferInfo['ccy']) == 3) ? strtoupper($this->transferInfo['ccy']) : self::CCY;
        
        $datetime = new DateTime();
        $reqdExctnDt = (isset($this->transferInfo['reqdExctnDt'])) ? $this->transferInfo['reqdExctnDt'] : $datetime->format('Y-m-d');
        
        $pmtInf->addChild('PmtInfId', $this->transferInfo['pmtInfId']);
        $pmtInf->addChild('PmtMtd', 'TRF');
        if(isset($this->transferInfo['btchBookg']) && ($this->transferInfo['btchBookg'] === 'false' || $this->transferInfo['btchBookg'] === 'true'))
            $pmtInf->addChild('BtchBookg', $this->transferInfo['btchBookg']);
        $pmtInf->addChild('NbOfTxs', $this->getNumberOfTransactions());
        $pmtInf->addChild('CtrlSum', sprintf("%01.2f", $this->getCtrlSum()));
        
        $pmtTpInf = $pmtInf->addChild('PmtTpInf');
        $pmtTpInf->addChild('InstrPrty','NORM');
        $pmtTpInf->addChild('SvcLvl')->addChild('Cd','SEPA');
        if(isset($this->transferInfo['ctgyPurp']))
            $pmtTpInf->addChild('CtgyPurp')->addChild('Cd', $this->transferInfo['ctgyPurp']);
        
        $pmtInf->addChild('ReqdExctnDt', $reqdExctnDt);
        $pmtInf->addChild('Dbtr')->addChild('Nm', SepaUtilities::sanitizeLength($this->transferInfo['dbtr'], 70));
        
        $dbtrAcct= $pmtInf->addChild('DbtrAcct');
        $dbtrAcct->addChild('Id')->addChild('IBAN', $this->transferInfo['iban']);
        $dbtrAcct->addChild('Ccy', $ccy);
        
        $pmtInf->addChild('DbtrAgt')->addChild('FinInstnId')->addChild('BIC', $this->transferInfo['bic']);
        
        if(isset($this->transferInfo['ultmtDbtr']))
            $pmtInf->addChild('UltmtDbtr')->addChild('Nm', SepaUtilities::sanitizeLength( $this->transferInfo['ultmtDbtr'], 70 ));
        
        $pmtInf->addChild('ChrgBr', 'SLEV');
        
        foreach($this->payments as $payment){
            $cdtTrfTxInf = $pmtInf->addChild('CdtTrfTxInf');
            $this->generatePaymentXml($cdtTrfTxInf, $payment, $ccy);
        }
    
    }
    
    /**
     * generates the xml for a single payment
     * @param SimpleXMLElement $cdtTrfTxInf
     * @param mixed[] $payment one of the payments in $this->payments
     * @param string $ccy currency
     * @return void
     */
    private function generatePaymentXml($cdtTrfTxInf, $payment, $ccy)
    {
        $cdtTrfTxInf->addChild('PmtId')->addChild('EndToEndId', $payment['pmtId']);
        $cdtTrfTxInf->addChild('Amt')->addChild('InstdAmt', $payment['instdAmt'])->addAttribute('Ccy', $ccy);
        if(!empty($payment['bic']))
            $cdtTrfTxInf->addChild('CdtrAgt')->addChild('FinInstnId')->addChild('BIC', $payment['bic']);
        $cdtTrfTxInf->addChild('Cdtr')->addChild('Nm', $payment['cdtr']);
        $cdtTrfTxInf->addChild('CdtrAcct')->addChild('Id')->addChild('IBAN', $payment['iban']);
        
        if(isset($payment['ultmtCdtr']))
            $cdtTrfTxInf->addChild('UltmtCdtr')->addChild('Nm', SepaUtilities::sanitizeLength( $payment['ultmtCdtr'], 70 ));
        if(isset($payment['purp']))
            $cdtTrfTxInf->addChild('Purp')->addChild('Cd', $payment['purp']);
        if(isset($payment['rmtInf']))
            $cdtTrfTxInf->addChild('RmtInf')->addChild('Ustrd', SepaUtilities::sanitizeLength( $payment['rmtInf'],  140 ));
    }

}
