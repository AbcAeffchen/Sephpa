<?php
/**
 * SEPA XML FILE GENERATOR
 *  
 * @license MIT License
 * @copyright © 2013 Alexander Schickedanz 
 * @link      http://abcaeffchen.net
 *
 * @author  Alexander Schickedanz <alex@abcaeffchen.net>
 */


/**
 * Abstract class for credit transfer and debit
 */
abstract class SepaPaymentCollection
{
    /**
     * @param mixed[] $info
     */
    abstract public function __construct(array $info);
    /**
     * Calculates the sum of all payments in this collection
     * @param mixed[] $paymentInfo
     * @return boolean
     */
    abstract public function addPayment(array $paymentInfo);
    /**
     * Calculates the sum of all payments in this collection
     * @return float
     */
    abstract public function getCtrlSum();
    /**
     * Counts the payments in this collection
     * @return int
     */
    abstract public function getNumberOfTransactions();
    /**
     * Generates the xml for the collection using generatePaymentXml
     * @param SimpleXMLElement $pmtInf The PmtInf-Child of the xml object
     * @return void
     */
    abstract public function generateCollectionXml(SimpleXMLElement $pmtInf);
    /**
     * Shortens a string $str down to a length of $len
     * @param $len int
     * @param $str string
     * @return string
     */
    public function shorten($len, $str)
    {
        return (strlen($str) < $len) ? $str : substr($str, 0, $len);
    }

}
