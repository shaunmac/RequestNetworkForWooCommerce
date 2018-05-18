<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WC_WooReq_Webhook_Handler.
 *
 * Handles webhooks from WooReq on sources that are not immediately chargeable.
 * @since 0.1.0
 */
class WC_WooReq_Webhook_Handler extends WC_WooReq_Payment_Gateway {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 * @version 0.1.0
	 */
	public function __construct() {
		add_action( 'woocommerce_api_wooreq_process', array( $this, 'wooreq_process_callback' ) );
		add_action( 'woocommerce_api_wooreq_txid', array( $this, 'wooreq_txid_callback' ) );
	}


	/**
	 * Check incoming requests for WooReq webhook data and process them.
	 *
	 * @since 0.1.0
	 * @version 0.1.0
	 */
	public function wooreq_process_callback( ) {

		if ( ( 'GET' !== $_SERVER['REQUEST_METHOD'] ) || ! $this->is_valid_request( $_GET ) )
		{
			WC_WooReq_Logger::log( 'Incoming callback failed validation: ' . print_r( $_GET, true ) );
			wc_add_notice( 'There was an error from the Request Network payment gateway, please contact the store owner.', 'error' );
			wp_safe_redirect( wc_get_checkout_url() );			
			exit;
		} 

		if ( $this->is_cancelled_request( $_GET ) ) {
			WC_WooReq_Logger::log( 'Order has been cancelled: ' . print_r( $_GET, true ) );
			wc_add_notice( 'Payment has been cancelled', 'error' );
			wp_safe_redirect( wc_get_checkout_url() );
			exit;
		}

		try {

			$order_id 		= wc_get_order_id_by_order_key( wc_clean( stripslashes ( $_GET['key'] ) ) );
			$order 			= wc_get_order( $order_id );

			if ( ! is_object( $order ) ) {
				WC_WooReq_Logger::log( 'Error: is_object( $order ) check failed.' );
				throw new WC_WooReq_Exception( "Error: is_object( $order ) check failed." );
			}

			if ( 'processing' === $order->get_status() || 'completed' === $order->get_status() || 'on-hold' === $order->get_status() ) {
				WC_WooReq_Logger::log( 'Error: get_status check for order failed. Already in process, completed or on-hold.' );
				wc_add_notice( "It looks like your order is already in process, on-hold or completed.", 'error' );
				wp_safe_redirect( wc_get_checkout_url() );
				return;
			}

			$txid 				= wc_clean( stripslashes ( $_GET['wooreq_txid'] ) );
			$total_owed_eth 	= get_post_meta( $order_id, 'total_owed_in_eth_raw', true );

			//Check TXID is valid
			if ( $this->check_txid( $order_id, $txid, $total_owed_eth ) ) {

				update_post_meta( $order_id, 'txid', $txid );

				// Empty cart.
				WC()->cart->empty_cart();

				//Signal the payment as complete
				$order->payment_complete( $order_id );

				// Add to the order note
				$message = sprintf( __( '%s ETH has been recieved. Transaction ID = %s', 'woocommerce-gateway-wooreq' ), $total_owed_eth, $txid );
				$order->add_order_note( $message );

				// Order has been complete - redirect to the thank you page
				wp_safe_redirect ( $this->get_return_url( $order ) );
				exit;
			} else {
				// Something has gone wrong verifying the transaction
				wc_add_notice( "The transaction could not be verified, please contact the store owner.", 'error' );
				wp_safe_redirect( wc_get_checkout_url() );
				exit;
			}

		} catch ( WC_WooReq_Exception $e ) {
			WC_WooReq_Logger::log( 'Error: ' . $e->getMessage() );

			if ( $order ) {
				$order->update_status( 'failed', sprintf( __( 'Request payment failed: %s', 'woocommerce-gateway-wooreq' ), $e->getLocalizedMessage() ) );
			
				if ( $order->has_status( array( 'pending', 'failed' ) ) ) {
					$this->send_failed_order_email( $order_id );
				}
			}

			wc_add_notice( "Something went wrong, please contact the store owner.", 'error' );

			wp_safe_redirect( wc_get_checkout_url() );
			exit;
		}
		
	}

	/**
	 * Adds the TXID to the order, this is extremely useful for store owners as low GWEI gas prices can cause transactions to take several hours to confirm.
	 * This allows the store owner to manually check the blockchain to process orders. 
	 *
	 * @since 0.1.0
	 * @version 0.1.0
	 * @return bool
	 */
	public function wooreq_txid_callback( ) {

		if ( ( 'GET' !== $_SERVER['REQUEST_METHOD'] ) || ! $this->is_valid_request( $_GET ) )
		{
			WC_WooReq_Logger::log( 'Incoming callback failed for TXID validation: ' . print_r( $_GET, true ) );
			wp_send_json_error( );
			exit;
		} 

		try {
			$order_id 		= wc_get_order_id_by_order_key( wc_clean( stripslashes ( $_GET['key'] ) ) );
			$order 			= wc_get_order( $order_id );

			if ( ! is_object( $order ) ) {
				WC_WooReq_Logger::log( 'Error: is_object( $order ) in TXID callback check failed.' );
				wp_send_json_error( );
				exit;
			}

			if ( 'processing' === $order->get_status() || 'completed' === $order->get_status() || 'on-hold' === $order->get_status() ) {
				WC_WooReq_Logger::log( 'Error: get_status check for in order failed. Already in process, completed or on-hold. TXID Callback' );
				wp_send_json_error( );
				exit;
			}

			$txid = wc_clean( stripslashes ( $_GET['wooreq_txid'] ) );

			if ( !empty( $txid ) ) {

				update_post_meta( $order_id, 'txid', $txid );
				wp_send_json_success( );
				exit;
			} 
			else {
				WC_WooReq_Logger::log( 'Error: wooreq_txid_callback empty check.' );
				wp_send_json_error( );
			}

		} catch ( WC_WooReq_Exception $e ) {
			WC_WooReq_Logger::log( 'Error: ' . $e->getMessage() );

			wp_send_json_error( );
			exit;
		}
		exit;

	}

	/**
	 * Validates the incoming transaction ID, checks if it's mined, the receiving address and the amounts.
	 *
	 * @since 0.1.0
	 * @version 0.1.0
	 * @return bool
	 */
	private function check_txid( $order_id, $txid, $expected_amount ) {

		try {

			$ch = curl_init();

			$network = get_post_meta( $order_id, 'network', true );
			$network_url = "mainnet";

			if ( !empty( $network ) ) {
				$network_url = $network;
			}

			//Construct the URL - we will get the transaction by it's hash and check client side.
			$api_call_url = sprintf( __( 'https://api.infura.io/v1/jsonrpc/%s/eth_getTransactionByHash?params=["%s"]', 'woocommerce-gateway-wooreq' ), $network_url, $txid );

			curl_setopt($ch, CURLOPT_URL, $api_call_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_HEADER, FALSE);

			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			  "Content-Type: application/json",
			  "Accept: application/json"
			));

			$response = curl_exec($ch);
			curl_close($ch);

			if ( !$response ) {
				return false;
			}

			//Check the value sent, from address and to address. 
			$json_response = json_decode( $response );

			//Check the 'from' address - we need to store this address to use for refunds later
			$from_address = $json_response->result->from;

			// We don't return false here as it's not absolutely critical for the order. TXID can be checked on-chain.
			if ( $from_address && WooReq_Helper::is_valid_address( $from_address ) ) {
				update_post_meta( $order_id, 'from_address', $from_address );
			} else {
				WC_WooReq_Logger::log( sprintf( __( 'Error: from_address( $order ) check failed. %s', 'woocommerce-gateway-wooreq' ), $from_address ) );
			}

			/*
			* As the transaction goes through the smart contract we can't just check the 'to' address
			* Instead, we must check the address is in the input data on the chain (less the 0x)
			*/
			$input_data_chain = $json_response->result->input; 
			$to_address_order = get_post_meta( $order_id, 'to_address', true );

			if ( empty ( $to_address_order ) ) {
				WC_WooReq_Logger::log( sprintf( __( 'Error: empty ( $to_address_order ) check failed. %s', 'woocommerce-gateway-wooreq' ), $to_address_order ) );
				return false;
			}

			$to_address_formatted = WooReq_Helper::remove_hex_prefix( $to_address_order );		


			//Check if the input data contains the to_address (store owners address)
			if ( strpos( strtolower ( $input_data_chain ), strtolower ( $to_address_formatted ) ) !== false ) {

				//Finally, check the value
				$value = $json_response->result->value;

				if ( $value ) {

					$dec_value = WooReq_Helper::bchexdec( $value );	

					//Check if the transaction has been mined + also is the correct amount
					if ( $this->is_mined( $network_url, $txid ) ) {
						if ( $this->is_correct_amount( $dec_value, $expected_amount ) ) {
							//All checks have passed successfully
							return true;
						}
						else {
							return false;
						}
					}
					else {

						WC_WooReq_Logger::log( sprintf( __( 'Error: is_mined( $order ) check failed. %s', 'woocommerce-gateway-wooreq' ), $from_address ) );
						return false;
						
					}		
				}
				return true;
			}
			else {
				WC_WooReq_Logger::log( sprintf( __( 'Error: to_address( $order ) check failed. %s, address: %s', 'woocommerce-gateway-wooreq' ), $input_data_chain, $from_address ) );
				return false;
			}
		

		} catch ( WC_WooReq_Exception $e ) {
			WC_WooReq_Logger::log( 'Error: ' . $e->getMessage() );
			return false;
		}

	}

	/**
	 * Checks if the transaction has been mined. We validate this on https://sign.wooreq.com/validate but we need to check server side to be safe.
	 *
	 * @since 0.1.0
	 * @version 0.1.0
	 * @return bool
	 */
	private function is_mined( $network_url, $txid ) {

		$api_call_url = sprintf( __( 'https://api.infura.io/v1/jsonrpc/%s/eth_getTransactionReceipt?params=["%s"]', 'woocommerce-gateway-wooreq' ), $network_url, $txid );

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $api_call_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);

		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		  "Content-Type: application/json",
		  "Accept: application/json"
		));

		$response = curl_exec($ch);
		curl_close($ch);

		if ( !$response ) {
			return false;
		}


		//Check the status 0x1 = mined. 
		$json_response = json_decode( $response );

		//Check the 'from' address - we need to store this address to use for refunds later
		$status = $json_response->result->status;

		return $status == "0x1";
	}

	/**
	 * Checks the amount sent in the transaction - we have to consider the REQ fee too.
	 *
	 * @since 0.1.0
	 * @version 0.1.0
	 * @return bool
	 */
	private function is_correct_amount( $value_sent, $expected_amount ) {

		// We need to pad everything to a consistant format
		$pad_amount = 22;
		// Add some 
		$dust_amount = 0.00000000000000001;
		$normalised_sent = str_pad ( bcdiv( $value_sent, '1000000000000000000', 18 ), $pad_amount, '0' );

		$upper_bound_calc = ($expected_amount * 1.001) + $dust_amount;
		//The TXID doesn't include the REQ fee so we must account for the bounds
		$upper_bound = ( str_pad ( $upper_bound_calc, $pad_amount, '0' ) );
		$lower_bound = str_pad ( $expected_amount, $pad_amount, '0' );

		if ( $normalised_sent >= $lower_bound && $normalised_sent <= $upper_bound ) {
			return true;
		}
		else {
			WC_WooReq_Logger::log( sprintf( __( 'Error: is_correct_amount( $order ) check failed. normalised_sent: %s. upper_bound: %s. lower_bound: %s.', 'woocommerce-gateway-wooreq' ), $normalised_sent, $upper_bound, $lower_bound ) );
			return false;
		}
	}


	/**
	 * Verify the incoming webhook notification to make sure it's valid.
	 *
	 * @since 0.1.0
	 * @version 0.1.0
	 * @param string $request_headers The request headers from WooReq.
	 * @param string $request_body The request body from WooReq.
	 * @return bool
	 */
	public function is_valid_request( $get_values = null, $request_body = null ) {
		if ( null === $get_values ) {
			return false;
		}

		if ( ! isset( $get_values['key'] ) || ! isset( $get_values['wooreq_txid'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Checks the TXID returned from https://sign.wooreq.com to see if it was cancelled.
	 *
	 * @since 0.1.0
	 * @version 0.1.0
	 * @param string $request_headers The request headers from WooReq.
	 * @param string $request_body The request body from WooReq.
	 * @return bool
	 */
	public function is_cancelled_request( $get_values = null, $request_body = null ) {
		
		if ( null === $get_values ) {
			return false;
		}

		if ( isset( $get_values['wooreq_txid'] ) ) {
			$ret_value = $get_values['wooreq_txid'];

			if ( $ret_value == 'cancelled' ) {
				return true;
			}
			
		}

		return false;
	}
}

new WC_WooReq_Webhook_Handler();
