<?php
// Only run when WordPress triggers uninstall
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

global $wpdb;

// Drop custom tables
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}HARP_reports" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}HARP_comments" );

// Remove plugin options
delete_option( 'HARP_db_version' );
delete_option( 'HARP_admin_email' );
delete_option( 'HARP_from_name' );
delete_option( 'HARP_from_email' );
delete_option( 'HARP_max_file_size' );
