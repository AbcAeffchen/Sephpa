<?php
/*
 * This is just an example to show how to use the Utilities class
 */

require_once 'sepaLib/SepaUtilities.php';

$results = array();

if(isset($_POST['check']))
    foreach($_POST as $field => $input)
    {
        if(!in_array($field, array('pmtinfid', 'dbtr', 'iban', 'bic', 'ccy', 'btchbookg',
                                   'ultmtdebtr', 'pmtid', 'instdamt', 'cdtr', 'ultmtcdrt',
                                   'rmtinf', 'ci')))
            continue;
        $results[$field] = SepaUtilities::check($field, $input);
    }

if(isset($_POST['sanitize']))
    foreach($_POST as $field => $input)
    {
        if(!in_array($field, array('cdtr', 'dbtr', 'rmtinf', 'ultmtcdrt', 'ultmtdebtr')))
            continue;
        $results[$field] = SepaUtilities::sanitize($field, $input);
    }

?>

<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Document</title>
    <style>
        input[type=text]
        {
            width: 20em;
            padding: 3px;
            margin: 3px;
        }
    </style>
</head>
<body>

<form action="" method="post">
    <input type="text" name="iban" placeholder="IBAN" value="<?php if(isset($_POST['iban'])) echo $_POST['iban']; ?>">
    <?php if(isset($results['iban'])){ echo ($results['iban'] === false ? 'invalid' : 'valid -> ' . $results['iban']); } ?><br>
    <input type="text" name="bic" placeholder="BIC" value="<?php if(isset($_POST['bic'])) echo $_POST['bic']; ?>">
    <?php if(isset($results['bic'])){ echo ($results['bic'] === false ? 'invalid' : 'valid -> ' . $results['bic']); } ?><br>
    <input type="text" name="pmtinfid" placeholder="Payment-Information-ID" value="<?php if(isset($_POST['pmtinfid'])) echo $_POST['pmtinfid']; ?>">
    <?php if(isset($results['pmtinfid'])){ echo ($results['pmtinfid'] === false ? 'invalid' : 'valid -> ' . $results['pmtinfid']); } ?><br>
    <input type="text" name="dbtr" placeholder="Debtor Name" value="<?php if(isset($_POST['dbtr'])) echo $_POST['dbtr']; ?>">
    <?php if(isset($results['dbtr'])){ echo ($results['dbtr'] === false ? 'invalid' : 'valid -> ' . $results['dbtr']); } ?><br>
    <input type="text" name="ccy" placeholder="Currency" value="<?php if(isset($_POST['ccy'])) echo $_POST['ccy']; ?>">
    <?php if(isset($results['ccy'])){ echo ($results['ccy'] === false ? 'invalid' : 'valid -> ' . $results['ccy']); } ?><br>
    <input type="text" name="btchbookg" placeholder="Batch Booking" value="<?php if(isset($_POST['btchbookg'])) echo $_POST['btchbookg']; ?>">
    <?php if(isset($results['btchbookg'])){ echo ($results['btchbookg'] === false ? 'invalid' : 'valid -> ' . $results['btchbookg']); } ?><br>
    <input type="text" name="ultmtdebtr" placeholder="Ultimate Debtor" value="<?php if(isset($_POST['ultmtdebtr'])) echo $_POST['ultmtdebtr']; ?>">
    <?php if(isset($results['ultmtdebtr'])){ echo ($results['ultmtdebtr'] === false ? 'invalid' : 'valid -> ' . $results['ultmtdebtr']); } ?><br>
    <input type="text" name="pmtid" placeholder="Payment ID" value="<?php if(isset($_POST['pmtid'])) echo $_POST['pmtid']; ?>">
    <?php if(isset($results['pmtid'])){ echo ($results['pmtid'] === false ? 'invalid' : 'valid -> ' . $results['pmtid']); } ?><br>
    <input type="text" name="instdamt" placeholder="Instructed Amount" value="<?php if(isset($_POST['instdamt'])) echo $_POST['instdamt']; ?>">
    <?php if(isset($results['instdamt'])){ echo ($results['instdamt'] === false ? 'invalid' : 'valid -> ' . $results['instdamt']); } ?><br>
    <input type="text" name="cdtr" placeholder="Creditor Name" value="<?php if(isset($_POST['cdtr'])) echo $_POST['cdtr']; ?>">
    <?php if(isset($results['cdtr'])){ echo ($results['cdtr'] === false ? 'invalid' : 'valid -> ' . $results['cdtr']); } ?><br>
    <input type="text" name="ultmtcdrt" placeholder="Ultimate Creditor" value="<?php if(isset($_POST['ultmtcdrt'])) echo $_POST['ultmtcdrt']; ?>">
    <?php if(isset($results['ultmtcdrt'])){ echo ($results['ultmtcdrt'] === false ? 'invalid' : 'valid -> ' . $results['ultmtcdrt']); } ?><br>
    <input type="text" name="rmtinf" placeholder="Remittance Information" value="<?php if(isset($_POST['rmtinf'])) echo $_POST['rmtinf']; ?>">
    <?php if(isset($results['rmtinf'])){ echo ($results['rmtinf'] === false ? 'invalid' : 'valid -> ' . $results['rmtinf']); } ?><br>
    <input type="text" name="ci" placeholder="Creditor Identifier" value="<?php if(isset($_POST['ci'])) echo $_POST['ci']; ?>">
    <?php if(isset($results['ci'])){ echo ($results['ci'] === false ? 'invalid' : 'valid -> ' . $results['ci']); } ?><br>
    <br>
    <br>
    <input type="text" name="iban2" pattern="<?php echo SepaUtilities::PATTERN_IBAN; ?>" placeholder="IBAN"><br>
    <input type="text" name="bic2" pattern="<?php echo SepaUtilities::PATTERN_BIC; ?>" placeholder="BIC"><br>
    <input type="text" name="bic2" pattern="<?php echo SepaUtilities::PATTERN_CREDITOR_IDENTIFIER; ?>" placeholder="Creditor Identifier"><br>
    <input type="text" name="bic2" pattern="<?php echo SepaUtilities::PATTERN_MANDATE_ID; ?>" placeholder="Mandate ID"><br>
    <input type="text" name="shortText" pattern="<?php echo SepaUtilities::PATTERN_SHORT_TEXT; ?>" placeholder="Short text (up to 70 chars)"><br>
    <input type="text" name="longText" pattern="<?php echo SepaUtilities::PATTERN_LONG_TEXT; ?>" placeholder="Long text (up to 140 chars)"><br>
    <br><br>

    <input type="submit" name="check" value="Check">
    <input type="submit" name="sanitize" value="Sanitize">
</form>

</body>
</html>