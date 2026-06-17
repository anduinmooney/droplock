<?php
/**
 * Helper utilities for DropLock (Lite).
 *
 * @package DropLock_Lite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DropLock_Helper {

	const META_ENABLED       = '_droplock_enabled';
	const META_MAX_QTY       = '_droplock_max_qty';
	const META_LIMIT_MESSAGE = '_droplock_limit_message';
	const META_BADGE_TEXT    = '_droplock_badge_text';
	const META_SHOW_BADGE    = '_droplock_show_badge';

	/**
	 * Lite hard-codes counted statuses (Pro makes them configurable).
	 *
	 * @return string[]
	 */
	public static function default_statuses() {
		return array( 'completed', 'processing', 'on-hold' );
	}

	public static function default_limit_message() {
		return __(
			'You have already reached the purchase limit for {product_name}. This limited product is restricted to {limit} per customer.',
			'droplock'
		);
	}

	public static function default_badge_text() {
		return __( 'Limit {limit} per customer', 'droplock' );
	}

	public static function is_enabled( $product_id ) {
		return 'yes' === get_post_meta( (int) $product_id, self::META_ENABLED, true );
	}

	public static function get_max_qty( $product_id ) {
		$raw = get_post_meta( (int) $product_id, self::META_MAX_QTY, true );
		$qty = absint( $raw );
		return $qty > 0 ? $qty : 1;
	}

	/* -------------------------------------------------------------------
	 * Free edition limit: DropLock Free protects ONE product.
	 * The "free slot" is the lowest-ID product with DropLock enabled.
	 * Only that product is actively enforced; enabling more is a Pro feature.
	 * ------------------------------------------------------------------- */

	/**
	 * IDs of all products with DropLock enabled.
	 *
	 * @return int[]
	 */
	public static function enabled_product_ids() {
		$ids = get_posts(
			array(
				'post_type'      => array( 'product' ),
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => 50,
				'meta_key'       => self::META_ENABLED,
				'meta_value'     => 'yes',
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);
		return is_array( $ids ) ? array_map( 'intval', $ids ) : array();
	}

	/**
	 * The single product the free edition actively protects (lowest enabled ID),
	 * or 0 if none.
	 */
	public static function free_slot_product_id() {
		$ids = self::enabled_product_ids();
		return $ids ? (int) $ids[0] : 0;
	}

	/**
	 * Is this product the active free slot (and therefore enforced)?
	 */
	public static function is_free_slot( $product_id ) {
		$product_id = (int) $product_id;
		return $product_id > 0 && $product_id === self::free_slot_product_id();
	}

	/**
	 * Whether DropLock actively enforces a limit on this product.
	 * In Free this is true only for the single free-slot product.
	 */
	public static function is_protected( $product_id ) {
		return self::is_enabled( $product_id ) && self::is_free_slot( $product_id );
	}

	/**
	 * Lite uses defaults only; ignores any saved status meta.
	 */
	public static function get_order_statuses( $product_id ) {
		return self::default_statuses();
	}

	/**
	 * Free always uses the default message. Custom messages are a Pro feature,
	 * so any previously-saved custom value is ignored here.
	 */
	public static function get_limit_message( $product_id ) {
		return self::default_limit_message();
	}

	/**
	 * Free always uses the default badge text. Custom badge text is a Pro feature.
	 */
	public static function get_badge_text( $product_id ) {
		return self::default_badge_text();
	}

	public static function should_show_badge( $product_id ) {
		$val = get_post_meta( (int) $product_id, self::META_SHOW_BADGE, true );
		if ( '' === $val ) {
			return true;
		}
		return 'yes' === $val;
	}

	public static function format_message( $template, $vars = array() ) {
		$defaults = array(
			'product_name'  => '',
			'limit'         => '',
			'purchased_qty' => '',
			'cart_qty'      => '',
			'remaining_qty' => '',
		);
		$vars = array_merge( $defaults, $vars );

		$replacements = array();
		foreach ( $vars as $key => $val ) {
			$replacements[ '{' . $key . '}' ] = (string) $val;
		}
		return strtr( $template, $replacements );
	}

	public static function current_user_can_bypass() {
		if ( current_user_can( 'manage_woocommerce' ) ) {
			return true;
		}
		return (bool) apply_filters( 'droplock_bypass_validation', false );
	}

	public static function get_effective_product_id( $product_id ) {
		$product = wc_get_product( $product_id );
		if ( $product && $product->is_type( 'variation' ) ) {
			return $product->get_parent_id();
		}
		return (int) $product_id;
	}
}
