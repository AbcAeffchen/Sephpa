<?php
/**
 * Sephpa
 *
 * @license   GNU LGPL v3.0 - For details have a look at the LICENSE file
 * @copyright ©2020 Alexander Schickedanz
 * @link      https://github.com/AbcAeffchen/Sephpa
 *
 * @author  Alexander Schickedanz <abcaeffchen@gmail.com>
 */

namespace AbcAeffchen\Sephpa;
use AbcAeffchen\SepaUtilities\SepaUtilities;
use AbcAeffchen\Sephpa\PaymentCollections\SepaPaymentCollection;

require_once __DIR__ . '/Sephpa.php';
/**
 * Base class for both credit transfer and direct debit
 */
class SephpaDirectDebit extends Sephpa
{
    // direct debits versions
    public const SEPA_PAIN_008_001_02 = SepaUtilities::SEPA_PAIN_008_001_02;
    public const SEPA_PAIN_008_001_02_AUSTRIAN_003 = SepaUtilities::SEPA_PAIN_008_001_02_AUSTRIAN_003;
    public const SEPA_PAIN_008_002_02 = SepaUtilities::SEPA_PAIN_008_002_02;
    public const SEPA_PAIN_008_003_02 = SepaUtilities::SEPA_PAIN_008_003_02;

    /**
     * @type string INITIAL_STRING_DD Initial sting for direct debit pain.008.001.02
     */
    private const INITIAL_STRING_PAIN_008_001_02 = '<?xml version="1.0" encoding="UTF-8"?><Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02 pain.008.001.02.xsd"></Document>';
    /**
     * @type string INITIAL_STRING_DD Initial sting for direct debit pain.008.001.02.austrian.003
     */
    private const INITIAL_STRING_PAIN_008_001_02_AUSTRIAN_003 = '<?xml version="1.0" encoding="UTF-8"?><Document xmlns="ISO:pain.008.001.02:APC:STUZZA:payments:003" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"></Document>';
    /**
     * @type string INITIAL_STRING_DD Initial sting for direct debit pain.008.002.02
     */
    private const INITIAL_STRING_PAIN_008_002_02 = '<?xml version="1.0" encoding="UTF-8"?><Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.002.02" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:iso:std:iso:20022:tech:xsd:pain.008.002.02 pain.008.002.02.xsd"></Document>';
    /**
     * @type string INITIAL_STRING_DD Initial sting for direct debit pain.008.003.02
     */
    private const INITIAL_STRING_PAIN_008_003_02 = '<?xml version="1.0" encoding="UTF-8"?><Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.003.02" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:iso:std:iso:20022:tech:xsd:pain.008.003.02 pain.008.003.02.xsd"></Document>';

    private const VERSIONS = [self::SEPA_PAIN_008_001_02              => ['class'   => '00800102',
                                                                          'initStr' => self::INITIAL_STRING_PAIN_008_001_02],
                              self::SEPA_PAIN_008_001_02_AUSTRIAN_003 => ['class'   => '00800102Austrian003',
                                                                          'initStr' => self::INITIAL_STRING_PAIN_008_001_02_AUSTRIAN_003],
                              self::SEPA_PAIN_008_002_02              => ['class'   => '00800202',
                                                                          'initStr' => self::INITIAL_STRING_PAIN_008_002_02],
                              self::SEPA_PAIN_008_003_02              => ['class'   => '00800302',
                                                                          'initStr' => self::INITIAL_STRING_PAIN_008_003_02]];

    /**
     * Creates a SepaXmlFile object and sets the head data
     *
     * @param string   $initgPty   The name of the initiating party (max. 70 characters)
     * @param string   $msgId      The unique id of the file
     * @param int      $version    Sets the type and version of the sepa file. Use the SEPA_PAIN_*
     *                             constants
     * @param string[] $orgId      It is not recommended to use this at all. If you have to use
     *                             this, the standard only allows one of the two. If you provide
     *                             both, options, both will be included in the SEPA file. So
     *                             only use this if you know what you do. Available keys:
     *                             - `id`: An Identifier of the organisation.
     *                             - `bob`: A BIC or BEI that identifies the organisation.
     * @param string   $initgPtyId An ID of the initiating party (max. 35 characters)
     * @param bool     $checkAndSanitize
     * @throws SephpaInputException
     */
    public function __construct($initgPty, $msgId, int $version, array $orgId = [], $initgPtyId = null, $checkAndSanitize = true)
    {
        if($version === self::SEPA_PAIN_008_001_02_AUSTRIAN_003 && $initgPtyId !== null)
            throw new SephpaInputException('$initgPtyId is not supported by pain.008.001.02.austrian.003.');

        parent::__construct($initgPty, $msgId, $orgId, $initgPtyId, $checkAndSanitize);

        $this->paymentType = 'CstmrDrctDbtInitn';

        if(!isset(self::VERSIONS[$version]))
            throw new SephpaInputException('You choose an invalid SEPA file version. Please use the SEPA_PAIN_008_* constants.');

        $this->version = $version;
        $this->xmlInitString = self::VERSIONS[$version]['initStr'];
    }

    /**
     * Adds a new collection of direct debits and sets main data
     *
     * @param array $collectionInfo @see \Sephpa\SepaDirectDebit*::addPayment() for details.
     * @return SepaPaymentCollection
     */
    public function addCollection(array $collectionInfo) : SepaPaymentCollection
    {
        $class = 'AbcAeffchen\Sephpa\PaymentCollections\SepaDirectDebit' . self::VERSIONS[$this->version]['class'];
        $this->paymentCollections[] = new $class($collectionInfo, $this->checkAndSanitize, $this->sanitizeFlags);
        return end($this->paymentCollections);
    }
}
