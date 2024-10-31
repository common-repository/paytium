<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * @var PT_Payment $payment
 */

?><div class="payment-items-meta-box">
	<table class="widefat payment-items-table pt-items-nonadmin" style="width: 100%">
        <thead>
        <tr>
            <th><?php _e( 'Item', 'paytium' ); ?></th>
            <th><?php _e( 'Amount', 'paytium' ); ?></th>
            <th><?php _e( 'Quantity', 'paytium' ); ?></th><?php
			if ( $payment->get_tax_total() ) : ?>
                <th><?php _e( 'Total without tax', 'paytium' ); ?></th>
                <th><?php _e( 'Taxes', 'paytium' ); ?></th>
			<?php endif; ?>
            <th><?php _e( 'Total', 'paytium' ); ?></th>
        </tr>
        </thead>

        <tbody><?php
		foreach ( $payment->get_items() as $item ) :
			if ($item->get_amount() != 0) :
				$amount = $item->get_amount();
				$quantity = $item->get_quantity();
				$amount = $item->get_quantity() > 1 ? $amount/$quantity : $amount;
				$amount = esc_html(pt_float_amount_to_currency( $amount, $payment->currency ));
				?><tr>
                <td style="width: 40%; padding-right: 30px;"><?php echo esc_html($item->get_label()) . ' ' .  esc_html($item->get_value()); ?></td>
                <td><?php echo $amount; ?></td>
                <td style=""><?php echo $item->get_quantity(); ?></td>
				<?php
				if ( $payment->get_tax_total() ) :
					if ( $item->get_tax_amount() ) :?>
                        <td><?php echo esc_html(pt_float_amount_to_currency( $item->get_amount(), $payment->currency )); ?></td>
                        <td><?php echo esc_html(pt_float_amount_to_currency( $item->get_tax_amount(), $payment->currency )); ?> <small class="muted">(<?php echo esc_html(absint( $item->get_tax_percentage() )); ?>%)</small></td>
					<?php
					else : ?>
                        <td></td>
					<?php endif;
				endif; ?>
                <td><?php echo esc_html(pt_float_amount_to_currency( $item->get_total_amount(), $payment->currency )); ?></td>
                </tr><?php
			endif;
		endforeach;
		if ($payment->is_discount()) : ?>
            <tr>
                <td>Discount (<?php echo $payment->get_discount_value() ?>)</td>
                <td></td>
                <td></td>
				<?php if ( $payment->get_tax_total() ) :?>
                    <td></td>
                    <td></td>
                <?php endif; ?>
                <td style="color:darkred;text-align:left"><?php echo esc_html(pt_float_amount_to_currency( -(float)$payment->get_discount_amount(), $payment->currency )); ?></td>
            </tr>
		<?php endif; ?>
        </tbody>

	</table>
</div>