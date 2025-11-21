<?php

namespace Voxel\Vendor\OTPHP {

	/**
	 * Bridge function for OTPHP to use the scoped Symfony deprecation function
	 */
	if ( ! function_exists( '\Voxel\Vendor\OTPHP\trigger_deprecation' ) ) {
		function trigger_deprecation( string $package, string $version, string $message, mixed ...$args ): void {
			@\trigger_error(
				( $package || $version ? "Since {$package} {$version}: " : '' ) .
				( $args ? \vsprintf( $message, $args ) : $message ),
				\E_USER_DEPRECATED
			);
		}
	}
}
