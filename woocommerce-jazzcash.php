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
        $this->method_description = __("JazzCash Payment Gateway Plug-in for WooCommerce", 'jazzcash');

        // The title to be used for the vertical tabs that can be ordered top to bottom
        $this->title = __("JazzCash", 'jazzcash');

        // If you want to show an image next to the gateway's name on the frontend, enter a URL to an image.
        $plugins_url = plugins_url();
		$my_plugin = $plugins_url . '/jazzcash-woocommerce-gateway';
		//$plugin_dir = WP_PLUGIN_DIR . '/JazzCash - WooCommerce Gateway';
        $this->icon = apply_filters( 'woocommerce_gateway_icon', $my_plugin.'/assets/jazzcash-logo-200x200.png' );

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
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }

		//executes a response method
		add_action( 'woocommerce_api_jazzcashresponse', array($this, 'jazzcash_response'));
		add_action('woocommerce_receipt_jazzcash', array($this, 'receipt_page'));
    }

    // Build the administration fields for this specific Gateway
    public function init_form_fields()
    {
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
			'wsdlURL' => array( // web service added (updated)
                'title' => __('Web services / WSDL', 'jazzcash'),
                'type' => 'text',
                'desc_tip' => __('Web services URL', 'jazzcash')
                ),	
			'validateHash' => array(
				'title' => __('Validate Hash', 'jazzcash'),
				'label' => __('Validate Hash', 'jazzcash'),
				'type' => 'checkbox',
				'default' => 'yes',
				));
    }

	public function payment_fields()
    {
        if ( $description = $this->get_description() ) {
            echo wpautop( wptexturize( $description ) );
        }
		$plugins_url = plugins_url();
		$my_plugin = $plugins_url . '/jazzcash-woocommerce-gateway';
            ?>
            <style>
			.hide {
  display: none;
}
			</style>
            <div id="custom_input">
            	<label class="container-jc" id="lable_MWALLET">
                	<input type="radio" name="TxnType" value="MWALLET" checked="checked"  onclick="show_MWALLET();"> JazzCash Mobile Account<br>
                    <span style="background: url(<?php echo $my_plugin ?>/assets/mw.png) left center no-repeat;" class="checkmark"></span>    
                </label>
                <div id="MWALLET" class="text-for-jc">
                	<p>JazzCash Mobile Account can be registered on any Jazz or Warid number</p>

<p>Biometric-verified Jazz and Warid customers can self-register their Mobile Account simply by dialing *786#.</p>

<p>Enter the confirmation code within 30 seconds to make a successful payment!</p>


<p>You're almost done! </p>
<p>To change or edit your order, go back. No changes will be allowed once you click "CONFIRM ORDER".</p>
                </div>
                <label class="container-jc" id="lable_OTC">
	                <input type="radio" name="TxnType" value="OTC"  onclick="show_OTC();"> JazzCash Shop<br>
                    <span style="background: url(<?php echo $my_plugin ?>/assets/sh.png) left center no-repeat;" class="checkmark"></span> 
                </label>
                <div id="OTC" class="hide-jazzCash text-for-jc">
                	<p>JazzCash Shop is an easy payment solution.</p>

<p>Click "Confirm Order" and your JazzCash voucher number will be generated and displayed on your screen</p>

<p>You can visit any JazzCash shop and pay against your token within 24 hours.</p>

<p>To see the list of JazzCash shop locations, <a href="http://www.jazzcash.com.pk/agent-locator/" target="_blank">Click here</a></p>

<p>You're almost done! </p>
<p>To change or edit your order, go back. No changes will be allowed once you click "CONFIRM ORDER".</p>
                </div>
                <label class="container-jc" id="lable_MIGS">
	                <input type="radio" name="TxnType" value="MIGS"  onclick="show_MIGS();"> Debit/Credit Card
                    <span style="background: url(<?php echo $my_plugin ?>/assets/cd.png) left center no-repeat;" class="checkmark"></span> 
                </label>
                <div id="MIGS" class="hide-jazzCash text-for-jc">
                	<p>Any local or international Visa/Mastercard Credit or Debit Card holder can pay online.</p>

<p>Please make sure your card is activated for online shopping. For more information about card activation Click here</p>

<p>You're almost done! </p>
<p>To change or edit your order, go back. No changes will be allowed once you click "CONFIRM ORDER".</p>
                </div>
            </div>
            <?php
        }
		
	/**
     * Receipt Page
     **/
    function receipt_page($order)
    {
        echo '<p>'.__('Please wait while your are being redirected to JazzCash...', 'jazzcash').'</p>';
		$plugins_url = plugins_url();
		$my_plugin = $plugins_url . '/jazzcash-woocommerce-gateway';
		echo '<p><img src="'.$my_plugin.'/assets/jazz-cash.png" /></p>';
        echo $this->generate_jazzcash_form($order);
    }
	
	/**
     * Generate jazzcash button link
     **/
    public function generate_jazzcash_form($order_id)
    {
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
		$_TxnType = get_post_meta( $order_id, 'TxnType', true );
		$_Language      = 'EN';
        $_Version       = '1.1';
        $_Currency      = 'PKR';
        $_BillReference = $customer_order->get_order_number();
		$_AmountTmp = $customer_order->order_total*100;
		$_AmtSplitArray = explode('.', $_AmountTmp);
		$_FormattedAmount = $_AmtSplitArray[0];
		
		date_default_timezone_set("Asia/karachi");
		$DateTime       = new DateTime();
		$_TxnRefNumber_WM  = "T" . $DateTime->format('YmdHisu');
		$_TxnRefNumber = substr($_TxnRefNumber_WM, 0, -3); // TxnRefNumber with mili seconds (updated)

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
        $SortedArrayOld = $SortedArrayOld . '&' . $_ReturnURL . '&' . $_Currency . '&' . $_TxnDateTime . '&' . $_ExpiryDateTime  . '&' . $_TxnRefNumber . '&' . $_TxnType . '&' . $_Version;
        $SortedArrayOld = $SortedArrayOld . '&' . $ppmpf1 . '&' . $ppmpf2 . '&' . $ppmpf3 . '&' . $ppmpf4 . '&' . $ppmpf5;
        
        //Calculating Hash
        $_Securehash = hash_hmac('sha256', $SortedArrayOld, $_IntegritySalt);
		
		$TxnType = get_post_meta( $order_id, 'TxnType', true );

		$jazzcash_args = array(
			'pp_Version' => $_Version,
			'pp_TxnType' => $TxnType,
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

		//insert data into reference talbe
		
		global $wpdb;
		$table_name = $wpdb->prefix . "jazz_cash_order_ref";
        $wpdb->insert(
			''.$table_name.'', 
			array( 
			'id_order' => ''.$order_id.'', 
			'pp_TxnRefNo' => ''.$_TxnRefNumber.'', 
			'is_updated' => 0 
			), 
			array( 
				'%d',
				'%s', 
				'%d'
			) 
		);
		
		WC()->session->set('jazzCashRequestData',  $jazzcash_args);
		
		$jazzcash_args_array = array();
        foreach($jazzcash_args as $key => $value){
          $jazzcash_args_array[] = "<input type='hidden' name='$key' value='$value'/>";
        }
        
		$form  = '<form action="'.$_ActionURL.'" id="jazzcashPostForm" name="JazzCashForm" method="post">';
		$form .= implode('', $jazzcash_args_array);
		$form .= '</form> <script type="text/javascript">document.getElementById("jazzcashPostForm").submit(); </script>';

		return $form;
	}
	
    /**
     * Process the payment and return the result
     **/
    function process_payment($order_id)
    {
		global $woocommerce;
    	$order = new WC_Order( $order_id );

		// Redirect to receipt page, which will contain the form that will actually
		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url(true),
		);
	}

    // Check if we are forcing SSL on checkout pages
    // Custom function not required by the Gateway
    public function do_ssl_check()
    {
        if ($this->enabled == "yes") {
            if (get_option('woocommerce_force_ssl_checkout') == "no") {
                echo "<div class=\"error\"><p>" . sprintf(__("<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>"),
                    $this->method_title, admin_url('admin.php?page=wc-settings&tab=checkout')) .
                    "</p></div>";
            }
        }
    }
	
	public function jazzcash_response()
    {
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
			$_MerchantID    = $this->merchantID;
			$_Password      = $this->password;
			$_IntegritySalt = $this->integritySalt;
			$_ValidateHash 	= $this->validateHash;
			
			$_ResponseMessage = $this->getEmptyIfNullFromPOST('pp_ResponseMessage');
			$pp_TxnType = $this->getEmptyIfNullFromPOST('pp_TxnType');
			$_ResponseCode    = $this->getEmptyIfNullFromPOST('pp_ResponseCode');
			$_TxnRefNo        = $this->getEmptyIfNullFromPOST('pp_TxnRefNo');
			$_BillReference   = $this->getEmptyIfNullFromPOST('pp_BillReference');
			$_SecureHash      = $this->getEmptyIfNullFromPOST('pp_SecureHash');

			$requestData = WC()->session->get('jazzCashRequestData');
			
			if (strtolower($_ValidateHash) == 'yes') {
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

					if (strtolower($CalSecureHash) == strtolower($_SecureHash)) {
						$isResponseOk = true;
					} else {
						$isResponseOk = false;
						$comment .= "Secure Hash mismatched.";
					}
				} else {
					$isResponseOk = false;
					$comment .= "Secure Hash is empty.";
				}
			} else {
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
			
			$order = new WC_Order($_BillReference);
			if($isResponseOk) {
				global $wpdb;
				$post_table = $wpdb->prefix ."posts";
				if($_ResponseCode == '000') {
					if($pp_TxnType=='MWALLET') {
						$wpdb->update(
							''.$post_table.'', 
							array( 
							'post_status' => 'wc-mwSuccess'	// string
							),
							array( 'ID' => $order->get_id() ), 
							array( 
							'%s'
							), 
							array( '%d' ) 
						);
						$order = new WC_Order($_BillReference);
						foreach ($order->get_items() as $item_id => $item) {
							// Get an instance of corresponding the WC_Product object
							$product = $item->get_product();
							$qty = $item->get_quantity(); // Get the item quantity
							wc_update_product_stock($product, $qty, 'decrease');
						}
						$woocommerce->cart->empty_cart();
					}
					if($pp_TxnType=='MIGS'){
						//$order->update_status('cardSuccess');
						$wpdb->update( //updating order status via query
							''.$post_table.'', 
							array( 
							'post_status' => 'wc-cardSuccess'	// string
							// integer (number) 
							), 
							array( 'ID' => $order->get_id() ), 
							array( 
							'%s'
							), 
							array( '%d' ) 
						);
						$order = new WC_Order($_BillReference);
						foreach ($order->get_items() as $item_id => $item) {
							// Get an instance of corresponding the WC_Product object
							$product = $item->get_product();
							$qty = $item->get_quantity(); // Get the item quantity
							wc_update_product_stock($product, $qty, 'decrease');
						}
						$woocommerce->cart->empty_cart();
						}
				}
				
				else if ($_ResponseCode == '124') {
						//$order->update_status('Payment Success / Ready for Shipment');
						$wpdb->update( //updating order status via query
							''.$post_table.'', 
							array( 
							'post_status' => 'wc-otcPending'	// string
							// integer (number) 
							), 
							array( 'ID' => $order->get_id() ), 
							array( 
							'%s'
							), 
							array( '%d' ) 
						);
						$order = new WC_Order($_BillReference);
						foreach ($order->get_items() as $item_id => $item) {
							// Get an instance of corresponding the WC_Product object
							$product = $item->get_product();
							$qty = $item->get_quantity(); // Get the item quantity
							wc_update_product_stock($product, $qty, 'decrease');
						}
						$woocommerce->cart->empty_cart();
					
				}
				else if ($_ResponseCode == '349') {
					//$order->update_status('timeOut');
					$wpdb->update( //updating order status via query
							''.$post_table.'', 
							array( 
							'post_status' => 'wc-timeOut'	// string
							// integer (number) 
							), 
							array( 'ID' => $order->get_id() ), 
							array( 
							'%s'
							), 
							array( '%d' ) 
						);
					$woocommerce->cart->empty_cart();
					//restore stock
					$order = new WC_Order($_BillReference);
					foreach ($order->get_items() as $item_id => $item) {
						// Get an instance of corresponding the WC_Product object
						$product = $item->get_product();
						$qty = $item->get_quantity(); // Get the item quantity
						wc_update_product_stock($product, $qty, 'increase');
					}
				} else {
                    if($pp_TxnType=='MWALLET'){
						//$order->update_status('mwFailure');
						$wpdb->update( //updating order status via query
							''.$post_table.'', 
							array( 
							'post_status' => 'wc-mwFailure'	// string
							// integer (number) 
							), 
							array( 'ID' => $order->get_id() ), 
							array( 
							'%s'
							), 
							array( '%d' ) 
						);
						//restore stock
						$order = new WC_Order($_BillReference);
						foreach ($order->get_items() as $item_id => $item) {
							// Get an instance of corresponding the WC_Product object
							$product = $item->get_product();
							$qty = $item->get_quantity(); // Get the item quantity
							wc_update_product_stock($product, $qty, 'increase');
						}
					}
					if($pp_TxnType=='MIGS') {
						//$order->update_status('migsFailure');
						$wpdb->update( //updating order status via query
							''.$post_table.'', 
							array( 
							'post_status' => 'wc-migsFailure'	// string
							// integer (number) 
							), 
							array( 'ID' => $order->get_id() ), 
							array( 
							'%s'
							), 
							array( '%d' ) 
						);
						//restore stock
						$order = new WC_Order($_BillReference);
						foreach ($order->get_items() as $item_id => $item) {
							// Get an instance of corresponding the WC_Product object
							$product = $item->get_product();
							$qty = $item->get_quantity(); // Get the item quantity
							wc_update_product_stock($product, $qty, 'increase');
						}
					}
					if($pp_TxnType=='OTC') {
						//$order->update_status('otcFailure');
						$wpdb->update( //updating order status via query
							''.$post_table.'', 
							array( 
							'post_status' => 'wc-otcFailure'	// string
							// integer (number) 
							), 
							array( 'ID' => $order->get_id() ), 
							array( 
							'%s'
							), 
							array( '%d' ) 
						);
						//restore stock
						$order = new WC_Order($_BillReference);
						foreach ($order->get_items() as $item_id => $item) {
							// Get an instance of corresponding the WC_Product object
							$product = $item->get_product();
							$qty = $item->get_quantity(); // Get the item quantity
							wc_update_product_stock($product, $qty, 'increase');
						}
					}
				}
				
				//takes customer to payment success / failure page
				wp_redirect($this->get_return_url($order));
				exit;
			} else {
				//takes customer to payment success / failure page
				$order->update_status($orderStatusFailed);
				wp_redirect($this->get_return_url($order));
				exit;
			}
		} catch(Exception $e) {
			//takes customer to payment success / failure page
			wp_redirect($this->get_return_url($order));
			exit;
		}
	}

    protected function complete_order($order, $posted_data)
    {
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
	
}
//Stylize radio buttons and divs
	add_action( 'wp_enqueue_scripts', 'jc_adding_scripts' );
	
	function jc_adding_scripts() {
	 
		wp_register_script('buttons', plugins_url('js/buttons.js', __FILE__)); 
		
		wp_enqueue_script('buttons');
	}
	
	add_action( 'wp_enqueue_scripts', 'jc_adding_styles' );
	
	function jc_adding_styles() {
	wp_register_style('jc_stylesheet', plugins_url('css/jc-buttons.css', __FILE__));
	wp_enqueue_style('jc_stylesheet');
	}
// Set Transaction type ACCORDINGLY

/**
 * Update the order meta with field value
 */
add_action( 'woocommerce_checkout_update_order_meta', 'jc_payment_update_order_meta' );
function jc_payment_update_order_meta( $order_id ) {

    if($_POST['payment_method'] != 'jazzcash')
        return;

    /* echo "<pre>";
     print_r($_POST);
     echo "</pre>";
     exit();*/

    update_post_meta( $order_id, 'TxnType', $_POST['TxnType'] );
    //update_post_meta( $order_id, 'transaction', $_POST['transaction'] );
}

/**
 * Display field value on the order edit page
 */
add_action( 'woocommerce_admin_order_data_after_billing_address', 'jc_checkout_field_display_admin_order_meta', 10, 1 );
function jc_checkout_field_display_admin_order_meta($order){
    $method = get_post_meta( $order->get_id(), '_payment_method', true );
    if($method != 'jazzcash')
        return;

    $TxnType = get_post_meta( $order->get_id(), 'TxnType', true );
	if($TxnType=='MWALLET'){
		$TxnType_show = 'JazzCash - MWALLET';
		}
	if($TxnType=='OTC'){
		$TxnType_show = 'JazzCash - Voucher';
		}
	if($TxnType=='MIGS'){
		$TxnType_show = 'JazzCash - Card';
		}		
    //$transaction = get_post_meta( $order->id, 'transaction', true );

    echo '<p><strong>'.__( 'Transaction Type' ).':</strong> ' . $TxnType_show . '</p>';
   // echo '<p><strong>'.__( 'Transaction ID').':</strong> ' . $transaction . '</p>';
}

// creating table for order refrence number (updated)
 

 
global $wpdb;
$table_name = $wpdb->prefix . "jazz_cash_order_ref";
$my_products_db_version = '1.0.0';
$charset_collate = $wpdb->get_charset_collate();

if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) != $table_name ) {

    $sql = "CREATE TABLE `". $table_name . "`(
            `id` INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `id_order` INT(10) NOT NULL,
            `pp_TxnRefNo` VARCHAR(52) NOT NULL,
            `is_updated` INT(10) NOT NULL)    $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
    add_option( 'my_db_version', $my_products_db_version );
}
// run the install scripts upon plugin activation

add_action( 'init', 'register_jc_new_order_statuses' );

function register_jc_new_order_statuses() {
    register_post_status( 'wc-mwSuccess', array(
        'label'                     => _x( 'MWALLET Payment Success/ Ready for Shipment', 'Order status', 'woocommerce' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'MWALLET Payment Success/ Ready for Shipment <span class="count">(%s)</span>', 'MWALLET Payment Success/ Ready for Shipment<span class="count">(%s)</span>', 'woocommerce' )
    ));
	register_post_status( 'wc-cardSuccess', array(
        'label'                     => _x( 'Card Payment Success/ Ready for Shipment', 'Order status', 'woocommerce' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Card Payment Success/ Ready for Shipment <span class="count">(%s)</span>', 'Card Payment Success/ Ready for Shipment<span class="count">(%s)</span>', 'woocommerce' )
    ));
	register_post_status( 'wc-otcSuccess', array(
        'label'                     => _x( 'Payment Success / Ready for Shipment', 'Order status', 'woocommerce' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Payment Success / Ready for Shipment <span class="count">(%s)</span>', 'Payment Success / Ready for Shipment<span class="count">(%s)</span>', 'woocommerce' )
    ));
	register_post_status( 'wc-timeOut', array(
        'label'                     => _x( 'Transaction Time Out', 'Order status', 'woocommerce' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Transaction Time Out <span class="count">(%s)</span>', 'Transaction Time Out<span class="count">(%s)</span>', 'woocommerce' )
    ));
	register_post_status( 'wc-otcPending', array(
        'label'                     => _x( 'Payment pending / Shipment Pending', 'Order status', 'woocommerce' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Payment pending / Shipment Pending<span class="count">(%s)</span>', 'Payment pending / Shipment Pending<span class="count">(%s)</span>', 'woocommerce' )
    ));
	register_post_status( 'wc-mwFailure', array(
        'label'                     => _x( 'MWALLET Failure', 'Order status', 'woocommerce' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'MWALLET Failure<span class="count">(%s)</span>', 'MWALLET Failure<span class="count">(%s)</span>', 'woocommerce' )
    ));
	register_post_status( 'wc-otcFailure', array(
        'label'                     => _x( 'OTC/Voucher Failure', 'Order status', 'woocommerce' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'OTC/Voucher Failure<span class="count">(%s)</span>', 'OTC/Voucher Failure<span class="count">(%s)</span>', 'woocommerce' )
    ));
	register_post_status( 'wc-migsFailure', array(
        'label'                     => _x( 'Card Failure', 'Order status', 'woocommerce' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Card Failure<span class="count">(%s)</span>', 'Card Failure<span class="count">(%s)</span>', 'woocommerce' )
    ));
}


add_filter( 'wc_order_statuses', 'jc_new_wc_order_statuses' );

// Register in wc_order_statuses.
function jc_new_wc_order_statuses( $order_statuses )
{
    $order_statuses['wc-mwSuccess'] = _x( 'MWALLET Payment Success/ Ready for Shipment', 'Order status', 'woocommerce' );
	$order_statuses['wc-cardSuccess'] = _x( 'Card Payment Success/ Ready for Shipment', 'Order status', 'woocommerce' );
	$order_statuses['wc-otcSuccess'] = _x( 'Payment Success / Ready for Shipment', 'Order status', 'woocommerce' );
	$order_statuses['wc-timeOut'] = _x( 'Transaction Time Out', 'Order status', 'woocommerce' );
	$order_statuses['wc-otcPending'] = _x( 'Payment pending / Shipment Pending', 'Order status', 'woocommerce' );
	$order_statuses['wc-mwFailure'] = _x( 'MWALLET Failure', 'Order status', 'woocommerce' );
	$order_statuses['wc-otcFailure'] = _x( 'OTC/Voucher Failure', 'Order status', 'woocommerce' );
	$order_statuses['wc-migsFailure'] = _x( 'Card Failure', 'Order status', 'woocommerce' );
    return $order_statuses;
}
add_filter( 'woocommerce_thankyou_order_received_text', 'wpb_thankyou', 10, 2 );
function wpb_thankyou( $thankyoutext, $order )
{
	$order_status = $order->get_status();
	if ($order_status == 'mwSuccess'){
		$order_message = '<h2>Thanks Your Order has been processed</h2>';
		}
	if ($order_status == 'cardSuccess'){
		$order_message = '<h2>Thanks Your Order has been processed</h2>';
		}
	if ($order_status == 'timeOut'){
		$order_message = '<h2>Sorry Your Order has been failed</h2>';
		}
	if ($order_status == 'otcSuccess'){
		$order_message = '<h2>Order is placed and waiting for financials to be received over the counter</h2>';
		}
	if ($order_status == 'otcPending'){
		$order_message = '<h2>Order is placed and waiting for financials to be received over the counter</h2>';
		}				
	if ($order_status == 'mwFailure'){
		$order_message = '<h2>Sorry Your Order has been failed</h2>';
		}	
	if ($order_status == 'otcFailure'){
		$order_message = '<h2>Sorry Your Order has been failed</h2>';
		}
	if ($order_status == 'migsFailure'){
		$order_message = '<h2>Sorry Your Order has been failed</h2>';
		}		
	$added_text = $order_message;
	return $added_text ;
}
