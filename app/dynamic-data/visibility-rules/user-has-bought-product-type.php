<?php

namespace Voxel\Dynamic_Data\Visibility_Rules;

if ( ! defined('ABSPATH') ) {
	exit;
}

class User_Has_Bought_Product_Type extends Base_Visibility_Rule {

	public function get_type(): string {
		return 'user:has_bought_product_type';
	}

	public function get_label(): string {
		return _x( 'User has bought product type', 'visibility rules', 'voxel-backend' );
	}

	protected function define_args(): void {
		$choices = [];
		foreach ( \Voxel\Product_Type::all(true) as $product_type ) {
			$choices[ $product_type->get_key() ] = $product_type->get_label();
		}

		$this->define_arg( 'product_type', [
			'type' => 'select',
			'label' => _x( 'Product type', 'visibility rules', 'voxel-backend' ),
			'choices' => $choices,
		] );
	}

	public function evaluate(): bool {
		$current_user = \Voxel\get_current_user();
		if ( ! $current_user ) {
			return false;
		}

		$product_type = $this->get_arg('product_type');
		if ( empty( $product_type ) || ! is_string( $product_type ) ) {
			return false;
		}

		return $current_user->has_bought_product_of_type( $product_type );
	}
}
