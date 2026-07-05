<?php
/**
 * illusi-theme/inc/admin-options.php
 * Membatasi akses menu admin untuk role selain administrator.
 * Selaras dengan pola cl_admin_restrictions() di sharelink-wp.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', 'illusi_restrict_admin_menus', 999 );

function illusi_restrict_admin_menus() {
    if ( current_user_can( 'manage_options' ) ) return;

    // Menu yang disembunyikan dari non-admin
    $remove_menus = apply_filters( 'illusi_restricted_admin_menus', [
        'edit.php',                    // Posts
        'edit.php?post_type=page',     // Pages
        'edit-comments.php',           // Comments
        'themes.php',                  // Appearance
        'plugins.php',                 // Plugins
        'users.php',                   // Users
        'tools.php',                   // Tools
        'options-general.php',         // Settings
        'upload.php',                  // Media
    ] );

    foreach ( $remove_menus as $menu ) {
        remove_menu_page( $menu );
    }
}

// Redirect jika non-admin mencoba akses halaman yang dilarang
add_action( 'admin_init', function () {
    if ( current_user_can( 'manage_options' ) ) return;
    if ( ! is_admin() || wp_doing_ajax() ) return;

    $screen = get_current_screen();
    if ( ! $screen ) return;

    $allowed_bases = apply_filters( 'illusi_allowed_admin_screen_bases', [
        'profile', 'admin', // selalu diizinkan
    ] );

    // Plugin-registered screens diizinkan (sharelink-wp, illu-shield, dll.)
    if ( strpos( $screen->id, 'sharelink' ) !== false ) return;
    if ( strpos( $screen->id, 'illu' ) !== false ) return;

    if ( in_array( $screen->base, $allowed_bases, true ) ) return;

    // Redirect ke halaman utama sharelink jika ada, atau ke profil
    $redirect = admin_url( 'admin.php?page=dashboard' );
    if ( ! menu_page_url( 'dashboard', false ) ) {
        $redirect = admin_url( 'profile.php' );
    }
    wp_safe_redirect( $redirect );
    exit;
} );

// Sembunyikan admin bar untuk subscriber (tapi bukan untuk plugin member area)
add_action( 'after_setup_theme', function () {
    if ( ! current_user_can( 'edit_posts' ) && ! is_admin() ) {
        show_admin_bar( false );
    }
} );

// Custom admin footer
add_filter( 'admin_footer_text', fn() => '&copy; ' . date('Y') . ' ' . get_bloginfo('name') . ' — Powered by Illusi Theme' );
add_filter( 'update_footer',     fn() => 'v' . ILLUSI_THEME_VERSION, 11 );
