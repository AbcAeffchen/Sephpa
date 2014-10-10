<?php

/**
 * Class SepaUtilities
 * Useful functions to validate an sanitize sepa input data
 */
class SepaUtilities
{
    const HTML_PATTERN_IBAN = '([a-zA-Z]\s*){2}([0-9]\s?){2}\s*([a-zA-Z0-9]\s*){1,30}';
    const HTML_PATTERN_BIC = '([a-zA-Z]\s*){6}[a-zA-Z2-9]\s*[a-nA-Np-zP-Z0-9]\s*(([A-Z0-9]\s*){3}){0,1}';

    const PATTERN_IBAN = '[A-Z]{2}[0-9]{2}[A-Z0-9]{1,30}';
    const PATTERN_BIC  = '[A-Z]{6}[A-Z2-9][A-NP-Z0-9]([A-Z0-9]{3}){0,1}';
    /**
     * equates to RestrictedPersonIdentifierSEPA
     */
    const PATTERN_CREDITOR_IDENTIFIER  = '[a-zA-Z]{2,2}[0-9]{2,2}([A-Za-z0-9]|[\+|\?|/|\-|:|\(|\)|\.|,|\']){3,3}([A-Za-z0-9]|[\+|\?|/|\-|:|\(|\)|\.|,|\']){1,28}';
    const PATTERN_SHORT_TEXT  = '[a-zA-Z0-9/\-?:().,\'+\s]{0,70}';
    const PATTERN_LONG_TEXT  = '[a-zA-Z0-9/\-?:().,\'+\s]{0,140}';
    /**
     * Used for Message-, Payment- and Transfer-IDs
     * equates to checkRestrictedIdentificationSEPA1
     */
    const PATTERN_FILE_IDS = '([A-Za-z0-9]|[\+|\?|/|\-|:|\(|\)|\.|,|\'|\s]){1,35}';
    /**
     * equates to checkRestrictedIdentificationSEPA2
     */
    const PATTERN_MANDATE_ID = '([A-Za-z0-9]|[\+|\?|/|\-|:|\(|\)|\.|,|\']){1,35}';

    const FLAG_ALT_REPLACEMENT_GERMAN = 1;

    /**
     * Checks if an creditor identifier (ci) is valid. Note that also if the ci is valid it does
     * not have to exist
     *
     * @param string $ci
     * @return string|false The valid iban or false if it is not valid
     */
    public static function checkCreditorIdentifier( $ci )
    {
        $alph =         array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J',
                              'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T',
                              'U', 'V', 'W', 'X', 'Y', 'Z');
        $alphValues =  array( 10,  11,  12,  13,  14,  15,  16,  17,  18,  19,
                              20,  21,  22,  23,  24,  25,  26,  27,  28,  29,
                              30,  31,  32,  33,  34,  35);

        $ci = preg_replace('/\s+/', '', $ci);   // remove whitespaces
        $ci = strtoupper($ci);                  // todo does this breaks the ci?

        if(!self::checkRestrictedPersonIdentifierSEPA($ci))
            return false;

        $ciCopy = $ci;

        // remove creditor business code
        $nationalIdentifier = substr($ci, 7);
        $check = substr($ci, 0,4);
        $concat = $nationalIdentifier . $check;

        $concat = preg_replace('#[^a-zA-Z0-9]#','',$concat);      // remove all non-alpha-numeric characters

        $concat = $check = str_replace($alph, $alphValues, $concat);

        if(self::iso7064Mod97m10ChecksumCheck($concat))
            return $ciCopy;
        else
            return false;
    }

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

        $iban = preg_replace('/\s+/', '' , $iban );     // remove whitespaces
        $iban = strtoupper($iban);

        if(!preg_match('/^' . self::PATTERN_IBAN . '$/',$iban))
            return false;

        $ibanCopy = $iban;
        $iban = $check = str_replace($alph, $alphValues, $iban);

        $bban = substr($iban, 6);
        $check = substr($iban, 0,6);

        $concat = $bban . $check;

        if(self::iso7064Mod97m10ChecksumCheck($concat))
            return $ibanCopy;
        else
            return false;
    }

    private static function iso7064Mod97m10ChecksumCheck($input)
    {
        $mod97 = array(1, 10, 3, 30, 9, 90, 27, 76, 81, 34, 49, 5, 50, 15, 53, 45, 62, 38,
                       89, 17, 73, 51, 25, 56, 75, 71, 31, 19, 93, 57, 85, 74, 61, 28, 86,
                       84, 64, 58, 95, 77, 91, 37, 79, 14, 43, 42, 32, 29, 96, 87, 94, 67,
                       88, 7, 70, 21, 16, 63, 48, 92, 47, 82, 44, 52, 35, 59, 8, 80, 24);

        $checksum = 0;
        $len = strlen($input);
        for($i = 1; $i  <= $len; $i++)
        {
            $checksum = (($checksum + $mod97[$i-1]*$input[$len-$i]) % 97);
        }

        return ($checksum == 1);
    }

    /**
     * Checks if a bic is valid. Note that also if the bic is valid it does not have to exist
     * @param string $bic
     * @return string|false the valid bic or false if it is not valid
     */
    public static function checkBIC($bic)
    {
        $bic = preg_replace('/\s+/', '' , $bic );   // remove whitespaces
        $bic = strtoupper($bic);                    // use only capital letters

        if(preg_match('/^' . self::PATTERN_BIC . '$/', $bic))
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
    public static function formatDate($date, $inputFormat = 'd.m.Y')
    {
        return DateTime::createFromFormat($inputFormat, $date)->format('Y-m-d');
    }

    /**
     * Checks if the input holds for the field.
     *
     * @param string $field Valid fields are: 'pmtinfid', 'dbtr', 'iban', 'bic', 'ccy',
     *                      'btchbookg', 'mndtid', 'orgnlmndtid', 'orgnlcdtrschmeid_nm',
     *                      'orgnlcdtrschmeid_id', 'orgnldbtracct_iban', 'ultmtdebtr', 'pmtid',
     *                      'instdamt', 'cdtr', 'ultmtcdrt', 'rmtinf', 'ci', 'initgpty'
     * @param mixed  $input
     * @return mixed|false The checked input or false, if it is not valid
     */
    public static function check($field, $input)
    {
        $field = strtolower($field);
        switch($field)      // fall-through's are on purpose
        {
            case 'orgnlcdtrschmeid_id':
            case 'ci': return self::checkCreditorIdentifier($input);
            case 'pmtid':   // next line
            case 'pmtinfid': return self::checkRestrictedIdentificationSEPA1($input);
            case 'orgnlmndtid':
            case 'mndtid': return self::checkRestrictedIdentificationSEPA2($input);
            case 'initgpty':
            case 'cdtr':                                    // cannot be empty (and the following things also)
            case 'dbtr': if(empty($input)) return false;    // cannot be empty (and the following things also)
            case 'orgnlcdtrschmeid_nm':
            case 'ultmtcdrt':
            case 'ultmtdebtr': return (self::checkLength($input, 70) && self::checkCharset($input)) ? $input : false;
            case 'rmtinf': return (self::checkLength($input, 140) && self::checkCharset($input)) ? $input : false;
            case 'orgnldbtracct_iban':
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
     *
     * @param string $field Valid fields are: 'cdtr', 'dbtr', 'rmtinf', 'ultmtcdrt', 'ultmtdebtr', 'initgpty', 'orgnlcdtrschmeid_nm'
     * @param mixed  $input
     * @param int    $flags Flags used in replaceSpecialChars()
     * @return mixed|false The sanitized input or false if the input is not sanitizeable or invalid
     *                      also after sanitizing.
     */
    public static function sanitize($field, $input, $flags = 0)
    {
        $field = strtolower($field);
        switch($field)
        {
            case 'ultmtcdrt':
            case 'ultmtdebtr': return self::sanitizeLength(self::replaceSpecialChars($input, $flags), 70);
            case 'orgnlcdtrschmeid_nm':
            case 'initgpty':
            case 'cdtr':
            case 'dbtr':
                $res = self::sanitizeLength(self::replaceSpecialChars($input, $flags), 70);
                return (empty($res) ? false : $res);
            case 'rmtinf': return self::sanitizeLength(self::replaceSpecialChars($input, $flags), 140);
            default: return false;
        }
    }

    /**
     * Checks the input and If it is not valid it tries to sanitize it.
     * @param string $field all fields check and/or sanitize supports
     * @param mixed $input
     * @return mixed|false
     */
    public static function checkAndSanitize($field, $input)
    {
        $checkedInput = self::check($field, $input);
        if($checkedInput !== false)
            return $checkedInput;

        return self::sanitize($field,$input);
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
     * @param string $bbi
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
        if(preg_match('#^' . self::PATTERN_FILE_IDS . '$#',$input))
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
        if(preg_match('#^' . self::PATTERN_MANDATE_ID . '$#',$input))
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
        if(preg_match('#^' . self::PATTERN_CREDITOR_IDENTIFIER . '$#',$input))
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
    public static function sanitizeLength($input, $maxLen)
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
     *
     * @param string $str
     * @param int    $flags Use the SepaUtilities::FLAG_ALT_REPLACEMENT_* constants. This will
     *                      ignore the best practice replacement and use a more common one.
     *                      You can use more than one flag by using the | (bitwise or) operator.
     * @return string
     */
    public static function replaceSpecialChars($str, $flags = 0)
    {
        if($flags & self::FLAG_ALT_REPLACEMENT_GERMAN)
            $str = str_replace(array('Ä','ä','Ö','ö','Ü','ü','ß'),
                               array('Ae','ae','Oe','oe','Ue','ue','ss'),
                               $str);

        // remove all '&' (they are not allowed)
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

    private static function checkCharset($str)
    {
        return (boolean) preg_match('#^[a-zA-Z0-9/\-?:().,\'+\s]*$#', $str);
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
        // $amount is a string -> check for '1,234.56'
        $amount = filter_var($amount, FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_THOUSAND);

        if($amount === false)
            $amount = filter_var(strtr($amount,array(',' => '.', '.' => ',')), FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_THOUSAND);

        if($amount === false || $amount < 0.01 || $amount > 999999999.99 || round($amount,2) != $amount)
            return false;

        return $amount;
    }

}