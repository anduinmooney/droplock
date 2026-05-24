<?php
/**
 * Order quantity lookups (Lite).
 *
 * @package DropLock_Lite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DropLock_Order_Query {

	public static function get_customer_purchased_quantity( $product_id, $user_id = 0, $billing_email = '', $statuses = array() ) {
		$product_id    = (int) $product_id;
		$user_id       = (int) $user_id;
		$billing_email = is_string( $billing_email ) ? strtolower( trim( $billing_email ) ) : '';

		if ( $product_id <= 0 || ( $user_id <= 0 && '' === $billing_email ) ) {
			return 0;
		}

		if ( empty( $statuses ) ) {
			$statuses = DropLock_Helper::default_statuses();
		}

		$cache_key = 'droplock_pq_' . md5( wp_json_encode( array( 'p' => $product_id, 'u' => $user_id, 'e' => $billing_email, 's' => $statuses ) ) );
		$cached    = wp_cache_get( $cache_key, 'droplock' );
		if ( false !== $cached ) {
			return (int) $cached;
		}

		$order_ids = array();
		$base_args = array(
			'type'   => 'shop_order',
			'status' => $statuses,
			'limit'  => 200,
			'return' => 'ids',
			'paged'  => 1,
		);

		if ( $user_id > 0 ) {
			$order_ids = array_merge( $order_ids, self::paged_query( array_merge( $base_args, array( 'customer_id' => $user_id ) ) ) );
		}
		if ( '' !== $billing_email ) {
			$order_ids = array_merge( $order_ids, self::paged_query( array_merge( $base_args, array( 'billing_email' => $billing_email ) ) ) );
		}

		$order_ids = array_values( array_unique( array_map( 'intval', $order_ids ) ) );
		if ( empty( $order_ids ) ) {
			wp_cache_set( $cache_key, 0, 'droplock', MINUTE_IN_SECONDS );
			return 0;
		}

		$total = 0;
		foreach ( $order_ids as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order instanceof WC_Order ) {
				continue;
			}
			foreach ( $order->get_items( 'line_item' ) as $item ) {
				$item_product_id   = (int) $item->get_product_id();
				$item_variation_id = (int) $item->get_variation_id();

				$matches_parent = ( $item_product_id === $product_id );
				$matches_var    = false;
				if ( $item_variation_id ) {
					$var = wc_get_product( $item_variation_id );
					if ( $var && $var->is_type( 'variation' ) && (int) $var->get_parent_id() === $product_id ) {
						$matches_var = true;
					}
				}

				if ( $matches_parent || $matches_var ) {
					$qty = (int) $item->get_quantity();
					if ( $qty > 0 ) {
						$total += $qty;
					}
				}
			}
		}

		wp_cache_set( $cache_key, $total, 'droplock', MINUTE_IN_SECONDS );
		return (int) $total;
	}

	protected static function paged_query( $args ) {
		$all = array();
		$page = 1;
		do {
			$args['paged'] = $page;
			$ids = wc_get_orders( $args );
			if ( ! is_array( $ids ) || empty( $ids ) ) {
				break;
			}
			$all = array_merge( $all, $ids );
			$page++;
			if ( $page > 25 ) {
				break;
			}
		} while ( count( $ids ) === (int) $args['limit'] );

		return $all;
	}

	public static function get_cart_quantity_for_product( $product_id ) {
		$product_id = (int) $product_id;
		if ( $product_id <= 0 || ! WC()->cart ) {
			return 0;
		}

		$total = 0;
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$item_product_id   = isset( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : 0;
			$item_variation_id = isset( $cart_item['variation_id'] ) ? (int) $cart_item['variation_id'] : 0;
			$qty               = isset( $cart_item['quantity'] ) ? (int) $cart_item['quantity'] : 0;

			$matches = ( $item_product_id === $product_id );
			if ( ! $matches && $item_variation_id ) {
				$var = wc_get_product( $item_variation_id );
				if ( $var && $var->is_type( 'variation' ) && (int) $var->get_parent_id() === $product_id ) {
					$matches = true;
				}
			}
			if ( $matches ) {
				$total += $qty;
			}
		}
		return $total;
	}

	public static function get_cart_quantity_for_key( $cart_item_key ) {
		if ( ! WC()->cart ) {
			return 0;
		}
		$item = WC()->cart->get_cart_item( $cart_item_key );
		if ( ! $item ) {
			return 0;
		}
		return isset( $item['quantity'] ) ? (int) $item['quantity'] : 0;
	}
}
