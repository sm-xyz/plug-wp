<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Illu_Shield_Cache {

    private string $cache_dir;

    public function __construct() {
        $settings = get_option( 'illu_shield_settings', [] );

        // Heartbeat control berlaku selalu (tidak perlu enable_cache toggle)
        add_action( 'init', [ $this, 'control_heartbeat' ], 1 );

        if ( ( $settings['enable_cache'] ?? 'yes' ) !== 'yes' ) return;

        $upload_dir      = wp_upload_dir();
        $this->cache_dir = trailingslashit( $upload_dir['basedir'] ) . 'illu-cache/';

        // Serve cache ASAP (before WordPress loads fully)
        $this->serve_cache();

        add_action( 'template_redirect', [ $this, 'start_cache_buffer' ], 1 );

        // Invalidation hooks
        add_action( 'save_post',    [ $this, 'clear_cache' ] );
        add_action( 'deleted_post', [ $this, 'clear_cache' ] );
        add_action( 'switch_theme', [ $this, 'clear_cache' ] );

        // Admin bar shortcut
        add_action( 'admin_bar_menu', [ $this, 'admin_bar_cache_clear' ], 100 );

        // Handle clear cache POST actions
        add_action( 'admin_post_illu_clear_all_caches',   [ $this, 'handle_clear_cache' ] );
        add_action( 'admin_post_illu_clear_smart_cache',  [ $this, 'handle_clear_cache' ] );
        add_action( 'admin_post_illu_clear_cf_cache',     [ $this, 'handle_clear_cache' ] );

        // Hook untuk di-trigger oleh illu-optimize (soft dependency tetap aman)
        add_action( 'illu_shield_clear_smart_cache', [ $this, 'clear_cache' ] );
    }

    // ── Heartbeat ──────────────────────────────────────────────────────────────

    public function control_heartbeat() {
        add_filter( 'heartbeat_settings', function ( $settings ) {
            $settings['interval'] = 60;
            return $settings;
        } );
        if ( ! is_admin() ) {
            wp_deregister_script( 'heartbeat' );
        }
    }

    // ── Bypass logic (single call, consolidated) ───────────────────────────────

    private function should_bypass_cache(): bool {
        // Sharelink / webhook selalu bypass
        if ( Illu_Shield_Request_Context::is_whitelisted() ) return true;

        $uri = $_SERVER['REQUEST_URI'];
        if ( strpos( $uri, '/ai/' ) !== false ) return true;
        if ( strpos( $uri, 'wp-admin' ) !== false ) return true;
        if ( strpos( $uri, 'wp-login.php' ) !== false ) return true;
        if ( ! empty( $_GET ) || ! empty( $_POST ) ) return true;

        // Logged-in users
        foreach ( $_COOKIE as $name => $value ) {
            if ( strpos( $name, 'wordpress_logged_in_' ) !== false ) return true;
            if ( strpos( $name, 'wp-settings-' ) !== false ) return true;
        }
        return false;
    }

    private function get_cache_file_path(): string {
        $path = strtok( $_SERVER['REQUEST_URI'], '?' );
        return $this->cache_dir . wp_hash( $path ) . '.html';
    }

    // ── Serve ──────────────────────────────────────────────────────────────────

    public function serve_cache() {
        if ( $this->should_bypass_cache() ) return;

        $cache_file = $this->get_cache_file_path();
        if ( file_exists( $cache_file ) ) {
            $mtime = filemtime( $cache_file );
            if ( time() - $mtime < 3600 ) {
                header( 'X-Illu-Cache: HIT' );
                readfile( $cache_file );
                exit;
            }
            @unlink( $cache_file );
        }
        header( 'X-Illu-Cache: MISS' );
    }

    // ── Write ──────────────────────────────────────────────────────────────────

    public function start_cache_buffer() {
        if ( $this->should_bypass_cache() || is_404() || is_search() ) return;

        if ( ! file_exists( $this->cache_dir ) ) {
            wp_mkdir_p( $this->cache_dir );
            file_put_contents( $this->cache_dir . 'index.php', '<?php // silence' );
            file_put_contents(
                $this->cache_dir . '.htaccess',
                "<Files *.html>\n    Header set Cache-Control \"public, max-age=3600\"\n</Files>\n<Files *.php>\n    Deny from all\n</Files>\n"
            );
        }

        ob_start( [ $this, 'cache_output_callback' ] );
    }

    public function cache_output_callback( string $buffer ): string {
        if ( empty( $buffer ) || http_response_code() !== 200 ) return $buffer;

        // Strip HTML comments (keep IE conditionals)
        $buffer = preg_replace( '/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $buffer );
        $buffer .= "\n<!-- Illu Shield Cache @ " . date( 'Y-m-d H:i:s' ) . " -->";

        @file_put_contents( $this->get_cache_file_path(), $buffer );
        return $buffer;
    }

    // ── Clear ──────────────────────────────────────────────────────────────────

    public function clear_cache() {
        if ( ! isset( $this->cache_dir ) || ! file_exists( $this->cache_dir ) ) return;
        $files = glob( $this->cache_dir . '*.html' );
        if ( $files ) {
            foreach ( $files as $file ) @unlink( $file );
        }
    }

    // ── Admin bar ─────────────────────────────────────────────────────────────

    public function admin_bar_cache_clear( $wp_admin_bar ) {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $wp_admin_bar->add_node( [
            'id'    => 'illu-cache-menu',
            'title' => '⚡ Illu Caches',
            'href'  => '#',
        ] );
        $wp_admin_bar->add_node( [
            'id'     => 'illu-clear-all',
            'parent' => 'illu-cache-menu',
            'title'  => 'Purge All Caches',
            'href'   => wp_nonce_url( admin_url( 'admin-post.php?action=illu_clear_all_caches' ), 'illu_clear_cache_nonce' ),
        ] );
        $wp_admin_bar->add_node( [
            'id'     => 'illu-clear-smart',
            'parent' => 'illu-cache-menu',
            'title'  => 'Clear Smart Cache',
            'href'   => wp_nonce_url( admin_url( 'admin-post.php?action=illu_clear_smart_cache' ), 'illu_clear_cache_nonce' ),
        ] );
        $wp_admin_bar->add_node( [
            'id'     => 'illu-clear-cf',
            'parent' => 'illu-cache-menu',
            'title'  => 'Purge Cloudflare',
            'href'   => wp_nonce_url( admin_url( 'admin-post.php?action=illu_clear_cf_cache' ), 'illu_clear_cache_nonce' ),
        ] );
    }

    public function handle_clear_cache() {
        if ( ! current_user_can( 'manage_options' ) || ! isset( $_REQUEST['action'] ) ) return;
        check_admin_referer( 'illu_clear_cache_nonce' );

        $action = sanitize_text_field( $_REQUEST['action'] );

        if ( in_array( $action, [ 'illu_clear_smart_cache', 'illu_clear_all_caches' ], true ) ) {
            $this->clear_cache();
        }
        if ( in_array( $action, [ 'illu_clear_cf_cache', 'illu_clear_all_caches' ], true ) ) {
            do_action( 'illu_shield_purge_cloudflare' );
        }
        if ( $action === 'illu_clear_all_caches' ) {
            do_action( 'illu_optimize_purge_redis' );
        }

        wp_safe_redirect( wp_get_referer() ?: admin_url() );
        exit;
    }
}
