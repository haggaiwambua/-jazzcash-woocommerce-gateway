<?php
/*
Plugin Name: JazzCash - WooCommerce Gateway
Description: Adds JazzCash as a payment gateway for WooCommerce.
Version: 1.0
Author: Haggai Wambua
Author URI: https://haggai.dev/
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Check if WooCommerce is active
function wc_jazzcash_check_dependencies() {
    if (!class_exists('WooCommerce')) {
        // Display an admin notice
        add_action('admin_notices', 'wc_jazzcash_missing_wc_notice');
        return;
    }

    // Initialize the gateway
    add_action('plugins_loaded', 'wc_jazzcash_gateway_init', 11);
    add_filter('woocommerce_payment_gateways', 'wc_add_jazzcash_gateway');

}

// Display admin notice if WooCommerce is not active
function wc_jazzcash_missing_wc_notice() {
    echo '<div class="error"><p>';
    echo esc_html__('WooCommerce JazzCash Gateway requires WooCommerce to be installed and active.', 'woocommerce-jazzcash');
    echo '</p></div>';
}

// Initialize the gateway class
function wc_jazzcash_gateway_init() {
    include_once 'includes/class-wc-gateway-jazzcash.php';
}

// Add JazzCash to the available payment gateways
function wc_add_jazzcash_gateway($gateways) {
    $gateways[] = 'WC_Gateway_JazzCash';
    return $gateways;
}

// Hook the dependency check
add_action('plugins_loaded', 'wc_jazzcash_check_dependencies');

// Add custom action links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_jazzcash_plugin_action_links' );
function wc_jazzcash_plugin_action_links( $links ) {
    $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=jazzcash') . '">' . __('Settings', 'woocommerce-jazzcash') . '</a>';

    array_unshift($links, $settings_link);
    return $links;
}

// Function to create the table
function jazzcash_create_table() {
    global $wpdb;

    // Set the table name
    $table_name = $wpdb->prefix . "jazz_cash_order_ref";

    // Define the character set and collation
    $charset_collate = $wpdb->get_charset_collate();

    // SQL to create the table
    $sql = "CREATE TABLE $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        order_id INT(10) NOT NULL,
        pp_TxnRefNo varchar(255) NOT NULL,
        is_updated INT(10) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // Include the upgrade file to use dbDelta
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Execute the SQL statement
    dbDelta($sql);
}

// Register the activation hook
register_activation_hook(__FILE__, 'jazzcash_create_table');

add_action('wp_enqueue_scripts', 'enqueue_jazzcash_scripts');
function enqueue_jazzcash_scripts()
{
    if (is_checkout()) {
        wp_enqueue_script(
            'jazzcash-checkout',
            plugins_url('assets/js/jazzcash-checkout.js', __FILE__),
            ['jquery', 'wc-checkout'], // wc-checkout ensures it loads after WooCommerce scripts
            '1.0.0',
            true
        );

        wp_enqueue_style(
            'jazzcash-checkout-style',
            plugins_url('assets/css/jazzcash-checkout.css', __FILE__),
            array(),
            null
        );
    }
}

/**
 * Update the order meta with field value
 */
add_action( 'woocommerce_checkout_update_order_meta', 'jc_payment_update_order_meta' );
function jc_payment_update_order_meta( $order_id )
{
    if($_POST['payment_method'] != 'jazzcash')
        return;

    update_post_meta( $order_id, 'TxnType', $_POST['TxnType'] );
}

/**
 * Display field value on the order edit page
 */
add_action( 'woocommerce_admin_order_data_after_billing_address', 'jc_checkout_field_display_admin_order_meta', 10, 1 );
function jc_checkout_field_display_admin_order_meta($order)
{
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

    echo '<p><strong>'.__( 'Transaction Type' ).':</strong> ' . $TxnType_show . '</p>';
}

//add_action( 'init', 'register_jc_new_order_statuses' );
function register_jc_new_order_statuses()
{
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

// Register in wc_order_statuses.
//add_filter( 'wc_order_statuses', 'jc_new_wc_order_statuses' );
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
//add_filter( 'woocommerce_thankyou_order_received_text', 'wpb_thankyou', 10, 2 );
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
