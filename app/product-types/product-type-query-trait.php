<?php

namespace Voxel\Product_Types;

if ( ! defined('ABSPATH') ) {
	exit;
}

trait Product_Type_Query_Trait {

	private static $instances = [];

	/**
	 * Get a product type based on its key.
	 *
	 * @since 1.0
	 */
	public static function get( $key ) {
		if ( ! isset( static::$instances[ $key ] ) ) {
			$product_types = \Voxel\get( 'product_types', [] );
			if ( ! isset( $product_types[ $key ] ) ) {
				return null;
			}

			static::$instances[ $key ] = new static( (array) $product_types[ $key ] );
		}

		return static::$instances[ $key ];
	}


	static $virtual_instances = [];
	public static function register_virtual( array $config ): static {
		$product_type = new static( $config );
		static::$instances[ $product_type->get_key() ] = $product_type;
		static::$virtual_instances[ $product_type->get_key() ] = $product_type;

		return $product_type;
	}

	public static function all( bool $include_virtual = false ) {
		$keys = array_keys( \Voxel\get( 'product_types', [] ) );

		$product_types = [];
		foreach ( $keys as $key ) {
			$product_type = static::get( $key );
			$product_types[ $product_type->get_key() ] = $product_type;
		}

		if ( $include_virtual ) {
			foreach ( static::$virtual_instances as $product_type ) {
				$product_types[ $product_type->get_key() ] = $product_type;
			}
		}

		return $product_types;
	}

	public static function get_all( bool $include_virtual = false ) {
		return static::all( $include_virtual );
	}

	public static function from( array $config ): static {
		return new static( $config );
	}
}
