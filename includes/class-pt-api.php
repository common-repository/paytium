<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Class PT_API.
 *
 * API Class makes general API calls to the underlying API.
 *
 * @class          PT_API
 * @version        1.0.0
 * @author         Jeroen Sormani
 */
class PT_API {

	protected $api_url = 'https://www.davdeb.com/api/';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

	}


	/**
	 * Create Mollie account.
	 *
	 * Create a new Mollie account via the API.
	 *
	 * @since 1.0.0
	 *
	 * @param  $args
	 *
	 * @param  array $args List of API arguments.
	 *
	 * @return array|WP_Error       WP_Error when the API call failed. Array with the result otherwise.
	 */
	public function create_mollie_account( $args ) {

		$args = wp_parse_args( $args, array (
			'action'           => 'account-create',
			'customer_details' => array (
				'testmode'     => '0',
				'name'         => isset( $args['name'] ) ? sanitize_text_field( $args['name'] ) : '',
				'company_name' => isset( $args['company_name'] ) ? sanitize_text_field( $args['company_name'] ) : '',
				'email'        => isset( $args['email'] ) ? sanitize_text_field( $args['email'] ) : '',
				'address'      => isset( $args['address'] ) ? sanitize_text_field( $args['address'] ) : '',
				'zipcode'      => isset( $args['zipcode'] ) ? sanitize_text_field( $args['zipcode'] ) : '',
				'city'         => isset( $args['city'] ) ? sanitize_text_field( $args['city'] ) : '',
				'country'      => isset( $args['country'] ) ? sanitize_text_field( $args['country'] ) : '',
			),
		) );

		$response = $this->post( $args );

		return $response;

	}


	/**
	 * Create a Mollie profile.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $args List of API arguments.
	 *
	 * @return array|WP_Error       WP_Error when the API call failed. Array with the result otherwise.
	 */
	public function create_mollie_profile( $args ) {

		// Encode username & password so it's save to transmit
		$args['username'] = base64_encode( $args['username'] );
		$args['password'] = base64_encode( $args['password'] );

		$args     = wp_parse_args( $args, array (
			'action'   => 'profile-create',
			'username' => isset( $args['username'] ) ? sanitize_text_field( $args['username'] ) : '',
			'password' => isset( $args['password'] ) ? sanitize_text_field( $args['password'] ) : '',
		) );
		$response = $this->post( $args );

		return $response;

	}


	/**
	 * Verify profile.
	 *
	 * Verify the user's website profile (see if its 'verified').
	 *
	 * @since 1.0.0
	 *
	 * @param  array $args List of API arguments.
	 *
	 * @return array|WP_Error       WP_Error when the API call failed. Array with the result otherwise.
	 */
	public function verify_profile( $args ) {

		// Encode username & password so it's save to transmit
		$args['username'] = base64_encode( $args['username'] );
		$args['password'] = base64_encode( $args['password'] );

		$args = wp_parse_args( $args, array (
			'action'   => 'profile-verified',
			'username' => isset( $args['username'] ) ? sanitize_text_field( $args['username'] ) : '',
			'password' => isset( $args['password'] ) ? sanitize_text_field( $args['password'] ) : '',
			'hash'     => isset( $args['hash'] ) ? sanitize_text_field( $args['hash'] ) : '',
		) );
		$response = $this->post( $args );

		return $response;

	}

	/**
	 * Get all profiles.
	 *
	 * Get all the website profiles belonging to a user.
	 *
	 * @since 2.0.0
	 *
	 * @param  array $args List of API arguments.
	 *
	 * @return array|WP_Error       WP_Error when the API call failed. Array with the result otherwise.
	 */
	public function profiles( $args = array() ) {

		// Encode username & password so it's save to transmit
		$args['username'] = base64_encode( $args['username'] );
		$args['password'] = base64_encode( $args['password'] );

		$args     = wp_parse_args( $args, array (
			'action'   => 'profiles',
			'username' => isset( $args['username'] ) ? sanitize_text_field( $args['username'] ) : '',
			'password' => isset( $args['password'] ) ? sanitize_text_field( $args['password'] ) : '',
		) );
		$response = $this->post( $args );

		return $response;

	}

	/**
	 * POST API call.
	 *
	 * Make a POST API call to the url.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $args List of API arguments.
	 *
	 * @return array|WP_Error       WP_Error when the API call failed. Array with the result otherwise.
	 */
	public function post( $args ) {

		$args = wp_parse_args( $args, array (
			'version'        => 'v1',
			'client'         => 'wordpress'
		) );

		$response = wp_remote_post( $this->api_url, array (
			'timeout'     => 5,
			'redirection' => 5,
			'blocking'    => true,
			'headers'     => array (),
			'body'        => $args,
			'cookies'     => array ()
		) );

		return $response;

	}


}
