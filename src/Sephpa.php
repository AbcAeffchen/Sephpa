<?php
/*
 * Sephpa
 *
 * @license   GNU LGPL v3.0 - For details have a look at the LICENSE file
 * @copyright ©2021 Alexander Schickedanz
 * @link      https://github.com/AbcAeffchen/Sephpa
 *
 * @author  Alexander Schickedanz <abcaeffchen@gmail.com>
 */

namespace AbcAeffchen\Sephpa;

use AbcAeffchen\SepaDocumentor\ControlList;
use AbcAeffchen\SepaDocumentor\FileRoutingSlip;
use AbcAeffchen\SepaUtilities\SepaUtilities;
use DateTime;
use ZipArchive;

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
    private const DEFAULT_FILENAME_TEMPLATE = '%msgId%';
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
     * @type string $initgPtyId Id of the party that initiates the transfer (optional and not recommended)
     */
    protected $initgPtyId;
    /**
     * @type string $msgId identify the Sepa file (unique id for all files)
     */
    protected $msgId;
    /**
     * @type array $orgId
     */
    protected $orgId;
    /**
     * @type string $creationDateTime The date time string of the creation date.
     */
    protected $creationDateTime;
    /**
     * @type PaymentCollections\SepaPaymentCollection[] $paymentCollections Stores all payment objects
     */
    protected $paymentCollections = [];
    /**
     * @type bool $checkAndSanitize
     */
    protected $checkAndSanitize;
    /**
     * @type int $sanitizeFlags
     */
    protected $sanitizeFlags = 0;

    /**
     * Creates a SepaXmlFile object and sets the head data.
     *
     * @param string   $initgPty   The name of the initiating party (max. 70 characters)
     * @param string   $msgId      The unique id of the file
     * @param string[] $orgId      It is not recommended to use this at all. If you have to use
     *                             this, the standard only allows one of the two keys.
     *                             Only use this if you know what you do. Available keys:
     *                             - `id`: An Identifier of the organisation.
     *                             - `bob`: A BIC or BEI that identifies the organisation.
     * @param string   $initgPtyId An ID of the initiating party (max. 35 characters)
     * @param bool     $checkAndSanitize
     * @throws SephpaInputException
     */
    protected function __construct($initgPty, $msgId, array $orgId = [], $initgPtyId = null, $checkAndSanitize = true)
    {
        if(isset($orgId['id'], $orgId['bob']))
            throw new SephpaInputException('You cannot use orgid[id] and orgid[bob] simultaneously.');

        $this->checkAndSanitize = $checkAndSanitize;
        $this->creationDateTime = (new DateTime())->format('Y-m-d\TH:i:s');

        if($this->checkAndSanitize)
        {
            $this->initgPty   = SepaUtilities::checkAndSanitize('initgpty', $initgPty);
            $this->initgPtyId = $initgPtyId === null ? null : SepaUtilities::checkAndSanitize('initgptyid', $initgPtyId);
            $this->msgId      = SepaUtilities::checkAndSanitize('msgid', $msgId);
            $this->orgId      = ['id' => isset($orgId['id']) ? SepaUtilities::checkAndSanitize('orgid_id', $orgId['id']) : '',
                                 'bob' =>isset($orgId['bob']) ? SepaUtilities::checkAndSanitize('orgid_bob', $orgId['bob']) : ''];

            if($this->initgPty === false || $this->initgPtyId === false || $this->msgId === false ||
                (!empty($this->orgId) && ($this->orgId['id'] === false || $this->orgId['bob'] === false)))
                throw new SephpaInputException('The input was invalid and couldn\'t be sanitized.');
        }
        else
        {
            $this->initgPty   = $initgPty;
            $this->initgPtyId = $initgPtyId;
            $this->msgId      = $msgId;
            $this->orgId      = ['id'  => isset($orgId['id']) ? $orgId['id'] : '',
                                 'bob' => isset($orgId['bob']) ? $orgId['bob'] : ''];
        }
    }

    /**
     * This flags will only be used if checkAndSanitize is set to true.
     * @param int $flags Use the SepaUtilities Flags
     */
    public function setSanitizeFlags($flags) : void
    {
        $this->sanitizeFlags = $flags;
    }

    /**
     * Adds a new collection and sets main data.
     *
     * @param mixed[] $information An array with information about the collection.
     * @throws SephpaInputException
     */
    abstract protected function addCollection(array $information);

    /**
     * Generates the XML string from the given data. All empty collections are skipped.
     *
     * @throws SephpaInputException
     * @return string Just the xml code of the file
     */
    protected function generateXml() : string
    {
        if(count($this->paymentCollections) === 0)
            throw new SephpaInputException('No payment collections provided.');

        $totalNumberOfTransaction = $this->getNumberOfTransactions();
        if($totalNumberOfTransaction === 0)
            throw new SephpaInputException('No payments provided.');

        $xml = simplexml_load_string($this->xmlInitString);
        $fileHead = $xml->addChild($this->paymentType);

        $grpHdr = $fileHead->addChild('GrpHdr');
        $grpHdr->addChild('MsgId', $this->msgId);
        $grpHdr->addChild('CreDtTm', $this->creationDateTime);
        $grpHdr->addChild('NbOfTxs', $totalNumberOfTransaction);
        $grpHdr->addChild('CtrlSum', sprintf('%01.2F', $this->getCtrlSum()));

        $initgPty = $grpHdr->addChild('InitgPty');
        $initgPty->addChild('Nm', $this->initgPty);

        if($this->initgPtyId !== null || !empty($this->orgId['bob']) || !empty($this->orgId['id']))
            $initgPty->addChild('Id');

        if($this->initgPtyId !== null)
            $initgPty->Id->addChild('PrvtId')->addChild('Othr')->addChild('Id', $this->initgPtyId);

        if(!empty($this->orgId['bob']) || !empty($this->orgId['id']))
        {
            $orgId = $initgPty->Id->addChild('OrgId');
            if(!empty($this->orgId['id']))
            {
                $initgPtyOthr = $orgId->addChild('Othr');
                $initgPtyOthr->addChild('Id', $this->orgId['id']);
                $initgPtyOthr->addChild('SchmeNm', '')->addChild("Prtry", "SEPA");
            }
            if(!empty($this->orgId['bob']))
                $orgId->addChild('BICOrBEI', $this->orgId['bob']);
        }

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
     * This function sets the default values for the option fields if not set already and checks the
     * resulting dependencies if any. If a dependency is missing a SephpaInputException is thrown.
     *
     * @param array $options @see generateOutput() for details.
     * @return bool[] The $options array with all three fields set.
     * @throws SephpaInputException
     */
    protected function sanitizeOutputOptions(array $options) : array
    {
        // sanitize options
        $options['addFileRoutingSlip'] = isset($options['addFileRoutingSlip']) && $options['addFileRoutingSlip'];
        $options['addControlList']     = isset($options['addControlList']) && $options['addControlList'];
        $options['zipToOneFile']       = isset($options['zipToOneFile']) && $options['zipToOneFile'];

        if(!isset($options['filenameTemplate']))
            $options['filenameTemplate'] = self::DEFAULT_FILENAME_TEMPLATE;

        // check dependencies
        if(($options['addFileRoutingSlip'] || $options['addControlList'])
            && !class_exists('\\AbcAeffchen\\SepaDocumentor\\BasicDocumentor'))
            throw new SephpaInputException('You need to install SepaDocumentor to be able to add File Routing Slips or Control Lists.');

        if(empty($options['dateFormat']))
            $options['dateFormat'] = 'd.m.Y';

        if(!isset($options['moneyFormat']) || !is_array($options['moneyFormat']))
            $options['moneyFormat'] = ['dec_point' => ',', 'thousands_sep' => '.', 'currency' => '%s €'];
        elseif(!isset($options['moneyFormat']['dec_point'])
               || !isset($options['moneyFormat']['thousands_sep'])
               || !isset($options['moneyFormat']['currency']))
            throw new SephpaInputException('The money format needs to have all the keys "dec_point", "thousands_sep" and "currency"');

        return $options;
    }

    /**
     * @param array $options possible fields:
     *                       - (string) `filenameTemplate`: The template used for filenames.
     *                       It can contain the placeholders `%msgId%` and `%initgPty%`. (default is '%msgId%')
     *                       - (bool) `addFileRoutingSlip`: If true, a file routing slip will be
     *                       added. Default ist false.
     *                       - (string) `FRSTemplate`: The path to the template for the file routing
     *                       slip. Default is the bundled file routing slip (german version).
     *                       - (bool) `addControlList`: If true, a control list will be added.
     *                       Default is false.
     *                       - (string) `CLTemplate`: The path to the template for this control
     *                       list. Default is the bundled control list template for either credit
     *                       transfer of direct debit (german version).
     *                       - (string[]) `moneyFormat`: Used to format amounts of money using
     *                       sprintf() and number_format(). The array needs to have the following keys:
     *                       `dec_point` (default is ','), `thousands_sep` (default is '.') and
     *                       `currency` (default is '%s €')
     *                       - (string) `dateFormat`: The format a date is represented in the PDF
     *                       files. Default is 'd.m.Y'. See date() documentation for details.
     *                       - (bool) `zipToOneFile`: If true, multiple files get zipped to one file.
     * @return string[][]    Returns a a pair [name, data] for each file
     * @throws SephpaInputException
     * @throws \Mpdf\MpdfException
     */
    public function generateOutput(array $options = []) : array
    {
        $options = $this->sanitizeOutputOptions($options);

        $files = [];
        $files[] = ['name' => $this->getFileName($options['filenameTemplate']) . '.xml',
                    'data' => $this->generateXml()];

        if($options['addFileRoutingSlip'])
            $files = array_merge($files, $this->getFileRoutingSlips($options));

        if($options['addControlList'])
            $files = array_merge($files, $this->getControlLists($options));

        if(!$options['zipToOneFile'])
            return $files;

        // multiple files need to be joint to one zip file.
        if(!class_exists('ZipArchive'))
            throw new SephpaInputException('You need the libzip extension (class ZipArchive) to download multiple files.');

        $tmpFile = tempnam(sys_get_temp_dir(), 'sephpa');
        $zip = new ZipArchive();
        if($zip->open($tmpFile, ZipArchive::OVERWRITE))
        {
            foreach($files as $file)
            {
                $zip->addFromString($file['name'], $file['data']);
            }

            $zip->close();
        }

        return [['name' => $this->getFileName($options['filenameTemplate']) . '.zip',
                'data' => file_get_contents($tmpFile)]];
    }

    /**
     * Generates a File Routing Slip for each collection and returns it as array of [name, data_string]
     * arrays. Requires mPDF.
     *
     * @param array $options @see generateOutput() for details.
     * @return array A File Routing Slip and returns it as array of [name, data_string] arrays.
     * @throws \Mpdf\MpdfException
     */
    protected function getFileRoutingSlips(array $options) : array
    {
        $template = empty($options['FRSTemplate'])
            ? __DIR__ . '/../templates/file_routing_slip_german.tpl'
            : $options['FRSTemplate'];

        $fileRoutingSlips = [];

        foreach($this->paymentCollections as $paymentCollection)
        {
            $collectionData = array_merge($paymentCollection->getCollectionData($options['dateFormat']),
                ['file_name'              => $this->getFileName($options['filenameTemplate']) . '.xml',
                 'scheme_version'         => SepaUtilities::version2string($this->version),
                 'payment_type'           => SepaUtilities::version2transactionType($this->version) === SepaUtilities::SEPA_TRANSACTION_TYPE_CT ? 'Credit Transfer' : 'Direct Debit',
                 'message_id'             => $this->msgId,
                 'creation_date_time'     => $this->creationDateTime,
                 'initialising_party'     => $this->initgPty,
                 'number_of_transactions' => $paymentCollection->getNumberOfTransactions(),
                 'control_sum'            => sprintf($options['moneyFormat']['currency'],
                                                     number_format($paymentCollection->getCtrlSum(), 2,
                                                                   $options['moneyFormat']['dec_point'],
                                                                   $options['moneyFormat']['thousands_sep'])),
                 'current_date'           => (new DateTime())->format($options['dateFormat'])]);

            $fileRoutingSlips[] = ['name' => $this->getFileName($options['filenameTemplate'])
                                            . '.' . str_replace(['\\', '/'], '-', $collectionData['collection_reference'])
                                            . '.FileRoutingSlip.pdf',
                                   'data' => FileRoutingSlip::createPDF($template, $collectionData)];
        }

        return $fileRoutingSlips;
    }

    /**
     * Generates a Control List for each collection and returns it as array of [name, data_string]
     * arrays. Requires mPDF.
     *
     * @param array $options @see generateOutput() for details.
     * @return array A Control List for each collection and returns it as array of [name, data_string] arrays.
     * @throws \Mpdf\MpdfException
     */
    protected function getControlLists(array $options) : array
    {
        $template = $options['CLTemplate']
            ?? (SepaUtilities::version2transactionType($this->version) === SepaUtilities::SEPA_TRANSACTION_TYPE_CT
                ? __DIR__ . '/../templates/credit_transfer_control_list_german.tpl'
                : __DIR__ . '/../templates/direct_debit_control_list_german.tpl');

        $controlLists = [];

        foreach($this->paymentCollections as $paymentCollection)
        {
            $collectionData = array_merge($paymentCollection->getCollectionData($options['dateFormat']),
                ['file_name'              => $this->getFileName($options['filenameTemplate']) . '.xml',
                 'message_id'             => $this->msgId,
                 'creation_date_time'     => $this->creationDateTime,
                 'number_of_transactions' => $paymentCollection->getNumberOfTransactions(),
                 'control_sum'            => sprintf($options['moneyFormat']['currency'],
                                                     number_format($paymentCollection->getCtrlSum(), 2,
                                                                   $options['moneyFormat']['dec_point'],
                                                                   $options['moneyFormat']['thousands_sep']))]);

            $controlLists[] = ['name' => $this->getFileName($options['filenameTemplate'])
                                        . '.' . str_replace(['\\', '/'], '-', $collectionData['collection_reference'])
                                        . '.ControlList.pdf',
                               'data' => ControlList::createPDF($template, $collectionData,
                                                                $paymentCollection->getTransactionData($options['moneyFormat']))];
        }

        return $controlLists;
    }

    /**
     * Returns the name prefix of the generated files.
     *
     * @param string $template The template used to create the filename. It must not have a file
     *                         extension, since it depends on the file created. It supports the
     *                         following placeholders:
     *                         - '%msgId%': The message ID of the file.
     *                         - '%initgPty%': The initiator of the file.
     *                         Note: Symbols that cannot be part of a filename are replaced by a hyphen (-).
     * @return string The name prefix of the generated files.
     */
    protected final function getFileName(string $template) : string
    {
        return strtr($template, ['%msgId%' => str_replace(['\\', '/'], '-', $this->msgId),
                                 '%initgPty%' => str_replace(['\\', '/'], '-', $this->initgPty)]);
    }

    /**
     * Generates the SEPA file and starts a download using the header 'Content-Disposition: attachment;'
     * The file will not stored on the server.
     *
     * @param array $options @see generateOutput() for details. zipToOneFile is forced to be true
     *                       if the settings lead to multiple files.
     * @throws SephpaInputException
     * @throws \Mpdf\MpdfException
     */
    public function download($options = [])
    {
        if(($options['addControlList'] ?? false) || ($options['addFileRoutingSlip'] ?? false))
            $options['zipToOneFile'] = true;

        $file = $this->generateOutput($options)[0];

        header('Content-Disposition: attachment; filename="' . $file['name'] . '"');
        print $file['data'];
    }

    /**
     * Generates the SEPA file and stores it on the server.
     *
     * @param string $path    The path where the file gets stored without trailing DIRECTORY_SEPARATOR.
     * @param array  $options @see generateOutput() for details.
     * @throws SephpaInputException
     * @throws \Mpdf\MpdfException
     */
    public function store($path, $options = [])
    {
        $files = $this->generateOutput($options);

        foreach($files as $fileData)
        {
            $file = fopen($path . DIRECTORY_SEPARATOR . $fileData['name'], 'wb');
            fwrite($file, $fileData['data']);
            fclose($file);
        }
    }

    /**
     * Calculates the sum of all payments
     * @return float
     */
    private function getCtrlSum() : float
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
    private function getNumberOfTransactions() : int
    {
        $nbOfTxs = 0;
        foreach($this->paymentCollections as $collection){
            $nbOfTxs += $collection->getNumberOfTransactions();
        }

        return $nbOfTxs;
    }
}
