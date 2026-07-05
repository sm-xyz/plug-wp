<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Illu_Optimize_Assets_Injector {

    public function __construct() {
        $options = get_option( 'illu_optimize_settings', [] );

        if ( ( $options['defer_js'] ?? 'yes' ) === 'yes' ) {
            add_filter( 'script_loader_tag', [ $this, 'defer_scripts' ], 10, 3 );
        }
        if ( ( $options['preload_assets'] ?? 'yes' ) === 'yes' ) {
            add_action( 'wp_head', [ $this, 'add_preload_hints' ], 2 );
        }

        add_action( 'wp_head', [ $this, 'add_dns_prefetch' ], 1 );
    }

    public function defer_scripts( string $tag, string $handle, string $src ): string {
        // Jangan defer scripts yang critical atau milik WP/admin/jQuery
        $no_defer = [
            'jquery', 'jquery-core', 'jquery-migrate', 'admin-bar',
            'wp-polyfill', 'regenerator-runtime',
        ];
        if ( in_array( $handle, $no_defer, true ) || is_admin() ) return $tag;

        if ( strpos( $tag, ' defer' ) !== false || strpos( $tag, ' async' ) !== false ) return $tag;

        return str_replace( '<script ', '<script defer ', $tag );
    }

    public function add_preload_hints() {
        global $wp_styles;
        if ( empty( $wp_styles->queue ) ) return;

        foreach ( $wp_styles->queue as $handle ) {
            $style = $wp_styles->registered[ $handle ] ?? null;
            if ( ! $style || ! $style->src ) continue;
            $src = $style->src;
            if ( strpos( $src, 'fonts.googleapis.com' ) !== false ||
                 strpos( $src, 'fonts.gstatic.com' ) !== false ) {
                echo '<link rel="preload" href="' . esc_url( $src ) . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">' . "\n";
                echo '<noscript><link rel="stylesheet" href="' . esc_url( $src ) . '"></noscript>' . "\n";
            }
        }

        // Preload hero image if front page
        if ( is_front_page() ) {
            $attachment_id = get_theme_mod( 'header_image_data' );
            if ( $attachment_id && is_array( $attachment_id ) ) {
                $img = $attachment_id['url'] ?? '';
                if ( $img ) echo '<link rel="preload" as="image" href="' . esc_url( $img ) . '">' . "\n";
            }
        }
    }

    public function add_dns_prefetch() {
        $origins = apply_filters( 'illu_optimize_dns_prefetch_origins', [
            'https://fonts.googleapis.com',
            'https://fonts.gstatic.com',
            'https://cdnjs.cloudflare.com',
            'https://unpkg.com',
        ] );

        foreach ( $origins as $origin ) {
            echo '<link rel="dns-prefetch" href="' . esc_url( $origin ) . '">' . "\n";
            echo '<link rel="preconnect" href="' . esc_url( $origin ) . '" crossorigin>' . "\n";
        }
    }
}
