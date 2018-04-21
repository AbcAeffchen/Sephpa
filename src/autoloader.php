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

function sephpaAutoloader($class)
{
    $class = preg_replace('#AbcAeffchen\\\Sephpa\\\(PaymentCollections\\\)?([^\.]+)#', '$2', $class);

    switch($class)
    {
        case 'Sephpa':
        case 'SephpaCreditTransfer':
        case 'SephpaDirectDebit':
            $file = __DIR__ . DIRECTORY_SEPARATOR  . $class . '.php';
            break;
        default:
            $file = __DIR__ . DIRECTORY_SEPARATOR . 'payment-collections'
                . DIRECTORY_SEPARATOR . $class . '.php';
    }

    if(file_exists($file))
        require_once $file;
}

spl_autoload_register('AbcAeffchen\Sephpa\sephpaAutoloader');