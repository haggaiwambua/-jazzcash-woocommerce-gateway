<?php
require '../../../../wp-config.php';
function DoUpdatePaymentStatus(
		$pp_Version,
		$pp_TxnType,
		$pp_BankID,
		$pp_Password,
		$pp_TxnRefNo,
		$pp_TxnDateTime,
		$pp_ResponseCode,
		$pp_ResponseMessage,
		$pp_AuthCode,
		$pp_RetreivalReferenceNo,
		$pp_SecureHash,
		$pp_ProductID,
		$pp_SettlementExpiry
	){
		
		global $db_obj;
		
		// check for required parameter
		$required_params = [
				'pp_Version' => $pp_Version, 
				'pp_TxnType' => $pp_TxnType, 
				'pp_Password' => $pp_Password, 
				'pp_TxnRefNo' => $pp_TxnRefNo, 
				'pp_TxnDateTime' => $pp_TxnDateTime, 
				'pp_ResponseCode' => $pp_ResponseCode, 
				'pp_RetreivalReferenceNo' => $pp_RetreivalReferenceNo
				];
		foreach ($required_params as $in => $iv){
			if(!isset($iv) or empty($iv)){
				return "012Missing mandatory parameter(s) ".$in;
				// echo "missing mandatory param. ".$in;
				exit;
			}
		}
		
		
		// some checks before going through the process
		/*if($pp_Version != '1.1' or $pp_TxnType != 'OTC'){
			return "013Invalid valued for parameter(s)";
			exit;
			// echo "invalid param value. ";
		}*/ //sunny change
		
		
		// get the payment token from response
		$payment_token = $pp_RetreivalReferenceNo;
		$password = $pp_Password;
		$application_id = $pp_TxnRefNo;
		
		
		//echo PASSWORD;exit;
		//echo $payment_token."-".$password."--".$application_id;exit;	 $password == PASSWORD and
	
		if( in_array($pp_ResponseCode, ['000', '121', '200'])){			
			
			$protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
			
			$url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];		 
			
			$modifyUrl = parse_url($url);
			
			$URL =  explode( "/",$modifyUrl['path'] ); 
			
			$ipnUrl = $modifyUrl['scheme']."://".$modifyUrl['host']."/".$URL[1]."/index.php/jazzcash/payment/IPN/?trxid=".$pp_TxnRefNo."&pp_TxnType=".$pp_TxnType;
				 
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_URL, $ipnUrl);
			
			$curl_response = curl_exec($curl);
			curl_close($curl);		
			
			global $wpdb;
			global $woocommerce;
			$table_name = $wpdb->prefix . "jazz_cash_order_ref";
			$post_table = $wpdb->prefix ."posts";
			$sql = "SELECT * FROM `".$table_name."` WHERE `pp_TxnRefNo` = '".$pp_TxnRefNo."'";
			$order_query = $wpdb->get_row("SELECT * FROM `".$table_name."` WHERE `pp_TxnRefNo` = '".$pp_TxnRefNo."'");
			$order_id =  $order_query->id_order;
			/*$order = new WC_Order($order_id);
			$order->update_status('otcSuccess');*/
			$wpdb->update( //updating via query
				''.$post_table.'', 
				array( 
				'post_status' => 'wc-otcSuccess'	// string
				// integer (number) 
				), 
				array( 'ID' => $order_id ), 
				array( 
				'%s'
				), 
				array( '%d' ) 
			);
			$order = new WC_Order($order_id);
						foreach ($order->get_items() as $item_id => $item) {
							// Get an instance of corresponding the WC_Product object
							$product = $item->get_product();
							$qty = $item->get_quantity(); // Get the item quantity
							wc_update_product_stock($product, $qty, 'decrease');
						}
			$woocommerce->cart->empty_cart();
			 //return $order_query->id_order."000 |status updated successfully|";
			 return "000 |status updated successfully|";
		} else {
			return "101|invalid merchant details or invalid response code|";
		}
		
		
		
	}