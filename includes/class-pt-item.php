<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


/**
 * A instance of PT_Item represents a purchased item.
 *
 * @since 1.5.0
 */
class PT_Item {

	private $payment;
	private $item_index;

	private $label;
	private $value;
	private $type;
	private $amount;
	private $tax_percentage;
	private $tax_amount;
	private $total_amount;


	/**
	 * Constructor.
	 *
	 * @since 1.5.0
	 *
	 * @param PT_Payment $payment
	 * @param $item_index
	 */
	public function __construct( PT_Payment $payment, $item_index ) {
		$this->set_payment( $payment );
		$this->set_item_index( $item_index );
	}

	/**
	 * @return PT_Payment
	 */
	public function get_payment() {
		return $this->payment;
	}

	/**
	 * @return mixed
	 */
	public function get_item_index() {
		return $this->item_index;
	}

	/**
	 * @param int $item_index
	 */
	public function set_item_index( $item_index ) {
		$this->item_index = absint( $item_index );
	}

	/**
	 * Get formatted meta key.
	 *
	 * @param string $name Un-formatted meta key.
	 * @return string Formatted meta key.
	 */
	public function get_meta_key( $name ) {
		return '_item-' . $this->get_item_index() . '-' . $name;
	}

	/**
	 * Get a meta value.
	 *
	 * @param string $name Name the meta to get.
	 * @return mixed Met value.
	 */
	public function get_meta( $name ) {
		return get_post_meta( $this->get_payment()->id, $this->get_meta_key( $name ), true );
	}

	/**
	 * @param PT_Payment $payment
	 */
	public function set_payment( PT_Payment $payment ) {
		$this->payment = $payment;
	}

	/**
	 * @return mixed
	 */
	public function get_label() {
		if ( is_null( $this->label ) ) {
			$label = $this->get_meta( 'label' );
			$this->set_label( $label );
		}

		return $this->label;
	}

	/**
	 * @param mixed $label
	 * @return $this
	 */
	public function set_label( $label ) {
		$this->label = $label;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function get_value() {
		if ( is_null( $this->value ) ) {
			$value = $this->get_meta( 'value' );
			$this->set_value( $value );
		}

		return $this->value;
	}

	/**
	 * @param mixed $value
	 * @return $this
	 */
	public function set_value( $value ) {
		$this->value = $value;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function get_type() {
		if ( is_null( $this->type ) ) {
			$type = $this->get_meta( 'type' );
			$this->set_type( $type );
		}

		return $this->type;
	}

	/**
	 * @param mixed $type
	 * @return $this
	 */
	public function set_type( $type ) {
		$this->type = $type;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function get_amount() {
		if ( is_null( $this->amount ) ) {
			$amount = $this->get_meta( 'amount' );
			$this->set_amount( $amount );
		}

		return $this->amount;
	}

	/**
	 * @param mixed $amount
	 * @return $this
	 */
	public function set_amount( $amount ) {
		$this->amount = $amount;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function get_tax_percentage() {
		if ( is_null( $this->tax_percentage ) ) {
			$tax_percentage = $this->get_meta( 'tax-percentage' );
			$this->set_tax_percentage( $tax_percentage );
		}

		return $this->tax_percentage;
	}

	/**
	 * @param mixed $tax_percentage
	 * @return $this
	 */
	public function set_tax_percentage( $tax_percentage ) {
		$this->tax_percentage = $tax_percentage;
		return $this;
	}

	/**
	 * @param bool|int $item_index
	 *
	 * @return mixed
	 */
	public function get_tax_amount($item_index = false) {

		if ( is_null( $this->tax_amount ) ) {
			$tax_amount = $this->get_meta( 'tax-amount' );
			$this->set_tax_amount( $tax_amount );
		}

		return (float) $this->tax_amount;
	}

	/**
	 * @param mixed $tax_amount
	 * @return $this
	 */
	public function set_tax_amount( $tax_amount ) {
		$this->tax_amount = $tax_amount;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function get_total_amount() {
		return $this->get_meta('zero-tax') ? $this->get_tax_amount() : $this->get_amount() + $this->get_tax_amount();
	}

	/**
	 * @param mixed $total_amount
	 * @return $this
	 */
	public function set_total_amount( $total_amount ) {
		$this->total_amount = $total_amount;
		return $this;
	}

	/**
	 * @return number
	 */
	public function get_quantity() {
		return $this->get_meta( 'quantity' );
	}

}