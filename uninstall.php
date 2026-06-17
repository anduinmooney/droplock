<?php
/**
 * Uninstall handler for DropLock (Lite).
 *
 * Runs when an admin clicks Delete in the Plugins screen (NOT on deactivate).
 *
 * @package DropLock_Lite
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Drop the log table.
$table = $wpdb->prefix . 'droplock_blocked_log';
$wpdb->query( "DROP TABLE IF EXISTS {$table}" );

// Remove plugin options.
delete_option( 'droplock_db_version' );
delete_option( 'droplock_milestone_dismissed' );

// Note: We intentionally keep per-product meta in postmeta so that a reinstall
// (or a switch to DropLock Pro) doesn't lose configured limits.
