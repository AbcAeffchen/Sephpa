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

use AbcAeffchen\SepaUtilities\SepaUtilities;
use ZipArchive;

/**
 * Base class for both credit transfer and direct debit
 */
class SephpaMultiFile
{
    /**
     * @var Sephpa[] $files Stores the generated files.
     */
    private $files = [];

    /**
     * @param string $initgPty
     * @param string $msgId
     * @param int    $version Use SephpaCreditTransfer::SEPA_PAIN_* constants
     * @param array  $orgId
     * @param string $initgPtyId
     * @param bool   $checkAndSanitize
     * @return SephpaCreditTransfer|SephpaDirectDebit
     * @throws SephpaInputException
     */
    public function &addFile($initgPty, $msgId, $version, array $orgId = [], $initgPtyId = null, $checkAndSanitize = true) : Sephpa
    {
        $class = SepaUtilities::version2transactionType($version) === SepaUtilities::SEPA_TRANSACTION_TYPE_CT
            ? 'AbcAeffchen\Sephpa\SephpaCreditTransfer'
            : 'AbcAeffchen\Sephpa\SephpaDirectDebit';
        $this->files[] = new $class($initgPty, $msgId, $version, $orgId, $initgPtyId, $checkAndSanitize);
        return $this->files[count($this->files) - 1];
    }

    /**
     * @param string $filename The name of the file that will be downloaded, including extension.
     * @param array  $options @see Sephpa::generateOutput() for details.
     * @throws SephpaInputException
     * @throws \Mpdf\MpdfException
     */
    public function download(string $filename, array $options = []) : void
    {
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        print $this->generateCombinedZipFile($options);
    }

    /**
     * Generates a zip file containing all generated files.
     * @param string $path Full path including filename and extension.
     * @param array  $options @see Sephpa::generateOutput() for details.
     * @throws SephpaInputException
     * @throws \Mpdf\MpdfException
     */
    public function store($path, array $options = [])
    {
        $file = fopen($path, 'wb');
        fwrite($file, $this->generateCombinedZipFile($options));
        fclose($file);
    }

    /**
     * @param array $options    @see Sephpa::generateOutput() for details.
     * @return string The content of the zip file as a string.
     * @throws SephpaInputException
     * @throws \Mpdf\MpdfException
     */
    private function generateCombinedZipFile(array $options) : string
    {
        if(!class_exists('ZipArchive'))
            throw new SephpaInputException('You need the libzip extension (class ZipArchive) to zip multiple files.');

        $options['zipToOneFile'] = false;

        $zip = new ZipArchive();

        $tmpFile = tempnam(sys_get_temp_dir(), 'sephpa');
        if($zip->open($tmpFile, ZipArchive::OVERWRITE))
        {
            foreach($this->getFiles() as $file)
            {
                foreach($file->generateOutput($options) as $item)
                {
                    $zip->addFromString($item['name'], $item['data']);
                }
            }

            $zip->close();
        }

        return file_get_contents($tmpFile);
    }

    /**
     * @return Sephpa[]
     */
    public function getFiles(): array
    {
        return $this->files;
    }
}
