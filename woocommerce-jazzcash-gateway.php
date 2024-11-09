<?php
/*
Plugin Name: JazzCash - WooCommerce Gateway
Plugin URI: https://jazzcash.com.pk/digital-payments/online-payments/
Description: Extends WooCommerce by Adding the JazzCash Payment Gateway.
Version: 1.0
Author: JC Development Team
Author URI: https://jazzcash.com.pk/
*/

// Include our Gateway Class and register Payment Gateway with WooCommerce
add_action( 'plugins_loaded', 'jazzcash_init', 0 );
function jazzcash_init() {
	// If the parent WC_Payment_Gateway class doesn't exist
	// it means WooCommerce is not installed on the site
	// so do nothing
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;
	
	// If we made it this far, then include our Gateway Class
	include_once( 'woocommerce-jazzcash.php' );

	// Now that we have successfully included our class,
	// Lets add it too WooCommerce
	add_filter( 'woocommerce_payment_gateways', 'add_jazzcash_gateway' );
	function add_jazzcash_gateway( $methods ) {
		$methods[] = 'Jazzcash';
		return $methods;
	}
}

// Add custom action links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'jazzcash_action_links' );
function jazzcash_action_links( $links ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout' ) . '">' . __( 'Settings', 'jazzcash' ) . '</a>',
	);

	// Merge our new link with the default ones
	return array_merge( $plugin_links, $links );	
}