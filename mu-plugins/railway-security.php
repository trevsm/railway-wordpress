<?php
/**
 * Plugin Name: Railway - Security Hardening
 * Description: Login throttling, user-enumeration protection, version hiding and admin lockdown. Must-use plugin, installed by the container startup script.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// No theme/plugin code editing from wp-admin (a stolen admin session can't plant code that way).
if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', true );
}

// Hide the WordPress version.
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

// Application passwords are another way in; this site does not use them.
add_filter( 'wp_is_application_passwords_available', '__return_false' );

/* ---------- User enumeration ---------- */

// REST API: no user list for anonymous visitors.
add_filter( 'rest_endpoints', function ( $endpoints ) {
	if ( ! is_user_logged_in() ) {
		foreach ( array_keys( $endpoints ) as $route ) {
			if ( 0 === strpos( $route, '/wp/v2/users' ) ) {
				unset( $endpoints[ $route ] );
			}
		}
	}
	return $endpoints;
} );

// ?author=N and /author/name/ pages leak login names.
add_action( 'parse_request', function () {
	if ( ! is_admin() && isset( $_GET['author'] ) ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
} );
add_action( 'template_redirect', function () {
	if ( is_author() ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
} );

// oEmbed and sitemaps also expose authors.
add_filter( 'oembed_response_data', function ( $data ) {
	unset( $data['author_name'], $data['author_url'] );
	return $data;
} );
add_filter( 'wp_sitemaps_add_provider', function ( $provider, $name ) {
	return 'users' === $name ? false : $provider;
}, 10, 2 );

/* ---------- Login protection ---------- */

// Same message whether the username exists or not (also covers the lost-password form).
add_filter( 'login_errors', function ( $error ) {
	if ( preg_match( '/(invalid username|incorrect|not registered|password you entered|unknown email|no account)/i', $error ) ) {
		return 'Invalid username or password.';
	}
	return $error;
} );

/**
 * Client IP. The site sits behind Cloudflare and Railway's proxy, so REMOTE_ADDR is the proxy.
 */
function railway_client_ip() {
	foreach ( array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP' ) as $header ) {
		if ( ! empty( $_SERVER[ $header ] ) && filter_var( $_SERVER[ $header ], FILTER_VALIDATE_IP ) ) {
			return $_SERVER[ $header ];
		}
	}
	return isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
}

function railway_login_key() {
	return 'rw_login_fail_' . md5( railway_client_ip() );
}

// After 5 failures from one IP, refuse further attempts for 15 minutes.
add_filter( 'authenticate', function ( $user, $username ) {
	if ( '' === (string) $username ) {
		return $user;
	}
	if ( (int) get_transient( railway_login_key() ) >= 5 ) {
		return new WP_Error( 'too_many_attempts', 'Too many failed attempts. Please try again in 15 minutes.' );
	}
	return $user;
}, 30, 2 );

add_action( 'wp_login_failed', function () {
	$key = railway_login_key();
	set_transient( $key, (int) get_transient( $key ) + 1, 15 * MINUTE_IN_SECONDS );
} );

add_action( 'wp_login', function () {
	delete_transient( railway_login_key() );
} );
