<?php
/**
 * Sephpa
 *  
 * @license MIT License
 * @copyright © 2014 Alexander Schickedanz
 * @link      http://abcaeffchen.net
 *
 * @author  Alexander Schickedanz <alex@abcaeffchen.net>
 */

require_once 'sepaLib/SepaCreditTransfer00100203.php';
require_once 'sepaLib/SepaCreditTransfer00100303.php';
require_once 'sepaLib/SepaDirectDebit00800202.php';
require_once 'sepaLib/SepaDirectDebit00800302.php';
require_once 'sepaLib/SepaUtilities.php';

/**
 * Class SephpaInputException thrown if an invalid input is detected
 */
class SephpaInputException extends Exception {}

// credit transfers < separator
const SEPA_PAIN_001_002_03 = 100203;
const SEPA_PAIN_001_003_03 = 100303;
// Separator is greater then credit transfer and lower than direct debit
const SEPA_RATOR           = 800000;
// direct debits > separator
const SEPA_PAIN_008_002_02 = 800202;
const SEPA_PAIN_008_003_02 = 800302;


/**
 Base class for both credit transfer and direct debit
*/
class Sephpa
{
    /**
     * @var SimpleXMLElement $xml xml object
     */
    private $xml;
    /**
     * @var int $type Saves the type of the object SEPA_PAIN_*
     */
    private $type;
    /**
     * @var string $xmlType Either 'CstmrCdtTrfInitn' or 'CstmrDrctDbtInitn'
     */
    private $xmlType;
    /**
     * @var string $initgPty Name of the party that initiates the transfer
     */
    private $initgPty;
    /**
     * @var string $msgId identify the Sepa file (unique id for all files)
     */
    private $msgId;
    /**
     * @var string $localInstrument used to check if the LocalInstrument of direct debits are all the same
     */
    private $localInstrument = null;
    /**
     * @var SepaPaymentCollection[] $paymentCollections saves all payment objects
     */
    private $paymentCollections = array();
    /**
     * @var string INITIAL_STRING_CT Initial sting for credit transfer pain.001.002.03
     */    
    const INITIAL_STRING_PAIN_001_002_03 = '<?xml version="1.0" encoding="UTF-8"?><Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.002.03" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:iso:std:iso:20022:tech:xsd:pain.001.002.03 pain.001.002.03.xsd"></Document>';
    /**
     * @var string INITIAL_STRING_CT Initial sting for credit transfer pain.001.003.03
     */
    const INITIAL_STRING_PAIN_001_003_03 = '<?xml version="1.0" encoding="UTF-8"?><Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.003.03" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:iso:std:iso:20022:tech:xsd:pain.001.003.03 pain.001.003.03.xsd"></Document>';
    /**
     * @var string INITIAL_STRING_DD Initial sting for direct debit pain.008.002.02
     */
    const INITIAL_STRING_PAIN_008_002_02 = '<?xml version="1.0" encoding="UTF-8"?><Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.002.02" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:iso:std:iso:20022:tech:xsd:pain.008.002.02 pain.008.002.02.xsd"></Document>';
    /**
     * @var string INITIAL_STRING_DD Initial sting for direct debit pain.008.003.02
     */
    const INITIAL_STRING_PAIN_008_003_02 = '<?xml version="1.0" encoding="UTF-8"?><Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.003.02" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:iso:std:iso:20022:tech:xsd:pain.008.003.02 pain.008.003.02.xsd"></Document>';

    /**
     * Creates a SepaXmlFile object and sets the head data
     *
     * @param string $initgPty The name of the initiating party (max. 70 characters)
     * @param string $msgId    The unique id of the file
     * @param string $type     Sets the type of the sepa file 'CT' or 'DD' (default = 'CT')
     * @throws SephpaInputException
     */
    public function __construct($initgPty, $msgId, $type = 'CT')
    {
        switch($type)
        {
            case SEPA_PAIN_001_002_03:
                $this->xml = simplexml_load_string(self::INITIAL_STRING_PAIN_001_002_03);
                $this->xmlType = 'CstmrCdtTrfInitn';
                $this->type = SEPA_PAIN_001_002_03;
                break;
            case SEPA_PAIN_001_003_03:
                $this->xml = simplexml_load_string(self::INITIAL_STRING_PAIN_001_003_03);
                $this->xmlType = 'CstmrCdtTrfInitn';
                $this->type = SEPA_PAIN_001_002_03;
                break;
            case SEPA_PAIN_008_002_02:
                $this->xml = simplexml_load_string(self::INITIAL_STRING_PAIN_008_002_02);
                $this->xmlType = 'CstmrDrctDbtInitn';
                $this->type = SEPA_PAIN_008_002_02;
                break;
            case SEPA_PAIN_008_003_02:
                $this->xml = simplexml_load_string(self::INITIAL_STRING_PAIN_008_003_02);
                $this->xmlType = 'CstmrDrctDbtInitn';
                $this->type = SEPA_PAIN_008_003_02;
                break;
            default:
                throw new SephpaInputException('You choose an invalid type. Please use the SEPA_PAIN_* constants.');
        }


        $this->initgPty = SepaUtilities::sanitizeLength( $initgPty, 70 );
        $this->msgId = $msgId;
    }

    /**
     * Adds a new collection of credit transfers and sets main data
     *
     * @param mixed[] $transferInfo Required keys: 'pmtInfId', 'dbtr', 'iban', ('bic' only pain.001.002.03);
     *                              optional keys: 'ccy', 'btchBookg', 'ctgyPurp', 'reqdExctnDt', 'ultmtDbtr'
     * @throws SephpaInputException
     * @return SepaCreditTransfer00100203
     */
    public function addCreditTransferCollection(array $transferInfo)
    {
        if($this->type > SEPA_RATOR)
            throw new SephpaInputException('You cannot add credit transfers collections to a direct debit file');


        switch($this->type)
        {
            case SEPA_PAIN_001_003_03:
                if(SepaUtilities::containsNotAllKeys($transferInfo, array('pmtInfId', 'dbtr', 'iban')))
                    throw new SephpaInputException('One of the required inputs \'pmtInfId\', \'dbtr\', \'iban\' is missing.');

                $paymentCollection = new SepaCreditTransfer00100303($transferInfo);
                break;
            default:        // only case here is SEPA_PAIN_001_002_03
                if(SepaUtilities::containsNotAllKeys($transferInfo, array('pmtInfId', 'dbtr', 'iban', 'bic')))
                    throw new SephpaInputException('One of the required inputs \'pmtInfId\', \'dbtr\', \'iban\', \'bic\' is missing.');

                $paymentCollection = new SepaCreditTransfer00100203($transferInfo);
        }
        $this->paymentCollections[] = $paymentCollection;
        
        return $paymentCollection;
    }

    /**
     * Adds a new collection of direct debits and sets main data
     *
     * @param mixed[] $debitInfo Required keys: 'pmtInfId', 'lclInstrm', 'seqTp', 'reqdColltnDt', 'cdtr', 'iban', 'bic', 'ci';
     *                           optional keys: 'ccy', 'btchBookg', 'ctgyPurp', 'ultmtCdtr', 'reqdColltnDt'
     * @throws SephpaInputException
     * @return SepaDirectDebit00800202
     */
    public function addDirectDebitCollection(array $debitInfo)
    {
        if($this->type < SEPA_RATOR)
            throw new SephpaInputException('You cannot add a direct debit collection to a credit transfer file.');

        if(SepaUtilities::containsNotAllKeys($debitInfo, array('pmtInfId', 'lclInstrm', 'seqTp', 'cdtr', 'iban', 'bic', 'ci')))
            throw new SephpaInputException('One of the required inputs \'pmtInfId\', \'lclInstrm\', \'seqTp\', \'cdtr\', \'iban\', \'bic\', \'ci\' is missing.');

        // to upper case for some inputs
        $debitInfo['lclInstrm'] = strtoupper($debitInfo['lclInstrm']);
        $debitInfo['seqTp'] = strtoupper($debitInfo['seqTp']);
        $debitInfo['ci'] = strtoupper($debitInfo['ci']);
        $debitInfo['iban'] = strtoupper($debitInfo['iban']);
        $debitInfo['bic'] = strtoupper($debitInfo['bic']);
        if(isset($debitInfo['btchBookg']))
            $debitInfo['btchBookg'] = strtolower($debitInfo['btchBookg']);

        if(!isset($this->localInstrument))
            $this->localInstrument = $debitInfo['lclInstrm'];

        if($debitInfo['lclInstrm'] !== $this->localInstrument)
            throw new SephpaInputException('You cannot add direct debits with different local instrument to the same collection.');


        if(!in_array($debitInfo['seqTp'], array('FRST', 'RCUR', 'OOFF', 'FNAL')))
            throw new SephpaInputException('The sequence type (seqTp) has to be \'FRST\' (first direct debit), \'RCUR\' (recurring), \'OOFF\' (single) or \'FNAL\' (final)');

        switch($this->type)
        {
            case SEPA_PAIN_008_003_02:
                if(!in_array($debitInfo['lclInstrm'], array('CORE','COR1','B2B')))
                    throw new SephpaInputException('The local Instrument (lclInstrm) as to be either \'CORE\', \'COR1\' or \'B2B\'');

                $paymentCollection = new SepaDirectDebit00800302($debitInfo);
                break;
            default:        // only case here is SEPA_PAIN_008_002_02
                if(!in_array($debitInfo['lclInstrm'], array('CORE','B2B')))
                    throw new SephpaInputException('The local Instrument (lclInstrm) as to be either \'CORE\' or \'B2B\'');

                $paymentCollection = new SepaDirectDebit00800202($debitInfo);
        }

        $this->paymentCollections[] = $paymentCollection;
        
        return $paymentCollection;
    }

    /**
     * Generates the XML file from the given data
     * @return string Just the xml code of the file
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
        $grpHdr->addChild('InitgPty')->addChild('Nm', SepaUtilities::sanitizeLength($this->initgPty,70));
        
        foreach($this->paymentCollections as $paymentCollection){
            $pmtInf = $fileHead->addChild('PmtInf');
            $paymentCollection->generateCollectionXml($pmtInf);
        }
        
        return $this->xml->asXML();
        
    }

    /**
     * Calculates the sum of all payments
     * @return float
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
     * Calculates the number payments in all collections
     * @return int
     */
    private function getNumberOfTransactions()
    {
        $nbOfTxs = 0;
        foreach($this->paymentCollections as $collection){
            $nbOfTxs += $collection->getNumberOfTransactions();
        }
        
        return $nbOfTxs;
    }

}
