<?php
/**
 * Plugin Name: Illu-Optimize
 * Plugin URI:  https://solusimarketing.xyz
 * Description: Performance layer untuk Sharelink AI ecosystem. Redis object cache, HTML minifier, WebP/AVIF converter, responsive images, SEO meta & sitemap, async queue, DB optimizer.
 * Version:     1.2.0
 * Author:      Solusi Marketing
 * Text Domain: illu-optimize
 * Requires PHP: 7.4
 *
 * DEPENDENCY NOTES (soft, intentional):
 *   - Memanggil do_action('illu_shield_clear_smart_cache') & do_action('illu_shield_purge_cloudflare')
 *     saat auto-purge berjalan. Jika illu-shield tidak aktif, action tersebut menjadi no-op — AMAN.
 *   - Admin bar node 'illu-clear-redis' dipasang sebagai child dari 'illu-cache-menu' yang
 *     dibuat oleh illu-shield/class-cache.php. Jika illu-shield tidak aktif, node ini tidak muncul
 *     di UI — AMAN, tidak menyebabkan error.
 *   - Menghapus SEO hook milik illusi-theme (remove_action 'illusi_seo_meta_tags') agar
 *     tidak double-output. illusi-theme didesain untuk tetap bekerja bahkan jika remove ini
 *     tidak dieksekusi (lihat inc/seo.php di theme).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'ILLU_OPTIMIZE_VERSION', '1.2.0' );
define( 'ILLU_OPTIMIZE_DIR',     plugin_dir_path( __FILE__ ) );
define( 'ILLU_OPTIMIZE_URL',     plugin_dir_url( __FILE__ ) );

// ── Includes ───────────────────────────────────────────────────────────────────
require_once ILLU_OPTIMIZE_DIR . 'includes/class-admin.php';
require_once ILLU_OPTIMIZE_DIR . 'includes/class-redis-manager.php';
require_once ILLU_OPTIMIZE_DIR . 'includes/class-minifier.php';
require_once ILLU_OPTIMIZE_DIR . 'includes/class-assets-injector.php';
require_once ILLU_OPTIMIZE_DIR . 'includes/class-clean-header.php';
require_once ILLU_OPTIMIZE_DIR . 'includes/class-image-converter.php';
require_once ILLU_OPTIMIZE_DIR . 'includes/class-responsive-images.php';
require_once ILLU_OPTIMIZE_DIR . 'includes/class-robots.php';
require_once ILLU_OPTIMIZE_DIR . 'includes/class-sitemap.php';
require_once ILLU_OPTIMIZE_DIR . 'includes/class-meta.php';
require_once ILLU_OPTIMIZE_DIR . 'includes/class-ads-manager.php';
require_once ILLU_OPTIMIZE_DIR . 'includes/class-async-worker.php';
require_once ILLU_OPTIMIZE_DIR . 'includes/class-micro-cache.php';
require_once ILLU_OPTIMIZE_DIR . 'includes/class-db-optimizer.php';

// ── Boot ───────────────────────────────────────────────────────────────────────
function illu_optimize_init() {
    new Illu_Optimize_Admin();
    new Illu_Optimize_Ads_Manager();
    new Illu_Optimize_Redis_Manager();
    new Illu_Optimize_Minifier();
    new Illu_Optimize_Assets_Injector();
    new Illu_Optimize_Clean_Header();
    new Illu_Media_Image_Converter();
    new Illu_Media_Responsive_Images();
    new Illu_SEO_Robots();
    new Illu_SEO_Sitemap();
    new Illu_SEO_Meta();        // ← override theme SEO; documented above
    new Illu_Optimize_Async_Worker();
    new Illu_Optimize_Micro_Cache();
    new Illu_Optimize_DB_Optimizer();

    add_action( 'illu_optimize_auto_purge', 'illu_optimize_run_auto_purge' );
}
add_action( 'plugins_loaded', 'illu_optimize_init' );

/**
 * Auto-purge runner: clears Smart Cache (illu-shield), Redis, Cloudflare.
 * Semua do_action ini adalah no-op jika plugin lain tidak menyediakan handler.
 */
function illu_optimize_run_auto_purge() {
    do_action( 'illu_shield_clear_smart_cache' );  // illu-shield/class-cache.php
    do_action( 'illu_optimize_purge_redis' );       // illu-optimize/class-redis-manager.php
    do_action( 'illu_shield_purge_cloudflare' );   // illu-shield/class-cloudflare.php
}

// ── Activation ─────────────────────────────────────────────────────────────────
register_activation_hook( __FILE__, function () {
    Illu_Optimize_Redis_Manager::install_object_cache();
    Illu_SEO_Sitemap::add_rewrite_rules();

    if ( ! wp_next_scheduled( 'illu_optimize_auto_purge' ) ) {
        $schedule = get_option( 'illu_auto_purge_schedule', 'twicedaily' );
        if ( $schedule !== 'never' ) {
            wp_schedule_event( time(), $schedule, 'illu_optimize_auto_purge' );
        }
    }
    flush_rewrite_rules();
} );

// ── Deactivation ───────────────────────────────────────────────────────────────
register_deactivation_hook( __FILE__, function () {
    Illu_Optimize_Redis_Manager::uninstall_object_cache();
    wp_clear_scheduled_hook( 'illu_optimize_auto_purge' );
    flush_rewrite_rules();
} );
