<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Gateway_WooReq class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_WooReq extends WC_WooReq_Payment_Gateway {
	public $retry_interval;

	/**
	 * Is test mode active?
	 *
	 * @var bool
	 */
	public $testmode;

	/**
	 * Recieving address
	 *
	 * @var string
	 */
	public $payment_address;


	/**
	 * Constructor
	 */
	public function __construct() {
		$this->retry_interval       = 2;
		$this->id                   = 'wooreq';
		$this->method_title         = __( 'Request Network', 'woocommerce-gateway-wooreq' );
		$this->method_description   = sprintf( __( 'The Request for WooCommerce plugin extends WooCommerce allowing you to take cryptocurrency payments directly on your store powered by the Request Network. More information about the Request Network can be found <a href="%s">here</a>', 'woocommerce-gateway-wooreq' ), 'https://request.network/' );
		$this->has_fields           = true;
		$this->supports             = array(
			'products',
			'add_payment_method',
			'pre-orders',
		);

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Get setting values.
		$this->title                   = $this->get_option( 'title' );
		$this->description             = $this->get_option( 'description' );
		$this->payment_address         = $this->get_option( 'payment_address' );
		$this->enabled                 = $this->get_option( 'enabled' );
		$this->testmode                = 'yes' === $this->get_option( 'testmode' );

		// Hooks.
		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		// Show payment instructions on thank you page.
		add_action( 'woocommerce_thankyou_wooreq', array( $this, 'wooreq_thank_you_page_process' ) );

		// Add the custom crypto email fields.
		add_filter( 'woocommerce_email_order_meta_fields', array( $this, 'wooreq_email_order_meta_fields' ), 10, 3 );
	}

	/**
	 * Adds the cryptocurrency information to the confirmation email
	 *
	 * @since 0.0.1
	 * @version 0.0.1
	 */
	public function wooreq_email_order_meta_fields( $fields, $sent_to_admin, $order ) {
		
		// Only show the extra fields if the payment method is 'Pay with Request'
		if ( $order->get_payment_method() != "wooreq" ) {
			return $fields;
		}

		// Total ETH paid
	    $fields['eth_paid'] = array(
	        'label' => __( 'ETH Paid' ),
	        'value' => get_post_meta( $order->get_id(), 'total_owed_in_eth_raw', true ) . " ETH",
	    );

	    $fields['eth_conversion_time'] = array(
	        'label' => __( 'ETH Conversion Time' ),
	        'value' => get_post_meta( $order->get_id(), 'eth_conversion_time', true ),
	    );

	  	$fields['conversion_rate'] = array(
	        'label' => __( 'Conversion Rate' ),
	        'value' => get_post_meta( $order->get_id(), 'eth_value', true ),
	    );

	    // Display the transaction 
		$network = get_post_meta( $order->get_id(), 'network', true );

		// Construct the etherscan TXID URL
		if ( $network == "rinkeby") {	
			$network .= '.';
		} else {
			$network = '';
		}

		$txid = get_post_meta( $order->get_id(), 'txid', true );
		$txid_url = sprintf( __( '<a href="https://%setherscan.io/tx/%s">%s</a>. ', 'woocommerce-gateway-wooreq' ), $network, $txid, $txid );

	    $fields['txid'] = array(
	        'label' => __( 'Transaction ID' ),
	        'value' => $txid_url,
	    );


	    return $fields;
	}
		

	/**
	 * Checks if gateway should be available to use.
	 *
	 * @since 0.0.1
	 */
	public function is_available() {
		return parent::is_available();
	}

	/**
	 * Returns the available currency icons.
	 *
	 * @since 0.0.1
	 * @version 0.0.1
	 * @return string
	 */
	public function get_icon() {
		$icons = $this->payment_icons();

		$icons_str = '';

		$icons_str .= $icons['eth'];

		return apply_filters( 'woocommerce_gateway_icon', $icons_str, $this->id );
	}

	/**
	 * Initialise the gateway form fields
	 *
	 * @since 0.0.1
	 * @version 0.0.1
	 */
	public function init_form_fields() {
		$this->form_fields = require( dirname( __FILE__ ) . '/admin/wooreq-settings.php' );
	}

	/**
	 * Renders the payment form on the checkout page.
	 *
	 * @since 0.0.1
	 * @version 0.0.1
	 */
	public function payment_fields() {
		$user                 = wp_get_current_user();
		$total                = WC()->cart->total;
		$user_email           = '';
		
		// If paying from order, we need to get total from order not cart.
		if ( isset( $_GET['pay_for_order'] ) && ! empty( $_GET['key'] ) ) {
			$order      = wc_get_order( wc_get_order_id_by_order_key( wc_clean( stripslashes ( $_GET['key'] ) ) ) );
			$total      = $order->get_total();
			$user_email = WooReq_Helper::is_pre_30() ? $order->billing_email : $order->get_billing_email();
		} else {
			if ( $user->ID ) {
				$user_email = get_user_meta( $user->ID, 'billing_email', true );
				$user_email = $user_email ? $user_email : $user->user_email;
			}
		}

		$total_owed_fiat = WooReq_Helper::get_wooreq_amount( $total );
		$current_exchange_rate = WooReq_Helper::get_crypto_rate();
		$total_owed_crypto = WooReq_Helper::calculate_amount_owed_crypto( $total_owed_fiat, $current_exchange_rate );

		echo '<div id="wooreq-payment">';

		echo '<p>Total to pay in ETH: <b>' . $total_owed_crypto . ' </b></p>';
		echo '<p>Current rate: <b>' . $current_exchange_rate . 'ETH / ' . get_woocommerce_currency()   . ' </b></p>';

		WC()->session->set(
			'wooreq_crypto_manager',
			array(
				'eth_value' 			=> $current_exchange_rate,
				'total_owed_in_eth'		=> $total_owed_crypto,
				'timestamp' 			=> time(),
			)
		);

		if ( $this->description ) {
			if ( $this->testmode ) {
				/* translators: link to WooReq testing page */
				$this->description .= ' ' . sprintf( __( '<i>Please note, Request for WooCommerce is currently in testmode and all payments are through the Rinkeby test net. If you need some "test" ETH you can use the Rinkeby faucet <a href="%s" target="_blank">here</a></i>.</br></br>', 'woocommerce-gateway-wooreq' ), 'https://faucet.rinkeby.io/' );

				$this->description  = trim( $this->description );
			}
			echo apply_filters( 'wc_wooreq_description', wpautop( wp_kses_post( $this->description ) ) );
		}

		$this->pay_with_request();
		echo '<div class="wooreq-errors" role="alert"></div>';

		echo '</div>';
	}

	/**
	 * Renders the WooReq button.
	 *
	 * @since 0.0.1
	 * @version 0.0.1
	 */
	public function pay_with_request() {
		?>
		<fieldset id="wc-<?php echo esc_attr( $this->id ); ?>-form" style="background:transparent;">
			<button id="request-payment-button">Pay with Request</button>
		</fieldset>
		<?php
	}

	/**
	 * Payment_scripts function.
	 *
	 * Outputs scripts used for wooreq payment
	 *
	 * @since 0.0.1
	 * @version 0.0.1
	 */
	public function payment_scripts() {
		if ( ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order'] ) && ! isset( $_GET['change_payment_method'] ) ) {
			return;
		}

		wp_register_style( 'wooreq_paymentfonts', plugins_url( 'assets/css/wooreq-paymentfonts.css', WC_WOOREQ_MAIN_FILE ), array(), '1.2.5' );
		wp_enqueue_style( 'wooreq_paymentfonts' );

		wp_register_style( 'wooreq_css', plugins_url( 'assets/css/wooreq.css', WC_WOOREQ_MAIN_FILE ), array(), '0.0.1' );
		wp_enqueue_style( 'wooreq_css' );

		if ( isset( $_GET['pay_for_order'] ) && 'true' === $_GET['pay_for_order'] ) {
			$order_id = wc_get_order_id_by_order_key( urldecode( $_GET['key'] ) );
			$order    = wc_get_order( $order_id );

			$wooreq_params['billing_first_name'] = WooReq_Helper::is_pre_30() ? $order->billing_first_name : $order->get_billing_first_name();
			$wooreq_params['billing_last_name']  = WooReq_Helper::is_pre_30() ? $order->billing_last_name : $order->get_billing_last_name();
			$wooreq_params['billing_address_1']  = WooReq_Helper::is_pre_30() ? $order->billing_address_1 : $order->get_billing_address_1();
			$wooreq_params['billing_address_2']  = WooReq_Helper::is_pre_30() ? $order->billing_address_2 : $order->get_billing_address_2();
			$wooreq_params['billing_state']      = WooReq_Helper::is_pre_30() ? $order->billing_state : $order->get_billing_state();
			$wooreq_params['billing_city']       = WooReq_Helper::is_pre_30() ? $order->billing_city : $order->get_billing_city();
			$wooreq_params['billing_postcode']   = WooReq_Helper::is_pre_30() ? $order->billing_postcode : $order->get_billing_postcode();
			$wooreq_params['billing_country']    = WooReq_Helper::is_pre_30() ? $order->billing_country : $order->get_billing_country();
		}

		$wooreq_params['wooreq_checkout_require_billing_address'] = apply_filters( 'wc_wooreq_checkout_require_billing_address', false ) ? 'yes' : 'no';
		$wooreq_params['is_checkout']                             = ( is_checkout() && empty( $_GET['pay_for_order'] ) );
		$wooreq_params['return_url']                              = $this->get_wooreq_return_url();
		$wooreq_params['ajaxurl']                                 = WC_AJAX::get_endpoint( '%%endpoint%%' );
		$wooreq_params['wooreq_nonce']                            = wp_create_nonce( '_wc_wooreq_nonce' );
		$wooreq_params['is_change_payment_page']                  = ( isset( $_GET['pay_for_order'] ) || isset( $_GET['change_payment_method'] ) ) ? 'yes' : 'no';
		$wooreq_params['elements_styling']                        = apply_filters( 'wc_wooreq_elements_styling', false );
		$wooreq_params['elements_styling']                        = apply_filters( 'wc_wooreq_elements_styling', false );
		$wooreq_params['elements_classes']                        = apply_filters( 'wc_wooreq_elements_classes', false );
	}

	/**
	 * Process the payment
	 *
	 * @since 0.0.1
	 * @version 0.0.1
	 * @param int  $order_id Reference.
	 * @param bool $retry Should we retry on fail.
	 *
	 * @throws Exception If payment will not be accepted.
	 *
	 * @return array|void
	 */
	public function process_payment( $order_id, $retry = true ) {
		try {
			$order = wc_get_order( $order_id );

			$stored_info = WC()->session->get( 'wooreq_crypto_manager' );

			$eth_value = $stored_info['eth_value'];
			$total_owed_in_eth = $stored_info['total_owed_in_eth'];
			$eth_conversion_time = $stored_info['timestamp'];

			$timezone = get_option( 'timezone_string' );

			update_post_meta( $order_id, 'eth_value', $eth_value . " ETH / GBP" );
			update_post_meta( $order_id, 'total_owed_in_eth', $total_owed_in_eth . " ETH" );
			update_post_meta( $order_id, 'eth_conversion_time', date( "d F Y H:i:s T", $eth_conversion_time ) );
			update_post_meta( $order_id, 'total_owed_in_eth_raw', $total_owed_in_eth );
			update_post_meta( $order_id, 'to_address', $this->payment_address );

			// Add order note.
			$order->add_order_note(
				sprintf( __( 'Order submitted, payment for %s ETH requested.', 'woocommerce-gateway-wooreq' ), $total_owed_in_eth )
			);

			//wooreq_txid is empty as that is sent back via the signer
			$return_data = array (
				'key'	=> $order->order_key
			);

			if ( $this->testmode ) {
				$return_data['network'] = "rinkeby";
			}

			$woocommerce_callback = WooReq_Helper::get_webhook_url() . "?" . http_build_query($return_data);

			$full_callback = sprintf( __( 'https://sign.wooreq.com/validate?callbackurl=%s&txid=', 'woocommerce-gateway-wooreq' ), $woocommerce_callback );

			$data = array (
				'order_id' 		=> $order_id,
				'redirect_url' 	=> $full_callback,
				'to_pay'		=> $total_owed_in_eth,
				'to_address'	=> $this->payment_address,
				'reason'		=> get_site_url() . " order for " . $total_owed_in_eth . " ETH "
			);

			if ( $this->testmode ) {
				$data['network'] = 4;
				update_post_meta( $order_id, 'network', 'rinkeby' );
			}
			else {
				update_post_meta( $order_id, 'network', 'mainnet' );
			}

			// Send a POST request to the signer API 
			$url = "https://sign.wooreq.com/sign?";

			$options = array(
			    'http' => array(
    				'header'=>  "Content-Type: application/json\r\n" .
                				"Accept: application/json\r\n",
			        'method'  => 'POST',
			        'content' => json_encode( $data )
			    )
			);

			$context  = stream_context_create($options);
			$result = file_get_contents($url, false, $context);

			// Send off to the Request App for payment
			return array(
				'result'   => 'success',
				'redirect' => $result,
			);

		} catch ( WC_WooReq_Exception $e ) {
			wc_add_notice( $e->getLocalizedMessage(), 'error' );
			WC_WooReq_Logger::log( 'Error: ' . $e->getMessage() );

			do_action( 'wc_gateway_wooreq_process_payment_error', $e, $order );

			if ( $order->has_status( array( 'pending', 'failed' ) ) ) {
				$this->send_failed_order_email( $order_id );
			}

			return array(
				'result'   => 'fail',
				'redirect' => '',
			);
		}
	}

	/**
	 * Displays the order confirmation page
	 *
	 * @since 0.0.1
	 * @version 0.0.1
	 * @param int  $order_id Reference.
	 *
	 *
	 * @return void
	 */
	public function wooreq_thank_you_page_process( $order_id ) {

		if ( ( 'GET' !== $_SERVER['REQUEST_METHOD'] ) || empty( $_GET['key'] ) )
		{
			return;
		}

		$order       = new WC_Order( $order_id );

		$eth_value 				= get_post_meta( $order_id, 'eth_value', true );
		$total_owed_in_eth 		= get_post_meta( $order_id, 'total_owed_in_eth', true );
		$eth_conversion_time 	= get_post_meta( $order_id, 'eth_conversion_time', true );
		$txid 					= get_post_meta( $order_id, 'txid', true );
	
		?>

		<h2 style="text-align: center;">Pay with Request details</h2>

		<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">

				<li class="woocommerce-order-overview__date date">
					Total Sent in ETH:					<strong><?= $total_owed_in_eth ?></strong>
				</li>

				<li class="woocommerce-order-overview__email email">
					ETH Conversion Rate:				<strong><?= $eth_value ?></strong>
				</li>

				<?php 

				    $network = "";

					if ( $this->testmode ) {
						$network = "rinkeby.";
					} 

					$txid_url = sprintf( __( '<a href="https://%setherscan.io/tx/%s">%s</a>. ', 'woocommerce-gateway-wooreq' ), $network, $txid, $txid );

				?>

				<li class="woocommerce-order-overview__order order">
					Transaction ID:						<strong><?= $txid_url ?></strong>
				</li>
				
				<li class="woocommerce-order-overview__total total">
					ETH Conversion Time:				<strong><?= $eth_conversion_time ?></strong>
				</li>
				
			</ul>

		<?php		
	}
}
