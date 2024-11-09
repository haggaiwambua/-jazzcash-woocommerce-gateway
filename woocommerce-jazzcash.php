<?php
/* JazzCash Payment Gateway Class */
class JazzCash extends WC_Payment_Gateway
{

    // Setup our Gateway's id, description and other values
    function __construct()
    {
		//file_put_contents('abc.txt', "__construct called".PHP_EOL, FILE_APPEND);
        // The global ID for this Payment method
        $this->id = "jazzcash";

        // The Title shown on the top of the Payment Gateways Page next to all the other Payment Gateways
        $this->method_title = __("JazzCash", 'jazzcash');

        // The description for this Payment Gateway, shown on the actual Payment options page on the backend
        $this->method_description = __("JazzCash Payment Gateway Plug-in for WooCommerce",
            'jazzcash');

        // The title to be used for the vertical tabs that can be ordered top to bottom
        $this->title = __("JazzCash", 'jazzcash');

        // If you want to show an image next to the gateway's name on the frontend, enter a URL to an image.
        $this->icon = null;

        // Bool. Can be set to true if you want payment fields to show on the checkout
        // if doing a direct integration, which we are doing in this case
        $this->has_fields = true;

        // This basically defines your settings which are then loaded with init_settings()
        $this->init_form_fields();

        // After init_settings() is called, you can get the settings and load them into variables, e.g:
        // $this->title = $this->get_option( 'title' );
        $this->init_settings();

        // Turn these settings into variables we can use
        foreach ($this->settings as $setting_key => $value) {
            $this->$setting_key = $value;
        }

        // Lets check for SSL
        add_action('admin_notices', array($this, 'do_ssl_check'));

        // Save settings
        if (is_admin()) {
            // Versions over 2.0
            // Save our administration options. Since we are not going to be doing anything special
            // we have not defined 'process_admin_options' in this class so the method in the parent
            // class will be used instead
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this,
                    'process_admin_options'));
        }
		
		
		//executes a response method
		add_action( 'woocommerce_api_jazzcashresponse', array($this, 'jazzcash_response'));
		
		add_action('woocommerce_receipt_jazzcash', array($this, 'receipt_page'));
		//file_put_contents('abc.txt', "woocommerce_receipt_jazzcash bind end, order: ||".$this->id."|| end".PHP_EOL, FILE_APPEND);
		
		//file_put_contents('abc.txt', "__construct end".PHP_EOL, FILE_APPEND);
    } // End __construct()

    // Build the administration fields for this specific Gateway
    public function init_form_fields()
    {
			//file_put_contents('abc.txt', "init_form_fields called".PHP_EOL, FILE_APPEND);
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable / Disable', 'jazzcash'),
                'label' => __('Enable this payment gateway', 'jazzcash'),
                'type' => 'checkbox',
                'default' => 'no',
                ),
            'title' => array(
                'title' => __('Payment Gateway Title', 'jazzcash'),
                'type' => 'text',
                'desc_tip' => __('Payment title the customer will see during the checkout process.',
                    'jazzcash'),
                'default' => __('JazzCash', 'jazzcash')
                ),
            'description' => array(
                'title' => __('Payment Gateway Description', 'jazzcash'),
                'type' => 'textarea',
                'desc_tip' => __('Payment Gateway description', 'jazzcash'),
                'default' => __('Pay freely using JazzCash.', 'jazzcash'),
                'css' => 'max-width:350px;'),
            'merchantID' => array(
                'title' => __('Merchant ID', 'jazzcash'),
                'type' => 'text',
                'desc_tip' => __('Merchant ID', 'jazzcash')
                ),
            'password' => array(
                'title' => __('Password', 'jazzcash'),
                'type' => 'password',
                'desc_tip' => __('Password.','jazzcash')
                ),
            'returnURL' => array(
                'title' => __('Return URL', 'jazzcash'),
                'type' => 'text',
                'desc_tip' => __('Merchant Url for returning Transactions','jazzcash')
                ),
			'expiryHours' => array(
                'title' => __('Transaction Expiry (Hours)', 'jazzcash'),
                'type' => 'number',
                'desc_tip' => __('Transaction Expiry (Hours)', 'jazzcash'),
				'default' => __('12', 'jazzcash')
                ),
            'integritySalt' => array(
                'title' => __('Integrty Salt', 'jazzcash'),
                'type' => 'password',
                'desc_tip' => __('Integrty Salt', 'jazzcash')
                ),
			'actionURL' => array(
                'title' => __('Transaction Post URL', 'jazzcash'),
                'type' => 'text',
                'desc_tip' => __('URL to post transaction', 'jazzcash')
                ),
			'validateHash' => array(
				'title' => __('Validate Hash', 'jazzcash'),
				'label' => __('Validate Hash', 'jazzcash'),
				'type' => 'checkbox',
				'default' => 'yes',
				));
				
				//file_put_contents('abc.txt', "init_form_fields ended.".PHP_EOL, FILE_APPEND);
    }

	
	/**
     * Receipt Page
     **/
    function receipt_page($order){
	//file_put_contents('abc.txt', "receipt_page called".PHP_EOL, FILE_APPEND);
        echo '<p>'.__('Please wait while your are being redirected to JazzCash...', 'jazzcash').'</p>';
        echo $this -> generate_jazzcash_form($order);
    }
	
	/**
     * Generate jazzcash button link
     **/
    public function generate_jazzcash_form($order_id){
	//file_put_contents('abc.txt', "generate_jazzcash_form".PHP_EOL, FILE_APPEND);
	
		global $woocommerce;

        // Get this Order's information so that we know
        // who to charge and how much
        $customer_order = new WC_Order($order_id);
		
		$_ActionURL     = $this->actionURL;
        $_MerchantID    = $this->merchantID;
        $_Password      = $this->password;
        $_ReturnURL     = $this->returnURL;
        $_IntegritySalt = $this->integritySalt;
        $_ExpiryHours   = $this->expiryHours;
		
		$items = $customer_order->get_items();
		$product_name  = array();
		foreach ( $items as $item ) {
			array_push($product_name, $item['name']);
		}
		$_Description   = implode(", ", $product_name);
		$_Language      = 'EN';
        $_Version       = '1.1';
        $_Currency      = 'PKR';
        $_BillReference = $customer_order->get_order_number();
		$_AmountTmp = $customer_order->order_total*100;
		$_AmtSplitArray = explode('.', $_AmountTmp);
		$_FormattedAmount = $_AmtSplitArray[0];
		
		date_default_timezone_set("Asia/karachi");
        $DateTime       = new DateTime();
        $_TxnRefNumber  = "T" . $DateTime->format('YmdHis');
        $_TxnDateTime   = $DateTime->format('YmdHis');
        $ExpiryDateTime = $DateTime;
        $ExpiryDateTime->modify('+' . $_ExpiryHours . ' hours');
        $_ExpiryDateTime = $ExpiryDateTime->format('YmdHis');
        
        $ppmpf1 = '1';
        $ppmpf2 = '2';
        $ppmpf3 = '3';
        $ppmpf4 = '4';
        $ppmpf5 = '5';
		
		 // Populating Sorted Array
        $SortedArrayOld = $_IntegritySalt . '&' . $_FormattedAmount . '&' . $_BillReference . '&' . $_Description . '&' . $_Language . '&' . $_MerchantID . '&' . $_Password;
        $SortedArrayOld = $SortedArrayOld . '&' . $_ReturnURL . '&' . $_Currency . '&' . $_TxnDateTime . '&' . $_ExpiryDateTime . '&' . $_TxnRefNumber . '&' . $_Version;
        $SortedArrayOld = $SortedArrayOld . '&' . $ppmpf1 . '&' . $ppmpf2 . '&' . $ppmpf3 . '&' . $ppmpf4 . '&' . $ppmpf5;
        
        //Calculating Hash
        $_Securehash = hash_hmac('sha256', $SortedArrayOld, $_IntegritySalt);
		
		//file_put_contents('abc.txt', "\n=> Request: " . $SortedArrayOld, FILE_APPEND);
		
		$jazzcash_args = array(
			'pp_Version' => $_Version,
			'pp_TxnType' => '',
			'pp_Language' => $_Language,
			'pp_MerchantID' => $_MerchantID,
			'pp_SubMerchantID' => '',
			'pp_Password' => $_Password,
			'pp_BankID' => '',
			'pp_ProductID' => '',
			'pp_TxnRefNo' => $_TxnRefNumber,
			'pp_Amount' => $_FormattedAmount,
			'pp_TxnCurrency' => $_Currency,
			'pp_TxnDateTime' => $_TxnDateTime,
			'pp_BillReference' => $_BillReference,
			'pp_Description' => $_Description,
			'pp_TxnExpiryDateTime' => $_ExpiryDateTime,
			'pp_ReturnURL' => $_ReturnURL,
			'pp_SecureHash' => $_Securehash,
			'ppmpf_1' => $ppmpf1,
			'ppmpf_2' => $ppmpf2,
			'ppmpf_3' => $ppmpf3,
			'ppmpf_4' => $ppmpf4,
			'ppmpf_5' => $ppmpf5
		);
		
		WC()->session->set('jazzCashRequestData',  $jazzcash_args);
		
		$jazzcash_args_array = array();
        foreach($jazzcash_args as $key => $value){
          $jazzcash_args_array[] = "<input type='hidden' name='$key' value='$value'/>";
        }
        
		$form  = '<form action="'.$_ActionURL.'" id="jazzcashPostForm" name="JazzCashForm" method="post">';
		$form .= implode('', $jazzcash_args_array);
		$form .= '</form> <script type="text/javascript"> document.getElementById("jazzcashPostForm").submit(); </script>';
			
		//file_put_contents('abc.txt', "generate_jazzcash_form ended".PHP_EOL, FILE_APPEND);
			
		return $form;
		
		
	}
	
    /**
     * Process the payment and return the result
     **/
   /* public function process_payment($order_id)
    {
        global $woocommerce;
    	$order = new WC_Order( $order_id );
        return array('result' => 'success', 'redirect' => add_query_arg('order',
            $order->get_id(), add_query_arg('key', $order->get_id(), get_permalink(get_option('woocommerce_pay_page_id'))))
        );
    }*/
	
	
		function process_payment($order_id) {
		//$order = new Aelia_Order($order_id);
		
		global $woocommerce;
    	$order = new WC_Order( $order_id );
		

		// Redirect to receipt page, which will contain the form that will actually
		// bring to the Skrill portal
		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url(true),
		);
	}
	
	
    // Validate fields
    public function validate_fields()
    {
        return true;
    }

    // Check if we are forcing SSL on checkout pages
    // Custom function not required by the Gateway
    public function do_ssl_check()
    {
	//file_put_contents('abc.txt', "do_ssl_check called".PHP_EOL, FILE_APPEND);
        if ($this->enabled == "yes") {
            if (get_option('woocommerce_force_ssl_checkout') == "no") {
                echo "<div class=\"error\"><p>" . sprintf(__("<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>"),
                    $this->method_title, admin_url('admin.php?page=wc-settings&tab=checkout')) .
                    "</p></div>";
            }
        }
		
			//file_put_contents('abc.txt', "do_ssl_check ended".PHP_EOL, FILE_APPEND);
    }

	public function callback_handler(){
		global $woocommerce;
		try {
		}
		catch(Exception $e){
		}
	}
	
	public function jazzcash_response(){
	//file_put_contents('abc.txt', "jazzcash_response called".PHP_EOL, FILE_APPEND);
		global $woocommerce;
		try {
			$comment             = "";
			$errorMsg            = 'Sorry! The transaction was not successful.';
			$successFlag         = false;
			$returnUrl           = 'checkout/onepage/success';
			$sortedResponseArray = array();
			if (!empty($_POST)) {
				foreach ($_POST as $key => $val) {
					$comment .= $key . "[" . $val . "],<br/>";
					$sortedResponseArray[$key] = $val;
				}
			}
			//file_put_contents('abc.txt', "Parameters to calculate HASH: ".$comment, FILE_APPEND);
			$_MerchantID    = $this->merchantID;
			$_Password      = $this->password;
			$_IntegritySalt = $this->integritySalt;
			$_ValidateHash 	= $this->validateHash;
			
			$_ResponseMessage = $this->getEmptyIfNullFromPOST('pp_ResponseMessage');
			$_ResponseCode    = $this->getEmptyIfNullFromPOST('pp_ResponseCode');
			$_TxnRefNo        = $this->getEmptyIfNullFromPOST('pp_TxnRefNo');
			$_BillReference   = $this->getEmptyIfNullFromPOST('pp_BillReference');
			$_SecureHash      = $this->getEmptyIfNullFromPOST('pp_SecureHash');
			
		//	file_put_contents('abc.txt', "pp_ResponseCode: ".$_ResponseCode.PHP_EOL, FILE_APPEND);
			
			$requestData = WC()->session->get('jazzCashRequestData');
			
			if (strtolower($_ValidateHash) == 'yes') {
			//file_put_contents('abc.txt', "Validate hash: yes".PHP_EOL, FILE_APPEND);
			
			//file_put_contents('abc.txt', "Secure Hash: ".$_SecureHash.PHP_EOL, FILE_APPEND);
				if (!$this->isNullOrEmptyString($_SecureHash)) {
					//removing pp_SecureHash key
					unset($sortedResponseArray['pp_SecureHash']);
					//sorting array w.r.t key
					ksort($sortedResponseArray);
					$sortedResponseValuesArray = array();
					//Populating Sorted Array
					array_push($sortedResponseValuesArray, $_IntegritySalt);
					
					foreach ($sortedResponseArray as $key => $val) {
						if (!$this->isNullOrEmptyString($val)) {
							array_push($sortedResponseValuesArray, $val);
						}
					}
					
					//joining array of sorted response values 
					$sortedResponseValuesForHash = implode('&', $sortedResponseValuesArray);
					//Calculating Hash
					$CalSecureHash               = hash_hmac('sha256', $sortedResponseValuesForHash, $_IntegritySalt);
					
					//file_put_contents('abc.txt', "Secure Hash: ".$_SecureHash.PHP_EOL, FILE_APPEND);
					//file_put_contents('abc.txt', "Calculated Hash: ".$CalSecureHash.PHP_EOL, FILE_APPEND);
					
					if (strtolower($CalSecureHash) == strtolower($_SecureHash)) {
						$isResponseOk = true;
						//file_put_contents('abc.txt', "Secure Hash match ".PHP_EOL, FILE_APPEND);
					} else {
						$isResponseOk = false;
						$comment .= "Secure Hash mismatched.";
						//file_put_contents('abc.txt', "Secure Hash mismatch ".PHP_EOL, FILE_APPEND);
					}
				} else {
					$isResponseOk = false;
					$comment .= "Secure Hash is empty.";
					//file_put_contents('abc.txt', "Secure Hash empty ".PHP_EOL, FILE_APPEND);
				}
			} else {
				//file_put_contents('abc.txt', "Validate hash: no, sending isResponseOk = true".PHP_EOL, FILE_APPEND);
				$isResponseOk = true;
			}
			
			if($isResponseOk) {
				if(isset($requestData)) {
					if((strtolower($this->getEmptyIfNull($requestData['pp_TxnRefNo'])) == strtolower($this->getEmptyIfNull($sortedResponseArray['pp_TxnRefNo']))) && 
					(strtolower($this->getEmptyIfNull($requestData['pp_TxnDateTime'])) == strtolower($this->getEmptyIfNull($sortedResponseArray['pp_TxnDateTime']))) &&
					(strtolower($this->getEmptyIfNull($requestData['pp_MerchantID'])) == strtolower($this->getEmptyIfNull($sortedResponseArray['pp_MerchantID']))) && 
					(strtolower($this->getEmptyIfNull($requestData['pp_BillReference'])) == strtolower($this->getEmptyIfNull($sortedResponseArray['pp_BillReference']))) &&
					(strtolower($this->getEmptyIfNull($requestData['pp_Amount'])) == strtolower($this->getEmptyIfNull($sortedResponseArray['pp_Amount'])))) {
						$isResponseOk = true;
					}
					else {
						$isResponseOk = false;
						$comment .= "Response integrity violated. Response values are not same as Request.";
					}
				}
				else {
					$isResponseOk = false;
					$comment .= "Session is empty. Response integrity cannot be validated.";
				}
			}
			

			
			$orderStatusCompleted = 'completed';
            $orderStatusFailed   = 'failed';
			$orderStatusPending  = 'pending';
			$order = new WC_Order($_BillReference);
			//file_put_contents('abc.txt', "isResponseOk: ".$isResponseOk.PHP_EOL, FILE_APPEND);
			if($isResponseOk) {
							//file_put_contents('abc.txt', "pp_ResponseCode: ".$_ResponseCode.PHP_EOL, FILE_APPEND);
				if($_ResponseCode == '000') {
					//file_put_contents('abc.txt', "000 called".PHP_EOL, FILE_APPEND);
					$order->update_status($orderStatusCompleted);
					$woocommerce->cart->empty_cart();
				}
				else if ($_ResponseCode == '124') {
					//file_put_contents('abc.txt', "124 called".PHP_EOL, FILE_APPEND);
					$order->update_status($orderStatusPending);
					$woocommerce->cart->empty_cart();
				}
				else if ($_ResponseCode == '349') {
					//file_put_contents('abc.txt', "349 called".PHP_EOL, FILE_APPEND);
					$order->update_status($orderStatusPending);
					$woocommerce->cart->empty_cart();
				}
				else {
					//file_put_contents('abc.txt', "failed called".PHP_EOL, FILE_APPEND);
					$order->update_status($orderStatusFailed);
				}
				
				//takes customer to payment success / failure page
				wp_redirect($this->get_return_url($order));
				exit;
				
			} else {
				//file_put_contents('abc.txt', "failed called - 2".PHP_EOL, FILE_APPEND);
				//takes customer to payment success / failure page
				$order->update_status($orderStatusFailed);
				wp_redirect($this->get_return_url($order));
				exit;
			}
		}
		catch(Exception $e){
			//file_put_contents('abc.txt', "jazzcash_response exception".PHP_EOL, FILE_APPEND);
			//takes customer to payment success / failure page
			wp_redirect($this->get_return_url($order));
			exit;
		}
		
			//file_put_contents('abc.txt', "jazzcash_response ended".PHP_EOL, FILE_APPEND);
		
	}
	
	
		protected function complete_order($order, $posted_data) {
		// Add order note upon successful completion of payment
		$approval_code = get_value('approval_code', $posted_data);
		$order->payment_complete();
		$this->woocommerce()->cart->empty_cart();
	}
	
	
	function showMessage($content){
		return '<div class="box '.$this -> msg['class'].'-box">'.$this -> msg['message'].'</div>'.$content;
	}
	
	protected function isNullOrEmptyString($question)
    {
        return (!isset($question) || trim($question) === '');
    }
    
    protected function getEmptyIfNullFromPOST($key)
    {
        if (!isset($_POST[$key]) || trim($_POST[$key]) == "") {
            return "";
        } else {
            return $_POST[$key];
        }
    }

    protected function getEmptyIfNull($key)
    {
        if (!isset($key) || trim($key) == "") {
            return "";
        } else {
            return $key;
        }
    }
	
} // End of JazzCash
?>

