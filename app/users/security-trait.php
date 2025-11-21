<?php

namespace Voxel\Users;

if ( ! defined('ABSPATH') ) {
	exit;
}

trait Security_Trait {

	public function send_recovery_code() {
		$code = \Voxel\random_string(16);
		$subject = _x( 'Account recovery', 'auth', 'voxel' );
		$message = sprintf( _x( 'Your recovery code is %s', 'auth', 'voxel' ), $code );

		wp_mail( $this->get_email(), $subject, \Voxel\email_template( $message ), [
			'Content-type: text/html;',
		] );

		// give user 2 minutes to enter correct code
		update_user_meta( $this->get_id(), 'voxel:recovery', wp_slash( wp_json_encode( [
			'code' => password_hash( $code, PASSWORD_DEFAULT ),
			'expires' => time() + ( 2 * MINUTE_IN_SECONDS ),
		] ) ) );
	}

	public function verify_recovery_code( $code ) {
		$recovery = json_decode( get_user_meta( $this->get_id(), 'voxel:recovery', true ), ARRAY_A );
		if ( ! is_array( $recovery ) || empty( $recovery['code'] ) || empty( $recovery['expires'] ) ) {
			throw new \Exception( __( 'Invalid request.', 'voxel' ) );
		}

		if ( $recovery['expires'] < time() ) {
			throw new \Exception( _x( 'Recovery session has expired.', 'auth', 'voxel' ) );
		}

		if ( ! password_verify( $code, $recovery['code'] ) ) {
			throw new \Exception( _x( 'Code is not correct.', 'auth', 'voxel' ) );
		}
	}

	public function send_email_update_code( $email ) {
		$code = \Voxel\random_string(5);
		$subject = _x( 'Update email address', 'auth', 'voxel' );
		$message = sprintf( _x( 'Your confirmation code is %s', 'auth', 'voxel' ), $code );

		wp_mail( $email, $subject, \Voxel\email_template( $message ), [
			'Content-type: text/html;',
		] );

		// give user 2 minutes to enter correct code
		update_user_meta( $this->get_id(), 'voxel:email_update', wp_slash( wp_json_encode( [
			'code' => password_hash( $code, PASSWORD_DEFAULT ),
			'expires' => time() + ( 5 * MINUTE_IN_SECONDS ),
			'email' => $email,
		] ) ) );
	}

	public function verify_email_update_code( $code ) {
		$update = json_decode( get_user_meta( $this->get_id(), 'voxel:email_update', true ), ARRAY_A );
		if ( ! is_array( $update ) || empty( $update['code'] ) || empty( $update['expires'] ) ) {
			throw new \Exception( __( 'Invalid request.', 'voxel' ) );
		}

		if ( $update['expires'] < time() ) {
			throw new \Exception( _x( 'Code has expired.', 'auth', 'voxel' ) );
		}

		if ( ! password_verify( $code, $update['code'] ) ) {
			throw new \Exception( _x( 'Code is not correct.', 'auth', 'voxel' ) );
		}

		return $update['email'] ?? null;
	}

	/* Two-Factor Authentication Methods */

	public function is_2fa_enabled() {
		return (bool) get_user_meta( $this->get_id(), 'voxel:2fa_enabled', true );
	}

	public function get_2fa_secret() {
		return get_user_meta( $this->get_id(), 'voxel:2fa_secret', true );
	}

	public function generate_2fa_secret() {
		$totp = \Voxel\Vendor\OTPHP\TOTP::generate();
		$secret = $totp->getSecret();

		update_user_meta( $this->get_id(), 'voxel:2fa_secret_temp', $secret );

		return $secret;
	}

	public function get_2fa_qr_code( $secret = null ) {
		if ( ! $secret ) {
			$secret = $this->get_2fa_secret();
		}

		if ( ! $secret ) {
			throw new \Exception( _x( 'No 2FA secret found.', 'auth', 'voxel' ) );
		}

		$totp = \Voxel\Vendor\OTPHP\TOTP::createFromSecret( $secret );
		$totp->setLabel( $this->get_email() );
		$totp->setIssuer( get_bloginfo( 'name' ) );

		// Generate QR code using Voxel's built-in QR code generator
		return \Voxel\qrcode( $totp->getProvisioningUri() );
	}

	public function verify_2fa_code( $code, $secret = null ) {
		if ( ! $secret ) {
			$secret = $this->get_2fa_secret();
		}

		if ( ! $secret ) {
			throw new \Exception( _x( 'Two-factor authentication is not enabled.', 'auth', 'voxel' ) );
		}

		// Rate limiting: check failed attempts
		$attempts_key = 'voxel:2fa_attempts';
		$attempts = json_decode( get_user_meta( $this->get_id(), $attempts_key, true ), true );

		if ( is_array( $attempts ) && isset( $attempts['count'], $attempts['time'] ) ) {
			// Reset if more than 5 minutes passed
			if ( time() - $attempts['time'] > 300 ) {
				delete_user_meta( $this->get_id(), $attempts_key );
			} elseif ( $attempts['count'] >= 5 ) {
				throw new \Exception( _x( 'Too many failed attempts. Please try again in 5 minutes.', 'auth', 'voxel' ) );
			}
		}

		$totp = \Voxel\Vendor\OTPHP\TOTP::createFromSecret( $secret );
		$totp->setLabel( $this->get_email() );
		$totp->setIssuer( get_bloginfo( 'name' ) );

		// Allow 1 period before and after for clock drift (90 second window)
		$verified = $totp->verify( $code, null, 1 );

		if ( ! $verified ) {
			// Increment failed attempts
			$current = is_array( $attempts ) ? $attempts : [ 'count' => 0, 'time' => time() ];
			$current['count']++;
			$current['time'] = time();
			update_user_meta( $this->get_id(), $attempts_key, wp_json_encode( $current ) );

			throw new \Exception( _x( 'Invalid authentication code. Please try again.', 'auth', 'voxel' ) );
		}

		// Clear failed attempts on success
		delete_user_meta( $this->get_id(), $attempts_key );

		return true;
	}

	public function enable_2fa( $code ) {
		$secret = get_user_meta( $this->get_id(), 'voxel:2fa_secret_temp', true );

		if ( ! $secret ) {
			throw new \Exception( _x( 'No 2FA setup in progress.', 'auth', 'voxel' ) );
		}

		// Verify the code with the temporary secret
		$this->verify_2fa_code( $code, $secret );

		// Generate backup codes
		$backup_codes = $this->generate_backup_codes();

		// Move secret from temp to permanent
		update_user_meta( $this->get_id(), 'voxel:2fa_secret', $secret );
		update_user_meta( $this->get_id(), 'voxel:2fa_enabled', true );
		delete_user_meta( $this->get_id(), 'voxel:2fa_secret_temp' );

		// Send email notification
		$subject = sprintf( _x( '%s - Two-Factor Authentication Enabled', 'auth', 'voxel' ), get_bloginfo( 'name' ) );
		$message = sprintf(
			_x( 'Two-factor authentication has been successfully enabled on your account.<br><br>From now on, you will need to enter a code from your authenticator app when logging in.<br><br>If you did not enable this, please contact support immediately.', 'auth', 'voxel' )
		);
		wp_mail( $this->get_email(), $subject, \Voxel\email_template( $message ), [
			'Content-type: text/html;',
		] );

		do_action( 'voxel/user/2fa-enabled', $this->get_id() );

		return [
			'backup_codes' => $backup_codes,
		];
	}

	public function disable_2fa( $password ) {
		// Verify password for security
		$wp_user = wp_authenticate( $this->get_username(), $password );
		if ( is_wp_error( $wp_user ) ) {
			throw new \Exception( _x( 'Invalid password.', 'auth', 'voxel' ) );
		}

		$this->disable_2fa_internal();

		// Send email notification
		$subject = sprintf( _x( '%s - Two-Factor Authentication Disabled', 'auth', 'voxel' ), get_bloginfo( 'name' ) );
		$message = sprintf(
			_x( 'Two-factor authentication has been disabled on your account.<br><br>You will now only need your password to log in.<br><br>If you did not disable this, please secure your account immediately and contact support.', 'auth', 'voxel' )
		);
		wp_mail( $this->get_email(), $subject, \Voxel\email_template( $message ), [
			'Content-type: text/html;',
		] );

		do_action( 'voxel/user/2fa-disabled', $this->get_id() );
	}

	public function disable_2fa_as_admin() {
		// This method is only for administrators to disable 2FA when user lost access
		if ( ! current_user_can( 'manage_options' ) ) {
			throw new \Exception( _x( 'Insufficient permissions.', 'auth', 'voxel' ) );
		}

		if ( ! $this->is_2fa_enabled() ) {
			throw new \Exception( _x( 'Two-factor authentication is not enabled for this user.', 'auth', 'voxel' ) );
		}

		$this->disable_2fa_internal();

		// Send email notification
		$subject = sprintf( _x( '%s - Two-Factor Authentication Disabled by Administrator', 'auth', 'voxel' ), get_bloginfo( 'name' ) );
		$message = sprintf(
			_x( 'Two-factor authentication has been disabled on your account by an administrator.<br><br>You will now only need your password to log in.<br><br>If you did not request this change, please contact support immediately.', 'auth', 'voxel' )
		);
		wp_mail( $this->get_email(), $subject, \Voxel\email_template( $message ), [
			'Content-type: text/html;',
		] );

		do_action( 'voxel/user/2fa-disabled', $this->get_id() );
		do_action( 'voxel/user/2fa-disabled-by-admin', $this->get_id(), get_current_user_id() );
	}

	protected function disable_2fa_internal() {
		// Remove all 2FA related meta
		delete_user_meta( $this->get_id(), 'voxel:2fa_secret' );
		delete_user_meta( $this->get_id(), 'voxel:2fa_secret_temp' );
		delete_user_meta( $this->get_id(), 'voxel:2fa_enabled' );
		delete_user_meta( $this->get_id(), 'voxel:2fa_backup_codes' );
		delete_user_meta( $this->get_id(), 'voxel:2fa_attempts' );
		delete_user_meta( $this->get_id(), 'voxel:2fa_login_session' );
		delete_user_meta( $this->get_id(), 'voxel:2fa_trusted_devices' );
	}

	public function generate_backup_codes() {
		$codes = [];
		for ( $i = 0; $i < 10; $i++ ) {
			$code = strtoupper( \Voxel\random_string( 8 ) );
			$codes[] = $code;
		}

		// Store hashed versions
		$hashed_codes = array_map( function( $code ) {
			return password_hash( $code, PASSWORD_DEFAULT );
		}, $codes );

		update_user_meta( $this->get_id(), 'voxel:2fa_backup_codes', wp_json_encode( $hashed_codes ) );

		return $codes; // Return plain codes to show user once
	}

	public function verify_backup_code( $code ) {
		$stored_codes = json_decode( get_user_meta( $this->get_id(), 'voxel:2fa_backup_codes', true ), true );

		if ( ! is_array( $stored_codes ) || empty( $stored_codes ) ) {
			throw new \Exception( _x( 'No backup codes available.', 'auth', 'voxel' ) );
		}

		$code = strtoupper( trim( $code ) );
		$verified = false;
		$remaining_codes = [];

		foreach ( $stored_codes as $stored_code ) {
			if ( ! $verified && password_verify( $code, $stored_code ) ) {
				$verified = true;
				// Don't add this code back - it's used
				continue;
			}
			$remaining_codes[] = $stored_code;
		}

		if ( ! $verified ) {
			throw new \Exception( _x( 'Invalid backup code.', 'auth', 'voxel' ) );
		}

		// Update with remaining codes
		update_user_meta( $this->get_id(), 'voxel:2fa_backup_codes', wp_json_encode( $remaining_codes ) );

		return true;
	}

	public function get_backup_codes_count() {
		$stored_codes = json_decode( get_user_meta( $this->get_id(), 'voxel:2fa_backup_codes', true ), true );
		return is_array( $stored_codes ) ? count( $stored_codes ) : 0;
	}

	public function create_2fa_login_session() {
		$session_token = wp_generate_password( 32, false );

		update_user_meta( $this->get_id(), 'voxel:2fa_login_session', wp_slash( wp_json_encode( [
			'token' => password_hash( $session_token, PASSWORD_DEFAULT ),
			'expires' => time() + ( 5 * MINUTE_IN_SECONDS ),
		] ) ) );

		return $session_token;
	}

	public function verify_2fa_login_session( $token ) {
		$session = json_decode( get_user_meta( $this->get_id(), 'voxel:2fa_login_session', true ), ARRAY_A );

		if ( ! is_array( $session ) || empty( $session['token'] ) || empty( $session['expires'] ) ) {
			throw new \Exception( _x( 'Invalid session.', 'auth', 'voxel' ) );
		}

		if ( $session['expires'] < time() ) {
			delete_user_meta( $this->get_id(), 'voxel:2fa_login_session' );
			throw new \Exception( _x( 'Session has expired. Please log in again.', 'auth', 'voxel' ) );
		}

		if ( ! password_verify( $token, $session['token'] ) ) {
			throw new \Exception( _x( 'Invalid session.', 'auth', 'voxel' ) );
		}

		// Session is valid - DO NOT delete here
		// It will be deleted after successful 2FA verification in the controller
		return true;
	}

	public function clear_2fa_login_session() {
		delete_user_meta( $this->get_id(), 'voxel:2fa_login_session' );
	}

	/* Trusted Device Management */

	public function create_trusted_device_token() {
		// Generate unique device token
		$device_token = wp_generate_password( 32, false );
		$device_id = md5( $device_token . $this->get_id() . time() );

		// Get existing trusted devices
		$trusted_devices = json_decode( get_user_meta( $this->get_id(), 'voxel:2fa_trusted_devices', true ), true );
		if ( ! is_array( $trusted_devices ) ) {
			$trusted_devices = [];
		}

		// Add new device
		$trusted_devices[ $device_id ] = [
			'token' => password_hash( $device_token, PASSWORD_DEFAULT ),
			'created' => time(),
			'expires' => time() + ( 30 * DAY_IN_SECONDS ),
			'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
			'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
		];

		// Clean up expired devices
		foreach ( $trusted_devices as $id => $device ) {
			if ( $device['expires'] < time() ) {
				unset( $trusted_devices[ $id ] );
			}
		}

		update_user_meta( $this->get_id(), 'voxel:2fa_trusted_devices', wp_json_encode( $trusted_devices ) );

		return [
			'device_id' => $device_id,
			'device_token' => $device_token,
		];
	}

	public function is_trusted_device( $device_id, $device_token ) {
		$trusted_devices = json_decode( get_user_meta( $this->get_id(), 'voxel:2fa_trusted_devices', true ), true );

		if ( ! is_array( $trusted_devices ) || ! isset( $trusted_devices[ $device_id ] ) ) {
			return false;
		}

		$device = $trusted_devices[ $device_id ];

		// Check if expired
		if ( $device['expires'] < time() ) {
			$this->remove_trusted_device( $device_id );
			return false;
		}

		// Verify token
		if ( ! password_verify( $device_token, $device['token'] ) ) {
			return false;
		}

		return true;
	}

	public function remove_trusted_device( $device_id ) {
		$trusted_devices = json_decode( get_user_meta( $this->get_id(), 'voxel:2fa_trusted_devices', true ), true );

		if ( is_array( $trusted_devices ) && isset( $trusted_devices[ $device_id ] ) ) {
			unset( $trusted_devices[ $device_id ] );
			update_user_meta( $this->get_id(), 'voxel:2fa_trusted_devices', wp_json_encode( $trusted_devices ) );
		}
	}

	public function get_trusted_devices_count() {
		$trusted_devices = json_decode( get_user_meta( $this->get_id(), 'voxel:2fa_trusted_devices', true ), true );

		if ( ! is_array( $trusted_devices ) ) {
			return 0;
		}

		// Clean up expired devices and count active ones
		$active_count = 0;
		$now = time();
		foreach ( $trusted_devices as $device ) {
			if ( isset( $device['expires'] ) && $device['expires'] >= $now ) {
				$active_count++;
			}
		}

		return $active_count;
	}

	public function remove_all_trusted_devices() {
		delete_user_meta( $this->get_id(), 'voxel:2fa_trusted_devices' );
	}
}
