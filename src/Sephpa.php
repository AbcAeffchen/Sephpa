<?php
/**
 * Sephpa
 *  
 * @license MIT License
 * @copyright © 2014 Alexander Schickedanz
 * @link      https://github.com/AbcAeffchen/Sephpa
 *
 * @author  Alexander Schickedanz <abcaeffchen@gmail.com>
 */

namespace AbcAeffchen\Sephpa;
use AbcAeffchen\SepaUtilities\SepaUtilities;

require __DIR__ . '/../vendor/autoload.php';


// Set default Timezone
date_default_timezone_set(@date_default_timezone_get());

/**
 * Class SephpaInputException thrown if an invalid input is detected
 */
class SephpaInputException extends \Exception {}

/**
 *Base class for both credit transfer and direct debit
 */
abstract class Sephpa
{
    // credit transfers versions
    const SEPA_PAIN_001_002_03 = SepaUtilities::SEPA_PAIN_001_002_03;
    const SEPA_PAIN_001_003_03 = SepaUtilities::SEPA_PAIN_001_003_03;
    // direct debits versions
    const SEPA_PAIN_008_002_02 = SepaUtilities::SEPA_PAIN_008_002_02;
    const SEPA_PAIN_008_003_02 = SepaUtilities::SEPA_PAIN_008_003_02;

    const XML_HEAD = '<?xml version="1.0" encoding="UTF-8"?>';
    const XML_CONTAINER = '<conxml xmlns="urn:conxml:xsd:container.nnn.002.02" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:conxml:xsd:container.nnn.002.02 container.nnn.002.02.xsd"></conxml>';
    const XML_DOCUMENT_TAG = '<Document></Document>';
    /**
     * @type \SimpleXMLElement $xml xml object
     */
    protected $xml;
    /**
     * @type int $version Saves the type of the object SEPA_PAIN_*
     */
    protected $version;
    /**
     * @type int $version Saves the type of the object SEPA_PAIN_*
     */
    protected $xmlns;
    /**
     * @type string $xmlType Either 'CstmrCdtTrfInitn' or 'CstmrDrctDbtInitn'
     */
    protected $xmlType;
    /**
     * @type string $xmlContainerType
     */
    protected $xmlContainerType;
    /**
     * @type string $initgPty Name of the party that initiates the transfer
     */
    protected $initgPty;
    /**
     * @type string $msgId identify the Sepa file (unique id for all files)
     */
    protected $msgId;
    /**
     * @type SepaPaymentCollection[] $paymentCollections saves all payment objects
     */
    protected $paymentCollections = array();
    /**
     * @type bool $checkAndSanitize
     */
    protected $checkAndSanitize;
    /**
     * @type int $sanitizeFlags
     */
    protected $sanitizeFlags = 0;
    /**
     * @type bool $closed if true, no collections or payments can be added anymore
     */
    protected $closed = false;
    /**
     * Creates a SepaXmlFile object and sets the head data
     *
     * @param string $initgPty The name of the initiating party (max. 70 characters)
     * @param string $msgId    The unique id of the file
     * @param int    $type     Sets the type and version of the sepa file. Use the SEPA_PAIN_* constants
     * @param bool   $checkAndSanitize
     */
    public function __construct($initgPty, $msgId, $type, $checkAndSanitize = true)
    {
        $this->checkAndSanitize = $checkAndSanitize;

        if($this->checkAndSanitize)
        {
            $this->initgPty = SepaUtilities::checkAndSanitize('initgpty',$initgPty);
            $this->msgId    = SepaUtilities::checkAndSanitize('msgid',$msgId);
        }
        else
        {
            $this->initgPty = $initgPty;
            $this->msgId    = $msgId;
        }
    }

    /**
     * This flags will only be used if checkAndSanitize is set to true.
     * @param int $flags Use the SepaUtilities Flags
     */
    public function setSanitizeFlags($flags)
    {
        $this->sanitizeFlags = $flags;
    }

    /**
     * Adds a new collection of credit transfers and sets main data
     *
     * @param mixed[] $information An array with information for this Collection
     * @throws SephpaInputException
     * @return SepaPaymentCollection
     */
    abstract public function addCollection(array $information);

    /**
     * Build the SEPA Document from the given data
     *
     * @param string $creDtTm You should not use this
     * @throws SephpaInputException
     * @return string Just the xml code of the Document
     */
    protected function buildDocument($creDtTm = '')
    {
        if($this->closed)
            return preg_replace('#<\?xml[^(\?>)]*\?>\s*#','',$this->xml->asXML());

        $this->closed = true;

        if(empty($creDtTm) || SepaUtilities::checkCreateDateTime($creDtTm) === false)
        {
            $now = new \DateTime();
            $creDtTm  = $now->format('Y-m-d\TH:i:s');
        }

        $totalNumberOfTransaction = $this->getNumberOfTransactions();

        if($totalNumberOfTransaction === 0)
            throw new SephpaInputException('No Payments provided.');

        $fileHead = $this->xml->addChild($this->xmlType);

        $grpHdr = $fileHead->addChild('GrpHdr');
        $grpHdr->addChild('MsgId', $this->msgId);
        $grpHdr->addChild('CreDtTm', $creDtTm);
        $grpHdr->addChild('NbOfTxs', $totalNumberOfTransaction);
        $grpHdr->addChild('CtrlSum', sprintf("%01.2f", $this->getCtrlSum()));
        $grpHdr->addChild('InitgPty')->addChild('Nm', $this->initgPty);

        foreach($this->paymentCollections as $paymentCollection)
        {
            // ignore empty collections
            if($paymentCollection->getNumberOfTransactions() === 0)
                continue;

            $pmtInf = $fileHead->addChild('PmtInf');
            $paymentCollection->generateCollectionXml($pmtInf);
        }

        return preg_replace('#<\?xml[^(\?>)]*\?>\s*#','',$this->xml->asXML());
    }

    /**
     * Generates the XML file from the given data.
     *
     * @param string $creDtTm You should not use this
     * @throws SephpaInputException
     * @return string Just the xml code of the file
     */
    public function generateXml($creDtTm = '')
    {
        return self::XML_HEAD . $this->buildDocument($creDtTm);
    }

    /**
     * Generates the XML container file from the given data.
     *
     * @param string $creDtTm You should not use this
     * @throws SephpaInputException
     * @return string Just the xml code of the file
     */
    public function generateXMLContainer($creDtTm = '')
    {
        if(empty($creDtTm) || SepaUtilities::checkCreateDateTime($creDtTm) === false)
        {
            $now = new \DateTime();
            $creDtTm  = $now->format('Y-m-d\TH:i:s');
        }

        $container = new AppendableXML(self::XML_HEAD . self::XML_CONTAINER);
        $container->addChild('CreDtTm',$creDtTm);

        $xml = $this->buildDocument($creDtTm);


        $domDocument = new \DOMDocument();
        // TODO Fix the canonization of the XML code to get a correct hash
        $domDocument->loadXML($xml,LIBXML_NOENT | LIBXML_DTDLOAD | LIBXML_DTDATTR | LIBXML_NOCDATA);
//        $domDocument = dom_import_simplexml($this->xml);
//        $domDocument->
//        $domDocument->normalize();
        $document = $domDocument->C14N(false,false);

//        $hash = strtoupper(hash('sha256',strtoupper(hash('sha256',$document))));
        $hash = strtoupper(hash('sha256',$document));
        $msg = $container->addChild($this->xmlContainerType);

        $msg->addChild('HashValue',$hash);
        $msg->addChild('HashAlgorithm','SHA256');

        $iterator = new \SimpleXMLIterator($document);
        $doc = $msg->addChild('Document');
        $doc->addAttribute('xmlns', $this->xmlns);

        $doc->appendXML($iterator);

        return $container->asXML();
    }

    /**
     * Generates the SEPA file and starts a download using the header 'Content-Disposition: attachment;'
     * The file will not stored on the server.
     *
     * @param string $filename
     * @param string $creDtTm You should not use this
     * @throws SephpaInputException
     */
    public function downloadSepaFile($filename = 'payments.xsd',$creDtTm = '')
    {
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        print $this->generateXml($creDtTm);
    }

    /**
     * Generates the SEPA container file and starts a download using the header 'Content-Disposition: attachment;'
     * The file will not stored on the server.
     *
     * @param string $filename
     * @param string $creDtTm You should not use this
     * @throws SephpaInputException
     */
    public function downloadSepaContainerFile($filename = 'payments_container.xsd',$creDtTm = '')
    {
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        print $this->generateXml($creDtTm);
    }

    /**
     * Generates the SEPA file and stores it on the server.
     *
     * @param string $filename The path and filename
     * @param string $creDtTm  You should not use this
     * @throws SephpaInputException
     */
    public function storeSepaFile($filename = 'payments.xsd', $creDtTm = '')
    {
        $file = fopen($filename, 'w');
        fwrite($file, $this->generateXml($creDtTm));
        fclose($file);
    }

    /**
     * Generates the SEPA container file and stores it on the server.
     *
     * @param string $filename The path and filename
     * @param string $creDtTm  You should not use this
     * @throws SephpaInputException
     */
    public function storeSepaContainerFile($filename = 'payments_container.xsd', $creDtTm = '')
    {
        $file = fopen($filename, 'w');
        fwrite($file, $this->generateXMLContainer($creDtTm));
        fclose($file);
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
