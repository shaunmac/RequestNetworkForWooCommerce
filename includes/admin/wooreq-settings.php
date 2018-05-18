<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$webhook_url = WooReq_Helper::get_webhook_url();

return apply_filters( 'wc_wooreq_settings',
	array(
		'enabled' => array(
			'title'       => __( 'Enable/Disable', 'woocommerce-gateway-wooreq' ),
			'label'       => __( 'Enable \'Pay with Request\'', 'woocommerce-gateway-wooreq' ),
			'type'        => 'checkbox',
			'description' => '',
			'default'     => 'no',
		),
		'title' => array(
			'title'       => __( 'Title', 'woocommerce-gateway-wooreq' ),
			'type'        => 'text',
			'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-gateway-wooreq' ),
			'default'     => __( 'Pay with Request', 'woocommerce-gateway-wooreq' ),
			'desc_tip'    => true,
		),
		'description' => array(
			'title'       => __( 'Description', 'woocommerce-gateway-wooreq' ),
			'type'        => 'text',
			'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-gateway-wooreq' ),
			'default'     => __( 'Pay with cryptocurrencies powered by the Request Network.', 'woocommerce-gateway-wooreq' ),
			'desc_tip'    => true,
		),
		'eth_payment_address' => array(
			'title'       => __( 'Your Ethereum Address', 'woocommerce-gateway-wooreq' ),
			'type'        => 'text',
			'description' => __( 'Your Ethereum address for receiving payment.', 'woocommerce-gateway-wooreq' ),
			'default'     => __( '0x000000000000000000000000000000000000000', 'woocommerce-gateway-wooreq' ),
			'desc_tip'    => true,
		),
		'btc_payment_address' => array(
			'title'       => __( 'Your Bitcoin Address', 'woocommerce-gateway-wooreq' ),
			'type'        => 'text',
			'description' => __( 'Your Bitcoin address for receiving payment.', 'woocommerce-gateway-wooreq' ),
			'desc_tip'    => true,
		),
		'accepted_currencies' => array(
			'title'       => __( 'Accepted Currencies', 'woocommerce-gateway-wooreq' ),
			'label'       => __( 'Select which currencies you would like to accept', 'woocommerce-gateway-wooreq' ),
			'type'        => 'multiselect',
	        'options'       => array(
	            'ETH' => __('Ethereum (ETH)', 'woocommerce-gateway-wooreq' ),
	            'BTC' => __('Bitcoin (BTC)', 'woocommerce-gateway-wooreq' ),
	            'OMG' => __('OmiseGO (OMG)', 'woocommerce-gateway-wooreq' ),
	            'REQ' => __('Request Network (REQ)', 'woocommerce-gateway-wooreq' ),
	            'KNC' => __('Kyber Network (KNC)', 'woocommerce-gateway-wooreq' ),
	            'DAI' => __('Dai (DAI)', 'woocommerce-gateway-wooreq' ),
	            'DGX' => __('Digix Gold (DGX)', 'woocommerce-gateway-wooreq' ),
	        ),
			'description' => __( 'Select which cryptocurrencies you would like to accept - CTRL + Click to select multiple options.', 'woocommerce-gateway-wooreq' ),
			'desc_tip'    => true,
		),
		'testmode' => array(
			'title'       => __( 'Test mode', 'woocommerce-gateway-wooreq' ),
			'label'       => __( 'Test on the Rinkeby Network', 'woocommerce-gateway-wooreq' ),
			'type'        => 'checkbox',
			'description' => __( 'Test the plugin on the Rinkeby Network without using real ETH.', 'woocommerce-gateway-wooreq' ),
			'default'     => 'no',
			'desc_tip'    => true,
		),
		'logging' => array(
			'title'       => __( 'Logging', 'woocommerce-gateway-wooreq' ),
			'label'       => __( 'Log debug messages', 'woocommerce-gateway-wooreq' ),
			'type'        => 'checkbox',
			'description' => __( 'Save debug messages to the WooCommerce System Status log.', 'woocommerce-gateway-wooreq' ),
			'default'     => 'true',
			'desc_tip'    => true,
		),
	)
);
