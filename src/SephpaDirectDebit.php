<?php
/**
 * Sephpa
 *
 * @license   GNU LGPL v3.0 - For details have a look at the LICENSE file
 * @copyright ©2018 Alexander Schickedanz
 * @link      https://github.com/AbcAeffchen/Sephpa
 *
 * @author  Alexander Schickedanz <abcaeffchen@gmail.com>
 */

namespace AbcAeffchen\Sephpa;
use AbcAeffchen\SepaUtilities\SepaUtilities;

require_once __DIR__ . '/Sephpa.php';
/**
 * Base class for both credit transfer and direct debit
 */
class SephpaDirectDebit extends Sephpa
{
    // direct debits versions
    const SEPA_PAIN_008_001_02 = SepaUtilities::SEPA_PAIN_008_001_02;
    const SEPA_PAIN_008_001_02_AUSTRIAN_003 = SepaUtilities::SEPA_PAIN_008_001_02_AUSTRIAN_003;
    const SEPA_PAIN_008_002_02 = SepaUtilities::SEPA_PAIN_008_002_02;
    const SEPA_PAIN_008_003_02 = SepaUtilities::SEPA_PAIN_008_003_02;

    /**
     * @type string INITIAL_STRING_DD Initial sting for direct debit pain.008.001.02
     */
    const INITIAL_STRING_PAIN_008_001_02 = '<?xml version="1.0" encoding="UTF-8"?><Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02 pain.008.001.02.xsd"></Document>';
    /**
     * @type string INITIAL_STRING_DD Initial sting for direct debit pain.008.001.02.austrian.003
     */
    const INITIAL_STRING_PAIN_008_001_02_AUSTRIAN_003 = '<?xml version="1.0" encoding="UTF-8"?><Document xmlns="ISO:pain.008.001.02:APC:STUZZA:payments:003" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"></Document>';
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
     * @param string   $initgPty  The name of the initiating party (max. 70 characters)
     * @param string   $msgId     The unique id of the file
     * @param int      $version   Sets the type and version of the sepa file. Use the SEPA_PAIN_*
     *                            constants
     * @param array    $debitInfo Required keys: 'pmtInfId', 'lclInstrm', 'seqTp', 'reqdColltnDt', 'cdtr', 'iban', 'bic', 'ci';
     *                            optional keys: 'ccy', 'btchBookg', 'ctgyPurp', 'ultmtCdtr', 'reqdColltnDt'
     * @param string[] $orgId     It is not recommended to use this at all. If you have to use
     *                            this, the standard only allows one of the two. If you provide
     *                            both, options, both will be included in the SEPA file. So
     *                            only use this if you know what you do. Available keys:
     *                            - `id`: An Identifier of the organisation.
     *                            - `bob`: A BIC or BEI that identifies the organisation.
     * @param bool     $checkAndSanitize
     * @throws SephpaInputException
     */
    public function __construct($initgPty, $msgId, $version, array $debitInfo, array $orgId = [], $checkAndSanitize = true)
    {
        parent::__construct($initgPty, $msgId, $version, $orgId, $checkAndSanitize);

        $this->paymentType = 'CstmrDrctDbtInitn';

        switch($version)
        {
            case self::SEPA_PAIN_008_001_02:
                $this->xmlInitString = self::INITIAL_STRING_PAIN_008_001_02;
                $this->version = self::SEPA_PAIN_008_001_02;
                $this->paymentCollection = new PaymentCollections\SepaDirectDebit00800102($debitInfo, $this->checkAndSanitize, $this->sanitizeFlags);
                break;
            case self::SEPA_PAIN_008_001_02_AUSTRIAN_003:
                $this->xmlInitString = self::INITIAL_STRING_PAIN_008_001_02_AUSTRIAN_003;
                $this->version = self::SEPA_PAIN_008_001_02_AUSTRIAN_003;
                $this->paymentCollection = new PaymentCollections\SepaDirectDebit00800102Austrian003($debitInfo, $this->checkAndSanitize, $this->sanitizeFlags);
                break;
            case self::SEPA_PAIN_008_002_02:
                $this->xmlInitString = self::INITIAL_STRING_PAIN_008_002_02;
                $this->version = self::SEPA_PAIN_008_002_02;
                $this->paymentCollection = new PaymentCollections\SepaDirectDebit00800202($debitInfo, $this->checkAndSanitize, $this->sanitizeFlags);
                break;
            case self::SEPA_PAIN_008_003_02:
                $this->xmlInitString = self::INITIAL_STRING_PAIN_008_003_02;
                $this->version = self::SEPA_PAIN_008_003_02;
                $this->paymentCollection = new PaymentCollections\SepaDirectDebit00800302($debitInfo, $this->checkAndSanitize, $this->sanitizeFlags);
                break;
            default:
                throw new SephpaInputException('You choose an invalid SEPA file version. Please use the SEPA_PAIN_008_* constants.');
        }
    }

    /**
     * Adds a new collection of direct debits and sets main data
     *
     * @param array $paymentInfo @see \Sephpa\SepaDirectDebit*::addPayment() for details.
     * @throws SephpaInputException
     */
    public function addPayment(array $paymentInfo)
    {
        $this->paymentCollection->addPayment($paymentInfo);
    }

    /**
     * Generates a File Routing Slip and returns it as [name, data] array. Requires mPDF.
     *
     * @param array $options @see generateOutput() for details.
     * @return array A File Routing Slip and returns it as [name, data] array.
     * @throws \Mpdf\MpdfException
     */
    protected function getFileRoutingSlip(array $options)
    {
        $collectionData = $this->paymentCollection->getCollectionData($options['dateFormat']);

        $collectionData = array_merge($collectionData,
                                      ['file_name'              => $this->getFileName() . '.xml',
                                       'scheme_version'         => SepaUtilities::version2string($this->version),
                                       'payment_type'           => 'Direct Debit',
                                       'message_id'             => $this->msgId,
                                       'creation_date_time'     => $this->creationDateTime,
                                       'initialising_party'     => $this->initgPty,
                                       'number_of_transactions' => $this->paymentCollection->getNumberOfTransactions(),
                                       'control_sum'            => sprintf($options['moneyFormat']['currency'],
                                                                           number_format($this->paymentCollection->getCtrlSum(), 2,
                                                                                         $options['moneyFormat']['dec_point'],
                                                                                         $options['moneyFormat']['thousands_sep'])),
                                       'current_date'           => ( new \DateTime() )->format($options['dateFormat'])]
        );

        $template = empty($options['FRSTemplate'])
            ? __DIR__ . '/../templates/file_routing_slip_german.tpl'
            : $options['FRSTemplate'];

        return ['name' => $this->getFileName() . '.FileRoutingSlip.pdf',
                'data' => \AbcAeffchen\SepaDocumentor\FileRoutingSlip::createPDF($template, $collectionData)];
    }

    /**
     * Generates a Control List and returns it as [name, data] array. Requires mPDF.
     *
     * @param array $options @see generateOutput() for details.
     * @return array A Control List and returns it as [name, data] array.
     * @throws \Mpdf\MpdfException
     */
    protected function getControlList(array $options)
    {
        $transactions = $this->paymentCollection->getTransactionData($options['moneyFormat']);
        $collectionData = $this->paymentCollection->getCollectionData($options['dateFormat']);

        $collectionData = array_merge($collectionData,
                                      ['file_name'              => $this->getFileName() . '.xml',
                                       'message_id'             => $this->msgId,
                                       'creation_date_time'     => $this->creationDateTime,
                                       'number_of_transactions' => $this->paymentCollection->getNumberOfTransactions(),
                                       'control_sum'            => sprintf($options['moneyFormat']['currency'],
                                                                           number_format($this->paymentCollection->getCtrlSum(), 2,
                                                                                         $options['moneyFormat']['dec_point'],
                                                                                         $options['moneyFormat']['thousands_sep']))
                                      ]
        );

        $template = empty($options['CLTemplate'])
            ? __DIR__ . '/../templates/direct_debit_control_list_german.tpl'
            : $options['CLTemplate'];

        return ['name' => $this->getFileName() . '.ControlList.pdf',
                'data' => \AbcAeffchen\SepaDocumentor\ControlList::createPDF($template, $collectionData, $transactions)];
    }

    /**
     * Returns the prefix of the names of the generated files.
     * @return string The prefix of the names of the generated files.
     */
    protected function getFileName()
    {
        return 'Sephpa.DirectDebit.' . $this->msgId;
    }
}
