<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Illu_Optimize_Clean_Header {

    public function __construct() {
        add_action( 'init',     [ $this, 'clean_wp_head' ] );
        add_action( 'wp_head',  [ $this, 'add_performance_meta' ], 1 );
        add_filter( 'wp_headers', [ $this, 'add_cache_headers' ] );
    }

    public function clean_wp_head() {
        // Remove unnecessary header items
        remove_action( 'wp_head', 'rsd_link' );
        remove_action( 'wp_head', 'wlwmanifest_link' );
        remove_action( 'wp_head', 'wp_shortlink_wp_head' );
        remove_action( 'wp_head', 'rest_output_link_wp_head' );
        remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
        remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
        remove_action( 'wp_print_styles', 'print_emoji_styles' );
        remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
        remove_action( 'admin_print_styles',  'print_emoji_styles' );

        // Feed links — optional (only remove if not blog)
        $options = get_option( 'illu_optimize_settings', [] );
        if ( ( $options['remove_feed_links'] ?? 'no' ) === 'yes' ) {
            remove_action( 'wp_head', 'feed_links',       2 );
            remove_action( 'wp_head', 'feed_links_extra', 3 );
        }

        // Block REST API user listing
        remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
        remove_action( 'template_redirect', 'rest_output_link_header', 11 );
    }

    public function add_performance_meta() {
        echo '<meta http-equiv="x-dns-prefetch-control" content="on">' . "\n";
        echo '<meta name="format-detection" content="telephone=no">' . "\n";
    }

    public function add_cache_headers( array $headers ): array {
        if ( is_user_logged_in() || is_admin() ) return $headers;

        // Static content: 1 hour browser cache
        $headers['Cache-Control'] = 'public, max-age=3600, s-maxage=3600';
        $headers['Vary']          = 'Accept-Encoding';

        return $headers;
    }
}
