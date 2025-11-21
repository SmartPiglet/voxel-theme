<?php

namespace Voxel\Modules\Paid_Memberships\Controllers\Frontend;

use \Voxel\Modules\Paid_Memberships as Module;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Auth_Controller extends \Voxel\Controllers\Base_Controller {

	protected function hooks() {
		$this->filter( 'voxel/register/redirect_to', '@set_registration_redirect', 50, 4 );
		$this->filter( 'voxel/login/redirect_to', '@set_login_redirect', 50, 3 );
	}

	protected function set_registration_redirect( $redirect_to, $user, $role, $raw_redirect_url ) {
		try {
			if ( ! is_string( $raw_redirect_url ) || empty( $raw_redirect_url ) ) {
				throw new \Exception('unrelated_context', 50);
			}

			$query = parse_url( $raw_redirect_url, PHP_URL_QUERY );
			if ( empty( $query ) ) {
				throw new \Exception('unrelated_context', 51);
			}

			$params = [];
			parse_str( $query, $params );
			if ( ( $params['_ctx'] ?? null ) !== 'membership_plans' ) {
				throw new \Exception('unrelated_context', 52);
			}

			if ( ( $params['plan'] ?? '' ) === 'default' ) {
				$plan = Module\Plan::get_or_create_default_plan();
				$price = null;
			} else {
				$price = Module\Price::from_checkout_key( sanitize_text_field( $params['plan'] ?? '' ) );
				$plan = $price->plan;
			}

			if ( $plan->is_archived() ) {
				throw new \Exception( _x( 'This plan is no longer available.', 'pricing plans', 'voxel' ) );
			}

			if ( $plan->get_key() === 'default' ) {
				Module\update_user_plan( $user->get_id(), [
					'plan' => 'default',
					'type' => 'default',
				] );

				return $redirect_to;
			} else {
				$product = $price->get_product();
				$cart_item = \Voxel\Product_Types\Cart_Items\Cart_Item::create( [
					'product' => [
						'post_id' => $product->get_id(),
						'field_key' => 'voxel:membership_plan',
					],
				] );

				$cart = new \Voxel\Product_Types\Cart\Direct_Cart;
				$cart->add_item( $cart_item );

				$order = \Voxel\Product_Types\Orders\Order::create_from_cart( $cart, [
					'meta' => [
						'redirect_to' => $redirect_to,
					],
				] );

				$payment_method = $order->get_payment_method();
				if ( $payment_method === null ) {
					throw new \Exception( __( 'Could not process payment', 'voxel' ), 101 );
				}

				$response = $payment_method->process_payment();
				if ( isset( $response['redirect_url'] ) ) {
					return $response['redirect_url'];
				}

				throw new \Exception( __( 'Could not process payment', 'voxel' ), 101 );
			}
		} catch ( \Exception $e ) {
			if ( $role->has_plans_enabled() && $role->config( 'registration.show_plans_on_signup', true ) ) {
				$plans_page = get_permalink( $role->get_pricing_page_id() ) ?: home_url('/');
				return add_query_arg( [
					'redirect_to' => urlencode( $redirect_to ),
				], $plans_page );
			}

			return $redirect_to;
		}
	}

	protected function set_login_redirect( $redirect_to, $user, $raw_redirect_url ) {
		try {
			if ( ! is_string( $raw_redirect_url ) || empty( $raw_redirect_url ) ) {
				throw new \Exception('unrelated_context', 50);
			}

			$query = parse_url( $raw_redirect_url, PHP_URL_QUERY );
			if ( empty( $query ) ) {
				throw new \Exception('unrelated_context', 51);
			}

			$params = [];
			parse_str( $query, $params );
			if ( ( $params['_ctx'] ?? null ) !== 'membership_plans' ) {
				throw new \Exception('unrelated_context', 52);
			}

			// Check if user is on the default free plan
			$membership = $user->get_membership();
			if ( ! (
				$membership->get_type() === 'default'
				&& $membership->get_selected_plan()->get_key() === 'default'
			) ) {
				// User is not on default plan, do nothing
				return $redirect_to;
			}

			if ( ( $params['plan'] ?? '' ) === 'default' ) {
				$plan = Module\Plan::get_or_create_default_plan();
				$price = null;
			} else {
				$price = Module\Price::from_checkout_key( sanitize_text_field( $params['plan'] ?? '' ) );
				$plan = $price->plan;
			}

			if ( $plan->is_archived() ) {
				throw new \Exception( _x( 'This plan is no longer available.', 'pricing plans', 'voxel' ) );
			}

			if ( $plan->get_key() === 'default' ) {
				// Mark the free plan as selected
				if ( $membership->is_initial_state() ) {
					Module\update_user_plan( $user->get_id(), [
						'plan' => 'default',
						'type' => 'default',
					] );
				}

				// User is already on default plan, no need to proceed to checkout
				return $redirect_to;
			} else {
				// Proceed to checkout for paid plans
				$product = $price->get_product();
				$cart_item = \Voxel\Product_Types\Cart_Items\Cart_Item::create( [
					'product' => [
						'post_id' => $product->get_id(),
						'field_key' => 'voxel:membership_plan',
					],
				] );

				$cart = new \Voxel\Product_Types\Cart\Direct_Cart;
				$cart->add_item( $cart_item );

				$order = \Voxel\Product_Types\Orders\Order::create_from_cart( $cart, [
					'meta' => [
						'redirect_to' => $redirect_to,
					],
				] );

				$payment_method = $order->get_payment_method();
				if ( $payment_method === null ) {
					throw new \Exception( __( 'Could not process payment', 'voxel' ), 101 );
				}

				$response = $payment_method->process_payment();
				if ( isset( $response['redirect_url'] ) ) {
					return $response['redirect_url'];
				}

				throw new \Exception( __( 'Could not process payment', 'voxel' ), 101 );
			}
		} catch ( \Exception $e ) {
			return $redirect_to;
		}
	}
}
