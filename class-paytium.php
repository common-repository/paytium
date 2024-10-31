<?php

/**
 * Main Paytium class
 *
 * @package PT
 * @author  David de Boer <david@davdeb.com>
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Paytium {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	protected $version = '';

	/**
	 * Unique identifier for your plugin.
	 *
	 * Use this value (not the variable name) as the text domain when internationalizing strings of text. It should
	 * match the Text Domain file header in the main plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'paytium';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	public $session;

	/**
	 * @var $admin PT_Admin class.
	 */
	public $admin;

	/**
	 * @since 1.0.0
	 * @var $api PT_API API class.
	 */
	public $api;

	/**
	 * @since 1.0.0
	 * @var $post_types  PT_Post_Types class.
	 */
	public $post_types;

	private $load_public_scripts = false;


	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// Set current version
		$this->version = PT_VERSION;

		add_action( 'init', array ( $this, 'includes' ), 1 );

		// Add the options page, menu item and icon
		add_action( 'admin_menu', array ( $this, 'add_plugin_admin_menu' ), 10 );

		// Add to the WP toolbar.
		add_action( 'admin_bar_menu', array ( $this, 'add_toolbar_link' ), 999 );

		// Enqueue admin styles.
		add_action( 'admin_enqueue_scripts', array ( $this, 'enqueue_admin_styles' ) );

		// Enqueue admin scripts
		add_action( 'admin_enqueue_scripts', array ( $this, 'enqueue_admin_scripts' ) );

		// Add admin notice after plugin activation, tip to use Setup Wizard. Also check if should be hidden.
		add_action( 'admin_notices', array ( $this, 'admin_notice_setup_wizard' ) );

		// Add admin notice that asks for newsletter sign-up. Also check if should be hidden.
		add_action( 'admin_notices', array ( $this, 'admin_notice_newsletter' ) );

		// Add admin notice that reminds users to view extensions page. Also check if should be hidden.
		add_action( 'admin_notices', array ( $this, 'admin_notice_extensions' ) );

		// Add admin notice when site already received live payments & completing the Setup Wizard is not necessary
		add_action( 'admin_notices', array ( $this, 'admin_notice_has_live_payments' ) );

		// Add admin notice when site is in Mollie test mode
		add_action( 'admin_notices', array ( $this, 'admin_notice_switch_to_live_mode' ) );

        // Add admin notice after three weeks since plugin settings "Mode" has been set to Live,
        // and there already 3 completed live payments with status Paid
        // TODO David: Enable in May 2019
        //add_action( 'admin_notices', array ( $this, 'admin_notice_please_review' ) );

		// Add plugin listing "Settings" action link.
		add_filter( 'plugin_action_links_' . plugin_basename( PT_PATH . $this->plugin_slug . '.php' ), array (
			$this,
			'paytium_action_links'
		) );

		// Check WP version
		add_action( 'admin_init', array ( $this, 'check_wp_version' ) );

		// Add public JS
		add_action( 'wp_loaded', array ( $this, 'enqueue_public_scripts' ) );

		// Add public CSS
		add_action( 'wp_loaded', array ( $this, 'enqueue_public_styles' ) );

		// Load scripts when posts load so we know if we need to include them or not
		add_filter( 'the_posts', array ( $this, 'load_scripts' ) );

		// Paytium TinyMCE button
		add_action( 'init', array ( $this, 'paytium_add_mce_button' ) );

		// Paytium toolbar link
		add_action( 'admin_enqueue_scripts', array ( $this, 'paytium_toolbar_css' ) );
		add_action( 'wp_enqueue_scripts', array ( $this, 'paytium_toolbar_css' ) );

		// Paytium scripts
		add_action( 'wp_enqueue_scripts', array ( $this, 'paytium_load_scripts' ) );

        // Paytium Admin Search
        add_action( 'pre_get_posts', array($this, 'paytium_admin_search'));
		add_filter( 'posts_request_ids', array($this, 'paytium_subscriptions_admin_search'), 10, 2);

        // Paytium Edit Payment "Back to payments" button
        add_action('admin_footer', array($this, 'pt_edit_payment_back_button'));

		// Paytium Code block for Block editor
		add_action( 'init', array($this, 'register_block_paytium_shortcode'));

		if (is_file( PT_PATH . 'features/my-profile.php' )) {

			// Extra Paytium settings for user profile
			add_action( 'edit_user_profile', array($this, 'pt_extra_user_profile_fields'));

			// Save extra Paytium settings for user profile
			add_action( 'edit_user_profile_update', array($this, 'pt_save_extra_user_profile_fields'));

			if( current_user_can('administrator') ) {
				add_action( 'show_user_profile', array($this, 'pt_extra_user_profile_fields'));
				add_action( 'personal_options_update', array($this, 'pt_save_extra_user_profile_fields'));
			}
        }
    }

	function load_scripts( $posts ) {

		if ( empty( $posts ) ) {
			return $posts;
		}

		foreach ( $posts as $post ) {
			if ( ( strpos( $post->post_content, '[paytium' ) !== false ) || true == get_option( 'paytium_always_enqueue' ) ) {

				$this->load_public_scripts = true;
				break;
			}
		}

		return $posts;

	}


	function paytium_load_scripts() {

		if ($this->load_public_scripts) {
			// Load CSS
			wp_enqueue_style( $this->plugin_slug . '-public' );
			wp_enqueue_style( $this->plugin_slug . '-jquery-ui' );

			// Load JS
			wp_enqueue_script( $this->plugin_slug . '-public' );
			wp_enqueue_script( $this->plugin_slug . '-parsley' );
			wp_enqueue_script( $this->plugin_slug . '-parsley-nl' );

			// Localize the site script with new language strings
			wp_localize_script( $this->plugin_slug . '-public', 'paytium_localize_script_vars', array (
					'admin_ajax_url' => admin_url( 'admin-ajax.php' ),
					'amount_too_low' => __( 'No (valid) amount entered or amount is too low!', 'paytium' ),
					'subscription_first_payment' => __( 'First payment:', 'paytium' ),
					'field_is_required' => __( 'Field \'%s\' is required!', 'paytium' ),
					'processing_please_wait' => __( 'Processing, please wait...', 'paytium' ),
					'validation_failed' => __( 'Validation failed, please try again.', 'paytium' ),
				)
			);
        }
	}

	/**
	 * Load public facing CSS
	 *
	 * @since 1.0.0
	 */
	function enqueue_public_styles() {
		wp_register_style( $this->plugin_slug . '-public', PT_URL . 'public/css/public.css', array (), $this->version );

		if (! is_admin() ) {
			wp_register_style( $this->plugin_slug . '-jquery-ui', PT_URL . 'public/css/jquery-ui.css', array (), $this->version );

			if (is_file( PT_PATH . 'features/css/discount.css' ) ) {
				wp_enqueue_style( $this->plugin_slug . '-discount', PT_URL . 'features/css/discount.css', array (), $this->version );
			}
		}
	}

	/**
	 * Find user's browser language
	 *
	 * @since 1.1.0
	 */

	function paytium_browser_language() {

		if ( isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {

			$languages = explode( ",", $_SERVER['HTTP_ACCEPT_LANGUAGE'] );
			$language  = strtolower( $languages[0] );
		} else {
			$language = '';
		}

		return $language;
	}

	/**
	 * Load scripts based on environment and language
	 *
	 * @since 1.1.0
	 *
	 * @param $environment
	 * @param $language
	 */

	function paytium_register_scripts( $environment, $language ) {

		$dependencies = array ( 'jquery', $this->plugin_slug . '-parsley', 'jquery-ui-datepicker' );

		if ( $environment == 'development' ) {

            if (get_option('footer_error') == 1) {
                wp_register_script( $this->plugin_slug . '-public', PT_URL . 'public/js/public.js', $dependencies, $this->version, false );
            }
            else {
                wp_register_script( $this->plugin_slug . '-public', PT_URL . 'public/js/public.js', $dependencies, $this->version, true );
            }
			wp_register_script( $this->plugin_slug . '-parsley', PT_URL . 'public/js/parsley.min.js', array ( 'jquery' ), time(), true );

		}

		if ( $environment == 'production' ) {

            if (get_option('footer_error') == 1) {
                wp_register_script( $this->plugin_slug . '-public', PT_URL . 'public/js/public.js', $dependencies, $this->version, false );
            }
            else {
                wp_register_script( $this->plugin_slug . '-public', PT_URL . 'public/js/public.js', $dependencies, $this->version, true );
            }
			wp_register_script( $this->plugin_slug . '-parsley', PT_URL . 'public/js/parsley.min.js', array ( 'jquery' ), $this->version, true );

		}

		// Add Dutch translation for Parsley if browser language is set to Dutch
		if ( $language == 'nl' ) {

			wp_register_script( $this->plugin_slug . '-parsley-nl', PT_URL . 'public/js/parsley-nl.js', $dependencies, time(), true );

		}

		return;
	}

	/**
	 * Load public facing JS
	 *
	 * @since 1.0.0
	 */
	public function enqueue_public_scripts() {

		// What's the user's browser language?
		$language = $this->paytium_browser_language();

		// Is this Paytium plugin on a production or development site?
		$environment = get_option( 'pt_environment', 'production' );

		if ( $language == 'nl' || $language == 'nl-nl' || $language == 'nl-be' ) {

			$this->paytium_register_scripts( $environment, 'nl' );

		} else {

			$this->paytium_register_scripts( $environment, '' );

		}

		wp_localize_script( $this->plugin_slug . '-public', 'pt', array (
			'currency_symbol' => is_file( PT_PATH . 'features/currency.php' ) ? get_paytium_currency_symbol(get_option('paytium_currency', 'EUR')) : '€', // currency feature
			'decimals' => apply_filters( 'paytium_amount_decimals', 2 ),
			'thousands_separator' => apply_filters( 'paytium_thousands_separator', '.' ),
			'decimal_separator' => apply_filters( 'paytium_decimal_separator', ',' ),
			'debug'           => ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ),
		) );

	}

	/**
	 * Load admin scripts
	 *
	 * @since 1.0.0
	 */
	public function enqueue_admin_scripts() {

		wp_register_style( 'pt-select2', plugins_url( 'admin/css/select2/select2.min.css', PT_MAIN_FILE ), array (), '4.0.3' );

		if ( ! $this->viewing_this_plugin() ) {
			return false;
		}

		// Is this Paytium plugin on a production or development site?
		$environment = get_option( 'pt_environment', 'production' );

		if ( $environment == 'development' ) {

			if ((isset($_GET['page']) && $_GET['page'] == 'pt-statistics') && is_file( PT_PATH . 'features/statistics.php' ) ) {
				wp_enqueue_script( $this->plugin_slug . '-chart', PT_URL . 'features/js/Chart.js', '', $this->version, true );
				wp_enqueue_script( $this->plugin_slug . '-moment', PT_URL . 'features/js/moment.js', '', $this->version, true );
				wp_enqueue_script( $this->plugin_slug . '-datepicker', PT_URL . 'features/js/datepicker.js', '', $this->version, true );
				wp_enqueue_script( $this->plugin_slug . '-statistics', PT_URL . 'features/js/statistics.js', '', $this->version, true );
			}

			wp_enqueue_script( $this->plugin_slug . '-admin', PT_URL . 'admin/js/admin.js', array ( 'jquery' ), time(), true );
			wp_enqueue_script( 'paytium-setup-wizard', PT_URL . 'admin/js/setup-wizard.js', array ( 'jquery' ), time(), true );

		}

		if ( $environment == 'production' ) {

			if ((isset($_GET['page']) && $_GET['page'] == 'pt-statistics') && is_file( PT_PATH . 'features/statistics.php' ) ) {
				wp_enqueue_script( $this->plugin_slug . '-chart', PT_URL . 'features/js/Chart.js', '', $this->version, true );
				wp_enqueue_script( $this->plugin_slug . '-moment', PT_URL . 'features/js/moment.js', '', $this->version, true );
				wp_enqueue_script( $this->plugin_slug . '-datepicker', PT_URL . 'features/js/datepicker.js', '', $this->version, true );
				wp_enqueue_script( $this->plugin_slug . '-statistics', PT_URL . 'features/js/statistics.js', '', $this->version, true );
			}

			wp_enqueue_script( $this->plugin_slug . '-admin', PT_URL . 'admin/js/admin.js', array ( 'jquery' ), $this->version, true );
			wp_enqueue_script( 'paytium-setup-wizard', PT_URL . 'admin/js/setup-wizard.js', array ( 'jquery' ), $this->version, true );

		}

		if (isset($_GET['page']) && ($_GET['page'] == 'paytium-invoices' || $_GET['page'] == 'paytium-export')) {
			wp_enqueue_script( 'jquery-ui-datepicker' );
		}

		if (is_file( PT_PATH . 'features/datepicker.php' ) && (get_post_type() == 'pt_payment' || get_current_screen()->post_type == 'pt_payment')) {
			wp_enqueue_script( $this->plugin_slug . '-moment', PT_URL . 'features/js/moment.js', '', $this->version, true );
			wp_enqueue_script( $this->plugin_slug . '-datepicker', PT_URL . 'features/js/datepicker.js', '', $this->version, true );
		}

		if ('pt_subscription' == get_post_type() && is_file( PT_PATH . 'features/subscriptions.php' )) {
			wp_enqueue_script( $this->plugin_slug . '-admin-features', PT_URL . 'features/js/admin-features.js', '', $this->version, true );
		};

		wp_localize_script( $this->plugin_slug . '-admin', 'paytium', array (
			'nonce' => wp_create_nonce( 'paytium-ajax-nonce' ),
		) );
		wp_localize_script( 'paytium-setup-wizard', 'paytium', array (
			'nonce' => wp_create_nonce( 'paytium-ajax-nonce' ),
		) );
		wp_localize_script( $this->plugin_slug . '-admin-features', 'paytium', array (
			'nonce' => wp_create_nonce( 'paytium-ajax-nonce' ),
			'update_confirm' => __( 'Are you sure you want to change subscription fee?', 'paytium' ),
			'confirm' => __( 'Yes', 'paytium' ),
			'cancel' => __( 'Cancel', 'paytium' ),
		) );
		wp_localize_script( $this->plugin_slug . '-statistics', 'paytium_statistics', array (
			'ajax_error' => __( 'Something went wrong', 'paytium' ),
		) );

		wp_localize_script( $this->plugin_slug . '-admin', 'paytium_localize_script_vars', array (
			'not_entered_api_keys' => sprintf( __( 'No API key(s) entered, use the %sSetup Wizard%s or get the API keys from the %sMollie Dashboard%s.', 'paytium' ), '<a href="' . esc_url( admin_url( 'admin.php?page=pt-setup-wizard' ) ) . '">', '</a>', '<a href="https://my.mollie.com/dashboard/signup/335035">', '</a>' ),
		) );

	}


	/**
	 * Enqueue admin-specific style sheets for this plugin's admin pages only.
	 *
	 * @since     1.0.0
	 */
	public function enqueue_admin_styles() {

		wp_register_script( 'pt-select2', plugins_url( 'admin/js/select2/select2.min.js', PT_MAIN_FILE ), array ( 'jquery' ), '4.0.7', true );
		wp_register_style( $this->plugin_slug . '-statistics-datepicker', PT_URL . 'features/css/daterangepicker.css', array (), $this->version );

		if ( ! $this->viewing_this_plugin() ) {
			return false;
		}

		// Is this Paytium plugin on a production or development site?
		$environment = get_option( 'pt_environment', 'production' );

		if ( $environment == 'development' ) {

			wp_enqueue_style( $this->plugin_slug . '-admin-styles', PT_URL . 'admin/css/admin.css', array (), time() );
			wp_enqueue_style( $this->plugin_slug . '-toggle-switch', PT_URL . 'admin/css/toggle-switch.css', array (), time() );

		}

		if ( $environment == 'production' ) {

			wp_enqueue_style( $this->plugin_slug . '-admin-styles', PT_URL . 'admin/css/admin.css', array (), $this->version );
			wp_enqueue_style( $this->plugin_slug . '-toggle-switch', PT_URL . 'admin/css/toggle-switch.css', array (), $this->version );
		}

		if (isset($_GET['page']) && ($_GET['page'] == 'paytium-invoices' || $_GET['page'] == 'paytium-export')) {
			wp_enqueue_style( $this->plugin_slug . '-jquery-ui', PT_URL . 'public/css/jquery-ui.css', array (), $this->version );
		}


		if ( get_current_screen()->post_type == 'pt_payment' ) {
			wp_enqueue_style( $this->plugin_slug . '-admin-notice-newsletter-style', PT_URL . 'admin/css/admin-notice-newsletter.css', array (), $this->version );
		}

		if ( get_current_screen()->base == 'paytium_page_pt-extensions' ) {
			wp_enqueue_style( $this->plugin_slug . '-admin-extensions', PT_URL . 'admin/css/admin-extensions.css', array (), $this->version );
		}

		if ('pt_subscription' == get_post_type() && is_file( PT_PATH . 'features/subscriptions.php' )) {
			wp_enqueue_style( $this->plugin_slug . '-subscription', PT_URL . 'features/css/subscription.css', array (), $this->version );
			wp_enqueue_style( $this->plugin_slug . '-admin-features', PT_URL . 'features/css/admin-features.css', array (), $this->version );
		};
    
		if ((isset($_GET['page']) && $_GET['page'] == 'pt-statistics') && is_file( PT_PATH . 'features/statistics.php' ) ) {
			wp_enqueue_style( $this->plugin_slug . '-statistics', PT_URL . 'features/css/statistics.css', array (), $this->version );
			wp_enqueue_style( $this->plugin_slug . '-statistics-datepicker' );
		}

		if (is_file( PT_PATH . 'features/datepicker.php' ) && (get_post_type() == 'pt_payment' || get_current_screen()->post_type == 'pt_payment')) {
			wp_enqueue_style( $this->plugin_slug . '-statistics-datepicker' );
		}

	}


	/**
	 * Make sure user has the minimum required version of WordPress installed to use the plugin
	 *
	 * @since 1.0.0
	 */
	public function check_wp_version() {

		global $wp_version;
		$required_wp_version = '3.6.1';

		if ( version_compare( $wp_version, $required_wp_version, '<' ) ) {
			deactivate_plugins( PT_MAIN_FILE );
			wp_die( sprintf( __( $this->get_plugin_title() . ' requires WordPress version <strong>' . $required_wp_version . '</strong> to run properly. ' .
			                     'Please update WordPress before reactivating this plugin. <a href="%s">Return to Plugins</a>.', 'paytium' ), get_admin_url( '', 'plugins.php' ) ) );
		}

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;

	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		global $submenu;

		$this->plugin_screen_hook_suffix[] = add_menu_page(
			$this->get_plugin_title() . ' ' . __( 'Settings', 'paytium' ),
			$this->get_plugin_title(),
			'edit_posts',
			$this->plugin_slug,
			array ( $this, 'display_plugin_admin_page' ),
			plugins_url( '/assets/ideal-2019.svg', __FILE__ )
		);

		// Settings page
		add_submenu_page( 'paytium', __( 'Paytium settings', 'paytium' ), __( 'Settings', 'paytium' ), 'manage_options', 'paytium', array (
			$this,
			'display_plugin_admin_page'
		) );

		// Statistics page
		if (is_file( PT_PATH . 'features/statistics.php' )) {
			add_submenu_page( 'paytium', __( 'Statistics', 'paytium' ), __( 'Statistics', 'paytium' ), 'manage_options', 'pt-statistics', array (
				$this,
				'paytium_statistics_page'
			) );
		}

		// Setup wizard
		if ( false == get_option( 'paytium_enable_live_key' ) ) {
			add_submenu_page( 'paytium', 'Setup wizard', __( 'Setup wizard', 'paytium' ), 'manage_options', 'pt-setup-wizard', array (
				$this,
				'setup_wizard_page'
			) );
		}

		// Add links about pro versions/features to free version
		if ( PT_PACKAGE == 'paytium' ) {

			// Extensions
			add_submenu_page( 'paytium', 'Extra features', __( 'Extra features', 'paytium' ), 'manage_options', 'pt-extensions', array (
				$this,
				'paytium_extensions_page'
			) );

			// Pro versions
			if ( current_user_can( 'manage_options' ) ) {
				$submenu['paytium'][] = array (
					'<span style="color: #3db634;">' . __( 'Pro versions', 'paytium' ) . '</span>',
					'manage_options',
					'https://www.paytium.nl/prijzen/'
				);
			}
		}

		// My profile page
		if (is_file( PT_PATH . 'features/my-profile.php' )) {
			add_submenu_page( 'paytium', __( 'My profile', 'paytium' ), __( 'My profile', 'paytium' ), 'read', 'pt-my-profile', array (
				$this,
				'display_user_profile'
			) );
		}

	}

	/**
	 * Print icon for WP Admin toolbar
	 *
	 */
	function add_toolbar_link( $wp_admin_bar ) {

		if ( current_user_can( 'manage_options' ) ) {
			$icon = "<span class='pt-icon' style='background: url(\"" . plugins_url( '/assets/ideal-2019.svg', __FILE__ ) . "\") no-repeat center; background-size:  20px auto; margin-right: 5px;'> </span>";
			$args = array (
				'id'    => 'paytium',
				'title' => $icon . __( 'Payments', 'paytium' ),
				'href'  => esc_url( admin_url( 'edit.php?post_type=pt_payment' ) ),
				'meta'  => array ( 'class' => 'pt-toolbar' )
			);
			$wp_admin_bar->add_node( $args );
		}
	}


	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {

		include_once( PT_PATH . 'admin/views/admin-settings.php' );

	}


	/**
	 * Statistics admin page.
	 *
	 * Display Paytium statistics.
	 *
	 * @since 2.2.0
	 */
	public function paytium_statistics_page() {

		require_once PT_PATH . 'features/views/statistics.php';

	}


	/**
	 * Setup wizard admin page.
	 *
	 * Display the setup wizard on a separate page for the best UX.
	 *
	 * @since 1.0.0
	 */
	public function setup_wizard_page() {

		require_once PT_PATH . 'admin/views/setup-wizard.php';

	}

	/**
	 * Extensions admin page.
	 *
	 * Display extensions for Paytium.
	 *
	 * @since 1.2.0
	 */
	public function paytium_extensions_page() {

		require_once PT_PATH . 'admin/views/admin-extensions.php';

	}

    /**
	 * User profile page
	 *
	 * @since 4.2.0
	 */
	public function display_user_profile() {

		require_once PT_PATH . 'features/my-profile.php';

	}


	/**
	 * Include required files (admin and frontend).
	 *
	 * @since     1.0.0
	 */
	public function includes() {

		global $pt_mollie, $plugin_slug;

		$plugin_slug = $this->plugin_slug;

		// TODO Check for curl -- function_exists( 'curl_version' )
		// TODO Check for sites on localhost?
		// TODO Check for PHP 5.3.3 (or whatever Mollie API currently requires).

		if ( ! class_exists( 'MollieApiClient' ) ) {
			require_once( PT_PATH . 'libraries/Mollie/vendor/autoload.php' );
		}

		$pt_mollie = new \Mollie\Api\MollieApiClient();

		/**
		 * Include functions
		 */
		include_once( PT_PATH . 'includes/misc-functions.php' );

		include_once( PT_PATH . 'includes/process-payment-functions.php' );
		include_once( PT_PATH . 'includes/webhook-url-functions.php' );
		include_once( PT_PATH . 'includes/redirect-url-functions.php' );

		include_once( PT_PATH . 'includes/shortcodes.php' );
		include_once( PT_PATH . 'includes/shortcodes-show.php' );
		include_once( PT_PATH . 'includes/register-settings.php' );
		include_once( PT_PATH . 'includes/payment-functions.php' );
		include_once( PT_PATH . 'includes/tax-functions.php' );

		include_once( PT_PATH . 'includes/user-data-functions.php' );
		include_once( PT_PATH . 'includes/item-limit-functions.php' );
		include_once( PT_PATH . 'includes/log-functions.php' );
		include_once( PT_PATH . 'includes/notification-functions.php' );

		// Include classes
		include_once( PT_PATH . 'includes/class-pt-item.php' );
		include_once( PT_PATH . 'includes/class-pt-payment.php' );

		/**
		 * Post types class
		 */
		include_once( PT_PATH . 'includes/class-pt-post-types.php' );
		$this->post_types = new PT_Post_Types();

		/**
		 * Admin includes
		 */
		if ( is_admin() ) {
			require_once PT_PATH . 'admin/class-pt-admin.php';
			$this->admin = new PT_Admin();

			require_once PT_PATH . 'includes/class-pt-api.php';
			$this->api = new PT_API();

		}

	}


	/**
	 * Return localized base plugin title.
	 *
	 * @since     1.0.0
	 *
	 * @return string
	 */
	public static function get_plugin_title() {

		return __( 'Paytium', 'paytium' );

	}


	/**
	 * Add Settings action link to left of existing action links on plugin listing page.
	 *
	 * @since   1.0.0
	 *
	 * @param  array $links Default plugin action links
	 *
	 * @return array $links Amended plugin action links
	 */
	public function paytium_action_links( $links ) {

		// Setup wizard
		if ( false == get_option( 'paytium_enable_live_key' ) ) {
			$links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=pt-setup-wizard' ) ) . '">' . __( 'Setup wizard', 'paytium' ) . '</a>';
		}

		$links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=paytium' ) ) . '">' . __( 'Settings', 'paytium' ) . '</a>';

		if ( PT_PACKAGE == 'paytium' ) {
			$links[] = '<a href="' . esc_url( 'https://www.paytium.nl/prijzen/' ) . '" style="color: #3db634;">' . __( 'Pro versions', 'paytium' ) . '</a>';
		}

		return $links;

	}


	/**
	 * Check if viewing this plugin's admin page.
	 *
	 * @since   1.0.0
	 *
	 * @return bool
	 */
	public function viewing_this_plugin() {

		$screen = get_current_screen();

		if ($screen !== NULL ) {
			if ( ! empty( $this->plugin_screen_hook_suffix ) && in_array( $screen->id, $this->plugin_screen_hook_suffix ) ) {
				return true;
			}

			if ( 'paytium_page_pt-extensions' == $screen->id ) {
				return true;
			}

			if ( 'paytium_page_pt-setup-wizard' == $screen->id ) {
				return true;
			}
		}

		if (
			'pt_payment' == get_post_type() ||
			'paytium_emails' == get_post_type() ||
			'pt_subscription' == get_post_type() ||
			( isset( $_GET['page'] ) && $_GET['page'] == 'paytium' ) ||
			( isset( $_GET['page'] ) && $_GET['page'] == 'pt-statistics' ) ||
			( isset( $_GET['page'] ) && $_GET['page'] == 'pt-my-profile') ||
			( isset( $_GET['post_type'] ) && $_GET['post_type'] == 'pt_payment' ) ||
			( isset( $_GET['post_type'] ) && $_GET['post_type'] == 'pt_subscription' )
		) {
			return true;
		}
		if (  isset($_REQUEST['option_page']) && 'pt_settings_invoices' == $_REQUEST['option_page']) {
		    return true;
        }

		$page_ids = array( 'paytium-export' , 'paytium-invoices'  );
		if ( isset( $_GET['page'] ) && in_array( $_GET['page'], $page_ids ) ) {
			return true;
		}

		return false;

	}


	/**
	 * Show notice after plugin install/activate in admin dashboard.
	 * Hide after first viewing.
	 *
	 * @since   1.0.0
	 */
	public function admin_notice_setup_wizard() {

		// Exit all of this is stored value is false/0 or not set.
		if ( true == get_option( 'paytium_enable_live_key' ) ) {
			return;
		}

		// Exit all of this is stored value is false/0 or not set.
		if ( false == get_option( 'pt_show_admin_notice_setup_wizard' ) ) {
			return;
		}

		// Delete stored value if "hide" button click detected (custom querystring value set to 1).
		if ( ! empty( $_REQUEST['pt-dismiss-install-nag'] ) ) {
			delete_option( 'pt_show_admin_notice_setup_wizard' );

			return;
		}

		// At this point show install notice. Show it only on the plugin screen.
		if ( get_current_screen()->id == 'plugins' ) {
			include_once( PT_PATH . 'admin/views/admin-notice-setup-wizard.php' );
		}

	}

	/**
	 * Use admin notice to ask for newsletter sign-up.
	 * Hide after first viewing.
	 *
	 * @since   1.2.0
	 */
	public function admin_notice_newsletter() {

		// Exit all of this is stored value is false/0 or not set.
		if ( get_option( 'pt_hide_admin_notice_newsletter' ) == true ) {
			return;
		}

		// Delete stored value if "hide" button click detected (custom querystring value set to 1).
		if ( ! empty( $_REQUEST['pt-dismiss-newsletter-nag'] ) ) {
			delete_option( 'pt_show_admin_notice_newsletter', true );
			update_option( 'pt_hide_admin_notice_newsletter', true );
			set_transient( 'pt_wait_to_show_admin_notice_extensions', '1', '30' );
			return;
		}

		// At this point show newsletter notice.
		if ( get_current_screen()->post_type == 'pt_payment' ) {
			include_once( PT_PATH . 'admin/views/admin-notice-newsletter.php' );
		}
	}

	/**
	 * Use admin notice to remind users to view extensions page.
	 * Hide after first viewing.
	 *
	 * @since   1.2.0
	 */
	public function admin_notice_extensions() {

		// Exit all of this is stored value is false/0 or not set.
		if ( false == get_option( 'pt_show_admin_notice_extensions' ) ) {
			return;
		}

		// Delete stored value if "hide" button click detected (custom querystring value set to 1).
		if ( ! empty( $_REQUEST['pt-dismiss-extensions-nag'] ) ) {
			delete_option( 'pt_show_admin_notice_extensions' );
			update_option( 'pt_hide_admin_notice_extensions', true );

			return;
		}

		// Add links about pro versions/features to free version
		if ( PT_PACKAGE !== 'paytium' ) {
		    return;
		}
		
		// At this point show extensions notice.
		if ( get_current_screen()->post_type == 'pt_payment' &&
		     true == get_option( 'pt_hide_admin_notice_newsletter' ) &&
		     false === get_transient( 'pt_wait_to_show_admin_notice_extensions' )
		) {
			include_once( PT_PATH . 'admin/views/admin-notice-extensions.php' );
		}
	}

	/**
	 * Add admin notice when site already received live payments &
	 * completing the Setup Wizard is not necessary
	 *
	 * @since   1.5.0
	 */
	public function admin_notice_has_live_payments() {

		// Delete stored value if "hide" button click detected (custom querystring value set to 1).
		if ( ! empty( $_REQUEST['pt-dismiss-has-live-payments-nag'] ) ) {
			delete_option( 'pt_show_admin_notice_has_live_payments' );

			return;
		}

		// Check if there are live payments in this site
		if ( false == pt_has_live_payments() ) {
			return;
		}

		// At this point show "has live payments" notice.
		if ( get_current_screen()->id == 'paytium_page_pt-setup-wizard' ) {
			include_once( PT_PATH . 'admin/views/admin-notice-has-live-payments.php' );
		}
	}

	/**
	 * Add admin notice when site in Mollie test mode
	 *
	 * @since   2.1.0
	 */
	public function admin_notice_switch_to_live_mode() {

		// Delete stored value if "hide" button click detected (custom querystring value set to 1).
		if ( ! empty( $_REQUEST['pt-dismiss-switch-to-live-mode-nag'] ) ) {
			update_option( 'pt_admin_notice_switch_to_live_mode', 1 );

			return;
		}

		// Exit all of this is stored value is false/0 or not set.
		if ( ( false == get_option( 'pt_hide_admin_notice_extensions' ) ) &&
		     ( false == get_option( 'pt_hide_admin_notice_newsletter' ) ) ) {
			return;
		}

		// Check if site is not on live mode
		if ( get_option( 'paytium_enable_live_key' ) == 1 ) {
			return;
		}

		// At this point show "test mode" notice.
		if ( get_current_screen()->post_type == 'pt_payment' &&
		     false == get_option( 'pt_admin_notice_switch_to_live_mode' )) {
			include_once( PT_PATH . 'admin/views/admin-notice-switch-to-live-mode.php' );
		}
	}


    /**
     * Add admin notice after three weeks since plugin settings "Mode" has been set to Live,
     * and there already 3 completed live payments with status Paid
     *
     * @since   2.2.0
     */
    public function admin_notice_please_review() {

        // Delete stored value if "hide" button click detected (custom querystring value set to 1).
        if ( ! empty( $_REQUEST['pt-dismiss-please-review-nag'] ) ) {
            update_option( 'pt_admin_notice_hide_please_review', 1 );

            return;
        }

	    // Don't show notice if user selected to hide it
	    if ( get_option( 'pt_admin_notice_hide_please_review' ) == 1 ) {
		    return;
	    }

	    // Don't show notice if extension notice should still be shown
	    if ( get_option( 'pt_show_admin_notice_extensions' ) == 1 ) {
		    return;
	    }

        // Don't show notice is newsletter or extensions notice still needs to be shown
        if ( ( false == get_option( 'pt_hide_admin_notice_extensions' ) ) ||
            ( false == get_option( 'pt_hide_admin_notice_newsletter' ) ) ) {
            return;
        }



        $args = wp_parse_args( array (
            'post_type'   => 'pt_payment',
            'post_status' => 'publish',
            'orderby' => 'id',
            'order'   => 'DESC',

            'meta_query' => array (
                array (
                    'key'     => '_mode',
                    'value'   => 'live',
                    'compare' => '='
                ),
                array (
                    'key'     => '_status',
                    'value'   => 'paid',
                    'compare' => '='
                ),
            ),

            'fields'        => 'ids',
            'posts_per_page' => 5,
        ) );

        $live_posts = new WP_Query( $args );

	    if ( get_current_screen()->post_type == 'pt_payment' &&
	         get_option( 'paytium_enable_live_key' ) == 1 && ( $live_posts->post_count == 5  )
	    ) {
		    include_once( PT_PATH . 'admin/views/admin-notice-please-review.php' );
	    }
    }

	/**
	 * Code for including a TinyMCE button
	 *
	 * @since   1.0.0
	 */
	function paytium_add_mce_button() {

		if ( current_user_can( 'edit_posts' ) && current_user_can( 'edit_pages' ) ) {
			add_filter( 'mce_external_plugins', array ( $this, 'paytium_add_buttons' ) );
			add_filter( 'mce_buttons', array ( $this, 'paytium_register_buttons' ) );
		}

	}


	function paytium_add_buttons( $plugin_array ) {

		$plugin_array['paytiumshortcodes'] = plugin_dir_url( __FILE__ ) . '/public/js/paytium-tinymce-button.js';

		return $plugin_array;

	}


	function paytium_register_buttons( $buttons ) {

		array_push( $buttons, 'separator', 'paytiumshortcodes' );

		return $buttons;

	}

	function paytium_toolbar_css() {

		if ( is_admin_bar_showing() ) {
			wp_enqueue_style( 'paytium-toolbar-css', plugins_url( '/public/css/paytium_toolbar.css', __FILE__ ), array (), $this->version );
		}

	}


    /**
     * Paytium Admin Search Postmeta fields
     */

    function paytium_admin_search( $query ) {

        if( ! is_admin() ) {
	        return;
        }

        if ( ! isset($query->query['post_type']) ) {
	       return;
        }

	    if ( $query->query['post_type'] != 'pt_payment' ) {
		    return;
	    }

        $search_term = $query->query_vars['s'];
        $query->query_vars['s'] = '';

        if ( $search_term != '' ) {
            $meta_query = array(array(
                'value' => $search_term,
                'compare' => 'LIKE'
            ) );

            $query->set( 'meta_query', $meta_query );
        };
    }

	/**
	 * Paytium Subscriptions Admin Search Postmeta fields
	 */
	function paytium_subscriptions_admin_search( $request, $wp_query ) {

		if ( ! is_admin() ) {
			return $request;
		}

		if ( ! isset( $wp_query->query['post_type'] ) ) {
			return $request;
		}

		if ( $wp_query->query['post_type'] === 'pt_subscription' && isset( $wp_query->query['s'] ) ) {

			global $wpdb;

			$request = "SELECT  {$wpdb->postmeta}.meta_value 
            FROM {$wpdb->postmeta}
            WHERE {$wpdb->postmeta}.post_id IN (
                SELECT {$wpdb->posts}.ID
                FROM {$wpdb->posts}
                LEFT JOIN {$wpdb->postmeta}
                ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
                WHERE ({$wpdb->posts}.post_title LIKE '%{$wp_query->query['s']}%' OR {$wpdb->postmeta}.meta_value LIKE '%{$wp_query->query['s']}%') AND {$wpdb->posts}.post_type LIKE 'pt_payment'
            ) AND {$wpdb->postmeta}.meta_key LIKE '_subscription_id'
            UNION
                SELECT  {$wpdb->posts}.ID
                FROM {$wpdb->posts}
                LEFT JOIN {$wpdb->postmeta}
                ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
                WHERE ({$wpdb->posts}.post_title LIKE '%{$wp_query->query['s']}%' OR {$wpdb->postmeta}.meta_value LIKE '%{$wp_query->query['s']}%') AND {$wpdb->posts}.post_type LIKE 'pt_subscription'";
		}

		return $request;
	}

    /**
    * Add inline script to add the 'Back to payments' button.
    */
    function pt_edit_payment_back_button()
    {
        ?>
        <script>
            jQuery(function () {
                jQuery("body.post-php.post-type-pt_payment .wrap h1").append('&nbsp;&nbsp;&nbsp;<a href="<?php echo esc_url(admin_url('edit.php?post_type=pt_payment')); ?>" class="page-title-action"><?php _e('Back to payments', 'paytium'); ?></a>');
                jQuery("body.post-php.post-type-pt_subscription .wrap h1").append('&nbsp;&nbsp;&nbsp;<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=pt_subscription' ) ); ?>" class="page-title-action"><?php _e( 'Back to subscriptions', 'paytium' ); ?></a>');
            });
        </script>
        <?php
    }

	function register_block_paytium_shortcode() {

		if ( ! function_exists( 'register_block_type' ) ) {
			// Gutenberg is not active.
			return;
		}

		wp_register_script(
			$this->plugin_slug . '-shortcode',
			PT_URL . 'admin/js/paytium-shortcode-block.js',
			array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-components', 'wp-editor' ),
			filemtime( PT_PATH . 'admin/js/paytium-shortcode-block.js' )
		);

		wp_register_style(
			$this->plugin_slug . '-shortcode',
			PT_URL . 'admin/css/paytium-shortcode-block.css',
			array(),
			filemtime( PT_PATH . 'admin/css/paytium-shortcode-block.css' )
		);

		register_block_type(
			'paytium/shortcode',
			array(
				'render_callback' => 'render_block_core_shortcode',
				'editor_script' => $this->plugin_slug . '-shortcode',
				'editor_style' => $this->plugin_slug . '-shortcode',
			)
		);
	}

	function pt_extra_user_profile_fields( $user ) {
		include_once( PT_PATH . 'features/views/user-profile-additional-fields.php' );
	}

	/**
	 * @param $user_id
	 * @return bool|void
	 */
	function pt_save_extra_user_profile_fields($user_id) {

		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'update-user_' . $user_id ) ) {
			return;
		}

		if ( !current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

        $show_payments = isset($_POST['show_payments']) ? 1 : 0;
		$show_invoices = isset($_POST['show_invoices']) ? 1 : 0;
		$show_subscriptions = isset($_POST['show_subscriptions']) ? 1 : 0;
		$show_cancel_subscription = isset($_POST['show_cancel_subscription']) ? 1 : 0;

		update_user_meta($user_id, 'show_payments', $show_payments);
		update_user_meta($user_id, 'show_invoices', $show_invoices);
		update_user_meta($user_id, 'show_subscriptions', $show_subscriptions);
		update_user_meta($user_id, 'show_cancel_subscription', $show_cancel_subscription);
	}
}
