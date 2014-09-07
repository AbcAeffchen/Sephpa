<?php


class SepaUtilities
{
    /**
     * Checks if an iban is valid. Note that also if the iban is valid it does not have to exist
     * @param string $iban
     * @return string|false The valid iban or false if it is not valid
     */
    public static function checkIBAN( $iban )
    {
        $alph =         array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J',
                         'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T',
                         'U', 'V', 'W', 'X', 'Y', 'Z');
        $alphValues =  array( 10,  11,  12,  13,  14,  15,  16,  17,  18,  19,
                         20,  21,  22,  23,  24,  25,  26,  27,  28,  29,
                         30,  31,  32,  33,  34,  35);

        $mod97 = array(1, 10, 3, 30, 9, 90, 27, 76, 81, 34, 49, 5, 50, 15, 53, 45, 62, 38,
                  89, 17, 73, 51, 25, 56, 75, 71, 31, 19, 93, 57, 85, 74, 61, 28, 86,
                  84, 64, 58, 95, 77, 91, 37, 79, 14, 43, 42, 32, 29, 96, 87, 94, 67,
                  88, 7, 70, 21, 16, 63, 48, 92, 47, 82, 44, 52, 35, 59, 8, 80, 24);

        $iban = str_replace( ' ', '' , $iban );     // remove whitespaces
        $iban = strtoupper($iban);

        if(!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{1,30}$/',$iban))
            return false;

        $ibanCopy = $iban;
        $iban = $check = str_replace($alph, $alphValues, $iban);

        $bban = substr($iban, 6);
        $check = substr($iban, 0,6);

        $concat = $bban . $check;

        $checksum = 0;
        $len = strlen($concat);
        for($i = 1; $i  <= $len; $i++)
        {
            if(filter_var($concat[$len-$i], FILTER_VALIDATE_INT) === false)
                return false;
            $checksum = (($checksum + $mod97[$i-1]*$concat[$len-$i]) % 97);
        }

        if($checksum == 1)
            return $ibanCopy;
        else
            return false;
    }

    /**
     * Checks if a bic is valid. Note that also if the bic is valid it does not have to exist
     * @param string $bic
     * @return string|false the valid bic or false if it is not valid
     */
    public static function checkBIC($bic)
    {
        $bic = str_replace( ' ', '' , $bic );       // remove whitespaces
        $bic = strtoupper($bic);                    // use only capital letters

        if(preg_match('/^[A-Z]{6}[A-Z2-9][A-NP-Z0-9]([A-Z0-9]{3}){0,1}$/', $bic))
            return $bic;
        else
            return false;
    }

    /**
     * Reformat a date string from a given format to the ISODate format.
     * @param string $date A date string of the given input format
     * @param string $inputFormat default is the german format DD.MM.YYYY
     * @return string date as YYYY-MM-DD
     */
    public static function formatDate( $date , $inputFormat = 'd.m.Y')
    {
        return DateTime::createFromFormat($inputFormat, $date)->format('Y-m-d');
    }

    /**
     * Checks if the input holds for the field.
     * @param string $field Valid fields are: 'pmtinfid', 'dbtr', 'iban', 'bic', 'ccy', 'btchbookg',
     *                      'ultmtdebtr', 'pmtId', 'instdamt', 'cdtr', 'ultmtcdrt', 'rmtinf', 'ci'
     * @param mixed $input
     * @return bool|string
     */
    public static function check( $field, $input )
    {
        $field = strtolower($field);
        switch($field)
        {
            case 'ci': return self::checkRestrictedPersonIdentifierSEPA($input);
            case 'pmtId':   // next line
            case 'pmtinfid': return self::checkRestrictedIdentificationSEPA1($input);
            case 'cdtr':
            case 'ultmtcdrt':
            case 'ultmtdebtr':  // next line
            case 'dbtr': return self::checkLength($input, 70) && self::checkCharset($input);
            case 'rmtinf': return self::checkLength($input, 140) && self::checkCharset($input);
            case 'iban': return self::checkIBAN($input);
            case 'bic': return self::checkBIC($input);
            case 'ccy': return self::checkActiveOrHistoricCurrencyCode($input);
            case 'btchbookg': return self::checkBatchBookingIndicator($input);
            case 'instdamt': return self::checkAmountFormat($input);
            default: return false;
        }
    }

    /**
     * Tries to sanitize the the input so it fits in the field.
     * @param string $field Valid fields are: 'cdtr', 'dbtr', 'rmtInf', 'ultmtCdrt', 'ultmtDebtr'
     * @param mixed $input
     * @return mixed|false The sanitized input or false if the input is not sanitizeable.
     */
    public static function sanitize( $field, $input )
    {
        $field = strtolower($field);
        switch($field)
        {
            case 'cdtr':
            case 'ultmtCdrt':
            case 'ultmtDebtr':  // next line
            case 'dbtr': return self::sanitizeLength(self::replaceSpecialChars($input), 70);
            case 'rmtInf': return self::sanitizeLength(self::replaceSpecialChars($input), 140);
            default: return false;
        }
    }

    /**
     * Checks if $arr misses one of the given $keys
     * @param array $arr
     * @param array $keys
     * @return bool true, if one key is missing, else false
     */
    public static function containsNotAllKeys(array $arr, array $keys)
    {
        foreach ($keys as $key) {
            if (!isset($arr[$key]))
                return true;
        }

        return false;
    }

    /**
     * Checks if $arr not contains any key of $keys
     * @param array $arr
     * @param array $keys
     * @return bool true, if $arr contains not even on the the keys, else false
     */
    public static function containsNotAnyKey(array $arr, array $keys)
    {
        foreach ($keys as $key) {
            if (isset($arr[$key]))
                return false;
        }

        return true;
    }

    /**
     * Checks if the currency code has a valid format. Also if it has a valid format it has not to exist.
     * If it has a valid format it will also be changed to upper case only.
     * @param string $ccy
     * @return string|false The valid input (in upper case only) or false if it is not valid.
     */
    private static function checkActiveOrHistoricCurrencyCode( $ccy )
    {
        $ccy = strtoupper($ccy);

        if(preg_match('/^[A-Z]{3}$/', $ccy))
            return $ccy;
        else
            return false;
    }

    /**
     * Checks if $bbi is a valid batch booking indicator, i.e. bbi equals 'true' or 'false'
     * @param $bbi
     * @return string|false The batch booking indicator (in lower case only) or false if not valid
     */
    private static function checkBatchBookingIndicator( $bbi )
    {
        $bbi = strtolower($bbi);
        if($bbi === 'true' || $bbi === 'false')
            return $bbi;
        else
            return false;
    }

    /**
     * @param string $input
     * @return string|bool
     */
    private static function checkRestrictedIdentificationSEPA1($input)
    {
        if(preg_match('#^([A-Za-z0-9]|[\+|\?|/|\-|:|\(|\)|\.|,|\'| ]){1,35}$#',$input))
            return $input;
        else
            return false;
    }

    /**
     * @param string $input
     * @return string|bool
     */
    private static function checkRestrictedIdentificationSEPA2($input)
    {
        if(preg_match('#^([A-Za-z0-9]|[\+|\?|/|\-|:|\(|\)|\.|,|\']){1,35}$#',$input))
            return $input;
        else
            return false;
    }

    /**
     * @param string $input
     * @return string|bool
     */
    private static function checkRestrictedPersonIdentifierSEPA($input)
    {
        if(preg_match('#^[a-zA-Z]{2,2}[0-9]{2,2}([A-Za-z0-9]|[\+|\?|/|\-|:|\(|\)|\.|,|\']){3,3}' .
                      '([A-Za-z0-9]|[\+|\?|/|\-|:|\(|\)|\.|,|\']){1,28}$#',$input))
            return $input;
        else
            return false;
    }

    /**
     * Checks if the length of the input string not longer than the entered length
     *
     * @param string $input
     * @param int $maxLen
     * @return bool
     */
    private static function checkLength( $input, $maxLen )
    {
        return !isset($input[$maxLen]);     // takes the string as char array
    }

    /**
     * Shortens the input string to the max length if it is to long.
     * @param string $input
     * @param int $maxLen
     * @return string sanitized string
     */
    public static function sanitizeLength( $input, $maxLen )
    {
        if(isset($input[$maxLen]))     // take string as array of chars
            return substr($input,0,$maxLen);
        else
            return $input;
    }

    /**
     * Replaces all special chars like á, ä, â, à, å, ã, æ, Ç, Ø, Š, ", ’ and & with a latin char.
     * All special characters that can not be replaced with a latin char (such like quotes) will
     * be removed as long as they can not converted. See http://www.europeanpaymentscouncil.eu/index.cfm/knowledge-bank/epc-documents/sepa-requirements-for-an-extended-character-set-unicode-subset-best-practices/
     * for more information about converting characters.
     * @param string $str
     * @return string
     */
    public static function replaceSpecialChars($str)
    {
        // Remove all '&' (they are not allowed anywhere)
        $str = str_replace('&', '', $str);
        // turning all special chars into html entities. Then they look like '&' + char + modification + ';'
        $str = htmlentities($str, ENT_COMPAT, 'utf-8');
        // remove '&' + modification + ';' -> left only the char
        $str = preg_replace('/&([a-z]{1,2})(acute|uml|circ|grave|ring|cedil|slash|tilde|caron|elig);/i', '$1', $str );
        
        // replace greek chars
        $greek = array( '&Alpha;', '&Beta;', '&Gamma;', '&Delta;', '&Epsilon;', '&Zeta;',
                        '&Eta;', '&Theta;', '&Iota;', '&Kappa;', '&Lambda;', '&Mu;', '&Nu;',
                        '&Xi;', '&Omicron;', '&Pi;', '&Rho;', '&Sigma;', '&Tau;', '&Upsilon;',
                        '&Phi;', '&Chi;', '&Psi;', '&Omega;', '&alpha;', '&beta;', '&gamma;',
                        '&delta;', '&epsilon;', '&zeta;', '&eta;', '&theta;', '&iota;',
                        '&kappa;', '&lambda;', '&mu;', '&nu;', '&xi;', '&omicron;', '&pi;',
                        '&rho;', '&sigmaf;', '&sigma;', '&tau;', '&upsilon;', '&phi;', '&chi;',
                        '&psi;', '&omega;', '&upsih;');

        $latin = array( 'A', 'V', 'G', 'D', 'E', 'Z', 'I', 'TH', 'I', 'K', 'L', 'M', 'N', 'X',
                        'O', 'P', 'R', 'S', 'T', 'Y', 'F', 'CH', 'PS', 'O', 'a', 'v', 'g', 'd',
                        'e', 'z', 'i', 'th', 'i', 'k', 'l', 'm', 'n', 'x', 'o', 'p', 'r', 's',
                        's', 't', 'y', 'f', 'ch', 'ps', 'o', 'y' );

        $str = str_replace($greek, $latin, $str);

        // remove all HTML entities left
        $str = preg_replace('/&[^;]+;/', '', $str);

        // replace some special chars
        $search  = array('[',']','{','}','\\','|','~','_','`', '¿','€');
        $replace = array('(',')','(',')','/', '/','-','-','\'','?','E');
        str_replace($search,$replace,$str);

        // remove everything not allowed in sepa files
        $str = preg_replace('[^a-zA-Z0-9/\-?:().,\'+\s]','',$str);

        // remove leading and closing whitespaces
        return trim($str);
    }

    public static function checkCharset( $str)
    {
        return (boolean) preg_match('#^[a-zA-Z0-9/\-?:().,\'+\s]*$#',$str, $test);
    }

    /**
     * Checks if the amount fits the format: A float with only two decimals, not lower than 0.01,
     * not greater than 999,999,999.99.
     *
     * @param mixed $amount float or string with or without thousand separator (use , or .). You
     *                      can use '.' or ',' as decimal point, but not one sign as thousand separator
     *                      and decimal point. So 1234.56; 1,234.56; 1.234,56; 1234,56 ar valid
     *                      inputs.
     * @return float|false
     */
    private static function checkAmountFormat( $amount )
    {
        // $amount is a string -> check for '1,234.56
        $amount = filter_var($amount, FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_THOUSAND);

        if($amount === false)
            $amount = filter_var(strtr($amount,array(',' => '.', '.' => ',')), FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_THOUSAND);

        if($amount === false || $amount < 0.01 || $amount > 999999999.99 || round($amount,2) != $amount)
            return false;

        return $amount;
    }


}