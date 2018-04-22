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
     * @param string   $initgPty
     * @param string   $msgId
     * @param int      $version Use SephpCreditTransfer::SEPA_PAIN_* constants
     * @param string[] $transferInfo
     * @param bool     $checkAndSanitize
     * @return SephpaCreditTransfer
     * @throws SephpaInputException
     */
    public function &addCreditTransferFile($initgPty, $msgId, $version, array $transferInfo, $checkAndSanitize = true)
    {
        $this->files[] = new SephpaCreditTransfer($initgPty, $msgId, $version, $transferInfo, $checkAndSanitize);
        return $this->files[count($this->files)-1];
    }

    /**
     * @param string   $initgPty
     * @param string   $msgId
     * @param int      $version Use SephpDirectDebit::SEPA_PAIN_* constants
     * @param string[] $debitInfo
     * @param bool     $checkAndSanitize
     * @return SephpaDirectDebit
     * @throws SephpaInputException
     */
    public function &addDirectDebitFile($initgPty, $msgId, $version, array $debitInfo, $checkAndSanitize = true)
    {
        $this->files[] = new SephpaDirectDebit($initgPty, $msgId, $version, $debitInfo, $checkAndSanitize);
        return $this->files[count($this->files)-1];
    }

    /**
     * @param array $options @see Sephpa::generateOutput() for details.
     * @throws SephpaInputException
     * @throws \Mpdf\MpdfException
     */
    public function download(array $options = [])
    {
        $file = $this->generateCombinedZipFile($options);

        header('Content-Disposition: attachment; filename="' . $file['name'] . '"');
        print $file['data'];
    }

    /**
     * @param string $path
     * @param array  $options @see Sephpa::generateOutput() for details.
     * @throws SephpaInputException
     * @throws \Mpdf\MpdfException
     */
    public function store($path, array $options = [])
    {
        $fileData = $this->generateCombinedZipFile($options);

        $file = fopen($path . DIRECTORY_SEPARATOR . $fileData['name'], 'wb');
        fwrite($file, $fileData['data']);
        fclose($file);
    }

    /**
     * @param array $options    @see Sephpa::generateOutput() for details
     * @return string[] [name, data]
     * @throws SephpaInputException
     * @throws \Mpdf\MpdfException
     */
    private function generateCombinedZipFile(array $options)
    {
        if(!class_exists('ZipArchive'))
            throw new SephpaInputException('You need the libzip extension (class ZipArchive) to zip multiple files.');

        $zip = new \ZipArchive();

        $tmpFile = tempnam(sys_get_temp_dir(), 'sephpa');
        if($zip->open($tmpFile, \ZipArchive::CREATE))
        {
            foreach($this->files as &$file)
            {
                foreach($file->generateOutput($options, false) as $item)
                {
                    $zip->addFromString($item['name'], $item['data']);
                }
            }

            $zip->close();
        }

        return ['name' => $this->getFileName() . '.zip',
                'data' => file_get_contents($tmpFile)];
    }

    private function getFileName()
    {
        return 'Sephpa.' . (new \DateTime())->format('Y-m-d-H-i-s');
    }
}
