<?php
/**
 * Plugin Name: WooCommerce Request Network
 * Plugin URI: https://wooreq.com
 * Description: Accept cryptocurrency payments on your store using the Request Network.
 * Author: Adam Dowson	
 * Author URI: https://wooreq.com/about/
 * Version: 0.1.4
 * Requires at least: 4.4
 * Tested up to: 4.9.5
 * WC requires at least: 2.6
 * WC tested up to: 3.4.3
 * Text Domain: woocommerce-request-network
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( dirname( __FILE__ ) . '/woo-includes/woo-functions.php' );

/**
 * WooCommerce fallback notice.
 *
 * @since 0.1.2
 * @return string
 */
function woocommerce_wooreq_missing_wc_notice() {
	echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'Request Network for WooCommerce requires WooCommerce to be installed and active. You can download %s here.', 'woocommerce-gateway-wooreq' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
}

if ( ! wc_wooreq_is_wc_active() ) {
	add_action( 'admin_notices', 'woocommerce_wooreq_missing_wc_notice' );
	return;
}

if ( ! class_exists( 'WC_WooReq' ) ) :
	/**
	 * Required minimums and constants
	 */
	define( 'WC_WOOREQ_VERSION', '0.1.2' );
	define( 'WC_WOOREQ_MIN_PHP_VER', '5.6.0' );
	define( 'WC_WOOREQ_MIN_WC_VER', '2.6.0' );
	define( 'WC_WOOREQ_MAIN_FILE', __FILE__ );
	define( 'WC_WOOREQ_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
	define( 'WC_WOOREQ_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );

	class WC_WooReq {

		/**
		 * @var Singleton The reference the *Singleton* instance of this class
		 */
		private static $instance;

		/**
		 * @var Reference to logging class.
		 */
		private static $log;

		/**
		 * Returns the *Singleton* instance of this class.
		 *
		 * @return Singleton The *Singleton* instance.
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Private clone method to prevent cloning of the instance of the
		 * *Singleton* instance.
		 *
		 * @return void
		 */
		private function __clone() {}

		/**
		 * Private unserialize method to prevent unserializing of the *Singleton*
		 * instance.
		 *
		 * @return void
		 */
		private function __wakeup() {}

		/**
		 * Notices (array)
		 * @var array
		 */
		public $notices = array();

		/**
		 * Protected constructor to prevent creating a new instance of the
		 * *Singleton* via the `new` operator from outside of this class.
		 */
		private function __construct() {
			add_action( 'admin_init', array( $this, 'check_environment' ) );
			add_action( 'plugins_loaded', array( $this, 'init' ) );
			add_action( 'wp_loaded', array( $this, 'hide_notices' ) );
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );

			add_action( 'admin_head', array( $this, 'admin_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		}

		/**
		 * Init the plugin after plugins_loaded so environment variables are set.
		 *
		 * @since 0.1.0
		 * @version 0.1.0
		 */
		public function init() {
			require_once( dirname( __FILE__ ) . '/includes/class-wc-wooreq-exception.php' );
			require_once( dirname( __FILE__ ) . '/includes/class-wc-wooreq-logger.php' );
			require_once( dirname( __FILE__ ) . '/includes/helpers/class-wc-wooreq-helper.php' );

			// Don't hook anything else in the plugin if we're in an incompatible environment
			if ( self::get_environment_warning() ) {
				return;
			}

			require_once( dirname( __FILE__ ) . '/includes/abstracts/abstract-wc-wooreq-payment-gateway.php' );
			require_once( dirname( __FILE__ ) . '/includes/class-wc-wooreq-webhook-handler.php' );
			require_once( dirname( __FILE__ ) . '/includes/class-wc-gateway-wooreq.php' );

			add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateways' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
			add_filter( 'woocommerce_get_sections_checkout', array( $this, 'filter_gateway_order_admin' ) );
		}

		/**
		 * Hides any admin notices.
		 *
		 * @since 0.1.0
		 * @version 0.1.0
		 */
		public function hide_notices() {
			if ( isset( $_GET['wc-wooreq-hide-notice'] ) && isset( $_GET['_wc_wooreq_notice_nonce'] ) ) {
				if ( ! wp_verify_nonce( $_GET['_wc_wooreq_notice_nonce'], 'wc_wooreq_hide_notices_nonce' ) ) {
					wp_die( __( 'Action failed. Please refresh the page and retry.', 'woocommerce-gateway-wooreq' ) );
				}

				if ( ! current_user_can( 'manage_woocommerce' ) ) {
					wp_die( __( 'Cheatin&#8217; huh?', 'woocommerce-gateway-wooreq' ) );
				}

				$notice = wc_clean( $_GET['wc-wooreq-hide-notice'] );

				switch ( $notice ) {
					case 'ssl':
						update_option( 'wc_wooreq_show_ssl_notice', 'no' );
						break;
					case 'eth_address':
						update_option( 'wc_wooreq_show_eth_address_notice', 'no' );
						break;
					case 'btc_address':
						update_option( 'wc_wooreq_show_btc_address_notice', 'no' );
						break;
					case 'empty_currencies':
						update_option( 'wc_wooreq_show_empty_currencies_notice', 'no' );
						break;


				}
			}
		}

		/**
		 * Allow this class and other classes to add slug keyed notices (to avoid duplication).
		 *
		 * @since 0.1.0
		 * @version 0.1.0
		 */
		public function add_admin_notice( $slug, $class, $message, $dismissible = false ) {
			$this->notices[ $slug ] = array(
				'class'       => $class,
				'message'     => $message,
				'dismissible' => $dismissible,
			);
		}

		/**
		 * Display any notices we've collected thus far.
		 *
		 * @since 0.1.2
		 * @version 0.1.2
		 */
		public function admin_notices() {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return;
			}

			$this->check_environment();

			foreach ( (array) $this->notices as $notice_key => $notice ) {
				echo '<div class="' . esc_attr( $notice['class'] ) . '" style="position:relative;">';

				if ( $notice['dismissible'] ) {
				?>
					<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc-wooreq-hide-notice', $notice_key ), 'wc_wooreq_hide_notices_nonce', '_wc_wooreq_notice_nonce' ) ); ?>" class="woocommerce-message-close notice-dismiss" style="position:absolute;right:1px;padding:9px;text-decoration:none;"></a>
				<?php
				}

				echo '<p>';
				echo wp_kses( $notice['message'], array( 'a' => array( 'href' => array() ) ) );
				echo '</p></div>';
			}
		}

		/**
		 * Checks the environment for compatibility problems.  Returns a string with the first incompatibility
		 * found or false if the environment has no problems.
		 *
		 * @since 0.1.0
		 * @version 0.1.3
		 */
		public function get_environment_warning() {
			if ( version_compare( phpversion(), WC_WOOREQ_MIN_PHP_VER, '<' ) ) {
				/* translators: 1) int version 2) int version */
				$message = __( 'WooCommerce WooReq - The minimum PHP version required for this plugin is %1$s. You are running %2$s.', 'woocommerce-gateway-wooreq' );

				return sprintf( $message, WC_WOOREQ_MIN_PHP_VER, phpversion() );
			}

			if ( ! defined( 'WC_VERSION' ) ) {
				return __( 'WooCommerce WooReq requires WooCommerce to be activated to work.', 'woocommerce-gateway-wooreq' );
			}

			if ( version_compare( WC_VERSION, WC_WOOREQ_MIN_WC_VER, '<' ) ) {
				/* translators: 1) int version 2) int version */
				$message = __( 'WooCommerce WooReq - The minimum WooCommerce version required for this plugin is %1$s. You are running %2$s.', 'woocommerce-gateway-wooreq' );

				return sprintf( $message, WC_WOOREQ_MIN_WC_VER, WC_VERSION );
			}

			if ( ! function_exists( 'curl_init' ) ) {
				return __( 'WooCommerce WooReq - cURL is not installed.', 'woocommerce-gateway-wooreq' );
			}

			return false;
		}

		/**
		 * Get setting link.
		 *
		 * @since 0.1.0
		 *
		 * @return string Setting link
		 */
		public function get_setting_link() {
			$use_id_as_section = function_exists( 'WC' ) ? version_compare( WC()->version, '2.6', '>=' ) : false;

			$section_slug = $use_id_as_section ? 'wooreq' : strtolower( 'WC_Gateway_WooReq' );

			return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $section_slug );
		}

		/**
		 * The backup sanity check, in case the plugin is activated in a weird way,
		 * or the environment changes after activation. Also handles upgrade routines.
		 *
		 * @since 0.1.0
		 * @version 0.1.0
		 */
		public function check_environment() {
			if ( ! defined( 'IFRAME_REQUEST' ) && ( WC_WOOREQ_VERSION !== get_option( 'wc_wooreq_version' ) ) ) {
				$this->install();

				do_action( 'woocommerce_wooreq_updated' );
			}

			$environment_warning = $this->get_environment_warning();

			if ( $environment_warning && is_plugin_active( plugin_basename( __FILE__ ) ) ) {
				$this->add_admin_notice( 'bad_environment', 'error', $environment_warning );
			}

			// Notices
			$show_ssl_notice  				= get_option( 'wc_wooreq_show_ssl_notice' );
			$show_eth_address_notice 		= get_option( 'wc_wooreq_show_eth_address_notice' );
			$show_btc_address_notice 		= get_option( 'wc_wooreq_show_btc_address_notice' );
			$show_empty_currenciess_notice 	= get_option( 'wc_wooreq_show_empty_currencies_notice' );

			$options          = get_option( 'woocommerce_wooreq_settings' );
			$testmode         = ( isset( $options['testmode'] ) && 'yes' === $options['testmode'] ) ? true : false;
			$eth_payment_address  = isset( $options['eth_payment_address'] ) ? $options['eth_payment_address'] : '';
			// $btc_payment_address  = isset( $options['btc_payment_address'] ) ? $options['btc_payment_address'] : '';
			$accepted_currencies = $options['accepted_currencies'];

			if ( isset( $options['enabled'] ) && 'yes' === $options['enabled'] && empty( $show_keys_notice ) ) {

				if ( empty( $show_eth_address_notice ) ) {
					if ( !empty ( $accepted_currencies ) && in_array ( 'ETH', $accepted_currencies ) ) {
						if ( empty( $eth_payment_address ) ) {
							$this->add_admin_notice( 'eth_address', 'notice notice-error', 'Pay with Request - You have selected ETH as a payment option but no ETH wallet address has been provided.', 'woocommerce-gateway-wooreq' );
						}
					}
				}

				if ( empty( $show_empty_currenciess_notice ) ) {
					if ( empty ( $accepted_currencies ) ) {
						$this->add_admin_notice( 'empty_currencies', 'notice notice-error', 'Pay with Request - No accepted currencies have been selected.', 'woocommerce-gateway-wooreq' );
					}		
				}
	
				
				// if ( empty( $show_btc_address_notice ) ) {
				// 	if ( !empty ( $accepted_currencies ) && in_array ( 'BTC', $accepted_currencies ) ) {
				// 		if ( empty( $btc_payment_address ) ) {
				// 			$this->add_admin_notice( 'btc_address', 'notice notice-error', 'You have selected BTC as a payment option but no BTC wallet address has been provided.', 'woocommerce-gateway-wooreq' );
				// 		}
				// 	}
				// }
			}

			if ( empty( $show_ssl_notice ) && isset( $options['enabled'] ) && 'yes' === $options['enabled'] ) {
				// Show message if enabled and FORCE SSL is disabled and WordpressHTTPS plugin is not detected.
				if ( ( function_exists( 'wc_site_is_https' ) && ! wc_site_is_https() ) && ( 'no' === get_option( 'woocommerce_force_ssl_checkout' ) && ! class_exists( 'WordPressHTTPS' ) ) ) {
					/* translators: 1) link 2) link */
					$this->add_admin_notice( 'ssl', 'notice notice-warning', sprintf( __( 'Pay with Request is enabled, but the <a href="%1$s">force SSL option</a> is disabled; your checkout may not be secure! Please enable SSL and ensure your server has a valid <a href="%2$s" target="_blank">SSL certificate</a>.', 'woocommerce-gateway-wooreq' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ), 'https://en.wikipedia.org/wiki/Transport_Layer_Security' ), true );
				}
			}
		}

		/**
		 * Updates the plugin version in db
		 *
		 * @since 0.1.0
		 * @version 0.1.0
		 */
		public function update_plugin_version() {
			delete_option( 'wc_wooreq_version' );
			update_option( 'wc_wooreq_version', WC_WOOREQ_VERSION );
		}

		/**
		 * Handles upgrade routines.
		 *
		 * @since 0.1.0
		 * @version 3.1.0
		 */
		public function install() {
			if ( ! defined( 'WC_WOOREQ_INSTALLING' ) ) {
				define( 'WC_WOOREQ_INSTALLING', true );
			}

			$this->update_plugin_version();
		}

		/**
		 * Adds plugin action links.
		 *
		 * @since 0.1.0
		 * @version 0.1.0
		 */
		public function plugin_action_links( $links ) {
			$plugin_links = array(
				'<a href="admin.php?page=wc-settings&tab=checkout&section=wooreq">' . esc_html__( 'Settings', 'woocommerce-gateway-wooreq' ) . '</a>',
				'<a href="https://wooreq.com/installation/">' . esc_html__( 'Docs', 'woocommerce-gateway-wooreq' ) . '</a>',
				'<a href="https://wooreq.com/contact/">' . esc_html__( 'Support', 'woocommerce-gateway-wooreq' ) . '</a>',
			);
			return array_merge( $plugin_links, $links );
		}

		/**
		 * Add the gateways to WooCommerce.
		 *
		 * @since 0.1.0
		 * @version 0.1.0
		 */
		public function add_gateways( $methods ) {
			$methods[] = 'WC_Gateway_WooReq';
			return $methods;
		}

		/**
		 * Modifies the order of the gateways displayed in admin.
		 *
		 * @since 0.1.0
		 * @version 0.1.0
		 */
		public function filter_gateway_order_admin( $sections ) {
			unset( $sections['wooreq'] );

			$sections['wooreq'] = 'Request Network';

			return $sections;
		}

		/**
		 * Load admin styles.
		 *
		 * @since 0.1.1
		 * @version 0.1.1
		 */
		public function admin_styles() {
			if ( 'woocommerce_page_wc-settings' !== get_current_screen()->id ) {
				return;
			}

			wp_register_style( 'wooreq_admin_styles', plugins_url( 'assets/css/admin.css', WC_WOOREQ_MAIN_FILE ), array(), WC_WOOREQ_VERSION, true );
			wp_enqueue_style( 'wooreq_admin_styles' );

		}

		/**
		 * Load admin scripts.
		 *
		 * @since 0.1.1
		 * @version 0.1.1
		 */
		public function admin_scripts() {
			if ( 'woocommerce_page_wc-settings' !== get_current_screen()->id ) {
				return;
			}

			wp_enqueue_script( 'woocommerce_wooreq_admin_select', plugins_url( 'assets/js/select2.min.js', WC_WOOREQ_MAIN_FILE ), array(), WC_WOOREQ_VERSION, true );
			wp_enqueue_script( 'woocommerce_wooreq_admin', plugins_url( 'assets/js/admin.js', WC_WOOREQ_MAIN_FILE ), array(), WC_WOOREQ_VERSION, true );
			
		}
	}

	WC_WooReq::get_instance();

endif;
