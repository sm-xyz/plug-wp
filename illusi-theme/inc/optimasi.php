<?php
/**
 * illusi-theme/inc/optimasi.php
 * Performance optimizations di level theme.
 * (Jika illu-optimize aktif, beberapa di bawah ini duplikat — tidak harmful karena idempotent.)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Hapus emoji scripts (fallback, illu-optimize juga melakukan ini)
remove_action( 'wp_head',             'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles',     'print_emoji_styles' );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'admin_print_styles',  'print_emoji_styles' );
remove_filter( 'the_content_feed',    'wp_staticize_emoji' );
remove_filter( 'comment_text_rss',    'wp_staticize_emoji' );
remove_filter( 'wp_mail',             'wp_staticize_emoji_for_email' );

// View Transitions CSS hint
add_action( 'wp_head', function () {
    echo '<style>
        @view-transition { navigation: auto; }
        ::view-transition-old(root) { animation-duration: 0.2s; }
        ::view-transition-new(root) { animation-duration: 0.2s; }
    </style>' . "\n";
}, 5 );

// Dark mode detection (baca prefers-color-scheme, set cookie untuk SSR)
add_action( 'wp_head', function () {
    echo '<script>
    (function(){
        var d = document.documentElement;
        var pref = localStorage.getItem("illusi_dark");
        if (pref === "1" || (!pref && window.matchMedia("(prefers-color-scheme:dark)").matches)) {
            d.classList.add("dark");
            document.cookie = "illusi_dark=1;path=/;max-age=31536000;SameSite=Strict";
        } else {
            d.classList.remove("dark");
            document.cookie = "illusi_dark=0;path=/;max-age=31536000;SameSite=Strict";
        }
    })();
    </script>' . "\n";
}, 1 );

// Disable Gutenberg block assets yang tidak dipakai
add_action( 'wp_enqueue_scripts', function () {
    wp_dequeue_style( 'wp-block-library' );
    wp_dequeue_style( 'wp-block-library-theme' );
    wp_dequeue_style( 'classic-theme-styles' );
    wp_dequeue_style( 'global-styles' );
}, 100 );
