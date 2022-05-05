<?php
require_once ('vendor/autoload.php');

require_once __DIR__ . '/vendor/abcaeffchen/sepa-utilities/src/SepaUtilities.php';
require_once __DIR__ . '/vendor/abcaeffchen/sephpa/src/autoloader.php';
require_once __DIR__ . '/vendor/abcaeffchen/sephpa//src/Sephpa.php';

$orgId = array('id' => '55689102760004','scheme_name' => 'CUST');
$creditTransferFile = new AbcAeffchen\Sephpa\SephpaCreditTransfer(
	'Simplex AB',
	'2004180913-325562255',	  #msgId - UUID
	AbcAeffchen\Sephpa\SephpaCreditTransfer::SEPA_PAIN_001_001_03, #Version
	$orgId
);


$creditTransferCollection = $creditTransferFile->addCollection([
	'pmtInfId'	=> 'B325562255', #UUID
	'dbtr'		=> 'Simplex AB',
	'iban'		=> 'SE715000000005222234362222',
	'ccy'		=> 'SEK',
	'bic'		=> 'ESSESESS',
#	'btchBookg'	=> 'true'
]);

$creditTransferCollection->addPayment([
	'InstrId'	=> 'Test1',
	'pmtId'		=> '664995832', #EndToEndId UUID
	'instdAmt'	=> '12.0000',
	'cdtr'		=> 'AdWiFi AB',
	'bgnr'		=> '4622222',
	'rmtInf'	=> 'Lösen', #OCR
	'BIC'		=> 'ESSESESS'
]);



$options = array('addDocumentation');
$creditTransferFile->download($options);

?>
