jQuery(function ($) {
	// Listen to the WooCommerce checkout form submission
	$(document.body).on('checkout_place_order', function () {
		// Show the loading spinner
		$('#jazzcash-loader').fadeIn();
	});

	// Hide the spinner on AJAX errors or success
	$(document.body).on('checkout_error', function () {
		$('#jazzcash-loader').fadeOut();
	});
});