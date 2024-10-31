<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Class PT_Admin.
 *
 * Admin class holds, initializes and manages all main admin features.
 *
 * @class          PT_Admin
 * @version        1.0.0
 * @author         Jeroen Sormani
 */
class PT_Admin {

	public $ajax;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Initialize all features
		add_action( 'admin_init', array ( $this, 'init' ) );

	}


	/**
	 * Initialize admin parts.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		/**
		 * AJAX class
		 */
		require_once PT_PATH . '/admin/class-pt-ajax.php';
		$this->ajax = new PT_Admin_AJAX();

	}


}
