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
    protected $payments = array();
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
     * @see SepaCreditTransfer00100203::addPayment()
     * @see SepaCreditTransfer00100303::addPayment()
     * @see SepaDirectDebit00800202::addPayment()
     * @see SepaDirectDebit00800302::addPayment()
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

}