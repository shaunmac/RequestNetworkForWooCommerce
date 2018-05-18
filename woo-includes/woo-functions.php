<?php
/**
 * Functions used by plugins
 */
if ( ! class_exists( 'WC_WooReq_Dependencies' ) ) {
	require_once( dirname( __FILE__ ) . '/class-wc-wooreq-dependencies.php' );
}

/**
 * WC Detection
 */
if ( ! function_exists( 'wc_wooreq_is_wc_active' ) ) {
	function wc_wooreq_is_wc_active() {
		return WC_WooReq_Dependencies::woocommerce_active_check();
	}
}
