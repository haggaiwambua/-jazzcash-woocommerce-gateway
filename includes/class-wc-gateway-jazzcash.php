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
                    <input type="radio" name="TxnType" value="MPAY">Card Payment<br>
                </label>
                <div id="jazzcash-card-fields" class="text-for-jc" style="display: none;">
                    <label for="jazzcash-card-number"><?php esc_html_e('Card Number', 'woocommerce-jazzcash'); ?></label>
                    <input id="jazzcash-card-number" name="jazzcash_card_number" type="text" maxlength="16" placeholder="4111 1111 1111 1111" />

                    <label for="jazzcash-expiry"><?php esc_html_e('Expiry Date (MMYY)', 'woocommerce-jazzcash'); ?></label>
                    <input id="jazzcash-expiry" name="jazzcash_expiry" type="text" maxlength="4" placeholder="MMYY" />

                    <label for="jazzcash-cvv"><?php esc_html_e('CVV', 'woocommerce-jazzcash'); ?></label>
                    <input id="jazzcash-cvv" name="jazzcash_cvv" type="text" maxlength="3" placeholder="123" />

                    <label for="jazzcash-cnic"><?php esc_html_e('Last 6 digits of CNIC number', 'woocommerce-jazzcash'); ?></label>
                    <input id="jazzcash-cnic" name="jazzcash_card_cnic" type="text" maxlength="6" placeholder="345678" />
                </div>
                <label class="container-jc" id="lable_MWALLET">
                	<input type="radio" name="TxnType" value="MWALLET">Mobile Account<br>
                </label>
                <div id="jazzcash-wallet-fields" class="text-for-jc" style="display: none;">
                    <label for="jazzcash-mobile-number"><?php esc_html_e('Mobile Number', 'woocommerce-jazzcash'); ?></label>
                    <input id="jazzcash-mobile-number" name="jazzcash_mobile_number" type="text" maxlength="11" placeholder="03411728699" />

                    <label for="jazzcash-cnic"><?php esc_html_e('Last 6 digits of CNIC number', 'woocommerce-jazzcash'); ?></label>
                    <input id="jazzcash-cnic" name="jazzcash_cnic" type="text" maxlength="6" placeholder="345678" />
                </div>
                <!--<label class="container-jc" id="lable_OTC">
	                <input type="radio" name="TxnType" value="OTC">Voucher Payment
                </label>
                <div id="jazzcash-voucher-fields" class="text-for-jc" style="display: none;">
                    <label for="jazzcash-voucher-number"><?php esc_html_e('Voucher Number', 'woocommerce-jazzcash'); ?></label>
                    <input id="jazzcash-voucher-number" name="jazzcash_voucher_number" type="text" maxlength="12" placeholder="03122036440" />
                </div> -->
                <!-- Spinner -->
                <div id="jazzcash-loader" style="display: none;">
                    <img src="<?php echo plugins_url('assets/images/loading.png', dirname(__FILE__)); ?>" alt="<?php esc_attr_e('Processing payment...', 'woocommerce'); ?>" />
                    <p><?php esc_html_e('Processing payment, please wait...', 'woocommerce'); ?></p>
                </div>
            </div>
        <script>
            document.querySelectorAll('input[name="TxnType"]').forEach((el) => {
                el.addEventListener('change', (e) => {
                    const cardFields = document.getElementById('jazzcash-card-fields');
                    const walletFields = document.getElementById('jazzcash-wallet-fields');
                    const voucherFields = document.getElementById('jazzcash-voucher-fields');
                    if (e.target.value === 'MPAY') {
                        walletFields.style.display = 'none';
                        //voucherFields.style.display = 'none';
                        cardFields.style.display = 'block';
                    }
                    if (e.target.value === 'MWALLET') {
                        cardFields.style.display = 'none';
                        //voucherFields.style.display = 'none';
                        walletFields.style.display = 'block';
                    }
                    /*if (e.target.value === 'OTC') {
                        cardFields.style.display = 'none';
                        walletFields.style.display = 'none';
                        voucherFields.style.display = 'block';
                    }*/
                });
            });
        </script>
        <?php
    }

    public function validate_fields() {
        $payment_method = sanitize_text_field($_POST['TxnType']);

        if ($payment_method === 'MPAY') {
            if (empty($_POST['jazzcash_card_number']) || empty($_POST['jazzcash_expiry']) || empty($_POST['jazzcash_cvv'])) {
                wc_add_notice(__('Please complete all card payment fields.', 'woocommerce-jazzcash'), 'error');
                return false;
            }
        }

        if ($payment_method === 'MWALLET') {
            if (empty($_POST['jazzcash_mobile_number']) || empty($_POST['jazzcash_cnic'])) {
                wc_add_notice(__('Please complete all mobile account payment fields.', 'woocommerce-jazzcash'), 'error');
                return false;
            }
        }

        if ($payment_method === 'OTC') {
            if (empty($_POST['jazzcash_voucher_number'])) {
                wc_add_notice(__('Please fill the voucher number.', 'woocommerce-jazzcash'), 'error');
                return false;
            }
        }

        return true;
    }
	
    /**
     * Process the payment and return the result
     **/
    public function process_payment($order_id)
    {
        global $wpdb;
        global $woocommerce;
        $order = new WC_Order( $order_id );

        $amount = $order->get_total();
        $return_url = add_query_arg('wc-api', 'wc_gateway_jazzcash', home_url('/'));

        $payment_type = isset($_POST['TxnType']) ? sanitize_text_field($_POST['TxnType']) : 'MPAY';

        $DateTime       = new DateTime();
        $DateTime->setTimezone(new DateTimeZone('Asia/karachi'));
        $_TxnRefNumber  = "T" . $DateTime->format('YmdHis');
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
            'pp_SubMerchantID' => null,
            'pp_Password' => $this->password,
            'pp_TxnRefNo' => $_TxnRefNumber,
            'pp_Amount' => round($amount * 100),
            'pp_TxnCurrency' => 'PKR',
            'pp_TxnDateTime' => $_TxnDateTime,
            'pp_BillReference' => $order_id,
            'pp_Description' => $_Description,
            'pp_TxnExpiryDateTime' => $_ExpiryDateTime,
            'pp_ReturnURL' => $return_url,
            'pp_UsageMode' => 'API',
            'ppmpf_1' => '1',
            'ppmpf_2' => '2',
            'ppmpf_3' => '3',
            'ppmpf_4' => '4',
            'ppmpf_5' => '5'
        ];

        if ($payment_type === 'MPAY') {
            $payload['pp_Version'] = '2.0';
            $payload['pp_IsRegisteredCustomer'] = 'yes';
            $payload['pp_ShouldTokenizeCardNumber'] = 'yes';
            $payload['pp_CustomerID'] = $order->get_customer_id();
            $payload['pp_CustomerEmail'] = $order->get_billing_email();
            $payload['pp_CustomerMobile'] = $order->get_billing_phone();
            $payload['pp_MobileNumber'] = $order->get_billing_phone();
            $payload['pp_CNIC'] = sanitize_text_field($_POST['jazzcash_card_cnic']);
            $payload['pp_CustomerCardNumber'] = sanitize_text_field($_POST['jazzcash_card_number']);
            $payload['pp_CustomerCardExpiry'] = sanitize_text_field($_POST['jazzcash_expiry']);
            $payload['pp_CustomerCardCvv'] = sanitize_text_field($_POST['jazzcash_cvv']);
        } elseif ($payment_type === 'MWALLET') {
            $payload['pp_Version'] = '2.0';
            $payload['pp_MobileNumber'] = sanitize_text_field($_POST['jazzcash_mobile_number']);
            $payload['pp_CNIC'] = sanitize_text_field($_POST['jazzcash_cnic']);
            $payload['pp_DiscountedAmount'] = round($amount * 100);
        } elseif ($payment_type === 'OTC') {
            $payload['ppmpf_1'] = sanitize_text_field($_POST['jazzcash_voucher_number']);
        }

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
        ? 'https://sandbox.jazzcash.com.pk/ApplicationAPI/API/2.0/Purchase/domwallettransaction'
        : 'https://payments.jazzcash.com.pk/ApplicationAPI/API/2.0/Purchase/domwallettransaction';

        $response = wp_remote_post($gateway_url, [
            'method'    => 'POST',
            'timeout'   => 45,
            'headers'   => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'body'      => http_build_query($payload),
        ]);
    
        if (is_wp_error($response)) {
            wc_add_notice(__('Payment error: Could not connect to JazzCash.', 'woocommerce-jazzcash'), 'error');
            return;
        }

        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);

        if ($response_data['pp_ResponseCode'] === '000') {
            $order->payment_complete();
            $order->add_order_note(__('JazzCash payment successful.', 'woocommerce'));
            $woocommerce->cart->empty_cart();

            return [
                'result' => 'success',
                'redirect' => $order->get_checkout_order_received_url(),
            ];
        } else {
            if (array_key_exists('pp_ResponseMessage', $response_data)){
                wc_add_notice(__('Payment error: ' . $response_data['pp_ResponseMessage'], 'error'));
            } else {
                wc_add_notice(__('Payment error: ' . $response_data['message'], 'error'));
            }
            return;
        }
	}

    private function generate_secure_hash($params) {
        ksort($params);
        $string_to_hash = $this->integritySalt . '&' . implode('&', $params);
        return strtoupper(hash_hmac('sha256', $string_to_hash, $this->integritySalt));
    }
	
	public function handle_return() {
        global $wpdb;
        global $woocommerce;
        $response = $_POST;e));

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
