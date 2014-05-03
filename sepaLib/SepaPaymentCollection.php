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
 Abstract class for credit transfer and debit 
*/
abstract class SepaPaymentCollection
{
    /**
      calculates the sum of all payments in this collection
      @param pmtInfId string the id of this collection
      @param transferinfo mixed[]
      @return void|false
     */
    abstract public function __construct(array $info);
    /**
      calculates the sum of all payments in this collection
      @param $paymentInfo mixed[]
      @return boolean
     */
    abstract public function addPayment(array $paymentInfo);
    /**
      calculates the sum of all payments in this collection
      @return float
     */
    abstract public function getCtrlSum();
    /**
      counts the payments in this collection
      @return int
     */
    abstract public function getNumberOfTransactions();
    /**
      generates the xml for the collection using generatePaymentXml
      @param pmtInf xml the PmtInf-Child of the xml object
      @return void
     */
    abstract public function generateCollectionXml($pmtInf);
    /**
      shortens a string $str down to a lenght of $len
      @param $len int 
      @param $str string
      @return string
     */
    public function shorten($len, $str)
    {
        return (strlen($str) < $len) ? $str : substr($str, 0, $len);
    }

}

?>