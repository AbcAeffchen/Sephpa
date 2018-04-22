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

namespace AbcAeffchen\Sephpa\PaymentCollections;

abstract class SepaCreditTransferCollection implements SepaPaymentCollection
{
    /**
     * @var string CCY Default currency
     */
    const CCY = 'EUR';
    /**
     * @type bool $sanitizeFlags
     */
    protected $checkAndSanitize = true;
    /**
     * @type int $sanitizeFlags
     */
    protected $sanitizeFlags = 0;
    /**
     * @var mixed[] $payments Saves all payments
     */
    protected $payments = [];
    /**
     * @var mixed[] $transferInfo Saves the transfer information for the collection.
     */
    protected $transferInfo;
    /**
     * @type string $dbtrIban The IBAN of the debtor
     */
    protected $dbtrIban;

    protected $today;

    /**
     * @param mixed[] $info         The input data defining the collection
     * @param bool    $check        All inputs will be checked and sanitized before creating
     *                              the collection. If you check the inputs yourself you can
     *                              set this to false.
     * @param int     $flags        The flags used for sanitizing
     */
    abstract public function __construct(array $info, $check = true, $flags = 0);

    /**
     * Adds a new payment to the collection.
     *
     * @see SepaCreditTransfer00100103::addPayment()
     * @see SepaCreditTransfer00100203::addPayment()
     * @see SepaCreditTransfer00100303::addPayment()
     * @param mixed[] $paymentInfo
     * @return boolean
     */
    abstract public function addPayment(array $paymentInfo);

    /**
     * Calculates the sum of all payments in this collection
     *
     * @return float
     */
    public function getCtrlSum()
    {
        $sum = 0;
        foreach($this->payments as $payment){
            $sum += $payment['instdAmt'];
        }

        return $sum;
    }

    /**
     * Counts the payments in this collection
     *
     * @return int
     */
    public function getNumberOfTransactions()
    {
        return count($this->payments);
    }

    /**
     * Generates the xml for the collection using generatePaymentXml
     *
     * @param \SimpleXMLElement $pmtInf The PmtInf-Child of the xml object
     * @return void
     */
    abstract public function generateCollectionXml(\SimpleXMLElement $pmtInf);

    /**
     * Generate an array of arrays containing all data relevant to the file routing slip and control list.
     *
     * @param string $dateFormat @see date() for details.
     * @return string[]
     */
    public function getCollectionData($dateFormat)
    {
        $data = ['due_date' => empty($this->transferInfo['reqdExctnDt'])
                     ? \DateTime::createFromFormat('Y-m-d', \AbcAeffchen\SepaUtilities\SepaUtilities::getDateWithOffset(1))->format($dateFormat)
                     : \DateTime::createFromFormat('Y-m-d', $this->transferInfo['reqdExctnDt'])->format($dateFormat),
                 'collection_reference' => $this->transferInfo['pmtInfId'],
                 'debtor_name' => $this->transferInfo['dbtr'],
                 'iban' => $this->transferInfo['iban']];

        if(!empty($this->transferInfo['bic']))
            $data['bic'] = $this->transferInfo['bic'];

        return $data;
    }

    /**
     * Generate an array of arrays containing all transaction data relevant to the control list.
     *
     * @param string[] $moneyFormat Array containing the keys `currency`, `dec_point` and `thousands_sep`.
     * @return \string[][]
     */
    public function getTransactionData(array $moneyFormat)
    {
        $transactionData = [];
        foreach($this->payments as $payment)
        {
            $tmp = ['creditor_name' => $payment['cdtr'],
                    'iban' => $payment['iban'],
                    'remittance_information' => isset($payment['rmtInf']) ? $payment['rmtInf'] : '',
                    'amount' => sprintf($moneyFormat['currency'],
                                        number_format($payment['instdAmt'], 2,
                                                      $moneyFormat['dec_point'],
                                                      $moneyFormat['thousands_sep']))
            ];

            if(isset($payment['bic']))
                $tmp['bic'] = $payment['bic'];

            $transactionData[] = $tmp;
        }

        return $transactionData;
    }
}