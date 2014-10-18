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

require_once 'Sephpa.php';

/**
 * Base class for both credit transfer and direct debit
 */
class SephpaDirectDebit extends Sephpa
{
    const SEPA_PAIN_008_002_02 = SepaUtilities::SEPA_PAIN_008_002_02;
    const SEPA_PAIN_008_003_02 = SepaUtilities::SEPA_PAIN_008_003_02;
    /**
     * @type string INITIAL_STRING_DD Initial sting for direct debit pain.008.002.02
     */
    const INITIAL_STRING_PAIN_008_002_02 = '<?xml version="1.0" encoding="UTF-8"?><Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.002.02" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:iso:std:iso:20022:tech:xsd:pain.008.002.02 pain.008.002.02.xsd"></Document>';
    /**
     * @type string INITIAL_STRING_DD Initial sting for direct debit pain.008.003.02
     */
    const INITIAL_STRING_PAIN_008_003_02 = '<?xml version="1.0" encoding="UTF-8"?><Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.003.02" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:iso:std:iso:20022:tech:xsd:pain.008.003.02 pain.008.003.02.xsd"></Document>';

    /**
     * Creates a SepaXmlFile object and sets the head data
     *
     * @param string $initgPty The name of the initiating party (max. 70 characters)
     * @param string $msgId    The unique id of the file
     * @param int    $type     Sets the type and version of the sepa file. Use the SEPA_PAIN_* constants
     * @param bool   $checkAndSanitize
     * @throws SephpaInputException
     */
    public function __construct($initgPty, $msgId, $type, $checkAndSanitize = true)
    {
        parent::__construct($initgPty, $msgId, $type, $checkAndSanitize);

        $this->xmlType = 'CstmrDrctDbtInitn';

        switch($type)
        {
            case self::SEPA_PAIN_008_002_02:
                $this->xml = simplexml_load_string(self::INITIAL_STRING_PAIN_008_002_02);
                $this->type = self::SEPA_PAIN_008_002_02;
                break;
            case self::SEPA_PAIN_008_003_02:
                $this->xml = simplexml_load_string(self::INITIAL_STRING_PAIN_008_003_02);
                $this->type = self::SEPA_PAIN_008_003_02;
                break;
            default:
                throw new SephpaInputException('You choose an invalid SEPA file version. Please use the SEPA_PAIN_008_* constants.');
        }
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
        switch($this->type)
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
