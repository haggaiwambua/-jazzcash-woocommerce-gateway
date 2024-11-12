<?php
	
	
	//call library

	require_once "./includes/nusoap-0.9.5/lib/nusoap.php";
 
/*

	$proxyhost = "10.250.10.81";
	$proxyport = "";
	$proxyusername = "";
	$proxypassword = "";*/
	//$client = new nusoap_client('http://203.215.160.149/magento/app/code/local/WebService%20Updated/jazzCash/UpdateOrderPaymentStatus62.php?wsdl', '');
	
	 $client = new nusoap_client('http://172.16.1.217/magento/app/code/local/WebService%20Updated/jazzCash/UpdateOrderPaymentStatus62.php?wsdl', '');
	
	
	
	
	$Client -> soap_defencoding = 'utf-8';
	$err = $client->getError();
	if ($err) {
		echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
	}
	// Doc/lit parameters get wrapped 75019F19EA
	$person = array(
	
		'pp_Version' => '1.1',
		'pp_TxnType' => 'MWALLET',
		'pp_BankID' => 'BNK1',
		'pp_Password' => '75019F19EA',
		'pp_TxnRefNo' => 'T20180716114030',
		'pp_TxnDateTime' => '20110909203547',
		'pp_ResponseCode' => '000',
		'pp_ResponseMessage' => 'Low Balance',
		'pp_AuthCode' => '123456',
		'pp_RetreivalReferenceNo' => '100000118',//100000118
		'pp_SecureHash' => 'null',
		'pp_ProductID' => 'RETL',
		'pp_SettlementExpiry' => 'null' 
	
	);
	//$param = array('Symbol' => 'IBM');
	//$result = $client->call('DoUpdatePaymentStatus', array('pp_Version' => 1.1), '', '', false, true);
	$result = $client->call( 'DoUpdatePaymentStatus', $person, '', '', false, true);
	
	// Check for a fault
	if ($client->fault) {
		
		echo '<h2>Fault</h2><pre>';
			print_r($result);
		echo '</pre>';
		
	} else {
		// Check for errors
		$err = $client->getError();
		if ($err) {
			// Display the error
			echo '<h2>Error</h2><pre>' . $err . '</pre>';		
		} else {
			// Display the result
			echo '<h2>Result</h2><pre>';
				print_r($result);
			echo '</pre>';
		}
	}
echo $client->response ;
	//echo '<h2>Request</h2><pre>' .  ($client->request) . '</pre>';
//	echo '<h2>Request</h2><pre>' . htmlspecialchars($client->request, ENT_QUOTES) . '</pre>';
//	echo '<h2>Response</h2><pre>' . htmlspecialchars($client->response, ENT_QUOTES) . '</pre>';
	//echo '<h2>Debug</h2><pre>' . htmlspecialchars($client->debug_str, ENT_QUOTES) . '</pre>';

?>