<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Class PT_Admin_AJAX.
 *
 * AJAX class has holds all the Admin ajax calls.
 *
 * @class          PT_Admin_AJAX
 * @version        1.0.0
 * @author         Jeroen Sormani
 */
class PT_Admin_AJAX {


	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Create Mollie account
		add_action( 'wp_ajax_paytium_mollie_create_account', array ( $this, 'create_mollie_account' ) );

		// Create Mollie profile
		add_action( 'wp_ajax_paytium_mollie_create_profile', array ( $this, 'create_mollie_profile' ) );

		// Check Mollie account details
		add_action( 'wp_ajax_paytium_mollie_check_account_details', array ( $this, 'check_mollie_account_details' ) );

		// Check for verified profiles
		add_action( 'wp_ajax_paytium_mollie_check_for_verified_profiles', array ( $this, 'check_for_verified_profiles' ) );

		// Update profile
		add_action( 'wp_ajax_paytium_mollie_update_profile_preference', array ( $this, 'update_profile_preference' ) );

		// Check if a payment exists
		add_action( 'wp_ajax_paytium_check_payment_exists', array ( $this, 'check_if_payment_exists' ) );

		// Email attachments
		add_action( 'wp_ajax_paytium_emails_attachments', array ( $this, 'paytium_emails_attachments' ) );

		// Save paytium API keys on Setup Wizard if entered
		add_action( 'wp_ajax_paytium_sw_save_api_keys', array ( $this, 'paytium_sw_save_api_keys' ) );
	}

	/**
	 * Create Mollie account.
	 *
	 * Create a Mollie account. Initialized via the setup wizard.
	 *
	 * @since 1.0.0
	 */
	public function create_mollie_account() {

		check_ajax_referer( 'paytium-ajax-nonce', 'nonce' );

		// Bail if required value is not set.
		if ( ! isset( $_POST['form'] ) || !current_user_can('administrator') ) {
			die();
		}

		$post_data = wp_parse_args( $_POST['form'] );

		$args     = array (
			'name'         => sanitize_text_field( $post_data['name'] ),
			'company_name' => sanitize_text_field( $post_data['company_name'] ),
			'email'        => sanitize_text_field( $post_data['email'] ),
			'address'      => sanitize_text_field( $post_data['address'] ),
			'zipcode'      => sanitize_text_field( $post_data['zipcode'] ),
			'city'         => sanitize_text_field( $post_data['city'] ),
			'country'      => sanitize_text_field( $post_data['country'] ),
		);
		$response = Paytium()->api->create_mollie_account( $args );

		// An error occurred in the initial API call
		if ( strpos( $response['body'], 'error' ) !== false ) {
			die( json_encode( array (
				'status'  => 'error',
				'message' => '<div class="pt-alert pt-alert-danger">' . $this->convert_api_error_messages($response['body'] ) . '</div>',
			) ) );
		}

		// Passed initial API call and got response from Mollie
		if ( is_object( json_decode( $response['body'] ) ) ) {
			$response_data = json_decode( $response['body'] );

			set_transient( 'paytium_mollie_username', $response_data->data->username, WEEK_IN_SECONDS * 2 ); // 2 week expiration
			set_transient( 'paytium_mollie_password', $response_data->data->password, WEEK_IN_SECONDS * 2 ); // 2 week expiration
			update_option( 'paytium_mollie_partner_id', $response_data->data->partner_id );

			die( json_encode( array (
				'status'  => 'success',
				'message' => '<div class="pt-alert pt-alert-success">' . esc_html( $response_data->data->resultmessage ) . '</div>',
			) ) );
		}

	}

	/**
	 * Check for Mollie account details
	 *
	 * Check Mollie account details are known, before allowing access to 'Website profile' step
	 *
	 * @since 2.1.0
	 */
	public function check_mollie_account_details() {

		check_ajax_referer( 'paytium-ajax-nonce', 'nonce' );

		if (current_user_can('administrator')) {

			$args     = array (
				'username' => get_transient( 'paytium_mollie_username' ),
				'password' => get_transient( 'paytium_mollie_password' ),
			);

			if ( (get_option('paytium_test_api_key') || get_option('paytium_live_api_key'))
				&& isset($_POST['existing_account']) && $_POST['existing_account'] == 1 ) {
				die( wp_send_json( array (
					'status'  => 'success'
				) ) );
			}
			else {
				// Make sure username and password are set
				if ( $args['username'] == false || $args['password'] == false) {
					die( wp_send_json( array (
						'status'  => 'error'
					) ) );
				} else {
					die( wp_send_json( array (
						'status'  => 'success'
					) ) );
				}
			}
		}
	}

	/**
	 * Create Mollie profile.
	 *
	 * Create a Mollie website profile. Initialized via the setup wizard.
	 *
	 * @since 1.0.0
	 */
	public function create_mollie_profile() {

		check_ajax_referer( 'paytium-ajax-nonce', 'nonce' );

		// Bail if required value is not set.
		if ( ! isset( $_POST['form'] ) || !current_user_can('administrator')) {
			die();
		}

		$post_data = wp_parse_args( $_POST['form'] );

		$args     = array (
			'username' => get_transient( 'paytium_mollie_username' ),
			'password' => get_transient( 'paytium_mollie_password' ),
			'name'     => sanitize_text_field( $post_data['name'] ),
			'website'  => sanitize_text_field( $post_data['website'] ),
			'email'    => sanitize_text_field( $post_data['email'] ),
			'phone'    => sanitize_text_field( $post_data['phone'] ),
		);
		$response = Paytium()->api->create_mollie_profile( $args );

		// An error occurred in the initial API call
		if ( strpos( $response['body'], 'error' ) !== false ) {
			die( json_encode( array (
				'status'  => 'error',
				'message' => '<div class="pt-alert pt-alert-danger">' . $this->convert_api_error_messages( $response['body'] ) . __(' Or maybe the account is not activated yet?', 'paytium' ) . '</div>',
			) ) );
		}

		// Passed initial API call and got response from Mollie
		if ( is_object( json_decode( $response['body'] ) ) ) {
			$response_data = json_decode( $response['body'] );

			// Save profile data
			update_option( 'paytium_mollie_website_profile', $response_data->data->profile->hash );
			update_option( 'paytium_test_api_key', $response_data->data->profile->api_keys->test );
			update_option( 'paytium_live_api_key', $response_data->data->profile->api_keys->live );

			die( json_encode( array (
				'status'  => 'success',
				'message' => '<div class="pt-alert pt-alert-success">' . esc_html( $response_data->data->resultmessage ) . '</div>',
			) ) );

		}

	}


	/**
	 * Update profile preference
	 *
	 * Update the user's profile preference and re-check.
	 *
	 * @since 2.0.0
	 */
	public function update_profile_preference() {

		check_ajax_referer( 'paytium-ajax-nonce', 'nonce' );

		// Bail if required value is not set.
		if ( (! isset( $_POST['hash'] ) && ! isset( $_POST['profile_test_key'] ) && ! isset( $_POST['profile_live_key'] )) || !current_user_can('administrator') ) {
			die();
		}
		// Save profile data
		update_option( 'paytium_mollie_website_profile', esc_attr($_POST['hash']) );
		update_option( 'paytium_test_api_key', esc_attr($_POST['test_key']) );
		update_option( 'paytium_live_api_key', esc_attr($_POST['live_key']) );

		die( wp_send_json( array ( 'status' => 'success' ) ) );
	}

	/**
	 * Check for verified profiles.
	 *
	 * Check the profile status that has been created before, and see if the
	 * status is set to 'verified'.
	 *
	 * @since 1.0.0
	 */
	public function check_for_verified_profiles() {

		check_ajax_referer( 'paytium-ajax-nonce', 'nonce' );

		if (current_user_can('administrator')) {

			$args     = array (
				'username' => get_transient( 'paytium_mollie_username' ),
				'password' => get_transient( 'paytium_mollie_password' ),
			);
			$response = Paytium()->api->profiles( $args );

			// An error occurred in the initial API call
			if ( strpos( $response['body'], 'error' ) !== false ) {

				die( wp_send_json( array (
					'status'  => 'error',
					'message' => '<div class="pt-alert pt-alert-danger">' . __( 'Can\'t log in, did you click the link in the activation email?', 'paytium' ) . '</div>',
				) ) );
			}

			// Passed initial API call and got response from Mollie
			if ( is_array( $response ) ) {

				$response_data = json_decode( $response['body'] );

				die( wp_send_json( array (
					'status'   => 'success',
					'profiles' => $response_data->data->items
				) ) );

			}

			die();
		}

	}

	/**
	 * Test payment.
	 *
	 * Check if a test payment exists.
	 *
	 * @since 1.0.0
	 */
	public function check_if_payment_exists() {

		$payments = pt_get_payments( array ( 'posts_per_page' ) );

		if ( ! empty( $payments ) ) :
			die( json_encode( array (
				'status'  => 'success',
				'message' => '<div class="pt-alert pt-alert-success">' . __( 'A test payment has been found.', 'paytium' ) . '</div>',
			) ) );
		else :
			die( json_encode( array (
				'status'  => 'error',
				'message' => '<div class="pt-alert pt-alert-danger">' . __( 'No test payment found, please try again.', 'paytium' ) . '</div>',
			) ) );
		endif;

	}

	/**
	 * Convert API error messages
	 *
	 * Basic changes tio API error messages so they are just a little better.
	 *
	 * @since 1.0.0
	 */
	public function convert_api_error_messages( $message ) {

		$message = str_replace( 'An error occurred when creating an account: ', '', $message );
		$message = str_replace( 'Argument ', __( 'Field ', 'paytium' ), $message );
		$message = str_replace( 'argument ', __( 'field ', 'paytium' ), $message );
		$message = str_replace( 'is required', __( 'is required', 'paytium' ), $message );

		return $message;
	}

	public function paytium_emails_attachments() {

		check_ajax_referer( 'paytium-ajax-nonce', 'nonce' );

		if (current_user_can('edit_posts')) {

			$attachment_id = isset($_POST['attachment_id']) ? $_POST['attachment_id'] : false;

			if ($attachment_id) {

				$thumbnail = wp_get_attachment_image_src($attachment_id, 'thumbnail');
				$thumbnail_url = $thumbnail ? $thumbnail[0] : $thumbnail;

				wp_send_json(array('result' => 'success', 'thumbnail' => $thumbnail_url));
			}
			else wp_send_json(array('result' => 'error'));
		}
	}

	/**
	 * Save paytium API keys on Setup Wizard if entered
	 */
	public function paytium_sw_save_api_keys() {

		check_ajax_referer( 'paytium-ajax-nonce', 'nonce' );

		if (current_user_can('administrator')) {

			$test_api_key = isset($_POST['test_api_key']) ? $_POST['test_api_key'] : false;
			$live_api_key = isset($_POST['live_api_key']) ? $_POST['live_api_key'] : false;

			if ($test_api_key) {
				update_option('paytium_test_api_key',$test_api_key);
			}
			if ($test_api_key) {
				update_option('paytium_live_api_key',$live_api_key);
			}

			echo 'success';
			wp_die();
		}
	}
}