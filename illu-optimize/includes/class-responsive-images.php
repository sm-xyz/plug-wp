<?php
/**
 * Illu_Media_Responsive_Images
 * Menambahkan srcset dan lazy-loading otomatis pada semua gambar.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Illu_Media_Responsive_Images {

    public function __construct() {
        $options = get_option( 'illu_optimize_settings', [] );
        if ( ( $options['responsive_images'] ?? 'yes' ) === 'yes' ) {
            add_filter( 'the_content',            [ $this, 'add_lazy_loading' ] );
            add_filter( 'wp_get_attachment_image_attributes', [ $this, 'add_attributes' ], 10, 3 );
            add_filter( 'max_srcset_image_width', [ $this, 'set_max_srcset_width' ] );
        }
    }

    public function add_lazy_loading( string $content ): string {
        return preg_replace_callback( '/<img([^>]+)>/i', function ( $m ) {
            $tag = $m[0];
            if ( strpos( $tag, 'loading=' ) === false ) {
                $tag = str_replace( '<img', '<img loading="lazy" decoding="async"', $tag );
            }
            return $tag;
        }, $content );
    }

    public function add_attributes( array $attr, $attachment, $size ): array {
        $attr['loading']  = 'lazy';
        $attr['decoding'] = 'async';
        return $attr;
    }

    public function set_max_srcset_width(): int {
        return 1920;
    }
}
