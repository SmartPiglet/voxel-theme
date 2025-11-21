<?php

namespace Voxel\Modules\Paid_Listings\Controllers\Frontend;

use \Voxel\Modules\Paid_Listings as Module;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Auth_Controller extends \Voxel\Controllers\Base_Controller {

	protected function hooks() {
		$this->filter( 'voxel/register/redirect_to', '@set_registration_redirect', 100, 4 );
		$this->filter( 'voxel/login/redirect_to', '@set_login_redirect', 100, 3 );
	}

	protected function set_registration_redirect( $redirect_to, $user, $role, $raw_redirect_url ) {
		if ( ! is_string( $raw_redirect_url ) || empty( $raw_redirect_url ) ) {
			return $redirect_to;
		}

		$query = parse_url( $raw_redirect_url, PHP_URL_QUERY );
		if ( empty( $query ) ) {
			return $redirect_to;
		}

		$params = [];
		parse_str( $query, $params );
		if ( ( $params['_ctx'] ?? null ) !== 'listing_plans' ) {
			return $redirect_to;
		}

		try {
			$process = \Voxel\from_list( $params['process'] ?? null, [ 'new', 'relist', 'claim', 'switch' ], null );
			if ( $process === 'new' ) {
				$post_type = \Voxel\Post_Type::get( $params['item_type'] ?? null );
				if ( $post_type === null ) {
					throw new \Exception( _x( 'This plan is not available.', 'pricing plans', 'voxel' ), 70 );
				}

				$plan = Module\Listing_Plan::get( sanitize_text_field( $params['plan'] ?? '' ) );
				if ( $plan === null || ! $plan->supports_post_type( $post_type ) ) {
					throw new \Exception( _x( 'This plan is not available.', 'pricing plans', 'voxel' ), 80 );
				}

				$cart_item = \Voxel\Cart_Item::create( [
					'product' => [
						'post_id' => $plan->get_product_id(),
						'field_key' => 'voxel:listing_plan',
					],
					'custom_data' => [
						'checkout_context' => [
							'process' => 'new',
							'post_type' => $post_type->get_key(),
						],
					],
				] );

				$cart = new \Voxel\Product_Types\Cart\Direct_Cart;
				$cart->add_item( $cart_item );

				$order = \Voxel\Order::create_from_cart( $cart );

				$payment_method = $order->get_payment_method();
				if ( $payment_method === null ) {
					throw new \Exception( __( 'Could not process payment', 'voxel' ), 101 );
				}

				$response = $payment_method->process_payment();
				if ( isset( $response['redirect_url'] ) ) {
					return $response['redirect_url'];
				}

				throw new \Exception( __( 'Could not process payment', 'voxel' ), 101 );
			} elseif ( in_array( $process, [ 'relist', 'switch' ] ) ) {
				throw new \Exception( __( 'Could not process payment', 'voxel' ), 101 );
			} elseif ( $process === 'claim' ) {
				$post = \Voxel\Post::get( $params['post_id'] ?? null );
				if ( ! ( $post && \Voxel\Modules\Claim_Listings\is_claimable( $post ) ) ) {
					throw new \Exception( _x( 'This item cannot be claimed.', 'pricing plans', 'voxel' ), 70 );
				}

				$plan = Module\Listing_Plan::get( sanitize_text_field( $params['plan'] ?? '' ) );
				if ( $plan === null || ! $plan->supports_post_type( $post->post_type ) ) {
					throw new \Exception( _x( 'This plan is not available.', 'pricing plans', 'voxel' ), 80 );
				}

				$cart_item = \Voxel\Cart_Item::create( [
					'product' => [
						'post_id' => $plan->get_product_id(),
						'field_key' => 'voxel:listing_plan',
					],
					'custom_data' => [
						'checkout_context' => [
							'process' => 'claim',
							'post_id' => $post->get_id(),
						],
					],
				] );

				$checkout_link = get_permalink( \Voxel\get( 'templates.checkout' ) ) ?: home_url('/');
				$checkout_link = add_query_arg( [
					'checkout_item' => $cart_item->get_key(),
					'_item' => base64_encode( wp_json_encode( $cart_item->get_value() ) ),
				], $checkout_link );

				return $checkout_link;
			} else {
				$plan = Module\Listing_Plan::get( sanitize_text_field( $params['plan'] ?? '' ) );
				if ( $plan === null ) {
					throw new \Exception( _x( 'This plan is not available.', 'pricing plans', 'voxel' ), 150 );
				}

				$cart_item = \Voxel\Cart_Item::create( [
					'product' => [
						'post_id' => $plan->get_product_id(),
						'field_key' => 'voxel:listing_plan',
					],
				] );

				$cart = new \Voxel\Product_Types\Cart\Direct_Cart;
				$cart->add_item( $cart_item );

				$order = \Voxel\Order::create_from_cart( $cart );

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

	protected function set_login_redirect( $redirect_to, $user, $raw_redirect_url ) {
		if ( ! is_string( $raw_redirect_url ) || empty( $raw_redirect_url ) ) {
			return $redirect_to;
		}

		$query = parse_url( $raw_redirect_url, PHP_URL_QUERY );
		if ( empty( $query ) ) {
			return $redirect_to;
		}

		$params = [];
		parse_str( $query, $params );
		if ( ( $params['_ctx'] ?? null ) !== 'listing_plans' ) {
			return $redirect_to;
		}

		try {
			$process = \Voxel\from_list( $params['process'] ?? null, [ 'new', 'relist', 'claim', 'switch' ], null );

			if ( $process === 'new' ) {
				$post_type = \Voxel\Post_Type::get( $params['item_type'] ?? null );
				if ( $post_type === null ) {
					throw new \Exception( _x( 'This plan is not available.', 'pricing plans', 'voxel' ), 70 );
				}

				$plan = Module\Listing_Plan::get( sanitize_text_field( $params['plan'] ?? '' ) );
				if ( $plan === null || ! $plan->supports_post_type( $post_type ) ) {
					throw new \Exception( _x( 'This plan is not available.', 'pricing plans', 'voxel' ), 80 );
				}

				// Check if user has available packages for this post type
				$available_packages = Module\get_available_packages( $user, $post_type );
				if ( ! empty( $available_packages ) ) {
					// Use the first available package
					$package = $available_packages[0];
					$draft = Module\get_or_create_draft( $package, $post_type, $user );
					if ( $draft !== null ) {
						return $draft->get_edit_link();
					}
				}

				// No available packages, proceed to checkout
				$cart_item = \Voxel\Cart_Item::create( [
					'product' => [
						'post_id' => $plan->get_product_id(),
						'field_key' => 'voxel:listing_plan',
					],
					'custom_data' => [
						'checkout_context' => [
							'process' => 'new',
							'post_type' => $post_type->get_key(),
						],
					],
				] );

				$cart = new \Voxel\Product_Types\Cart\Direct_Cart;
				$cart->add_item( $cart_item );

				$order = \Voxel\Order::create_from_cart( $cart );

				$payment_method = $order->get_payment_method();
				if ( $payment_method === null ) {
					throw new \Exception( __( 'Could not process payment', 'voxel' ), 101 );
				}

				$response = $payment_method->process_payment();
				if ( isset( $response['redirect_url'] ) ) {
					return $response['redirect_url'];
				}

				throw new \Exception( __( 'Could not process payment', 'voxel' ), 101 );
			} elseif ( in_array( $process, [ 'relist', 'switch' ] ) ) {
				throw new \Exception( __( 'Could not process payment', 'voxel' ), 101 );
			} elseif ( $process === 'claim' ) {
				$post = \Voxel\Post::get( $params['post_id'] ?? null );
				if ( ! ( $post && \Voxel\Modules\Claim_Listings\is_claimable( $post ) ) ) {
					throw new \Exception( _x( 'This item cannot be claimed.', 'pricing plans', 'voxel' ), 70 );
				}

				$plan = Module\Listing_Plan::get( sanitize_text_field( $params['plan'] ?? '' ) );
				if ( $plan === null || ! $plan->supports_post_type( $post->post_type ) ) {
					throw new \Exception( _x( 'This plan is not available.', 'pricing plans', 'voxel' ), 80 );
				}

				// Check if user has available packages for this post type
				$available_packages = Module\get_available_packages( $user, $post->post_type );
				if ( ! empty( $available_packages ) ) {
					// Use the first available package
					$package = $available_packages[0];
					$cart_item = \Voxel\Cart_Item::create( [
						'product' => [
							'post_id' => \Voxel\Modules\Claim_Listings\get_product()->get_id(),
							'field_key' => 'voxel:claim_request',
						],
						'custom_data' => [
							'voxel:claim_request' => [
								'post_id' => $post->get_id(),
								'package_id' => $package->get_id(),
							],
						],
					] );

					$checkout_link = get_permalink( \Voxel\get( 'templates.checkout' ) ) ?: home_url('/');
					$checkout_link = add_query_arg( [
						'checkout_item' => $cart_item->get_key(),
						'_item' => base64_encode( wp_json_encode( $cart_item->get_value() ) ),
					], $checkout_link );

					return $checkout_link;
				}

				// No available packages, proceed to checkout
				$cart_item = \Voxel\Cart_Item::create( [
					'product' => [
						'post_id' => $plan->get_product_id(),
						'field_key' => 'voxel:listing_plan',
					],
					'custom_data' => [
						'checkout_context' => [
							'process' => 'claim',
							'post_id' => $post->get_id(),
						],
					],
				] );

				$checkout_link = get_permalink( \Voxel\get( 'templates.checkout' ) ) ?: home_url('/');
				$checkout_link = add_query_arg( [
					'checkout_item' => $cart_item->get_key(),
					'_item' => base64_encode( wp_json_encode( $cart_item->get_value() ) ),
				], $checkout_link );

				return $checkout_link;
			} else {
				// Always proceed to checkout for other processes
				$plan = Module\Listing_Plan::get( sanitize_text_field( $params['plan'] ?? '' ) );
				if ( $plan === null ) {
					throw new \Exception( _x( 'This plan is not available.', 'pricing plans', 'voxel' ), 150 );
				}

				$cart_item = \Voxel\Cart_Item::create( [
					'product' => [
						'post_id' => $plan->get_product_id(),
						'field_key' => 'voxel:listing_plan',
					],
				] );

				$cart = new \Voxel\Product_Types\Cart\Direct_Cart;
				$cart->add_item( $cart_item );

				$order = \Voxel\Order::create_from_cart( $cart );

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
