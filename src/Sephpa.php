<?php
/**
 * Sephpa
 *
 * @license   GNU LGPL v3.0 - For details have a look at the LICENSE file
 * @copyright ©2017 Alexander Schickedanz
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
     * @type string $creationDateTime The date time string of the creation date.
     */
    protected $creationDateTime;
    /**
     * @type SepaPaymentCollection $paymentCollection Stores all payment objects
     */
    protected $paymentCollection;
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
        $this->creationDateTime = (new \DateTime())->format('Y-m-d\TH:i:s');

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
     * Adds a new payment to the SEPA file.
     *
     * @param mixed[] $information An array with information about the payment.
     * @throws SephpaInputException
     */
    abstract protected function addPayment(array $information);

    /**
     * Generates the XML file from the given data. All empty collections are skipped.
     *
     * @throws SephpaInputException
     * @return string Just the xml code of the file
     */
    protected function generateXml()
    {
        if($this->paymentCollection->getNumberOfTransactions() === 0)
            throw new SephpaInputException('The file contains no payments.');

        $xml = simplexml_load_string($this->xmlInitString);
        $fileHead = $xml->addChild($this->paymentType);

        $grpHdr = $fileHead->addChild('GrpHdr');
        $grpHdr->addChild('MsgId', $this->msgId);
        $grpHdr->addChild('CreDtTm', $this->creationDateTime);
        $grpHdr->addChild('NbOfTxs', $this->paymentCollection->getNumberOfTransactions());
        $grpHdr->addChild('CtrlSum', sprintf('%01.2f', $this->paymentCollection->getCtrlSum()));
        $grpHdr->addChild('InitgPty')->addChild('Nm', $this->initgPty);
        // todo add InitgPty > OrgId block support. here it is either bic or bei or an ID (TEXT35)
        // todo test if it is valid to provied both information. Add notice that it is not recommended to use this at all.
        // information is on page 42 DFÜ V3.0

        $pmtInf = $fileHead->addChild('PmtInf');
        $this->paymentCollection->generateCollectionXml($pmtInf);

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
    protected function sanitizeOutputOptions(array $options)
    {
        // sanitize options
        $options['addFileRoutingSlip'] = isset($options['addFileRoutingSlip']) && $options['addFileRoutingSlip'];
        $options['addControlList']     = isset($options['addControlList']) && $options['addControlList'];

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
     * @param array $options   possible fields:
     *                         - (bool) `addFileRoutingSlip`: If true, a file routing slip will be
     *                           added. Default ist false.
     *                         - (string) `FRSTemplate`: The path to the template for the file routing
     *                           slip. Default is the bundled file routing slip (german version).
     *                         - (bool) `addControlList`: If true, a control list will be added.
     *                           Default is false.
     *                         - (string) `CLTemplate`: The path to the template for this control
     *                           list. Default is the bundled control list template for either credit
     *                           transfer of direct debit (german version).
     *                         - (string[]) `moneyFormat`: Used to format amounts of money using
     *                           sprintf() and number_format(). The array needs to have the following keys:
     *                           `dec_point` (default is ','), `thousands_sep` (default is '.') and
     *                           `currency` (default is '%s €')
     *                         - (string) `dateFormat`: The format a date is represented in the PDF
     *                           files. Default is 'd.m.Y'. See date() documentation for details.
     * @return string[]
     * @throws SephpaInputException
     * @throws \Mpdf\MpdfException
     */
    protected function generateOutput(array $options)
    {
        $options = $this->sanitizeOutputOptions($options);

        $files = [];
        $files[] = ['name' => $this->getFileName() . '.xml',
                    'data' => $this->generateXml()];

        if($options['addFileRoutingSlip'])
            $files[] = $this->getFileRoutingSlip($options);

        if($options['addControlList'])
            $files[] = $this->getControlList($options);

        // multiple files need to be joint to one zip file.
        if(count($files) > 1)
        {
            if(!class_exists('ZipArchive'))
                throw new SephpaInputException('You need the libzip extension (class ZipArchive) to download multiple files.');

            $tmpFile = tempnam(sys_get_temp_dir(), 'sephpa');
            $zip = new \ZipArchive();
            if($zip->open($tmpFile, \ZipArchive::CREATE))
            {
                foreach($files as $file)
                {
                    $zip->addFromString($file['name'], $file['data']);
                }

                $zip->close();
            }

            return [$this->getFileName() . '.zip', file_get_contents($tmpFile)];
        }
        else
        {
            return $files[0];
        }
    }

    /**
     * Generates a File Routing Slip and returns it as [name, data] array. Requires mPDF.
     *
     * @param array $options @see generateOutput() for details.
     * @return array A File Routing Slip and returns it as [name, data] array.
     * @throws \Mpdf\MpdfException
     */
    protected abstract function getFileRoutingSlip(array $options);

    /**
     * Generates a Control List and returns it as [name, data] array. Requires mPDF.
     *
     * @param array $options @see generateOutput() for details.
     * @return array A Control List and returns it as [name, data] array.
     * @throws \Mpdf\MpdfException
     */
    protected abstract function getControlList(array $options);

    /**
     * Returns the name prefix of the generated files.
     * @return string The name prefix of the generated files.
     */
    protected abstract function getFileName();

    /**
     * Generates the SEPA file and starts a download using the header 'Content-Disposition: attachment;'
     * The file will not stored on the server.
     *
     * @param array $options @see generateOutput() for details.
     * @throws SephpaInputException
     * @throws \Mpdf\MpdfException
     */
    public function download($options = array())
    {
        $file = $this->generateOutput($options);

        header('Content-Disposition: attachment; filename="' . $file['name'] . '"');
        print $file['data'];
    }

    /**
     * Generates the SEPA file and stores it on the server.
     *
     * @param string $path    The path where the file gets stored without trailing "/".
     * @param array  $options @see generateOutput() for details.
     * @throws SephpaInputException
     * @throws \Mpdf\MpdfException
     */
    public function store($path, $options = array())
    {
        $fileData = $this->generateOutput($options);

        $file = fopen($path . '/' . $fileData[0], 'wb');
        fwrite($file, $fileData[1]);
        fclose($file);
    }
}
