<?php
/* JazzCash Payment Gateway Class */
class JazzCash extends WC_Payment_Gateway
{

    // Setup our Gateway's id, description and other values
    function __construct()
    {

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

        // Supports the default credit card form
        //$this->supports = array( 'default_credit_card_form' );

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
		
		
		//executes a calback method
		add_action( 'woocommerce_api_jazzcash', array($this, 'callback_handler'));
		
		add_action('woocommerce_receipt_jazzcash', array(&$this, 'receipt_page'));
		
    } // End __construct()

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
                'default' => __('JazzCash', 'jazzcash'),
                ),
            'description' => array(
                'title' => __('Payment Gateway Description', 'jazzcash'),
                'type' => 'textarea',
                'desc_tip' => __('Payment Gateway description', 'jazzcash'),
                'default' => __('Pay freely using JazzCash.', 'jazzcash'),
                'css' => 'max-width:350px;'),
            'pp_MerchantID' => array(
                'title' => __('Merchant Code', 'jazzcash'),
                'type' => 'text',
                'desc_tip' => __('Merchant Code', 'jazzcash'),
                ),
            'pp_Password' => array(
                'title' => __('Password', 'jazzcash'),
                'type' => 'password',
                'desc_tip' => __('Merchant Password.','jazzcash'),
                ),
            'pp_ReturnURL' => array(
                'title' => __('Return URL', 'jazzcash'),
                'type' => 'text',
                'desc_tip' => __('Merchant Url for returning Transactions','jazzcash')
                ),
			'pp_ExpiryDays' => array(
                'title' => __('Transaction Expiry Days', 'jazzcash'),
                'type' => 'number',
                'desc_tip' => __('Transaction Expiry Days', 'jazzcash'),
                ),
            'pp_SecureHash' => array(
                'title' => __('Integrty Salt', 'jazzcash'),
                'type' => 'password',
                'desc_tip' => __('Integrty Salt', 'jazzcash'),
                ),
			'pp_posturl' => array(
                'title' => __('Transaction Post URL', 'jazzcash'),
                'type' => 'text',
                'desc_tip' => __('URL to post transaction', 'jazzcash'),
                ));
    }

	
	/**
     * Receipt Page
     **/
    function receipt_page($order){
        echo '<p>'.__('Thank you for your order, you will be redirected to JazzCash website shortly.', 'jazzcash').'</p>';
        echo $this -> generate_jazzcash_form($order);
    }
	
	/**
     * Generate jazzcash button link
     **/
    public function generate_jazzcash_form($order_id){
		global $woocommerce;

        // Get this Order's information so that we know
        // who to charge and how much
        $customer_order = new WC_Order($order_id);
			//zia
		$items = $customer_order->get_items();
		$product_name  = "";
		foreach ( $items as $item ) {
			$product_name .= $item['name'].", ";
			//$product_id = $item['product_id'];
			// $product_variation_id = $item['variation_id'];
		}
		
		$_Description = trim($product_name,", ");
		$_TxnRefNumber = "Woo". date('YmdHis');
		$_AmountTmp = $customer_order->order_total*100;
		$_AmtSplitArray = explode('.', $_AmountTmp);
		$_FormattedAmount = $_AmtSplitArray[0];
		$_ExpiryTime = date('YmdHis', strtotime("+".$this->pp_ExpiryDays." hours"));
        $_PostUrl = $this->pp_posturl;
		$_TXNDateTime = date('YmdHis');
		$_BillReference = str_replace("#", "", $customer_order->get_order_number());
		$_Securehash = $this -> pp_SecureHash;
		$_Language = 'EN';
		$_Version = '1.1';
		$_TxnCurrency = 'PKR';
		
		$SortedArray = $_Securehash.'&'.$_FormattedAmount.'&'.$_BillReference.'&'.$_Description
		.'&'.$_Language.'&'.$this->pp_MerchantID.'&'.$this->pp_Password.'&'.$this->pp_ReturnURL
		.'&'.$_TxnCurrency.'&'.$_TXNDateTime.'&'.$_ExpiryTime.'&'.$_TxnRefNumber.'&'.$_Version
		.'&1&2&3&4&5';
		
		$_Securehash = hash_hmac('sha256', $SortedArray, $_Securehash);
		
		$jazzcash_args = array(
			'pp_Version' => $_Version,
			'pp_TxnType' => '',
			'pp_Language' => $_Language,
			'pp_MerchantID' => $this->pp_MerchantID,
			'pp_SubMerchantID' => '',
			'pp_Password' => $this->pp_Password,
			'pp_BankID' => '',
			'pp_ProductID' => '',
			'pp_TxnRefNo' => $_TxnRefNumber,
			'pp_Amount' => $_FormattedAmount,
			'pp_TxnCurrency' => $_TxnCurrency,
			'pp_TxnDateTime' => $_TXNDateTime,
			'pp_BillReference' => $_BillReference,
			'pp_Description' => $_Description,
			'pp_TxnExpiryDateTime' => $_ExpiryTime,
			'pp_ReturnURL' => $this->pp_ReturnURL,
			'pp_SecureHash' => $_Securehash,
			'ppmpf_1' => '1',
			'ppmpf_2' => '2',
			'ppmpf_3' => '3',
			'ppmpf_4' => '4',
			'ppmpf_5' => '5'
		);
		
		$jazzcash_args_array = array();
        foreach($jazzcash_args as $key => $value){
          $jazzcash_args_array[] = "<input type='hidden' name='$key' value='$value'/>";
        }
        
		$form = '<form action="'.$_PostUrl.'" id="postform" name="JazzCashForm" method="post">
				' . implode('', $jazzcash_args_array) . '
			</form>
			<script type="text/javascript">
				document.getElementById("postform").submit();// Form submission
			</script>';
			
		return $form;

	}
	
    /**
     * Process the payment and return the result
     **/
    public function process_payment($order_id)
    {
        global $woocommerce;
    	$order = new WC_Order( $order_id );
        return array('result' => 'success', 'redirect' => add_query_arg('order',
            $order->id, add_query_arg('key', $order->order_key, get_permalink(get_option('woocommerce_pay_page_id'))))
        );
		
		//session_start();
		// $_SESSION['customer_order']=$customer_order;
		// $_SESSION['_TxnRefNumber']=$_TxnRefNumber;
		// $_SESSION['_FormattedAmount']=$_FormattedAmount;
		// $_SESSION['ExpiryTime']=$ExpiryTime;	
		// $_SESSION['post_url']=$post_url;
		// $_SESSION['TXN_DateTime']=$TXN_DateTime;
		// $_SESSION['Bill_Reference']=$Bill_Reference;
		// $_SESSION['pp_version']= '1.1'; //$this->pp_Version;
		// $_SESSION['pp_language']= 'EN'; //$this->pp_Language;
		// $_SESSION['pp_merchantid']=$this->pp_MerchantID;
		// $_SESSION['pp_password']=$this->pp_Password;
		// $_SESSION['pp_txncurrency']= 'PKR'; //$this->pp_TxnCurrency;
		// $_SESSION['pp_returnurl']=$this->pp_ReturnURL;
		// $_SESSION['pp_securehash']=$this->pp_SecureHash;
		// //zia
		// $_SESSION['pp_Description']=$product_name;		
		// //zia
		// $customer_order->payment_complete();
		// // $woocommerce->cart->empty_cart();
				
		// return array(
		// 'result' => 'success',
		// 'message'=> 'message',
		//'redirect'=>'..\jazzcashcheckout'
		//);
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
        if ($this->enabled == "yes") {
            if (get_option('woocommerce_force_ssl_checkout') == "no") {
                echo "<div class=\"error\"><p>" . sprintf(__("<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>"),
                    $this->method_title, admin_url('admin.php?page=wc-settings&tab=checkout')) .
                    "</p></div>";
            }
        }
    }

	public function callback_handler(){
		global $woocommerce;
		try{
		$order_id = $_POST['pp_BillReference'];
		$rrn = $_POST['pp_RetreivalReferenceNo'];
		$responseCode = $_POST['pp_ResponseCode'];
		$responseMessage = $_POST['pp_ResponseMessage'];

		$order = new WC_Order( $order_id );
		if($responseCode== '000' or $responseCode == '200' or $responseCode == '121' ){
			$this -> msg['message'] = $responseCode . " " .$responseMessage;
			$this -> msg['class'] = 'woocommerce_message';
			if($order -> status == 'processing'){

			}else{
				$order -> payment_complete();
				$order -> add_order_note($this->msg['message']);
				$woocommerce -> cart -> empty_cart();
			}
		}else {
			$this -> msg['message'] = $responseCode . " " .$responseMessage;
			$this -> msg['class'] = 'woocommerce_message woocommerce_message_info';
			$order -> add_order_note($this->msg['message']);;
			$woocommerce -> cart -> empty_cart();
		}
		add_action('the_content', array(&$this, 'showMessage'));
		}
		catch(Exception $e){
			// $errorOccurred = true;
			$msg = "Error";
		}
	}
	
	
	function showMessage($content){
		return '<div class="box '.$this -> msg['class'].'-box">'.$this -> msg['message'].'</div>'.$content;
	}
	
} // End of JazzCash
?>

