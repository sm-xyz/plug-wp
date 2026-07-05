<?php
/**
 * Template Name: Canvas (AI App)
 * Template Post Type: post, page
 *
 * Full-screen template untuk render HTML canvas dari Gemini/AI.
 * Tidak ada wp_head/wp_footer — output murni HTML dari post content.
 * Keamanan: sharelink-wp sudah memvalidasi lisensi sebelum request sampai sini.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Izinkan hanya saat viewing konten publik yang valid
if ( ! is_singular() ) {
    wp_redirect( home_url() );
    exit;
}

// Ambil konten — sharelink-wp mungkin menyimpan sebagai base64
$content = get_post_field( 'post_content', get_the_ID() );

// Decode base64 jika dipakai (pola sharelink-wp)
if ( base64_encode( base64_decode( $content, true ) ) === $content ) {
    $content = base64_decode( $content );
}

// Strip wpautop & texturize yang bisa merusak HTML/CSS Tailwind
remove_filter( 'the_content', 'wpautop' );
remove_filter( 'the_content', 'wptexturize' );

// Output HTML mentah
echo $content;
exit;
