<?php
/**
 * Plugin Name: HARP
 * Plugin URI:  https://hit.ac.zw
 * Description: HIT Anonymous Reporting Portal — confidential fault and incident reporting for HIT university. Includes report submission, unique tracking codes, admin dashboard, status updates, and email notifications.
 * Version:     1.0.1
 * Author:      HIT University
 * License:     GPL-2.0+
 * Text Domain: HARP
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'HARP_VERSION',     '1.0.1' );
define( 'HARP_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'HARP_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'HARP_PLUGIN_FILE', __FILE__ );

// ─── Load core files ────────────────────────────────────────────────────────
require_once HARP_PLUGIN_DIR . 'includes/class-HARP-db.php';
require_once HARP_PLUGIN_DIR . 'includes/class-HARP-mailer.php';
require_once HARP_PLUGIN_DIR . 'includes/class-HARP-ajax.php';
require_once HARP_PLUGIN_DIR . 'includes/class-HARP-shortcodes.php';
require_once HARP_PLUGIN_DIR . 'includes/class-HARP-admin.php';
require_once HARP_PLUGIN_DIR . 'includes/class-HARP-settings.php';

// ─── Activation / Deactivation ──────────────────────────────────────────────
register_activation_hook(   __FILE__, [ 'HARP_DB', 'install' ] );
register_deactivation_hook( __FILE__, [ 'HARP_DB', 'deactivate' ] );

// ─── Boot ────────────────────────────────────────────────────────────────────
add_action( 'plugins_loaded', function () {
    // Auto-create/upgrade tables if they don't exist yet
    // (handles cases where activation hook didn't fire properly)
    HARP_DB::maybe_install();

    HARP_Ajax::init();
    HARP_Shortcodes::init();
    HARP_Admin::init();
    HARP_Settings::init();
} );

// ─── Enqueue front-end assets ────────────────────────────────────────────────
add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'HARP-styles',
        HARP_PLUGIN_URL . 'assets/css/HARP-frontend.css',
        [],
        HARP_VERSION
    );
    wp_enqueue_script(
        'HARP-scripts',
        HARP_PLUGIN_URL . 'assets/js/HARP-frontend.js',
        [ 'jquery' ],
        HARP_VERSION,
        true
    );
    wp_localize_script( 'HARP-scripts', 'HARP', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'HARP_nonce' ),
        'strings'  => [
            'submitting'       => 'Submitting report…',
            'tracking'         => 'Tracking report…',
            'file_size_error'  => 'File exceeds 10 MB limit.',
            'file_type_error'  => 'Only images (JPG, PNG, GIF) and videos (MP4, MOV) are allowed.',
        ],
    ] );
} );
