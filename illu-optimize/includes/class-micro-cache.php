<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Illu_Optimize_Micro_Cache
 *
 * Cache REST API GET responses dengan TTL pendek.
 *
 * PENTING: Hanya cache GET request. POST/PUT/DELETE TIDAK pernah di-cache.
 * Sharelink & webhook endpoints di-bypass via Illu_Shield_Request_Context
 * jika illu-shield aktif, atau via internal path check jika tidak aktif.
 */
class Illu_Optimize_Micro_Cache {

    public function __construct() {
        $options = get_option( 'illu_optimize_settings', [] );
        if ( ( $options['micro_cache'] ?? 'no' ) !== 'yes' ) return;

        add_filter( 'rest_pre_dispatch', [ $this, 'serve_from_cache' ], 10, 3 );
        add_filter( 'rest_post_dispatch', [ $this, 'store_to_cache' ], 10, 3 );
    }

    /**
     * Apakah request ini boleh di-cache?
     * Prinsip: hanya GET, tidak ada credentials, bukan sharelink/webhook/admin.
     */
    private function is_cacheable( WP_REST_Request $request ): bool {
        if ( $request->get_method() !== 'GET' ) return false;
        if ( is_user_logged_in() ) return false;

        $route = $request->get_route();

        // Selalu bypass sharelink & canvas-app endpoints
        $bypass_prefixes = [
            '/sharelink/',
            '/canvas-app/',
            '/webhook',
            '/illu-shield/',
        ];
        foreach ( $bypass_prefixes as $prefix ) {
            if ( strpos( $route, $prefix ) !== false ) return false;
        }

        // Jika illu-shield tersedia, pakai centralized check
        if ( class_exists( 'Illu_Shield_Request_Context' ) &&
             Illu_Shield_Request_Context::is_whitelisted() ) {
            return false;
        }

        return true;
    }

    private function get_cache_key( WP_REST_Request $request ): string {
        return 'illu_mc_' . md5( $request->get_route() . serialize( $request->get_query_params() ) );
    }

    public function serve_from_cache( $result, $server, WP_REST_Request $request ) {
        if ( ! $this->is_cacheable( $request ) ) return $result;

        $cached = get_transient( $this->get_cache_key( $request ) );
        return $cached !== false ? $cached : $result;
    }

    public function store_to_cache( $response, $server, WP_REST_Request $request ) {
        if ( ! $this->is_cacheable( $request ) ) return $response;
        if ( $response->get_status() !== 200 ) return $response;

        $options = get_option( 'illu_optimize_settings', [] );
        $ttl     = intval( $options['micro_cache_ttl'] ?? 60 );

        set_transient( $this->get_cache_key( $request ), $response, $ttl );
        return $response;
    }
}
