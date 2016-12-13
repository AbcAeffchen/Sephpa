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

require_once __DIR__ . '/Sephpa.php';
use AbcAeffchen\SepaUtilities\SepaUtilities;
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

        $this->paymentType = 'CstmrDrctDbtInitn';

        switch($version)
        {
            case self::SEPA_PAIN_008_001_02:
                $this->xmlInitString = self::INITIAL_STRING_PAIN_008_001_02;
                $this->version = self::SEPA_PAIN_008_001_02;
                break;
            case self::SEPA_PAIN_008_001_02_AUSTRIAN_003:
                $this->xmlInitString = self::INITIAL_STRING_PAIN_008_001_02_AUSTRIAN_003;
                $this->version = self::SEPA_PAIN_008_001_02_AUSTRIAN_003;
                break;
            case self::SEPA_PAIN_008_002_02:
                $this->xmlInitString = self::INITIAL_STRING_PAIN_008_002_02;
                $this->version = self::SEPA_PAIN_008_002_02;
                break;
            case self::SEPA_PAIN_008_003_02:
                $this->xmlInitString = self::INITIAL_STRING_PAIN_008_003_02;
                $this->version = self::SEPA_PAIN_008_003_02;
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
        switch($this->version)
        {
            case self::SEPA_PAIN_008_001_02:
                $paymentCollection = new SepaDirectDebit00800102($debitInfo, $this->checkAndSanitize, $this->sanitizeFlags);
                break;
            case self::SEPA_PAIN_008_001_02_AUSTRIAN_003:
                $paymentCollection = new SepaDirectDebit00800102Austrian003($debitInfo, $this->checkAndSanitize, $this->sanitizeFlags);
                break;
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
        // direct debit file and multiple files have to be created
        if(!isset($options['unmixedFiles']) || $options['unmixedFiles'])
        {
            if(!class_exists('\\ZipArchive'))
                throw new SephpaInputException('You need the libzip extension (class ZipArchive) to download multiple files.');

            $tmpFile = tempnam(sys_get_temp_dir(), 'sephpa');
            $zip = new \ZipArchive();
            if($zip->open($tmpFile, \ZipArchive::CREATE))
            {
                foreach($this->generateMultiFileXml() as $xmlFile)
                {
                    $zip->addFromString($xmlFile[0] . '.xml', $xmlFile[1]);
                }

                $zip->close();

                // send headers for zip download
                header('Pragma: public');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Cache-Control: public');
                header('Content-Description: File Transfer');
                header('Content-type: application/octet-stream');
                header('Content-Disposition: attachment; filename="'
                   . str_replace('.xml', '', $filename . (strtolower(substr($filename, -4)) !== '.zip' ? '.zip' : '')) . '"');
                header('Content-Transfer-Encoding: binary');
                // make sure the file size isn't cached
                clearstatcache();
                header('Content-Length: ' . filesize($tmpFile));
                ob_end_flush();
                // output the file
                @readfile($tmpFile);
                unlink($tmpFile);
            }
        }
        else
        {
            parent::downloadSepaFile($filename, $options);
        }
    }

    /**
     * Generates the SEPA file and stores it on the server.
     *
     * @param string $filename The path and filename
     * @param array  $options Available options:
     *                        (bool) "correctlySortedFiles": Only available for direct debit files.
     *                                               If set to true, there will one file per
     *                                               collection be created. Defaults to true.
     *                        (bool) "storeAsZipFile": Stores all generated file in a zip file.
     *                        (bool) "addFileRoutingSlips": Adds file routing slips for every created
     *                                               SEPA file. Defaults to false.
     * @throws SephpaInputException
     */
    public function storeSepaFile($filename = 'payments.xml', $options = array())
    {
        $file = fopen($filename, 'wb');
        fwrite($file, $this->generateXml());
        fclose($file);
    }

}
