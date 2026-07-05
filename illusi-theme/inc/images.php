<?php
/**
 * illusi-theme/inc/images.php
 * Gambar & media helpers.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Tambahkan srcset support (fallback jika illu-optimize tidak aktif)
add_filter( 'wp_get_attachment_image_attributes', function( $attr ) {
    if ( ! isset( $attr['loading'] ) ) $attr['loading'] = 'lazy';
    if ( ! isset( $attr['decoding'] ) ) $attr['decoding'] = 'async';
    return $attr;
} );

// SVG support untuk upload (hanya admin)
add_filter( 'upload_mimes', function( $mimes ) {
    if ( current_user_can( 'manage_options' ) ) {
        $mimes['svg']  = 'image/svg+xml';
        $mimes['svgz'] = 'image/svg+xml';
    }
    return $mimes;
} );

// Sanitasi SVG saat upload (cegah XSS di SVG)
add_filter( 'wp_handle_upload_prefilter', function( $file ) {
    if ( $file['type'] === 'image/svg+xml' ) {
        $content = file_get_contents( $file['tmp_name'] );
        // Reject SVGs dengan inline script
        if ( preg_match( '/<script/i', $content ) || preg_match( '/javascript:/i', $content ) ) {
            $file['error'] = 'SVG mengandung script — upload ditolak demi keamanan.';
        }
    }
    return $file;
} );

/**
 * Render gambar featured dengan aspek rasio aman.
 */
function illusi_featured_image( int $post_id = 0, string $size = 'illusi-card', string $class = '' ): string {
    if ( ! $post_id ) $post_id = get_the_ID();
    if ( ! has_post_thumbnail( $post_id ) ) return '';

    $html  = get_the_post_thumbnail( $post_id, $size, [
        'class'   => 'illusi-featured-img w-full h-auto object-cover ' . sanitize_html_class($class),
        'loading' => 'lazy',
        'decoding'=> 'async',
        'alt'     => esc_attr( get_the_title($post_id) ),
    ] );
    return $html;
}
