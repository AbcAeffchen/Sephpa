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
class SephpaDirectDebit extends Sephpa
{
    /**
     * @type string INITIAL_STRING_DD Initial sting for direct debit pain.008.002.02
     */
    const XMLNS_PAIN_008_002_02 = 'urn:iso:std:iso:20022:tech:xsd:pain.008.002.02';
    /**
     * @type string INITIAL_STRING_DD Initial sting for direct debit pain.008.003.02
     */
    const XMLNS_PAIN_008_003_02 = 'urn:iso:std:iso:20022:tech:xsd:pain.008.003.02';
    /**
     * @type string $xmlContainerType
     */
    protected $xmlContainerType = 'MsgPain008';
    /**
     * Creates a SepaXmlFile object and sets the head data
     *
     * @param string $initgPty The name of the initiating party (max. 70 characters)
     * @param string $msgId    The unique id of the file
     * @param int    $version  Sets the type and version of the sepa file. Use the SEPA_PAIN_*
     *                         constants
     * @param bool   $checkAndSanitize
     * @throws SephpaInputException
     */
    public function __construct($initgPty, $msgId, $version, $checkAndSanitize = true)
    {
        parent::__construct($initgPty, $msgId, $version, $checkAndSanitize);

        $this->xmlType = 'CstmrDrctDbtInitn';

        switch($version)
        {
            case self::SEPA_PAIN_008_002_02:
                $this->xmlns = self::XMLNS_PAIN_008_002_02;
                break;
            case self::SEPA_PAIN_008_003_02:
                $this->xmlns = self::XMLNS_PAIN_008_003_02;
                break;
            default:
                throw new SephpaInputException('You choose an invalid SEPA file version. Please use the SEPA_PAIN_008_* constants.');
        }

        $this->version = $version;
        $this->xml = new \SimpleXMLElement(self::XML_DOCUMENT_TAG);
        $this->xml->addAttribute('xmlns', $this->xmlns);
    }

    /**
     * Adds a new collection of direct debits and sets main data
     *
     * @param mixed[] $debitInfo Required keys: 'pmtInfId', 'lclInstrm', 'seqTp', 'reqdColltnDt', 'cdtr', 'iban', 'bic', 'ci';
     *                           optional keys: 'ccy', 'btchBookg', 'ctgyPurp', 'ultmtCdtr', 'reqdColltnDt'
     * @throws SephpaInputException
     * @return SepaPaymentCollection
     */
    public function addCollection(array $debitInfo)
    {
        switch($this->version)
        {
            case self::SEPA_PAIN_008_002_02:
                $paymentCollection = new SepaDirectDebit00800202($debitInfo, $this->checkAndSanitize, $this->sanitizeFlags);
                break;
            case self::SEPA_PAIN_008_003_02:
                $paymentCollection = new SepaDirectDebit00800302($debitInfo, $this->checkAndSanitize, $this->sanitizeFlags);
                break;
            default:
                throw new SephpaInputException('You choose an invalid SEPA file version. Please use the SEPA_PAIN_008_* constants.');
        }

        $this->paymentCollections[] = $paymentCollection;
        
        return $paymentCollection;
    }

}
