<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


?>

    <div class="submitbox" id="submitpost">
        <div id="minor-publishing">

			<?php

			$payments = unserialize(get_post_meta($payment->subscription_id, '_payments', true));

			if ( ($payment->subscription == 1 && $payment->subscription_payment_status == 'pending') ||
			     ( $payment->mollie_subscription_id != '' && $payment->subscription_id != '' && $payment->subscription_status == 'pending') ) {

				?>
                <div id="misc-publishing-actions">
                    <div class="inside-options">
						<?php

						echo __( 'Subscription not created yet.', 'paytium' );

						?>
                    </div> <!-- END INSIDE OPTIONS -->
                    <div class="clear"></div>
                </div> <!-- END MISC PUBLISHING ACTIONS -->

				<?php

			} elseif ( ! empty($payment->subscription_error) )  {

				?>
                <div id="misc-publishing-actions">
                    <div class="inside-options">
						<?php

						echo __( 'Creating subscription failed:', 'paytium' ) . '<br />' . strtolower( $payment->subscription_error );

						?>
                    </div> <!-- END INSIDE OPTIONS -->
                    <div class="clear"></div>
                </div> <!-- END MISC PUBLISHING ACTIONS -->
				<?php

			}

			// Show subscription details
			if ( ( $payment->subscription == 1 && ( $payment->subscription_payment_status == 'active' ||  $payment->subscription_payment_status == 'pending' || $payment->subscription_payment_status == 'initial' ) ) ||
			           ( $payment->mollie_subscription_id != '' && $payment->subscription_id != '' &&
			             ($payment->subscription_status == 'active' || $payment->subscription_status == 'cancelled') && !empty($payments)) ) {

				$subscription_cancelled = null;

				$sub_id = $payment->mollie_subscription_id != '' ? $payment->mollie_subscription_id : $payment->subscription_id;
				// BC
				try {
					$subscription = $pt_mollie->subscriptions->getForId($payment->customer_id,$sub_id);

					if ($subscription->status == 'canceled') { // my profile
						$subscription_cancelled = 1;
					}
				}
				catch ( Mollie\Api\Exceptions\ApiException $e ) {

					if ( strpos( $e->getMessage(), 'The subscription has been cancelled' ) !== false ) {
						$subscription_cancelled       = 1;

						$new_details = array (
							'subscription_cancelled_date' =>  date('Y-m-d'),
							'subscription_status'         => 'cancelled',
						);
						if ($payment->mollie_subscription_id != '' && $payment->subscription_status != 'cancelled') {
							$payment->subscription_status = 'cancelled';
							pt_update_payment_meta( $payment->subscription_id, $new_details);
						}
					}
				}

				?>
                <div id="misc-publishing-actions">
                    <div class="inside-options">

						<?php

                        // Show in Pro version only, link to Paytium Subscriptions Pro
                        if ($payment->mollie_subscription_id != '') {

                            do_action('pt_link_to_subscriptions_pro', $payment->subscription_id );
                        }

						if ( $payment->subscription_status != null ) {
							?>
                            <div class="option-group-subscription">
                                <label for="claimer">
									<?php echo __( 'Status', 'paytium' ) ?>:
                                </label>
                                <span class="option-value" id="option-value-subscription-status">
                                    <?php echo ucfirst( __( $payment->subscription_status, 'paytium' ) ) ?>
                                </span>
                            </div>
							<?php
						}
						?>

                        <div class="option-group-subscription">
                            <label for="claimer">
								<?php echo __( 'Payment', 'paytium' ) ?>:
                            </label>
                            <span class="option-value" id="option-value-subscription-payment-status">
                                    <?php
                                    $payment_type = ( $payment->subscription_payment_status == 'completed' ? 'initial' : $payment->subscription_payment_status );
                                    echo __( ucfirst( $payment_type ), 'paytium' );
                                    ?>
                                </span>
                        </div>

                        <div class="option-group-subscription">
                            <label for="claimer">
								<?php echo __( 'Interval', 'paytium' ) ?>:
                            </label>
                            <span class="option-value">
                                    <?php echo ucfirst( __( $payment->subscription_interval, 'paytium' ) ) ?>
                                </span>
                        </div>

                        <div class="option-group-subscription">
                            <label for="claimer">
								<?php echo __( 'Times', 'paytium' ) ?>:
                            </label>
                            <span class="option-value">
                                    <?php echo ucfirst( __( $payment->subscription_times, 'paytium' ) ) ?>
                                </span>
                        </div>

                        <div class="option-group-subscription">
                            <label for="claimer">
								<?php echo __( 'Amount', 'paytium' ) ?>:
                            </label>
                            <span class="option-value">

                                <?php
                                if ( $payment->subscription_first_payment !== '' ) {
	                                $subscription_amount = $payment->subscription_recurring_payment;
                                } else {
	                                $subscription_amount = $payment->payment_amount;
                                }
                                echo esc_html( pt_float_amount_to_currency( __( $subscription_amount, 'paytium' ), $payment->currency ) )
                                ?>
                                </span>
                        </div>

                        <?php if ($payment->is_discount()) : ?>
                            <div class="option-group-subscription">
                                <label for="claimer">
									<?php _e( 'Discount', 'paytium' ) ?>:
                                </label>
                                <span class="option-value">
                                    <?php
									echo $payment->get_discount_value() . ' (code: ' . $payment->get_discount_code() . ')';
									?>
                                    </span>
                            </div>
						<?php endif; ?>

                        <div class="option-group-subscription">
                            <label for="claimer">
								<?php echo __( 'ID', 'paytium' ) ?>:
                            </label>
                            <span class="option-value">
                                    <?php echo $payment->mollie_subscription_id != '' ? $payment->mollie_subscription_id : $payment->subscription_id ?>
                            </span>
                        </div>

                        <div class="option-group-subscription">
                            <label for="claimer">
								<?php echo __( 'Customer', 'paytium' ) ?>:
                            </label>
                            <span class="option-value">
                                    <?php echo __( $payment->customer_id, 'paytium' ) ?>
                                </span>
                        </div>

						<?php

						if ( $payment->payment_date != null ) {
							?>
                            <div class="option-group-subscription">
                                <label for="claimer">
									<?php echo __( 'First payment', 'paytium' ) ?>:
                                </label>
                                <span class="option-value">
                                    <?php echo __( $payment->subscription_first_payment, 'paytium' ) ?>
                                </span>
                            </div>

							<?php
						}

						if ( $payment->subscription_start_date != null ) {
							?>
                            <div class="option-group-subscription">
                                <label for="claimer">
									<?php echo __( 'Start renewals', 'paytium' ) ?>:
                                </label>
                                <span class="option-value">
                                    <?php echo preg_replace( '/T.*/', '', __( $payment->subscription_start_date, 'paytium' ) ) ?>
                                </span>
                            </div>

							<?php
						}

						$visibility = ( $payment->subscription_cancelled_date == null ) ? 'none' : 'block';
						?>

                        <div class="option-group-subscription option-group-subscription-cancelled"
                             style="display:<?php echo $visibility ?>">
                            <label for="claimer">
								<?php echo __( 'Cancelled', 'paytium' ) ?>:
                            </label>
                            <span class="option-value option-value-cancelled">
                                    <?php echo preg_replace( '/T.*/', '', __( $payment->subscription_cancelled_date, 'paytium' ) ) ?>
                                </span>
                        </div>

                        <div class="option-group-subscription option-group-subscription-cancelled"
                             style="display:<?php echo $visibility ?>">
                            <p>
			                    <?php echo __( 'Note: subscription renewal payments that are already planned at Mollie can not be cancelled. Most of the time these are SEPA Direct Debit payments. You can choose to refund them manually after their status is confirmed. This can take up to 7 workdays.', 'paytium' ) ?>
                            </p>
                        </div>

                    </div> <!-- END INSIDE OPTIONS -->
                    <div class="clear"></div>
                </div> <!-- END MISC PUBLISHING ACTIONS -->

				<?php
				if ( $subscription_cancelled == null ) {
					?>

                    <div id="major-publishing-actions">

                        <div id="publishing-action">
                            <span class="spinner"></span>

                            <input type="hidden" id="payment_id" name="payment_id"
                                   value="<?php echo $payment->id ?>">
                            <input type="hidden" id="subscription_id" name="subscription_id"
                                   value="<?php echo $payment->mollie_subscription_id != '' ? $payment->mollie_subscription_id : $payment->subscription_id ?>">
                            <input type="hidden" id="customer_id" name="customer_id"
                                   value="<?php echo $payment->customer_id ?>">
                            <input type="submit"
                                   class="button button-secondary button-large paytium-cancel-subscription"
                                   id="paytium-cancel-subscription-button" value="Cancel subscription">
                        </div>
                        <div class="clear"></div>
                    </div>

					<?php
				}

			}

			?>
        </div>
    </div>
<?php




