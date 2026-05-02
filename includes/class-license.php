<?php
/**
 * License client for NP MCP Builder Pro.
 *
 * Verifies Ed25519-signed activation tokens issued by the license server at
 * hamzanabulsi.com. Public key is hard-coded at the top of this file —
 * tampering with it breaks the signature check and the plugin falls back to
 * the free 10-ability tier.
 *
 * @package NP_MCP_Builder
 */

namespace NP_MCP_Builder;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class License {

	/** Server-issued public key (base64-encoded Ed25519). */
	const PUBLIC_KEY_B64 = 'i1QoFmrYZnZ+TzHiaLThIKug0Ah/ma6Q+wyXw7/gcjA=';

	/** License server (override with `NP_MCP_LICENSE_SERVER` constant). */
	const SERVER_DEFAULT = 'https://hamzanabulsi.com';

	private static function server_url(): string {
		if ( defined( 'NP_MCP_LICENSE_SERVER' ) && is_string( NP_MCP_LICENSE_SERVER ) && NP_MCP_LICENSE_SERVER !== '' ) {
			return rtrim( (string) NP_MCP_LICENSE_SERVER, '/' );
		}
		return self::SERVER_DEFAULT;
	}

	/** Refresh attempt interval. */
	const REFRESH_INTERVAL = 7 * DAY_IN_SECONDS;

	/** Offline grace period — Pro stays on this many seconds past last successful refresh. */
	const GRACE_PERIOD = 14 * DAY_IN_SECONDS;

	/** Free tier abilities (always available, no key required). */
	const FREE_ABILITIES = array(
		'np/site-info',
		'np/system-info',
		'np/list-posts',
		'np/list-plugins',
		'np/list-themes',
		'np/list-menus',
		'np/list-users',
		'np/get-yoast-global',
		'np/get-elementor-kit',
		'np/elementor-list-templates',
	);

	const OPTION_KEY    = 'np_mcp_license_key';
	const OPTION_TOKEN  = 'np_mcp_license_token';
	const OPTION_LAST   = 'np_mcp_license_last_check';
	const OPTION_STATUS = 'np_mcp_license_last_status';

	/* ------------------------------------------------------------------ */
	/* Public API                                                         */
	/* ------------------------------------------------------------------ */

	public static function is_pro(): bool {
		$token = get_option( self::OPTION_TOKEN, array() );
		if ( ! is_array( $token ) || empty( $token['signature'] ) ) {
			return false;
		}

		// Verify signature.
		if ( ! self::verify_token( $token ) ) {
			return false;
		}

		// Site-binding.
		if ( self::normalize_site( home_url() ) !== (string) ( $token['site_url'] ?? '' ) ) {
			return false;
		}

		// Hard expiry from the server.
		$expires = (int) ( $token['expires_at'] ?? 0 );
		if ( $expires > 0 && $expires < time() ) {
			return false;
		}

		// Grace period: even if we can't reach the server, keep working until
		// last successful refresh + GRACE_PERIOD.
		$last = (int) get_option( self::OPTION_LAST, 0 );
		if ( $last > 0 && ( time() - $last ) > self::GRACE_PERIOD ) {
			return false;
		}

		return true;
	}

	public static function is_free_ability( string $tool ): bool {
		return in_array( $tool, self::FREE_ABILITIES, true );
	}

	public static function get_status(): array {
		$key   = (string) get_option( self::OPTION_KEY, '' );
		$token = get_option( self::OPTION_TOKEN, array() );
		$last  = (int) get_option( self::OPTION_LAST, 0 );
		$pro   = self::is_pro();
		return array(
			'has_key'    => $key !== '',
			'is_pro'     => $pro,
			'plan'       => is_array( $token ) ? (string) ( $token['plan'] ?? '' )       : '',
			'customer'   => is_array( $token ) ? (string) ( $token['customer'] ?? '' )   : '',
			'expires_at' => is_array( $token ) ? (int)    ( $token['expires_at'] ?? 0 )  : 0,
			'site_url'   => is_array( $token ) ? (string) ( $token['site_url'] ?? '' )   : '',
			'last_check' => $last,
			'last_msg'   => (string) get_option( self::OPTION_STATUS, '' ),
		);
	}

	/**
	 * Activate a key against the server.
	 *
	 * @return true|\WP_Error
	 */
	public static function activate( string $key ) {
		$key = strtoupper( trim( $key ) );
		if ( ! preg_match( '/^NPMCP(-[A-Z2-9]{4}){4}$/', $key ) ) {
			return new \WP_Error( 'bad_format', __( 'License key format is invalid.', 'np-mcp-builder' ) );
		}
		$res = self::call_server( '/wp-json/np-license/v1/activate', $key );
		if ( is_wp_error( $res ) ) { return $res; }

		update_option( self::OPTION_KEY, $key, false );
		update_option( self::OPTION_TOKEN, $res, false );
		update_option( self::OPTION_LAST, time(), false );
		update_option( self::OPTION_STATUS, 'Activated', false );
		return true;
	}

	/**
	 * Deactivate this site against the server (frees a slot).
	 */
	public static function deactivate(): bool {
		$key = (string) get_option( self::OPTION_KEY, '' );
		if ( $key === '' ) { return true; }
		wp_remote_post( self::server_url() . '/wp-json/np-license/v1/deactivate', array(
			'timeout' => 15,
			'body'    => array(
				'key'      => $key,
				'site_url' => self::normalize_site( home_url() ),
			),
		) );
		delete_option( self::OPTION_KEY );
		delete_option( self::OPTION_TOKEN );
		delete_option( self::OPTION_LAST );
		update_option( self::OPTION_STATUS, 'Deactivated', false );
		return true;
	}

	/**
	 * Refresh token from server if it's been longer than REFRESH_INTERVAL.
	 * Called from a daily cron — keep silent on failures (grace period kicks in).
	 */
	public static function maybe_refresh(): void {
		$key  = (string) get_option( self::OPTION_KEY, '' );
		if ( $key === '' ) { return; }
		$last = (int) get_option( self::OPTION_LAST, 0 );
		if ( $last && ( time() - $last ) < self::REFRESH_INTERVAL ) { return; }

		$res = self::call_server( '/wp-json/np-license/v1/validate', $key );
		if ( is_wp_error( $res ) ) {
			update_option( self::OPTION_STATUS, 'Refresh failed: ' . $res->get_error_message(), false );
			return;
		}
		update_option( self::OPTION_TOKEN, $res, false );
		update_option( self::OPTION_LAST, time(), false );
		update_option( self::OPTION_STATUS, 'Refreshed', false );
	}

	/* ------------------------------------------------------------------ */
	/* Internals                                                          */
	/* ------------------------------------------------------------------ */

	private static function call_server( string $path, string $key ) {
		$resp = wp_remote_post( self::server_url() . $path, array(
			'timeout' => 15,
			'body'    => array(
				'key'      => $key,
				'site_url' => self::normalize_site( home_url() ),
			),
		) );
		if ( is_wp_error( $resp ) ) {
			return new \WP_Error( 'server_unreachable', $resp->get_error_message() );
		}
		$code = (int) wp_remote_retrieve_response_code( $resp );
		$body = json_decode( (string) wp_remote_retrieve_body( $resp ), true );
		if ( $code !== 200 || ! is_array( $body ) || empty( $body['ok'] ) || empty( $body['token'] ) ) {
			$msg = is_array( $body ) ? (string) ( $body['message'] ?? wp_remote_retrieve_body( $resp ) ) : (string) wp_remote_retrieve_body( $resp );
			return new \WP_Error( 'server_error', $msg ?: ( 'HTTP ' . $code ) );
		}
		$token = $body['token'];
		if ( ! self::verify_token( $token ) ) {
			return new \WP_Error( 'bad_signature', __( 'Server response failed signature verification.', 'np-mcp-builder' ) );
		}
		return $token;
	}

	private static function verify_token( $token ): bool {
		if ( ! is_array( $token ) || empty( $token['signature'] ) ) { return false; }
		if ( ! function_exists( 'sodium_crypto_sign_verify_detached' ) ) { return false; }
		$sig    = (string) base64_decode( (string) $token['signature'], true );
		$pk     = (string) base64_decode( self::PUBLIC_KEY_B64, true );
		$payload = $token;
		unset( $payload['signature'] );
		$msg = self::canonical_json( $payload );
		try {
			return sodium_crypto_sign_verify_detached( $sig, $msg, $pk );
		} catch ( \Throwable $e ) {
			return false;
		}
	}

	private static function canonical_json( array $payload ): string {
		$sort = function ( $v ) use ( &$sort ) {
			if ( is_array( $v ) ) {
				if ( array_is_list( $v ) ) {
					return array_map( $sort, $v );
				}
				ksort( $v );
				foreach ( $v as $k => $vv ) { $v[ $k ] = $sort( $vv ); }
			}
			return $v;
		};
		$sorted = $sort( $payload );
		return (string) wp_json_encode( $sorted, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}

	private static function normalize_site( string $url ): string {
		$url = strtolower( trim( $url ) );
		$url = (string) preg_replace( '#^https?://#', '', $url );
		return rtrim( $url, '/' );
	}
}

// Daily cron for token refresh.
add_action( 'np_mcp_license_daily', array( '\\NP_MCP_Builder\\License', 'maybe_refresh' ) );
add_action( 'init', static function () {
	if ( ! wp_next_scheduled( 'np_mcp_license_daily' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'np_mcp_license_daily' );
	}
} );
