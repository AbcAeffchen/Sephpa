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

require_once 'Sephpa.php';

/**
 * Base class for both credit transfer and direct debit
 */
class SephpaCreditTransfer extends Sephpa
{
    /**
     * @type string XMLNS_PAIN_001_002_03 xmlns value for credit transfer pain.001.002.03
     */
    const XMLNS_PAIN_001_002_03 = 'urn:iso:std:iso:20022:tech:xsd:pain.001.002.03';
    /**
     * @type string XMLNS_PAIN_001_002_03 xmlns value for credit transfer pain.001.003.03
     */
    const XMLNS_PAIN_001_003_03 = 'urn:iso:std:iso:20022:tech:xsd:pain.001.003.03';
    /**
     * @type string $xmlContainerType
     */
    protected $xmlContainerType = 'MsgPain001';

    /**
     * Creates a SepaXmlFile object and sets the head data
     *
     * @param string $initgPty The name of the initiating party
     * @param string $msgId    The unique id of the file
     * @param int    $version  Sets the type and version of the sepa file. Use the SEPA_PAIN_*
     *                         constants
     * @param bool   $checkAndSanitize
     * @throws SephpaInputException
     */
    public function __construct($initgPty, $msgId, $version, $checkAndSanitize = true)
    {
        parent::__construct($initgPty, $msgId, $version, $checkAndSanitize);

        $this->xmlType = 'CstmrCdtTrfInitn';

        switch($version)
        {
            case self::SEPA_PAIN_001_002_03:
                $this->xmlns =  self::XMLNS_PAIN_001_002_03;
                break;
            case self::SEPA_PAIN_001_003_03:
                $this->xmlns =  self::XMLNS_PAIN_001_003_03;
                break;
            default:
                throw new SephpaInputException('You choose an invalid SEPA file version. Please use the SEPA_PAIN_001_* constants.');
        }

        $this->version = $version;
        $this->xml = new \SimpleXMLElement(self::XML_DOCUMENT_TAG,LIBXML_DTDATTR);
        $this->xml->addAttribute('xmlns', $this->xmlns);
        $this->version = self::SEPA_PAIN_001_002_03;
    }

    /**
     * Adds a new collection of credit transfers and sets main data
     *
     * @param mixed[] $transferInfo Required keys: 'pmtInfId', 'dbtr', 'iban', ('bic' only pain.001.002.03);
     *                              optional keys: 'ccy', 'btchBookg', 'ctgyPurp', 'reqdExctnDt', 'ultmtDbtr'
     * @throws SephpaInputException
     * @return SepaPaymentCollection
     */
    public function addCollection(array $transferInfo)
    {
        switch($this->version)
        {
            case self::SEPA_PAIN_001_002_03:
                $paymentCollection = new SepaCreditTransfer00100203($transferInfo, $this->checkAndSanitize, $this->sanitizeFlags);
                break;
            case self::SEPA_PAIN_001_003_03:
                $paymentCollection = new SepaCreditTransfer00100303($transferInfo, $this->checkAndSanitize, $this->sanitizeFlags);
                break;
            default:
                throw new SephpaInputException('You choose an invalid SEPA file version. Please use the SEPA_PAIN_001_* constants.');
        }
        $this->paymentCollections[] = $paymentCollection;
        
        return $paymentCollection;
    }

}
