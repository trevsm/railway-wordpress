<?php
/**
 * Plugin Name: Railway - Disable Comments & XML-RPC
 * Description: Turns comments, trackbacks and XML-RPC off site-wide. Must-use plugin, installed by the container entrypoint.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// New content defaults to closed, regardless of what is stored in the database.
add_filter( 'pre_option_default_comment_status', function () { return 'closed'; } );
add_filter( 'pre_option_default_ping_status', function () { return 'closed'; } );

// Existing content: always closed, and hide any stored comments.
add_filter( 'comments_open', '__return_false', 20, 2 );
add_filter( 'pings_open', '__return_false', 20, 2 );
add_filter( 'comments_array', '__return_empty_array', 20, 2 );
add_filter( 'get_comments_number', '__return_zero', 20 );

// No comment support on any post type.
add_action( 'init', function () {
	foreach ( get_post_types() as $post_type ) {
		if ( post_type_supports( $post_type, 'comments' ) ) {
			remove_post_type_support( $post_type, 'comments' );
			remove_post_type_support( $post_type, 'trackbacks' );
		}
	}
}, 100 );

// Admin UI: hide the Comments menu, admin bar item, and dashboard widget; block the screens.
add_action( 'admin_menu', function () {
	remove_menu_page( 'edit-comments.php' );
	remove_submenu_page( 'options-general.php', 'options-discussion.php' );
} );
add_action( 'admin_init', function () {
	global $pagenow;
	if ( in_array( $pagenow, array( 'edit-comments.php', 'options-discussion.php' ), true ) ) {
		wp_safe_redirect( admin_url() );
		exit;
	}
	remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
} );
add_action( 'wp_before_admin_bar_render', function () {
	global $wp_admin_bar;
	$wp_admin_bar->remove_node( 'comments' );
} );

// REST API: no comment endpoints.
add_filter( 'rest_endpoints', function ( $endpoints ) {
	foreach ( array_keys( $endpoints ) as $route ) {
		if ( 0 === strpos( $route, '/wp/v2/comments' ) ) {
			unset( $endpoints[ $route ] );
		}
	}
	return $endpoints;
} );

// Comment feeds: 404.
add_filter( 'feed_links_show_comments_feed', '__return_false' );
add_action( 'template_redirect', function () {
	if ( is_comment_feed() ) {
		wp_die( 'Comments are disabled.', '', array( 'response' => 410 ) );
	}
} );

// XML-RPC off, and no pingbacks.
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'xmlrpc_methods', function ( $methods ) {
	unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );
	return $methods;
} );
add_filter( 'wp_headers', function ( $headers ) {
	unset( $headers['X-Pingback'] );
	return $headers;
} );
