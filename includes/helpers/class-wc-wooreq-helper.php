<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides static methods as helpers.
 *
 * @since 0.1.0
 */
class WooReq_Helper {

	/**
	 * Get amount to pay
	 *
	 * @param float  $total Amount due.
	 * @param string $currency Accepted currency.
	 *
	 * @return float|int
	 */
	public static function get_wooreq_amount( $total, $currency = '' ) {
		if ( ! $currency ) {
			$currency = get_woocommerce_currency();
		}

		if ( in_array( strtolower( $currency ), self::no_decimal_currencies() ) ) {
			return absint( $total );
		} else {
			return absint( wc_format_decimal( ( (float) $total * 100 ), wc_get_price_decimals() ) ); // In cents.
		}
	}

	/**
	 * Get payment currency from the payment_scripts() POST list
	 *
	 * @param array  $checkout_vars POST variables passed back from the checkout form
	 *
	 * @return string
	 */
	public static function get_payment_currency( $checkout_vars ) {

		$ret_val = 'ETH';

		if ( !empty( $checkout_vars ) ) {
			$post_data = $checkout_vars['post_data'];
			parse_str( $post_data, $parts );

			if ( array_key_exists( 'payment_currency', $parts ) ) {
				$payment_currency = $parts['payment_currency'];
				return $payment_currency;
			}
		}

		return $ret_val;
	}

	/**
	 * Gets the current crypto conversion rate from cryptocompare or coinmarketcap. get_woocommerce_currency()
	 *
	 * @since 0.1.0
	 * @version 0.1.2
	 * @return float
	 */
	public static function get_crypto_rate( $base_currency = '', $crypto_currency = 'ETH' ) {
		if ( ! $base_currency ) {
			$base_currency = get_woocommerce_currency();
		}

		$transient_key = 'wooreq_exchange_rate_' . $base_currency . '_' . $crypto_currency;
		// Check for a cached rate first. Use it if present.
		$rate = get_transient( $transient_key );

		if ( false !== $rate && isset( $rate ) ) {
			return (float) $rate;
		}

		if ( $crypto_currency == 'DGX' ) {

			$response = wp_remote_get( 'https://api.coinmarketcap.com/v2/ticker/2739/?convert=' . $base_currency );			

			if ( is_wp_error( $response ) ) {
				WC_WooReq_Logger::log( sprintf( __( 'Error: could not get price from coinmarketcap. BaseCurrency: %s, CryptoCurrency %s.', 'woocommerce-gateway-wooreq' ), $base_currency, $crypto_currency ) );
				throw new WC_WooReq_Exception( "Error: price check failed ( $response ) in get_crypto_rate" );		
			}
			$body = json_decode( $response['body'] );
			if ( json_last_error() !== JSON_ERROR_NONE || $body->metadata->error != null ) {
				WC_WooReq_Logger::log( sprintf( __( 'Error: Error returned from the Coinmarketcap API - %s. JSON error.', 'woocommerce-gateway-wooreq' ), $body ) );
				throw new WC_WooReq_Exception( "Error: Error returned from the Coinmarketcap API ( $body ) in get_crypto_rate" );		
			}

			if ( !isset( $body->data->quotes->$base_currency ) ) {
				WC_WooReq_Logger::log( sprintf( __( 'Error: Currency not supported by the Coinmarketcap API - %s.', 'woocommerce-gateway-wooreq' ), $body ) );
				throw new WC_WooReq_Exception( "Error: Currency not supported by the Coinmarketcap API ( $body ) in get_crypto_rate" );		
			}

			$price_per_amount = (float) round( 1 / $body->data->quotes->$base_currency->price, 6 );

			set_transient( $transient_key, $price_per_amount, 300 );

			return (float) $price_per_amount;

		} else {
			$response = wp_remote_get( 'https://min-api.cryptocompare.com/data/price?fsym=' . $base_currency . '&tsyms=' . $crypto_currency );
			if ( is_wp_error( $response ) || 200 !== $response['response']['code'] ) {
				WC_WooReq_Logger::log( sprintf( __( 'Error: could not get price from cryptocompare. BaseCurrency: %s, CryptoCurrency %s.', 'woocommerce-gateway-wooreq' ), $base_currency, $crypto_currency ) );
				throw new WC_WooReq_Exception( "Error: price check failed ( $response ) in get_crypto_rate" );			
			}
			$body = json_decode( $response['body'] );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				WC_WooReq_Logger::log( sprintf( __( 'Error: Could not convert %s. JSON error.', 'woocommerce-gateway-wooreq' ), $crypto_currency ) );
				throw new WC_WooReq_Exception( "Error: JSON Decode failed ( $body ) in get_crypto_rate" );		
			}
			if ( ! isset( $body->$crypto_currency ) ) {
				WC_WooReq_Logger::log( sprintf( __( 'Error: Could not convert %s. missing value after decoding.', 'woocommerce-gateway-wooreq' ), $crypto_currency ) );
				throw new WC_WooReq_Exception( "Error: Price conversion failed ( $body ) in get_crypto_rate" );		
			}
			
			$rounded = round( $body->$crypto_currency, 6 );

			set_transient( $transient_key, $body->$crypto_currency, 300 );
			
			return (float) $body->$crypto_currency;
		}
	}

	/**
	 * Returns a list of accepted currencies on the site.
	 *
	 * @since 0.1.2
	 * @version 0.1.2
	 * @return array
	 */
	public static function get_accepted_currencies( $filter = null ) {

		$all_currencies = array(
	        'ETH' => __( 'Ethereum (ETH)', 'woocommerce-gateway-wooreq' ),
	        // 'BTC' => __( 'Bitcoin (BTC)', 'woocommerce-gateway-wooreq' ),
	        'OMG' => __( 'OmiseGO (OMG)', 'woocommerce-gateway-wooreq' ),
	        'REQ' => __( 'Request Network (REQ)', 'woocommerce-gateway-wooreq' ),
	        'KNC' => __( 'Kyber Network (KNC)', 'woocommerce-gateway-wooreq' ),
	        'DAI' => __( 'Dai (DAI)', 'woocommerce-gateway-wooreq' ),
	        'DGX' => __( 'Digix Gold (DGX)', 'woocommerce-gateway-wooreq' )
		);

		if ( $filter ) {
			return array_flip( array_intersect( array_flip( $all_currencies ), $filter ) );
		}

		return $all_currencies;
	}

	/**
	 * Gets the amount the customer owns in crypto
	 *
	 * @since 0.1.0
	 * @version 0.1.2
	 * @return int|float
	 */
	public static function calculate_amount_owed_crypto( $total, $rate ) {
		return sprintf('%f', ($rate * $total) / 100);
	}

	/**
	 * List of currencies supported by WooReq that has no decimals.
	 *
	 * @since 0.1.0
	 * @version 0.1.0
	 * @return array $currencies
	 */
	public static function no_decimal_currencies() {
		return array(
			'bif', // Burundian Franc
			'djf', // Djiboutian Franc
			'jpy', // Japanese Yen
			'krw', // South Korean Won
			'pyg', // Paraguayan Guaraní
			'vnd', // Vietnamese Đồng
			'xaf', // Central African Cfa Franc
			'xpf', // Cfp Franc
			'clp', // Chilean Peso
			'gnf', // Guinean Franc
			'kmf', // Comorian Franc
			'mga', // Malagasy Ariary
			'rwf', // Rwandan Franc
			'vuv', // Vanuatu Vatu
			'xof', // West African Cfa Franc
		);
	}

	/**
	 * Gets all the saved setting options from a specific method.
	 * If specific setting is passed, only return that.
	 *
	 * @since 0.1.0
	 * @version 0.1.0
	 * @param string $method The payment method to get the settings from.
	 * @param string $setting The name of the setting to get.
	 */
	public static function get_settings( $method = null, $setting = null ) {
		$all_settings = null === $method ? get_option( 'woocommerce_wooreq_settings', array() ) : get_option( 'woocommerce_wooreq_' . $method . '_settings', array() );

		if ( null === $setting ) {
			return $all_settings;
		}

		return isset( $all_settings[ $setting ] ) ? $all_settings[ $setting ] : '';
	}

	/**
	 * Check if WC version is pre 3.0.
	 *
	 * @since 0.1.0
	 * @version 0.1.0
	 * @return bool
	 */
	public static function is_pre_30() {
		return version_compare( WC_VERSION, '3.0.0', '<' );
	}

	/**
	 * Gets the webhook URL that's used to process the transaction. 
	 *
	 * @since 0.1.0
	 * @version 0.1.0
	 * @return string
	 */
	public static function get_webhook_url() {
		return get_home_url() . "/wc-api/wooreq_process";
	}

	/**
	 * Check for a valid Ethereum address.
	 *
     * Tests if the given string qualifies as a Ethereum address.
     * (DATA, 20 Bytes - address)
     *
	 * @since 0.1.0
	 * @version 0.1.0
	 * @param string $address
	 * @param bool   $throw
	 * @return bool
	 */
    public static function is_valid_address( $address, $throw = false )
    {
        if ( !self::has_hex_prefix( $address ) ) {
            return false;
        }
        // Address should be 20bytes=40 HEX-chars + prefix.
        if ( strlen( $address ) !== 42 ) {
            return false;
        }
        $return = ctype_xdigit( self::remove_hex_prefix($address) );

        if ( !$return && $throw ) {
            throw new \InvalidArgumentException( $address . ' has invalid format.' );
        }
        return $return;
    }

    /**
     * Test if a string is prefixed with "0x".
     * @since 0.1.0
	 * @version 0.1.0
     * @param string $str
     * @return bool
     */
    public static function has_hex_prefix( $str )
    {
        return substr( $str, 0, 2 ) === '0x';
    }


    /**
     * Remove Hex Prefix "0x".
     * @since 0.1.0
	 * @version 0.1.0
     * @param string $str
     * @return string
     */
    public static function remove_hex_prefix( $str )
    {
        if ( !self::has_hex_prefix( $str ) ) {
            throw new \InvalidArgumentException( 'String is not prefixed with "0x".' );
        }
        return substr( $str, 2 );
    }
}
