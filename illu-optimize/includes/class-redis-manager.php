<?php
/**
 * Illu_Optimize_Redis_Manager
 *
 * Mengelola Redis object cache drop-in (wp-content/object-cache.php).
 *
 * SOFT DEPENDENCY: Admin bar node Redis ditambahkan sebagai child dari
 * 'illu-cache-menu' yang dibuat oleh illu-shield/class-cache.php.
 * Jika illu-shield tidak aktif, parent node tidak ada → node ini
 * tidak muncul di UI, tapi TIDAK menyebabkan PHP error.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Illu_Optimize_Redis_Manager {

    private bool $redis_active;
    private ?object $redis_client = null;

    public function __construct() {
        $this->redis_active = $this->is_redis_active();

        // Admin bar — child dari illu-shield parent node
        add_action( 'admin_bar_menu', [ $this, 'admin_bar_cache_clear' ], 110 );

        // Purge hook — bisa dipanggil dari mana saja (illu-optimize, illu-shield, dll.)
        add_action( 'illu_optimize_purge_redis', [ $this, 'flush_redis' ] );

        // Schedule check
        add_action( 'admin_init', [ $this, 'maybe_reschedule_purge' ] );
    }

    // ── Status ─────────────────────────────────────────────────────────────────

    public function is_redis_active(): bool {
        return defined( 'WP_REDIS_HOST' ) && extension_loaded( 'redis' );
    }

    public function get_redis_client(): ?object {
        if ( $this->redis_client ) return $this->redis_client;
        if ( ! $this->redis_active ) return null;

        try {
            $redis = new \Redis();
            $host  = defined( 'WP_REDIS_HOST' )     ? WP_REDIS_HOST     : '127.0.0.1';
            $port  = defined( 'WP_REDIS_PORT' )     ? WP_REDIS_PORT     : 6379;
            $pass  = defined( 'WP_REDIS_PASSWORD' ) ? WP_REDIS_PASSWORD : '';
            $db    = defined( 'WP_REDIS_DATABASE' ) ? WP_REDIS_DATABASE : 0;

            $redis->connect( $host, $port, 2.0 );
            if ( $pass ) $redis->auth( $pass );
            $redis->select( $db );
            $this->redis_client = $redis;
        } catch ( \Exception $e ) {
            $this->redis_client = null;
        }
        return $this->redis_client;
    }

    // ── Drop-in install / uninstall ────────────────────────────────────────────

    public static function install_object_cache() {
        $source = ILLU_OPTIMIZE_DIR . 'dropins/object-cache.php';
        $dest   = WP_CONTENT_DIR . '/object-cache.php';

        if ( file_exists( $source ) && ! file_exists( $dest ) ) {
            @copy( $source, $dest );
        }
    }

    public static function uninstall_object_cache() {
        $dest = WP_CONTENT_DIR . '/object-cache.php';
        if ( file_exists( $dest ) ) {
            $content = file_get_contents( $dest );
            if ( strpos( $content, 'Illu' ) !== false ) {
                @unlink( $dest );
            }
        }
    }

    // ── Flush ──────────────────────────────────────────────────────────────────

    public function flush_redis() {
        wp_cache_flush();

        $redis = $this->get_redis_client();
        if ( $redis ) {
            try {
                $prefix = defined( 'WP_CACHE_KEY_SALT' ) ? WP_CACHE_KEY_SALT : '';
                if ( $prefix ) {
                    $keys = $redis->keys( $prefix . '*' );
                    if ( ! empty( $keys ) ) $redis->del( $keys );
                } else {
                    $redis->flushDb();
                }
            } catch ( \Exception $e ) {
                // Redis flush gagal — tidak fatal
            }
        }
    }

    // ── Schedule helper ────────────────────────────────────────────────────────

    public function maybe_reschedule_purge() {
        $schedule = get_option( 'illu_auto_purge_schedule', 'twicedaily' );
        $hook     = 'illu_optimize_auto_purge';

        $current = wp_get_schedule( $hook );
        if ( $schedule === 'never' ) {
            if ( $current ) wp_clear_scheduled_hook( $hook );
            return;
        }
        if ( $current !== $schedule ) {
            wp_clear_scheduled_hook( $hook );
            wp_schedule_event( time(), $schedule, $hook );
        }
    }

    // ── Admin bar ─────────────────────────────────────────────────────────────
    /**
     * Menambahkan node Redis ke dalam parent 'illu-cache-menu'.
     * Parent ini dibuat oleh Illu_Shield_Cache::admin_bar_cache_clear() (priority 100).
     * Kita daftar di priority 110 agar parent sudah pasti ada saat illu-shield aktif.
     * Jika parent tidak ada (illu-shield nonaktif), WP akan menampilkan node ini
     * sebagai top-level item sendiri — tidak akan error, hanya posisi berbeda.
     */
    public function admin_bar_cache_clear( $wp_admin_bar ) {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $status = $this->redis_active ? '🟢 Redis' : '🔴 Redis (Nonaktif)';
        $wp_admin_bar->add_node( [
            'id'     => 'illu-clear-redis',
            'parent' => 'illu-cache-menu',
            'title'  => 'Purge Redis Cache',
            'href'   => wp_nonce_url( admin_url( 'admin-post.php?action=illu_clear_all_caches' ), 'illu_clear_cache_nonce' ),
            'meta'   => [ 'title' => $status ],
        ] );
    }

    // ── Status info untuk admin page ───────────────────────────────────────────

    public function get_status_info(): array {
        $redis = $this->get_redis_client();

        if ( ! $this->redis_active ) {
            return [ 'status' => 'unavailable', 'message' => 'Redis extension tidak tersedia atau WP_REDIS_HOST tidak terdefinisi.' ];
        }
        if ( ! $redis ) {
            return [ 'status' => 'error', 'message' => 'Gagal terhubung ke Redis server.' ];
        }

        try {
            $info   = $redis->info();
            $memory = $info['used_memory_human']   ?? 'N/A';
            $uptime = $info['uptime_in_days']       ?? 'N/A';
            $keys   = $redis->dbSize();
            return [
                'status'  => 'connected',
                'memory'  => $memory,
                'uptime'  => $uptime . ' hari',
                'keys'    => $keys,
                'version' => $info['redis_version'] ?? 'N/A',
            ];
        } catch ( \Exception $e ) {
            return [ 'status' => 'error', 'message' => $e->getMessage() ];
        }
    }
}
