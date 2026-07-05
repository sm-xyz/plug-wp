<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Illu_Optimize_Async_Worker {

    private string $hook = 'illu_async_task';

    public function __construct() {
        add_action( $this->hook, [ $this, 'process_task' ] );
    }

    /**
     * Tambahkan task ke antrian async.
     * Task dieksekusi via WP Cron pada run berikutnya (ASAP).
     *
     * @param string $type  Tipe task ('webhook_retry', 'image_convert', 'purge_cache', dll.)
     * @param array  $args  Data yang dibutuhkan task.
     */
    public function dispatch( string $type, array $args = [] ) {
        wp_schedule_single_event( time(), $this->hook, [ [ 'type' => $type, 'args' => $args ] ] );

        // Trigger cron sekarang jika memungkinkan (non-blocking)
        if ( ! defined( 'DOING_CRON' ) ) {
            $this->trigger_cron_async();
        }
    }

    /**
     * Trigger WP-Cron secara non-blocking (fire-and-forget HTTP request).
     */
    private function trigger_cron_async() {
        wp_remote_get( site_url( '/wp-cron.php?doing_wp_cron' ), [
            'timeout'   => 0.01,
            'blocking'  => false,
            'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
        ] );
    }

    /**
     * Handler utama — semua tipe task diproses di sini.
     */
    public function process_task( array $task ) {
        $type = $task['type'] ?? '';
        $args = $task['args'] ?? [];

        switch ( $type ) {
            case 'purge_cache':
                do_action( 'illu_shield_clear_smart_cache' );
                do_action( 'illu_optimize_purge_redis' );
                break;

            case 'purge_cloudflare':
                do_action( 'illu_shield_purge_cloudflare' );
                break;

            case 'image_convert':
                if ( ! empty( $args['attachment_id'] ) ) {
                    $converter = new Illu_Media_Image_Converter();
                    $path      = get_attached_file( $args['attachment_id'] );
                    if ( $path ) {
                        $converter->convert_image( $path, 'webp' );
                        $converter->convert_image( $path, 'avif' );
                    }
                }
                break;

            case 'webhook_retry':
                // Retry kirim webhook ke URL tujuan
                if ( ! empty( $args['url'] ) && ! empty( $args['body'] ) ) {
                    wp_remote_post( esc_url_raw( $args['url'] ), [
                        'body'    => $args['body'],
                        'headers' => $args['headers'] ?? [ 'Content-Type' => 'application/json' ],
                        'timeout' => 15,
                    ] );
                }
                break;

            default:
                // Hook untuk task custom dari plugin lain
                do_action( 'illu_optimize_async_task_' . $type, $args );
                break;
        }
    }
}

/**
 * Helper global — dispatch task dari mana saja.
 *
 * Contoh:
 *   illu_dispatch_async('purge_cache');
 *   illu_dispatch_async('image_convert', ['attachment_id' => 123]);
 */
function illu_dispatch_async( string $type, array $args = [] ) {
    static $worker = null;
    if ( ! $worker ) $worker = new Illu_Optimize_Async_Worker();
    $worker->dispatch( $type, $args );
}
