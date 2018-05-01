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
		'payment_address' => array(
			'title'       => __( 'Your Ethereum Address', 'woocommerce-gateway-wooreq' ),
			'type'        => 'text',
			'description' => __( 'Your Ethereum address for recieving payment.', 'woocommerce-gateway-wooreq' ),
			'default'     => __( '0x000000000000000000000000000000000000000', 'woocommerce-gateway-wooreq' ),
			'desc_tip'    => true,
		),
		'testmode' => array(
			'title'       => __( 'Test mode', 'woocommerce-gateway-wooreq' ),
			'label'       => __( 'Test on the Rinkeby Network', 'woocommerce-gateway-wooreq' ),
			'type'        => 'checkbox',
			'description' => __( 'Test the plugin on the Rinkeby Network without using real ETH.', 'woocommerce-gateway-wooreq' ),
			'default'     => 'yes',
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
