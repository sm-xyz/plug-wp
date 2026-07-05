<?php
/**
 * Illu_Shield_Request_Context
 *
 * Single source of truth untuk:
 *   - mendeteksi apakah request saat ini adalah sharelink/canvas-app/webhook path
 *     yang HARUS dibypass dari semua proteksi keamanan.
 *   - memudahkan penambahan path baru (cukup update 1 tempat ini).
 *
 * CARA EXTEND: tambahkan path prefix ke filter 'illu_shield_whitelisted_paths'
 * contoh dari plugin lain:
 *   add_filter('illu_shield_whitelisted_paths', function($paths){
 *       $paths[] = '/wp-json/my-plugin/v1/';
 *       return $paths;
 *   });
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Illu_Shield_Request_Context {

    /** @var bool|null memoized result */
    private static $is_whitelisted = null;

    /**
     * Path prefix yang selalu dibypass dari SEMUA security check.
     * Consolidates the formerly 5-times-duplicated block.
     */
    private static function whitelisted_path_prefixes(): array {
        $defaults = [
            '/wp-json/sharelink/',
            '/wp-json/canvas-app/',
            '/wp-json/webhook/',
            '/webhook',
        ];
        // Query-string style REST juga harus dicek
        $query_defaults = [
            'rest_route=/sharelink/',
            'rest_route=/canvas-app/',
        ];

        return apply_filters( 'illu_shield_whitelisted_paths', array_merge( $defaults, $query_defaults ) );
    }

    /**
     * Apakah REQUEST_URI saat ini masuk whitelist sharelink/webhook?
     * Memoized per-request (satu kali hitung, hasil disimpan statik).
     */
    public static function is_whitelisted(): bool {
        if ( self::$is_whitelisted !== null ) {
            return self::$is_whitelisted;
        }

        $raw_uri = $_SERVER['REQUEST_URI'] ?? '';

        foreach ( self::whitelisted_path_prefixes() as $prefix ) {
            if ( strpos( $raw_uri, $prefix ) !== false ) {
                self::$is_whitelisted = true;
                return true;
            }
        }

        self::$is_whitelisted = false;
        return false;
    }

    /**
     * Reset memoized value — berguna untuk unit testing.
     */
    public static function reset(): void {
        self::$is_whitelisted = null;
    }
}
