<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract class that will be inherited by all payment methods.
 *
 * @extends WC_Payment_Gateway_CC
 *
 * @since 0.0.1
 */
abstract class WC_WooReq_Payment_Gateway extends WC_Payment_Gateway_CC {


	/**
	 * Check if this gateway is enabled
	 *
	 * @return float|int
	 */
	public function is_available() {
		if ( 'yes' === $this->enabled ) {
			if ( ! $this->payment_address && is_checkout() && ! is_ssl()) {
 				
 				try {

 					$current_exchange_rate = WooReq_Helper::get_crypto_rate();

 					if ( !empty( $current_exchange_rate ) ) {
 						return true;
 					}

 				} catch ( WC_WooReq_Exception $e ) {
					WC_WooReq_Logger::log( 'Error: Currency exchange rate failed in is_available();' );
 				}
				return false;
			}
			return true;
		}

		return parent::is_available();
	}

	/**
	 * Allow this class and other classes to add slug keyed notices (to avoid duplication).
	 *
	 * @since 0.0.1
	 * @version 0.0.1
	 */
	public function add_admin_notice( $slug, $class, $message ) {
		$this->notices[ $slug ] = array(
			'class'   => $class,
			'message' => $message,
		);
	}

	/**
	 * All payment icons that work with WooReq.
	 *
	 * @since 0.0.1
	 * @version 0.0.1
	 * @return array
	 */
	public function payment_icons() {
		return apply_filters( 'wc_wooreq_payment_icons', array(
			'eth'       => '<i class="wooreq-pf wooreq-pf-eth wooreq-pf-right" alt="Visa" aria-hidden="true"></i>',
		) );
	}

	/**
	 * Gets the transaction URL linked to WooReq dashboard.
	 *
	 * @since 0.0.1
	 * @version 0.0.1
	 */
	public function get_transaction_url( $order ) {

		$txid = get_post_meta( $order->get_id(), 'txid', true );

		if ( $this->testmode ) {
			$this->view_transaction_url = sprintf( __( 'https://rinkeby.etherscan.io/tx/%s', 'woocommerce-gateway-wooreq' ), $txid );
		} else {
			$this->view_transaction_url = sprintf( __( 'https://etherscan.io/tx/%s', 'woocommerce-gateway-wooreq' ), $txid );
		}

		return parent::get_transaction_url( $order );
	}

	/**
	 * Builds the return URL from redirects.
	 *
	 * @since 0.0.1
	 * @version 0.0.1
	 * @param object $order
	 * @param int $id WooReq session id.
	 */
	public function get_wooreq_return_url( $order = null, $id = null ) {
		if ( is_object( $order ) ) {
			if ( empty( $id ) ) {
				$id = uniqid();
			}

			$order_id = WooReq_Helper::is_pre_30() ? $order->id : $order->get_id();

			$args = array(
				'utm_nooverride' => '1',
				'order_id'       => $order_id,
			);

			return esc_url_raw( add_query_arg( $args, $this->get_return_url( $order ) ) );
		}

		return esc_url_raw( add_query_arg( array( 'utm_nooverride' => '1' ), $this->get_return_url() ) );
	}

	/**
	 * Sends the failed order email to admin.
	 *
	 * @since 0.0.1
	 * @version 0.0.1
	 * @param int $order_id
	 * @return null
	 */
	public function send_failed_order_email( $order_id ) {
		$emails = WC()->mailer()->get_emails();
		if ( ! empty( $emails ) && ! empty( $order_id ) ) {
			$emails['WC_Email_Failed_Order']->trigger( $order_id );
		}
	}
}
