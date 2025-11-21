<?php

namespace Voxel\Controllers\Frontend\Auth;

use \Voxel\Vendor\Firebase\JWT\BeforeValidException;
use \Voxel\Vendor\Firebase\JWT\ExpiredException;
use \Voxel\Vendor\Firebase\JWT\JWK;
use \Voxel\Vendor\Firebase\JWT\JWT;
use \Voxel\Vendor\Firebase\JWT\SignatureInvalidException;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Google_Controller extends \Voxel\Controllers\Base_Controller {

	protected function hooks() {
		$this->on( 'voxel_ajax_nopriv_auth.google.login', '@login_with_google' );
	}

	protected function login_with_google() {
		try {
			$state = json_decode( base64_decode( $_REQUEST['state'] ?? '' ), true );
			\Voxel\verify_nonce( $state['_wpnonce'] ?? '', 'vx_auth_google' );
			if ( empty( $_GET['code'] ) || ! \Voxel\get( 'settings.auth.google.enabled' ) ) {
				throw new \Exception( _x( 'Invalid request.', 'login with google', 'voxel' ) );
			}

			$code = $_GET['code'];
			$redirect_url = ! empty( $state['redirect_to'] ) ? $state['redirect_to'] : home_url('/');

			$client_id = \Voxel\get( 'settings.auth.google.client_id' );
			$client_secret = \Voxel\get( 'settings.auth.google.client_secret' );

			$response = wp_remote_post( 'https://oauth2.googleapis.com/token', [
				'timeout' => 10,
				'headers' => [
					'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8',
				],
				'body' => http_build_query( [
					'grant_type' => 'authorization_code',
					'client_id' => $client_id,
					'client_secret' => $client_secret,
					'redirect_uri' => home_url('/?vx=1&action=auth.google.login'),
					'code' => $code,
				] ),
			] );

			if ( is_wp_error( $response ) ) {
				throw new \Exception( _x( 'Could not retrieve data.', 'login with google', 'voxel' ) );
			}

			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( empty( $data['id_token'] ) ) {
				throw new \Exception( _x( 'Could not retrieve details.', 'login with google', 'voxel' ) );
			}

			$userinfo = $this->verify_google_id_token( $data['id_token'], $client_id );

			$email = $userinfo['email'];

			// see if this account is connected to an existing user
			$users = get_users( [
			   'meta_key' => 'voxel:google_auth_id',
			   'meta_value' => $email,
			   'number' => 1,
			   'count_total' => false
			] );

			// if so, log them in
			if ( ! empty( $users ) ) {
				wp_clear_auth_cookie();
				wp_set_auth_cookie( $users[0]->ID, true );
				wp_safe_redirect( $redirect_url );
				exit;
			}

			// if a user with this email already exists, log them in
			if ( $user = get_user_by( 'email', $email ) ) {
				update_user_meta( $user->ID, 'voxel:google_auth_id', $email );
				wp_clear_auth_cookie();
				wp_set_auth_cookie( $user->ID, true );
				wp_safe_redirect( $redirect_url );
				exit;
			}

			/* Create a new account */
			$role = \Voxel\Role::get( sanitize_key( wp_unslash( ! empty( $state['role'] ) ? $state['role'] : apply_filters( 'voxel/social-login/default-role', 'subscriber' ) ) ) );

			if ( ! ( $role && $role->is_registration_enabled() && $role->is_social_login_allowed() ) ) {
				$auth_link = get_permalink( \Voxel\get( 'templates.auth' ) ) ?: home_url('/');
				wp_safe_redirect( add_query_arg( [
					'redirect_to' => urlencode( $redirect_url ),
					'err' => 'social_login_requires_account',
				], $auth_link ) );
				exit;
			}

			// otherwise, insert a new user
			$username = \Voxel\generate_username_from_email( $email );
			try {
				\Voxel\validate_username( $username );
			} catch ( \Exception $e ) {
				throw new \Exception( __( 'Something went wrong.', 'voxel' ) );
			}

			$args = [
				'user_login' => $username,
				'user_email' => $email,
				'user_pass' => wp_generate_password(16),
				'role' => $role->get_key(),
			];

			$user_id = wp_insert_user( $args );
			if ( is_wp_error( $user_id ) ) {
				throw new \Exception( $user_id->get_error_message() );
			}

			update_user_meta( $user_id, 'voxel:google_auth_id', $email );

			do_action( 'voxel/user-registered', $user_id );
			( new \Voxel\Events\Membership\User_Registered_Event )->dispatch( $user_id );

			wp_clear_auth_cookie();
			wp_set_auth_cookie( $user_id, true );
			wp_set_current_user( $user_id );

			$redirect_to = home_url('/');
			if ( ! empty( $state['redirect_to'] ) && wp_validate_redirect( $state['redirect_to'] ) ) {
				$redirect_to = wp_validate_redirect( $state['redirect_to'] );
			}

			if ( ! empty( $state['plan'] ) ) {
				$_REQUEST['plan'] = $state['plan'];
			}

			$redirect_to = apply_filters(
				'voxel/register/redirect_to',
				$redirect_to,
				\Voxel\User::get( $user_id ),
				$role,
				$state['redirect_to'] ?? null // raw redirect url
			);

			wp_redirect( $redirect_to );
			exit;
		} catch ( \Exception $e ) {
			$auth_link = get_permalink( \Voxel\get( 'templates.auth' ) ) ?: home_url('/');
			wp_safe_redirect( add_query_arg( 'redirect_to', urlencode( $redirect_url ), $auth_link ) );
			exit;
		}
	}

	protected function verify_google_id_token( string $id_token, string $client_id ): array {
		$payload = null;
		try {
			$segments = explode( '.', $id_token );
			if ( \count( $segments ) !== 3 ) {
				throw new \Exception;
			}

			$header = json_decode( JWT::urlsafeB64Decode( $segments[0] ), true );
			if ( ! \is_array( $header ) || empty( $header['kid'] ) || empty( $header['alg'] ) || $header['alg'] !== 'RS256' ) {
				throw new \Exception;
			}

			$jwks = $this->get_google_jwks();
			$keys = JWK::parseKeySet( $jwks, 'RS256' );

			$previous_leeway = JWT::$leeway;
			JWT::$leeway = 60;

			try {
				$payload = JWT::decode( $id_token, $keys );
			} finally {
				JWT::$leeway = $previous_leeway;
			}
		} catch ( SignatureInvalidException | BeforeValidException | ExpiredException $e ) {
			throw new \Exception( _x( 'Token is no longer valid.', 'login with google', 'voxel' ) );
		} catch ( \Throwable $e ) {
			throw new \Exception( _x( 'Could not validate request.', 'login with google', 'voxel' ) );
		}

		$claims = (array) $payload;

		if ( empty( $claims['iss'] ) || ! \in_array( $claims['iss'], [ 'https://accounts.google.com', 'accounts.google.com' ], true ) ) {
			throw new \Exception( _x( 'Token issuer mismatch.', 'login with google', 'voxel' ) );
		}

		$audiences = [];
		if ( isset( $claims['aud'] ) ) {
			if ( \is_array( $claims['aud'] ) ) {
				$audiences = $claims['aud'];
			} else {
				$audiences = [ $claims['aud'] ];
			}
		}

		if ( empty( $audiences ) || ! \in_array( $client_id, $audiences, true ) ) {
			throw new \Exception( _x( 'Token audience mismatch.', 'login with google', 'voxel' ) );
		}

		if ( isset( $claims['azp'] ) && $claims['azp'] !== $client_id ) {
			throw new \Exception( _x( 'Token authorized party mismatch.', 'login with google', 'voxel' ) );
		}

		if ( empty( $claims['sub'] ) ) {
			throw new \Exception( _x( 'Token subject missing.', 'login with google', 'voxel' ) );
		}

		if ( empty( $claims['email'] ) ) {
			throw new \Exception( _x( 'Email address missing from token.', 'login with google', 'voxel' ) );
		}

		$email_verified = filter_var( $claims['email_verified'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
		if ( true !== $email_verified ) {
			throw new \Exception( _x( 'Email address must be verified.', 'login with google', 'voxel' ) );
		}

		return [
			'email' => $claims['email'],
			'sub' => $claims['sub'],
			'picture' => $claims['picture'] ?? null,
			'name' => $claims['name'] ?? null,
		];
	}

	protected function get_google_jwks(): array {
		$cache_key = 'voxel_google_jwks';
		$jwks = get_transient( $cache_key );

		if ( ! \is_array( $jwks ) || empty( $jwks['keys'] ) ) {
			$response = wp_remote_get( 'https://www.googleapis.com/oauth2/v3/certs', [
				'timeout' => 10,
			] );

			if ( is_wp_error( $response ) ) {
				throw new \Exception( _x( 'Could not retrieve token signing keys.', 'login with google', 'voxel' ) );
			}

			$body = wp_remote_retrieve_body( $response );
			$jwks = json_decode( $body, true );

			if ( ! \is_array( $jwks ) || empty( $jwks['keys'] ) ) {
				throw new \Exception( _x( 'Invalid signing keys response.', 'login with google', 'voxel' ) );
			}

			$cache_control = wp_remote_retrieve_header( $response, 'cache-control' );
			$ttl = HOUR_IN_SECONDS;
			if ( \is_string( $cache_control ) && preg_match( '/max-age=(\\d+)/', $cache_control, $matches ) ) {
				$ttl = max( 60, min( (int) $matches[1], DAY_IN_SECONDS ) );
			}

			set_transient( $cache_key, $jwks, $ttl );
		}

		return $jwks;
	}
}
