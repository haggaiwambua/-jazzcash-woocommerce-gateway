<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/* JazzCash Payment Gateway Class */
class WC_Gateway_JazzCash extends WC_Payment_Gateway
{
    public function __construct() {
        $this->id = "jazzcash";
        $this->method_title = __("JazzCash", 'woocommerce-jazzcash');
        $this->method_description = __("JazzCash Payment Gateway Plug-in for WooCommerce", 'woocommerce-jazzcash');
        $this->title = __("JazzCash", 'woocommerce-jazzcash');
        //$this->icon =  apply_filters( 'woocommerce_gateway_icon', plugins_url('assets/images/jazzcash-logo.png', dirname(__FILE__)) );

        $this->has_fields = true;

        $this->init_form_fields();
        $this->init_settings();

        foreach ($this->settings as $setting_key => $value) {
            $this->$setting_key = $value;
        }

        if (is_admin()) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        }

        add_action('woocommerce_api_wc_gateway_jazzcash', [$this, 'handle_return']);
        add_action('woocommerce_receipt_' . $this->id, [$this, 'receipt_page']);
    }

    public function init_form_fields()
    {
        $this->form_fields = [
            'enabled' => [
                'title' => __('Enable / Disable', 'woocommerce-jazzcash'),
                'label' => __('Enable this payment gateway', 'woocommerce-jazzcash'),
                'type' => 'checkbox',
                'default' => 'no',
            ],
            'title' => [
                'title' => __('Payment Gateway Title', 'woocommerce-jazzcash'),
                'type' => 'text',
                'desc_tip' => __('Payment title the customer will see during the checkout process.',
                    'woocommerce-jazzcash'),
                'default' => __('JazzCash', 'woocommerce-jazzcash')
            ],
            'description' => [
                'title' => __('Payment Gateway Description', 'woocommerce-jazzcash'),
                'type' => 'textarea',
                'desc_tip' => __('Payment Gateway description', 'woocommerce-jazzcash'),
                'default' => __('Pay freely using JazzCash.', 'woocommerce-jazzcash'),
                'css' => 'max-width:350px;'
            ],
            'merchantID' => [
                'title' => __('Merchant ID', 'woocommerce-jazzcash'),
                'type' => 'text',
                'desc_tip' => __('Merchant ID', 'woocommerce-jazzcash')
            ],
            'password' => [
                'title' => __('Password', 'woocommerce-jazzcash'),
                'type' => 'password',
                'desc_tip' => __('Password.','woocommerce-jazzcash')
            ],
            'integritySalt' => [
                'title' => __('Integrty Salt', 'woocommerce-jazzcash'),
                'type' => 'password',
                'desc_tip' => __('Integrty Salt', 'woocommerce-jazzcash')
            ],
            'sandboxMode' => [
                'title' => __('Sandbox Mode', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable Sandbox Mode', 'woocommerce'),
                'default' => 'yes',
            ],
            'expiryHours' => [
                'title' => __('Transaction Expiry (Hours)', 'woocommerce-jazzcash'),
                'type' => 'number',
                'desc_tip' => __('Transaction Expiry (Hours)', 'woocommerce-jazzcash'),
                'default' => __('12', 'woocommerce-jazzcash')
            ],
        ];
    }

	public function payment_fields()
    {
        if ( $description = $this->get_description() ) {
            echo wpautop( wptexturize( $description ) );
        }
            ?>
            <div id="custom_input">
                <label class="container-jc" id="lable_MIGS">
                    <input type="radio" name="TxnType" value="MPAY" checked>Card Payment<br>
                </label>
                <label class="container-jc" id="lable_MWALLET">
                	<input type="radio" name="TxnType" value="MWALLET">Mobile Account<br>
                </label>
                <label class="container-jc" id="lable_OTC">
	                <input type="radio" name="TxnType" value="OTC">Voucher Payment
                </label>
            </div>
        <?php
    }

    public function receipt_page($order_id) {
        $this->generate_jazzcash_form($order_id);
    }

    public function generate_jazzcash_form($order_id) {
        global $wpdb;
        $order = new WC_Order( $order_id );

        $amount = $order->get_total();
        $return_url = add_query_arg('wc-api', 'wc_gateway_jazzcash', home_url('/'));

        $payment_type = isset($_POST['TxnType']) ? sanitize_text_field($_POST['TxnType']) : 'MPAY';

        $DateTime       = new DateTime();
        $DateTime->setTimezone(new DateTimeZone('Asia/karachi'));
        $_TxnRefNumber  = "T" . $DateTime->format('YmdHis') . mt_rand(10, 100);
        $_TxnDateTime   = $DateTime->format('YmdHis');

        $items = $order->get_items();
        $product_name  = array();
        foreach ( $items as $item ) {
            array_push($product_name, $item['name']);
        }
        $_Description   = implode(", ", $product_name);

        $ExpiryDateTime = $DateTime;
        $ExpiryDateTime->modify('+' . $this->expiryHours . ' hours');
        $_ExpiryDateTime = $ExpiryDateTime->format('YmdHis');

        $payload = [
            'pp_Version' => '1.1',
            'pp_TxnType' => $payment_type,
            'pp_Language' => 'EN',
            'pp_MerchantID' => $this->merchantID,
            'pp_SubMerchantID' => '',
            'pp_Password' => $this->password,
            'pp_BankID' => '',
            'pp_ProductID' => '',
            'pp_TxnRefNo' => $_TxnRefNumber,
            'pp_Amount' => round($amount * 100),
            'pp_TxnCurrency' => 'PKR',
            'pp_TxnDateTime' => $_TxnDateTime,
            'pp_BillReference' => $order_id,
            'pp_Description' => $_Description,
            'pp_TxnExpiryDateTime' => $_ExpiryDateTime,
            'pp_ReturnURL' => $return_url,
            'ppmpf_1' => '1',
            'ppmpf_2' => '2',
            'ppmpf_3' => '3',
            'ppmpf_4' => '4',
            'ppmpf_5' => '5'
        ];

        $payload['pp_SecureHash'] = $this->generate_secure_hash($payload);

        $table_name = $wpdb->prefix . "jazz_cash_order_ref";
        $wpdb->insert(
            $table_name,
            array(
                'order_id' => $order_id,
                'pp_TxnRefNo' => $_TxnRefNumber,
                'is_updated' => 0
            ),
            array(
                '%d',
                '%s',
                '%d'
            )
        );

        $gateway_url = $this->sandboxMode
            ? 'https://sandbox.jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform'
            : 'https://payments.jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform';

        // Display the redirect message and form
        echo '<p>' . __('Please wait while we redirect you to JazzCash...', 'woocommerce') . '</p>';
        echo '<form id="jazzcash-form" action="' . esc_url($gateway_url) . '" method="POST">';
        foreach ($payload as $key => $value) {
            echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
        }
        echo '</form>';
        echo '<script>
            setTimeout(function() {
                document.getElementById("jazzcash-form").submit();
            }, 1000);
          </script>';
    }
	
    /**
     * Process the payment and return the result
     **/
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        // Mark the order as pending payment
        $order->update_status('pending', __('Awaiting JazzCash payment', 'woocommerce-jazzcash'));

        // Redirect to the receipt page
        return [
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true),
        ];
	}

    private function generate_secure_hash($params) {
        $payload = array_filter($params);
        ksort($payload);
        $string_to_hash = $this->integritySalt . '&' . implode('&', $payload);
        return strtoupper(hash_hmac('sha256', $string_to_hash, $this->integritySalt));
    }
	
	public function handle_return() {
        global $wpdb;
        global $woocommerce;
        $response = $_POST;

        $secure_hash = $response['pp_SecureHash'];
        unset($response['pp_SecureHash']);
        $calculated_hash = $this->generate_secure_hash($response);

        if ($secure_hash !== $calculated_hash) {
            wp_die('Invalid hash.', 'JazzCash Payment', ['response' => 400]);
        }

        $order_id = $response['pp_BillReference'];
        $order = new WC_Order( $order_id );

        if ($response['pp_ResponseCode'] === '000') {
            $order->payment_complete();
            $order->add_order_note(__('JazzCash payment successful.', 'woocommerce-jazzcash'));
            $woocommerce->cart->empty_cart();
        }  else {
            $order->update_status('failed', __('JazzCash payment failed: ', 'woocommerce') . $response['pp_ResponseMessage']);

        }

        wp_redirect($order->get_checkout_order_received_url());
        exit;
	}
}
