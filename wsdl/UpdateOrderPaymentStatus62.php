<?php	
	//call library
	
	
	require_once "./includes/nusoap-0.9.5/lib/nusoap.php";
	require_once "./functions.php";		
	$server = new nusoap_server();
	$server->configureWSDL("UpdateOrderPaymentStatus62", "urn:UpdateOrderPaymentStatus62");
	
	$server->register('DoUpdatePaymentStatus',
		[
			'pp_Version' => 'xsd:string',
			'pp_TxnType' => 'xsd:string',
			'pp_BankID' => 'xsd:string',
			'pp_Password' => 'xsd:string',
			'pp_TxnRefNo' => 'xsd:string',
			'pp_TxnDateTime' => 'xsd:string',
			'pp_ResponseCode' => 'xsd:string',
			'pp_ResponseMessage' => 'xsd:string',
			'pp_AuthCode' => 'xsd:string',
			'pp_RetreivalReferenceNo' => 'xsd:string',
			'pp_SecureHash' => 'xsd:string',
			'pp_ProductID' => 'xsd:string',
			'pp_SettlementExpiry' => 'xsd:string'
		],			
		['DoUpdatePaymentStatusResult' => 'xsd:string']
	);	
	$HTTP_RAW_POST_DATA = isset( $HTTP_RAW_POST_DATA ) ? $HTTP_RAW_POST_DATA : '';
	$server->service($HTTP_RAW_POST_DATA);

?>