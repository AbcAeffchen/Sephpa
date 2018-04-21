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

/**
 * Abstract class for credit transfer and debit
 */
interface SepaPaymentCollection
{
    /**
     * @param mixed[] $info         The input data defining the collection
     * @param bool    $check        All inputs will be checked and sanitized before creating
     *                              the collection. If you check the inputs yourself you can
     *                              set this to false.
     * @param int     $flags        The flags used for sanitizing
     */
    public function __construct(array $info, $check = true, $flags = 0);
    /**
     * Adds a new payment to the collection.
     * @see SepaCreditTransfer00100103::addPayment()
     * @see SepaCreditTransfer00100203::addPayment()
     * @see SepaCreditTransfer00100303::addPayment()
     * @see SepaDirectDebit00800102::addPayment()
     * @see SepaDirectDebit00800102Austrian003::addPayment()
     * @see SepaDirectDebit00800202::addPayment()
     * @see SepaDirectDebit00800302::addPayment()
     * @param mixed[] $paymentInfo
     * @return boolean
     */
    public function addPayment(array $paymentInfo);
    /**
     * Calculates the sum of all payments in this collection
     * @return float
     */
    public function getCtrlSum();
    /**
     * Counts the payments in this collection
     * @return int
     */
    public function getNumberOfTransactions();
    /**
     * Generates the xml for the collection using generatePaymentXml
     * @param \SimpleXMLElement $pmtInf The PmtInf-Child of the xml object
     * @return void
     */
    public function generateCollectionXml(\SimpleXMLElement $pmtInf);

    /**
     * Generate an array containing all data relevant to the file routing slip and control list.
     *
     * @param string $dateFormat @see date() for details.
     * @return string[]
     */
    public function getCollectionData($dateFormat);

    /**
     * Generate an array of arrays containing all transaction data relevant to the control list.
     *
     * @param string[] $moneyFormat Array containing the keys `currency`, `dec_point` and `thousands_sep`.
     * @return string[][]
     */
    public function getTransactionData(array $moneyFormat);

}
