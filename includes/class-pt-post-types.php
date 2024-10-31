<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Class PT_Post_Types.
 *
 * Initialize and set up payment post type.
 *
 * @class       PT_Post_Types
 * @version     1.0.0
 * @author      David de Boer
 */
class PT_Post_Types {


	/**
	 * Constructor.
	 *
	 * Initialize this class including hooks.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Register post type
		add_action( 'init', array ( $this, 'register_post_type' ) );

		// Update post type messages
		add_filter( 'post_updated_messages', array ( $this, 'custom_notice_messages' ) );

		// Add custom columns
		add_action( 'manage_edit-pt_payment_columns', array ( $this, 'custom_columns' ), 10, 1 );

		// Add contents to the new columns
		add_action( 'manage_pt_payment_posts_custom_column', array ( $this, 'custom_column_contents' ), 10, 2 );

		// Add meta box
		add_action( 'add_meta_boxes', array ( $this, 'add_custom_meta_boxes' ) );

		// Save meta box
		add_action( 'save_post', array ( $this, 'save_custom_meta_boxes' ) );

		// Add pt_payment filters
		add_action( 'restrict_manage_posts', array ( $this, 'add_filters' ) );

		// Filter actions
		add_action( 'request', array ( $this, 'request_filter_actions' ) );

		// Modify bulk actions
		add_action( 'bulk_actions-edit-pt_payment', array ( $this, 'bulk_actions' ) );

		// Hide redundant stuff from Publish metabox
		add_action( 'admin_head', array ( $this, 'pt_payment_hide_minor_publishing' ) );

		// Add nice text to Publish metabox
		add_action( 'post_submitbox_misc_actions', array ( $this, 'pt_payment_postbox_contents' ) );

	}


	/**
	 * Post type.
	 *
	 * Register post type.
	 *
	 * @since 1.0.0
	 */
	public function register_post_type() {

		$subscription_ui = is_file( PT_PATH . 'features/subscriptions.php' );
		$subscription_show_in_menu = false;

		// Only check in admin, solves conflict with CiviCRM
		if (is_admin()){
			$subscription_show_in_menu = get_posts( array('post_type' => 'pt_subscription', 'posts_per_page' => 1) ) ? 'paytium' : false;
		}

		$labels = array (
			// Don't change to Payment, this is shown top left in Payments overview
			'name'               => __( 'Payments', 'paytium' ),
			'singular_name'      => __( 'Payment', 'paytium' ),
			'menu_name'          => __( 'Payments', 'paytium' ),
			'name_admin_bar'     => __( 'Payment', 'paytium' ),
			'add_new'            => __( 'Add New', 'paytium' ),
			'add_new_item'       => __( 'Add New Payment', 'paytium' ),
			'new_item'           => __( 'New Payment', 'paytium' ),
			'edit_item'          => __( 'Edit Payment', 'paytium' ),
			'view_item'          => __( 'View Payment', 'paytium' ),
			'all_items'          => __( 'Payments', 'paytium' ),
			'search_items'       => __( 'Search Payments', 'paytium' ),
			'parent_item_colon'  => __( 'Parent Payments:', 'paytium' ),
			'not_found'          => sprintf( __( 'Patience, my friend, patience!<br /> No payments received yet...<br />Did you finish the Paytium %ssetup%s?', 'paytium' ), '<a
                href="' . esc_url( admin_url( 'admin.php?page=paytium' ) ) . '" target="_blank">', '</a>' ),
			'not_found_in_trash' => __( 'No Payments found in Trash.', 'paytium' )
		);

		$args = array (
			'labels'              => $labels,
			'public'              => false,
			'exclude_from_search' => true,
			'can_export'          => true,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => 'paytium',
			'query_var'           => true,
			'rewrite'             => array ( 'slug' => 'payment' ),
			'capability_type'     => 'post',
			'has_archive'         => true,
			'hierarchical'        => false,
			'menu_position'       => null,
			'supports'            => false,
			'capabilities'        => array (
				'create_posts' => 'do_not_allow',
			),
			'map_meta_cap'        => true,
		);
		register_post_type( 'pt_payment', $args );


		$labels = array (
			'name'               => __( 'Subscriptions', 'paytium' ),
			'singular_name'      => __( 'Subscription', 'paytium' ),
			'menu_name'          => __( 'Subscriptions', 'paytium' ),
			'name_admin_bar'     => __( 'Subscriptions', 'paytium' ),
			'add_new'            => __( 'Add New', 'paytium' ),
			'add_new_item'       => __( 'Add New Subscription', 'paytium' ),
			'new_item'           => __( 'New Subscription', 'paytium' ),
			'edit_item'          => __( 'Edit Subscription', 'paytium' ),
			'view_item'          => __( 'View Subscription', 'paytium' ),
			'all_items'          => __( 'Subscriptions', 'paytium' ),
			'search_items'       => __( 'Search Subscriptions', 'paytium' ),
			'parent_item_colon'  => __( 'Parent Subscriptions:', 'paytium' ),
			'not_found'          => sprintf( __( 'Patience, my friend, patience!<br /> No subscriptions received yet...<br />Did you finish the Paytium %ssetup%s?', 'paytium' ), '<a
                href="' . esc_url( admin_url( 'admin.php?page=paytium' ) ) . '" target="_blank">', '</a>' ),
			'not_found_in_trash' => __( 'No Subscriptions found in Trash.', 'paytium' )
		);

		$args = array (
			'labels'              => $labels,
			'public'              => false,
			'exclude_from_search' => true,
			'can_export'          => true,
			'publicly_queryable'  => false,
			'show_ui'             => $subscription_ui,
			'show_in_menu'        => $subscription_show_in_menu,
			'query_var'           => true,
			'rewrite'             => array ( 'slug' => 'subscription' ),
			'capability_type'     => 'post',
			'has_archive'         => true,
			'hierarchical'        => false,
			'menu_position'       => null,
			'supports'            => false,
			'capabilities'        => array (
				'create_posts' => 'do_not_allow',
			),
			'map_meta_cap'        => true,
		);
		register_post_type( 'pt_subscription', $args );


	}

	/**
	 * Admin messages.
	 *
	 * Custom admin messages when using the post type.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $messages List of existing messages.
	 *
	 * @return array           Full list of all messages.
	 */
	public function custom_notice_messages( $messages ) {

		$post             = get_post();
		$post_type        = 'pt_payment';
		$post_type_object = get_post_type_object( $post_type );

		$messages[ $post_type ] = array (
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Payment updated.', 'paytium' ),
			2  => __( 'Custom field updated.', 'paytium' ),
			3  => __( 'Custom field deleted.', 'paytium' ),
			4  => __( 'Payment updated.', 'paytium' ),
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Payment restored to revision from %s', 'paytium' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => __( 'Payment published.', 'paytium' ),
			7  => __( 'Payment saved.', 'paytium' ),
			8  => __( 'Payment submitted.', 'paytium' ),
			9  => sprintf(
				__( 'Payment scheduled for: <strong>%1$s</strong>.', 'paytium' ),
				date_i18n( __( 'M j, Y @ G:i', 'paytium' ), strtotime( $post->post_date ) )
			),
			10 => __( 'Payment draft updated.', 'paytium' )
		);

		if ( $post_type_object->publicly_queryable ) {
			$permalink = get_permalink( $post->ID );

			$view_link = sprintf( ' <a href="%s">%s</a>', esc_url( $permalink ), __( 'View Payment', 'paytium' ) );
			$messages[ $post_type ][1] .= $view_link;
			$messages[ $post_type ][6] .= $view_link;
			$messages[ $post_type ][9] .= $view_link;

			$preview_permalink = add_query_arg( 'preview', 'true', $permalink );
			$preview_link      = sprintf( ' <a target="_blank" href="%s">%s</a>', esc_url( $preview_permalink ), __( 'Preview Payment', 'paytium' ) );
			$messages[ $post_type ][8] .= $preview_link;
			$messages[ $post_type ][10] .= $preview_link;
		}

		return $messages;

	}


	/**
	 * Custom payment columns.
	 *
	 * Set custom columns for the payment post type.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $existing_columns List of existing post columns.
	 *
	 * @return array                   List of edited columns.
	 */
	public function custom_columns( $existing_columns ) {

		$columns['cb']             = '<input type="checkbox" />';
		$columns['status']         = __( 'Payment Status', 'paytium' );
		$columns['payment']        = __( 'Payment', 'paytium' );
		$columns['transaction_id'] = __( 'Transaction ID', 'paytium' );
		$columns['amount']         = __( 'Amount', 'paytium' );
		$columns['payment_date']   = __( 'Date', 'paytium' );
		$columns['order_status']   = __( 'Order Status', 'paytium' );

		unset( $existing_columns['title'] );
		unset( $existing_columns['date'] );
		unset( $columns['transaction_id'] );
		$merged_columns = array_merge( $existing_columns, $columns );

		return $merged_columns;

	}


	/**
	 * Columns contents.
	 *
	 * Output the custom columns contents.
	 *
	 * @since 1.0.0
	 *
	 * @param string $column Slug of the current columns to output data for.
	 * @param int $post_id   ID of the current post.
	 */
	public function custom_column_contents( $column, $post_id ) {

		$payment = pt_get_payment( $post_id );
		switch ( $column ) :

			case 'status' :
				$status = $payment->get_status();
				?><span class='status status-<?php echo sanitize_html_class( strtolower( $payment->status ) ); ?>'><?php
				echo $status;
				?></span><?php
				break;

			case 'amount' :
				echo pt_float_amount_to_currency( $payment->get_amount(), $payment->currency );
				break;

			case 'payment_date' :
				echo $payment->get_payment_date();
				break;

			case 'transaction_id' :
				echo $payment->get_transaction_id();
				break;

			case 'payment' :

				if ( $payment->no_payment == false ) {
					echo $this->payments_overview_details_for_payments( $payment );
				} else {
					echo $this->payments_overview_details_for_submissions( $payment );
				}

				break;

			case 'order_status' :
				$order_status = $payment->get_order_status();
				?><span
				class='order-status order-status-<?php echo sanitize_html_class( strtolower( $payment->order_status ) ); ?>'><?php
				echo $order_status;
				?></span><?php
				break;

		endswitch;

	}


	/**
	 * Details in Payments overview for payments, eg "Betaling #1000", "Payment #1000".
	 *
	 * @since 1.5.0
	 */
	public function payments_overview_details_for_payments( $payment ) {

		$html = '';

		// This shows the status of a PAYMENT connected to a subscription, not the subscription, that is viewable in the Payment detail view
		if ($payment->subscription == 1) {

			if ( ( $payment->subscription_payment_status == 'pending')  ) {
				$html .= '<span class="dashicons dashicons-backup paytium-subscription-pending-icon" title="';
				$html .= __( 'Subscription not created yet.', 'paytium' );
				$html .= '"></span>';


			} elseif ( ( $payment->subscription_payment_status == 'completed' || $payment->subscription_payment_status == 'initial' ) ) {
				$html .= '<span class="dashicons dashicons-backup paytium-subscription-active-icon" title="';
				$html .= __( 'Subscription created and paid.', 'paytium' );
				$html .= '"></span>';

				// TODO: what about failed renewal payments?
			} elseif ( $payment->subscription_payment_status == 'renewal' ) {
				$html .= '<span class="dashicons dashicons-backup paytium-subscription-renewal-icon" title="';
				$html .= __( 'Subscription renewal payment.', 'paytium' );
				$html .= '"></span>';

			} elseif ( $payment->subscription_payment_status == 'failed' ) {
				$html .= '<span class="dashicons dashicons-backup paytium-subscription-failed-icon" title="';
				$html .= __( 'Creating subscription failed:', 'paytium' ) . ' ' . strtolower( $payment->subscription_error );
				$html .= '"></span>';
			}

		}

		else if ( $payment->subscription == '' && $payment->subscription_id != '') {
			$payments = unserialize(get_post_meta($payment->subscription_id, '_payments', true));

			if ( $payment->subscription_status == 'pending' ) {
				$html .= '<span class="dashicons dashicons-backup paytium-subscription-pending-icon" title="';
				$html .= __( 'Subscription not created yet.', 'paytium' );
				$html .= '"></span>';

			} elseif ( ( $payment->subscription_status == 'active' || $payment->subscription_status == 'cancelled' ) && !empty($payments) && $payments[0] == $payment->id &&
			           $payment->subscription_error == '') {
				$html .= '<span class="dashicons dashicons-backup paytium-subscription-active-icon" title="';
				$html .= __( 'Subscription created and paid.', 'paytium' );
				$html .= '"></span>';

				// TODO: what about failed renewal payments?
			} elseif ( ( $payment->subscription_status == 'active' || $payment->subscription_status == 'cancelled' ) && !empty($payments) && $payments[0] != $payment->id &&
			           $payment->subscription_error == '') {
				$html .= '<span class="dashicons dashicons-backup paytium-subscription-renewal-icon" title="';
				$html .= __( 'Subscription renewal payment.', 'paytium' );
				$html .= '"></span>';

			} elseif ( $payment->subscription_error != '' && $payment->subscription_status == 'cancelled' ) {
				$html .= '<span class="dashicons dashicons-backup paytium-subscription-failed-icon" title="';
				$html .= __( 'Creating subscription failed:', 'paytium' ) . ' ' . strtolower( __($payment->subscription_error, 'paytium') );
				$html .= '"></span>';

			}
		}

		$html .= '<a href="' . get_edit_post_link( $payment->id ) . '" >';
		$html .= '<strong>' . sprintf( __( 'Payment #%d', 'paytium' ), $payment->id ) . '</strong>';
		$html .= '</a>';

		return $html;
	}

	/**
	 * Details in Payments overview for submissions, eg "Submission #1000", "Inzending #1000".
	 *
	 * @since 1.5.0
	 */
	public function payments_overview_details_for_submissions( $payment ) {

		$html = '';

		$html .= '<a href="' . get_edit_post_link( $payment->id ) . '" >';
		$html .= '<strong>' . sprintf( __( 'Submission #%d', 'paytium' ), $payment->id ) . '</strong>';
		$html .= '</a>';

		return $html;
	}


	/**
	 * Add meta boxes.
	 *
	 * Add an meta box with all settings.
	 *
	 * @since 1.0.0
	 */
	public function add_custom_meta_boxes() {

		global $post;

		$payment = pt_get_payment( $post->ID );

        if (get_post_meta($payment->id, '_status', true) == 'failed' && get_post_meta($payment->id, '_pt_payment_error', true) != '') {
            add_meta_box( 'pt_payment_failed', __( 'Payment failed', 'paytium' ), array (
                $this,
                'pt_payment_failed_callback'
            ), 'pt_payment', 'normal', 'high' );
        }

		add_meta_box( 'pt_payment_details', __( 'Payment details', 'paytium' ), array (
			$this,
			'pt_payment_details_callback'
		), 'pt_payment', 'normal' );

		if ( $payment->get_subscription_first_payment() !== '' ) {
			add_meta_box( 'pt_subscription_first_payment', __( 'Subscription first payment', 'paytium' ), array (
				$this,
				'pt_subscription_first_payment_callback'
			), 'pt_payment', 'normal' );
		}

		add_meta_box( 'pt_payment_items', __( 'Payment items', 'paytium' ), array (
			$this,
			'pt_payment_items_callback'
		), 'pt_payment', 'normal' );

		add_meta_box( 'pt_customer_details', __( 'Customer details', 'paytium' ), array (
			$this,
			'pt_customer_details_callback'
		), 'pt_payment', 'normal' );

		if ( $payment->subscription == 1 || ($payment->subscription_id != '')) {
			add_meta_box( 'pt_subscription_details', __( 'Subscription details', 'paytium' ), array (
				$this,
				'pt_subscription_details_callback'
			), 'pt_payment', 'side' );
		}

        if (get_post_meta($payment->id, '_pt-uploaded-files', true) != '') {
            add_meta_box( 'pt_payment_files', __( 'Uploaded files', 'paytium' ), array (
                $this,
                'pt_payment_files_callback'
            ), 'pt_payment', 'side' );
        }

	}


	/**
	 * Meta box content.
	 *
	 * Get contents from file and put them in the meta box.
	 *
	 * @since 1.0.0
	 */
	public function pt_payment_details_callback() {

		global $post;

		$payment = pt_get_payment( $post->ID );

		require_once( PT_PATH . 'admin/views/meta-boxes/payment-details.php' );

	}

	/**
	 * Subscription first payment meta box content.
	 *
	 * @since 2.1.0
	 */
	public function pt_subscription_first_payment_callback() {

		global $post;

		$payment = pt_get_payment( $post->ID );

		require ( PT_PATH . 'admin/views/meta-boxes/subscription-first-payment.php' );

	}

	/**
	 * Meta box content.
	 *
	 * Get contents from file and put them in the meta box.
	 *
	 * @since 1.0.0
	 */
	public function pt_payment_items_callback() {

		global $post;

		$payment = pt_get_payment( $post->ID );

		require ( PT_PATH . 'admin/views/meta-boxes/payment-items-admin.php' );

	}


	/**
	 * Meta box content.
	 *
	 * Get contents from file and put them in the meta box.
	 *
	 * @since 1.0.0
	 */
	public function pt_customer_details_callback() {

		global $post;

		$payment = pt_get_payment( $post->ID );

		require_once( PT_PATH . 'admin/views/meta-boxes/customer-details.php' );

	}

	/**
	 * Meta box content.
	 *
	 * Get contents from file and put them in the meta box.
	 *
	 * @since 1.0.0
	 */
	public function pt_subscription_details_callback() {

		global $post;
		global $pt_mollie;

		$payment = pt_get_payment( $post->ID );

        // Get current subscription data from Mollie
		pt_set_paytium_key( $payment->mode );

        require_once( PT_PATH . 'admin/views/meta-boxes/subscription-details.php' );

	}

    /**
     * Meta box content.
     *
     * Get contents from file and put them in the meta box.
     *
     * @since 2.2.0
     */
    public function pt_payment_failed_callback() {
        global $post;

        $payment = pt_get_payment( $post->ID );

        require_once( PT_PATH . 'admin/views/meta-boxes/payment-failed.php' );

    }

    /**
     * Meta box content.
     *
     * Get contents from file and put them in the meta box.
     *
     * @since 2.2.0
     */
    public function pt_payment_files_callback() {
        global $post;

        $payment = pt_get_payment( $post->ID );

        require_once( PT_PATH . 'admin/views/meta-boxes/uploaded-files.php' );

    }


	/**
	 * Save meta boxes.
	 *
	 * Save the given contents from the meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param  int $post_id ID of the current post.
	 *
	 * @return int          ID of the post when something went wrong.
	 */
	public function save_custom_meta_boxes( $post_id ) {

		if ( ! isset( $_POST['pt_payment_nonce'] ) || ! wp_verify_nonce( $_POST['pt_payment_nonce'], 'pt_payment_details' ) ) :
			return $post_id;
		endif;

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) :
			return $post_id;
		endif;

		$author_id = get_post_field ('post_author', $post_id);
		if ($author_id == 0) {
		    update_post_meta($post_id, 'payment_author', 0);
        }

		$old_status = get_post_meta( $post_id, '_status', true );
		switched_payment_status_log($post_id, $old_status, $_POST['payment_status']);

		$payment = pt_get_payment( $post_id );

		// Update post meta
		$payment->set_status( $_POST['payment_status'] );
		$payment->set_order_status( $_POST['order_status'] );

		$payment->update_status_from_admin( $post_id );
	}


	/**
	 * Add payment filters.
	 *
	 * Add filters to the pt_payment post type overview.
	 *
	 * @since 1.0.0
	 */
	public function add_filters() {

		global $typenow;

		if ( 'pt_payment' == $typenow ) :

			do_action('datepicker_input');

			$payment_status = isset( $_GET['_payment_status'] ) ? esc_attr($_GET['_payment_status']) : '';
			// Display payment status drop down
			?><select name='_payment_status'>
			<option value=''><?php _e( 'All payment statuses', 'paytium' ); ?></option><?php
			foreach ( pt_get_payment_statuses() as $key => $value ) :
				?>
				<option <?php selected( $payment_status, $key ); ?>
				value='<?php echo $key; ?>'><?php echo $value; ?></option><?php
			endforeach;
			?></select><?php

			$order_status   = isset( $_GET['_order_status'] ) ? esc_attr($_GET['_order_status']) : '';
			// Display payment status drop down
			?><select name='_order_status'>
			<option value=''><?php _e( 'All order statuses', 'paytium' ); ?></option><?php
			foreach ( pt_get_order_statuses() as $key => $value ) :
				?>
				<option <?php selected( $order_status, $key ); ?>
				value='<?php echo $key; ?>'><?php echo $value; ?></option><?php
			endforeach;
			?></select><?php

		endif;

	}


	/**
	 * Apply filter actions.
	 *
	 * Modify the main request when filters are set.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $request Existing request arguments.
	 *
	 * @return mixed          Modified request arguments.
	 */
	public function request_filter_actions( $request ) {

		global $typenow;

		if ( 'pt_payment' == $typenow ) :

			if ( isset ( $_GET['_payment_status'] ) && ! empty( $_GET['_payment_status'] ) ) :
				$request['meta_query'][] = array (
					'key'     => '_status',
					'compare' => '=',
					'value'   => sanitize_text_field( $_GET['_payment_status'] ),
				);

			endif;

			if ( isset ( $_GET['_order_status'] ) && ! empty( $_GET['_order_status'] ) ) :
				$request['meta_query'][] = array (
					'key'     => '_order_status',
					'compare' => '=',
					'value'   => sanitize_text_field( $_GET['_order_status'] ),
				);

			endif;

		endif;

		return $request;

	}


	/**
	 * Modify bulk actions.
	 *
	 * Modify the bulk actions for the pt_payment post type.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $actions List of existing actions.
	 *
	 * @return array          List of modified actions.
	 */
	public function bulk_actions( $actions ) {

		unset( $actions['edit'] );

		return $actions;

	}

	/**
	 * In Publish metabox, remove default content
	 * @since 1.4.0
	 */
	public function pt_payment_hide_minor_publishing() {
		$screen = get_current_screen();
		if ( $screen->id == 'pt_payment' || $screen->id == 'pt_subscription' ) {
			echo '<style>.misc-pub-post-status, .misc-pub-visibility, .misc-pub-curtime { display: none; }</style>';
		}
	}

	/**
	 * In Publish metabox, add simple text for pt_payment.
	 *
	 * @since 1.4.0
	 */
	public function pt_payment_postbox_contents() {

		global $post;
		$payment = pt_get_payment( $post->ID );

		if ( get_post_type( $post ) != 'pt_payment' ) {
			return false;
		}

		if ( $payment->no_payment == false ) {
			?>
            <div class="misc-pub-section">
            <label>
                <p class="howto pt-payment-intro"><?php echo __( 'A payment processed by Paytium.', 'paytium' ); ?></p>
            </label>

			<?php

			if ( $payment->mode == 'test' ) {
				?>
                <label>
                    <p class="howto pt-payment-intro test-mode-payment"><?php echo __( 'This payment was created in test mode!', 'paytium' ); ?></p>
                </label>
				<?php
			}

		} else {
			?>
            <div class="misc-pub-section">
            <label>
                <p class="howto pt-payment-intro"><?php echo __( 'A form without payment, processed by Paytium.', 'paytium' ); ?></p>
            </label>

			<?php
		}

		do_action( 'paytium_payment_after_publish_meta_box' );
		?>

        </div>

		<?php

		return null;
	}

}
