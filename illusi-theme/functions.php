<?php
/**
 * Illusi Theme — functions.php
 * Version: 1.0.3
 *
 * DEPENDENCY NOTES:
 *   - SEO meta tags (inc/seo.php) akan di-remove oleh illu-optimize/class-meta.php
 *     jika illu-optimize aktif. Ini DISENGAJA — illu-optimize adalah SEO owner.
 *     Jika illu-optimize tidak aktif, theme tetap output meta sendiri sebagai fallback.
 *   - Tidak ada dependency ke sharelink-wp, illu-shield, atau plugin lain.
 *     Theme berdiri sendiri, plugin bebas extend via hook.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'ILLUSI_THEME_VERSION', '1.0.3' );
define( 'ILLUSI_THEME_DIR',     get_template_directory() );
define( 'ILLUSI_THEME_URL',     get_template_directory_uri() );

// ── Includes ───────────────────────────────────────────────────────────────────
require_once ILLUSI_THEME_DIR . '/inc/seo.php';         // Fallback SEO jika illu-optimize tidak aktif
require_once ILLUSI_THEME_DIR . '/inc/images.php';
require_once ILLUSI_THEME_DIR . '/inc/optimasi.php';
require_once ILLUSI_THEME_DIR . '/inc/admin-options.php';

// ── Theme Setup ────────────────────────────────────────────────────────────────
function illusi_theme_setup() {
    load_theme_textdomain( 'illusi', get_template_directory() . '/languages' );

    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', [ 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption', 'script', 'style' ] );
    add_theme_support( 'custom-logo', [ 'height' => 60, 'width' => 200, 'flex-height' => true, 'flex-width' => true ] );
    add_theme_support( 'customize-selective-refresh-widgets' );
    add_theme_support( 'wp-block-styles' );
    add_theme_support( 'align-wide' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'editor-color-palette', [] ); // Disable WP default palette
    add_theme_support( 'dark-editor-style' );

    // Navigation menus
    register_nav_menus( [
        'primary'  => 'Menu Utama',
        'footer'   => 'Footer Menu',
        'mobile'   => 'Mobile Menu',
    ] );

    // Default thumbnail size
    set_post_thumbnail_size( 1200, 630, true );
    add_image_size( 'illusi-card',   600, 400, true );
    add_image_size( 'illusi-wide',   1920, 600, true );
    add_image_size( 'illusi-square', 400,  400, true );
}
add_action( 'after_setup_theme', 'illusi_theme_setup' );

// ── Assets ─────────────────────────────────────────────────────────────────────
function illusi_enqueue_assets() {
    $theme_url = get_template_directory_uri();
    $ver       = ILLUSI_THEME_VERSION;

    // Main stylesheet
    wp_enqueue_style( 'illusi-main', $theme_url . '/assets/css/theme.min.css', [], $ver );

    // Main JS (deferred by illu-optimize, manual defer here as fallback)
    wp_enqueue_script( 'illusi-theme', $theme_url . '/assets/js/theme.min.js', [], $ver, true );

    // Comment reply
    if ( is_singular() && comments_open() ) {
        wp_enqueue_script( 'comment-reply' );
    }
}
add_action( 'wp_enqueue_scripts', 'illusi_enqueue_assets' );

// Editor stylesheet
function illusi_editor_styles() {
    add_editor_style( 'assets/css/editor.min.css' );
}
add_action( 'admin_init', 'illusi_editor_styles' );

// ── Widgets ────────────────────────────────────────────────────────────────────
function illusi_widgets_init() {
    $default_args = [
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
    ];

    register_sidebar( array_merge( $default_args, [ 'id' => 'sidebar-main', 'name' => 'Sidebar Utama' ] ) );
    register_sidebar( array_merge( $default_args, [ 'id' => 'footer-1',     'name' => 'Footer Widget 1' ] ) );
    register_sidebar( array_merge( $default_args, [ 'id' => 'footer-2',     'name' => 'Footer Widget 2' ] ) );
    register_sidebar( array_merge( $default_args, [ 'id' => 'footer-3',     'name' => 'Footer Widget 3' ] ) );
}
add_action( 'widgets_init', 'illusi_widgets_init' );

// ── Content width ──────────────────────────────────────────────────────────────
if ( ! isset( $content_width ) ) $content_width = 1200;

// ── Nav walker helper ──────────────────────────────────────────────────────────
function illusi_nav_menu_link_attributes( $atts, $item, $args, $depth ) {
    // Add base Tailwind classes to all nav links for consistent styling
    $classes = 'nav-link';
    if ( $item->current ) $classes .= ' active';
    $atts['class'] = $classes;
    return $atts;
}
add_filter( 'nav_menu_link_attributes', 'illusi_nav_menu_link_attributes', 10, 4 );

// ── Excerpt ────────────────────────────────────────────────────────────────────
add_filter( 'excerpt_length', fn() => 25 );
add_filter( 'excerpt_more',   fn() => '...' );

// ── Body classes ───────────────────────────────────────────────────────────────
function illusi_body_classes( array $classes ): array {
    if ( is_singular() ) $classes[] = 'singular';
    if ( is_home() || is_archive() ) $classes[] = 'blog-view';
    // Dark mode preference via cookie (set by JS)
    if ( isset( $_COOKIE['illusi_dark'] ) && $_COOKIE['illusi_dark'] === '1' ) {
        $classes[] = 'dark';
    }
    return $classes;
}
add_filter( 'body_class', 'illusi_body_classes' );

// ── Pagination ─────────────────────────────────────────────────────────────────
function illusi_pagination() {
    $args = [
        'prev_text' => '&larr; Sebelumnya',
        'next_text' => 'Berikutnya &rarr;',
        'mid_size'  => 2,
    ];
    the_posts_pagination( $args );
}

// ── Reading time ───────────────────────────────────────────────────────────────
function illusi_reading_time( int $post_id = 0 ): string {
    if ( ! $post_id ) $post_id = get_the_ID();
    $content = get_post_field( 'post_content', $post_id );
    $words   = str_word_count( strip_tags( $content ) );
    $minutes = max( 1, (int) ceil( $words / 200 ) );
    return $minutes . ' menit baca';
}

// ── Breadcrumbs ────────────────────────────────────────────────────────────────
function illusi_breadcrumbs() {
    if ( is_front_page() ) return;

    $items = [];
    $items[] = '<a href="' . home_url() . '">Home</a>';

    if ( is_category() || is_single() ) {
        $categories = get_the_category();
        if ( $categories ) {
            $cat = $categories[0];
            $items[] = '<a href="' . get_category_link($cat->term_id) . '">' . esc_html($cat->name) . '</a>';
        }
    }
    if ( is_singular() || is_page() ) {
        $items[] = '<span>' . get_the_title() . '</span>';
    } elseif ( is_category() ) {
        $items[] = '<span>' . single_cat_title('',false) . '</span>';
    } elseif ( is_search() ) {
        $items[] = '<span>Hasil: "' . get_search_query() . '"</span>';
    } elseif ( is_archive() ) {
        $items[] = '<span>' . get_the_archive_title() . '</span>';
    } elseif ( is_404() ) {
        $items[] = '<span>404 Not Found</span>';
    }

    echo '<nav class="illusi-breadcrumbs" aria-label="Breadcrumb">';
    echo implode( ' <span aria-hidden="true">/</span> ', $items );
    echo '</nav>';
}

// ── View Transitions polyfill hint ────────────────────────────────────────────
function illusi_view_transition_meta() {
    echo '<meta name="view-transition" content="same-origin">' . "\n";
}
add_action( 'wp_head', 'illusi_view_transition_meta', 1 );

// ── Block editor cleanup ───────────────────────────────────────────────────────
function illusi_dequeue_block_styles() {
    wp_dequeue_style( 'wp-block-library' );
    wp_dequeue_style( 'wp-block-library-theme' );
    wp_dequeue_style( 'classic-theme-styles' );
    wp_dequeue_style( 'global-styles' );
}
add_action( 'wp_enqueue_scripts', 'illusi_dequeue_block_styles', 100 );

// ── Admin: remove unneeded menus for non-admin roles ──────────────────────────
// (Dikelola oleh inc/admin-options.php)
