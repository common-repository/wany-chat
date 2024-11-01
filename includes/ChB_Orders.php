<?php

namespace ChatBot;


class ChB_Orders {

	public static function getMyOrders( ChB_User $user, $limit = 10 ) {
		$args = [
			'customer' => $user->getUserAccountEmail(),
			'limit'    => $limit,
			'orderby'  => 'date',
			'order'    => 'DESC'
		];

		$wc_orders = wc_get_orders( $args );
		$orders    = [];

		if ( $wc_orders ) {
			foreach ( $wc_orders as $wc_order ) {
				$orders[] = ChB_Order::getOrderSummary( $wc_order );
			}
		}

		return $orders;
	}

	public static function getOrders( $status, $limit = 10 ) {
		$args      = [ 'status' => $status, 'limit' => $limit ];
		$wc_orders = wc_get_orders( $args );
		$orders    = [];

		if ( $wc_orders ) {
			foreach ( $wc_orders as $wc_order ) {
				$orders[] = ChB_Order::getOrderSummary( $wc_order );
			}
		}

		return $orders;
	}

	public static function getStockReservedByOrders() {
		$statuses = array_unique( array_filter( [
			ChB_Order::getInitStatus(),
			ChB_Order::getToShipStatus(),
			ChB_Order::getShippedStatus()
		] ) );
		$reserve  = [];
		foreach ( $statuses as $status ) {
			$args      = [ 'status' => $status ];
			$wc_orders = wc_get_orders( $args );
			foreach ( $wc_orders as $wc_order ) {
				foreach ( $wc_order->get_items( [ 'line_item' ] ) as $item_id => $item ) {
					if ( $item instanceof \WC_Order_Item_Product ) {
						$product_id = $item->get_variation_id();
						if ( $product_id == 0 ) {
							$product_id = $item->get_product_id();
						}
						if ( isset( $reserve[ $product_id ] ) ) {
							$reserve[ $product_id ] += $item->get_quantity();
						} else {
							$reserve[ $product_id ] = $item->get_quantity();
						}
					}
				}
			}
		}

		return $reserve;
	}

	public static function getCustomerOrdersStats( ChB_User $user ) {

		$completed_statuses = ChB_Order::getStatuses4StatsCompleted();
		$statuses           = $completed_statuses;
		$statuses[]         = ChB_Order::ORDER_STATUS_CANCELLED;

		$args = [
			'status'      => $statuses,
			'limit'       => - 1,
			'customer_id' => $user->wp_user_id
		];

		$wc_orders    = wc_get_orders( $args );
		$orders_stats = [
			'sum_completed' => 0,
			'qty_completed' => 0,
			'sum_cancelled' => 0,
			'qty_cancelled' => 0,
		];

		if ( $wc_orders ) {
			foreach ( $wc_orders as $wc_order ) {
				$status = $wc_order->get_status();
				if ( in_array( $status, $statuses ) ) {
					$qty = $wc_order->get_item_count();
					$sum = $wc_order->get_total();
					if ( $status === ChB_Order::ORDER_STATUS_CANCELLED ) {
						$orders_stats['sum_cancelled'] += $sum;
						$orders_stats['qty_cancelled'] += $qty;
					} else {
						$orders_stats['sum_completed'] += $sum;
						$orders_stats['qty_completed'] += $qty;
					}
				}
			}
		}

		return $orders_stats;
	}
}