<?php
/**
 * Sephpa
 *
 * @license   GNU LGPL v3.0 - For details have a look at the LICENSE file
 * @copyright ©2016 Alexander Schickedanz
 * @link      https://github.com/AbcAeffchen/Sephpa
 *
 * @author  Alexander Schickedanz <abcaeffchen@gmail.com>
 */

namespace AbcAeffchen\Sephpa;
use AbcAeffchen\SepaUtilities\SepaUtilities;

// Set default Timezone
date_default_timezone_set(@date_default_timezone_get());

/**
 * Class SephpaInputException thrown if an invalid input is detected
 */
class SephpaInputException extends \Exception {}

/**
 * Base class for both credit transfer and direct debit
 */
abstract class Sephpa
{
    /**
     * @type string $xmlInitString stores the initialization string of the xml file
     */
    protected $xmlInitString;
    /**
     * @type int $version Saves the type of the object SEPA_PAIN_*
     */
    protected $version;
    /**
     * @type string $paymentType Either 'CstmrCdtTrfInitn' or 'CstmrDrctDbtInitn'
     */
    protected $paymentType;
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
     * Generates the XML file from the given data. All empty collections are skipped.
     *
     * @param string $creDtTm Creation Date Time. You should not use this (just for testing)
     * @throws SephpaInputException
     * @return string Just the xml code of the file
     */
    protected function generateXml($creDtTm = '')
    {
        if(count($this->paymentCollections) === 0)
            throw new SephpaInputException('No payment collections provided.');

        $totalNumberOfTransaction = $this->getNumberOfTransactions();
        if($totalNumberOfTransaction === 0)
            throw new SephpaInputException('No payments provided.');

        if(empty($creDtTm) || SepaUtilities::checkCreateDateTime($creDtTm) === false)
        {
            $now = new \DateTime();
            $creDtTm  = $now->format('Y-m-d\TH:i:s');
        }

        $xml = simplexml_load_string($this->xmlInitString);
        $fileHead = $xml->addChild($this->paymentType);
        
        $grpHdr = $fileHead->addChild('GrpHdr');
        $grpHdr->addChild('MsgId', $this->msgId);
        $grpHdr->addChild('CreDtTm', $creDtTm);
        $grpHdr->addChild('NbOfTxs', $totalNumberOfTransaction);
        $grpHdr->addChild('CtrlSum', sprintf('%01.2f', $this->getCtrlSum()));
        $grpHdr->addChild('InitgPty')->addChild('Nm', $this->initgPty);
        
        foreach($this->paymentCollections as $paymentCollection)
        {
            // ignore empty collections
            if($paymentCollection->getNumberOfTransactions() === 0)
                continue;

            $pmtInf = $fileHead->addChild('PmtInf');
            $paymentCollection->generateCollectionXml($pmtInf);
        }

        return $xml->asXML();
    }

    /**
     * Generates one XML file per collection. All empty collections are skipped. The message
     * IDs get extended with a file counter
     *
     * @param string $creDtTm Creation Date Time. You should not use this (just for testing)
     * @throws SephpaInputException
     * @return string[][] An array containing pairs of message ID and the xml string.
     */
    protected function generateMultiFileXml($creDtTm = '')
    {
        if(count($this->paymentCollections) === 0)
            throw new SephpaInputException('No payment collections provided.');

        $totalNumberOfTransaction = $this->getNumberOfTransactions();
        if($totalNumberOfTransaction === 0)
            throw new SephpaInputException('No payments provided.');

        if(empty($creDtTm) || SepaUtilities::checkCreateDateTime($creDtTm) === false)
        {
            $now = new \DateTime();
            $creDtTm  = $now->format('Y-m-d\TH:i:s');
        }

        $fileCounter = 1;
        $xmlFiles = array();

        foreach($this->paymentCollections as $paymentCollection)
        {
            // ignore empty collections
            $numberOfTransactions = $paymentCollection->getNumberOfTransactions();
            if($numberOfTransactions === 0)
                continue;

            $xml = simplexml_load_string($this->xmlInitString);
            $fileHead = $xml->addChild($this->paymentType);

            $msgId = $this->msgId . sprintf('%02d', $fileCounter);

            $grpHdr = $fileHead->addChild('GrpHdr');
            $grpHdr->addChild('MsgId', $msgId);
            $grpHdr->addChild('CreDtTm', $creDtTm);
            $grpHdr->addChild('NbOfTxs', $numberOfTransactions);
            $grpHdr->addChild('CtrlSum', sprintf('%01.2f', $paymentCollection->getCtrlSum()));
            $grpHdr->addChild('InitgPty')->addChild('Nm', $this->initgPty);

            $pmtInf = $fileHead->addChild('PmtInf');
            $paymentCollection->generateCollectionXml($pmtInf);

            $xmlFiles[] = array($msgId, $xml->asXML());
            $fileCounter++;
        }

        return $xmlFiles;
    }

    // todo find correct names for unmixed and doc...
    /**
     * Generates the SEPA file and starts a download using the header 'Content-Disposition: attachment;'
     * The file will not stored on the server.
     *
     * @param string $filename
     * @param array  $options Available options:
     *                        (bool) "correctlySortedFiles": Only available for direct debit files.
     *                                               If set to true, there will one file per
     *                                               collection be created. Defaults to true.
     *                        (bool) "addFileRoutingSlips": Adds file routing slips for every created
     *                                               SEPA file. Defaults to false.
     * @throws SephpaInputException
     */
    public function downloadSepaFile($filename = 'payments.xml', $options = array())
    {
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        print $this->generateXml();
    }

    /**
     * Generates the SEPA file and stores it on the server.
     *
     * @param string $filename The path and filename
     * @param array  $options  todo
     * @throws SephpaInputException
     */
    public function storeSepaFile($filename = 'payments.xml', $options = array())
    {
        $file = fopen($filename, 'wb');
        fwrite($file, $this->generateXml());
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
