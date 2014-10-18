<?php
/**
 * SEPA XML FILE GENERATOR
 *  
 * @license MIT License
 * @copyright © 2014 Alexander Schickedanz
 * @link      http://abcaeffchen.net
 *
 * @author  Alexander Schickedanz <alex@abcaeffchen.net>
 */

namespace AbcAeffchen\Sephpa;

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
     * Calculates the sum of all payments in this collection
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

}
