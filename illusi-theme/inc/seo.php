<?php
/**
 * illusi-theme/inc/seo.php
 *
 * FALLBACK SEO — hanya aktif jika illu-optimize TIDAK aktif.
 * Jika illu-optimize aktif, ia akan memanggil:
 *   remove_action('wp_head',   'illusi_seo_meta_tags', 1)
 *   remove_action('wp_footer', 'illusi_seo_json_ld',  100)
 * sehingga fungsi di file ini tidak pernah di-output dua kali.
 *
 * Desain: fungsi named (bukan closure) agar remove_action bisa bekerja.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Daftar ke hook — illu-optimize akan remove jika aktif
add_action( 'wp_head',   'illusi_seo_meta_tags', 1 );
add_action( 'wp_footer', 'illusi_seo_json_ld',  100 );

function illusi_seo_meta_tags() {
    $site_name = get_bloginfo( 'name' );

    if ( is_singular() ) {
        global $post;
        $title   = get_the_title();
        $excerpt = has_excerpt() ? get_the_excerpt() : wp_trim_words( $post->post_content, 25, '...' );
        $excerpt = esc_attr( strip_tags( strip_shortcodes( $excerpt ) ) );
        $url     = get_permalink();
        $image   = has_post_thumbnail() ? get_the_post_thumbnail_url( $post->ID, 'large' ) : '';

        echo '<title>' . esc_html( $title . ' - ' . $site_name ) . '</title>' . "\n";
        echo '<meta name="robots" content="index, follow" />' . "\n";
        echo '<link rel="canonical" href="' . esc_url($url) . '" />' . "\n";
        echo '<meta name="description" content="' . $excerpt . '" />' . "\n";
        echo '<meta property="og:title" content="'      . esc_attr($title) . '" />' . "\n";
        echo '<meta property="og:description" content="' . $excerpt . '" />' . "\n";
        echo '<meta property="og:url" content="'       . esc_url($url) . '" />' . "\n";
        echo '<meta property="og:type" content="article" />' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '" />' . "\n";
        if ( $image ) echo '<meta property="og:image" content="' . esc_url($image) . '" />' . "\n";
        echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
        echo '<meta name="twitter:title" content="'       . esc_attr($title) . '" />' . "\n";
        echo '<meta name="twitter:description" content="' . $excerpt . '" />' . "\n";
        if ( $image ) echo '<meta name="twitter:image" content="' . esc_url($image) . '" />' . "\n";

    } elseif ( is_front_page() || is_home() ) {
        $title = get_bloginfo('name');
        $desc  = get_bloginfo('description');
        echo '<title>' . esc_html($title . ($desc ? ' - '.$desc : '')) . '</title>' . "\n";
        echo '<meta name="description" content="' . esc_attr($desc) . '" />' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($title) . '" />' . "\n";
        echo '<meta property="og:type" content="website" />' . "\n";
        echo '<meta property="og:url" content="'  . esc_url(home_url('/')) . '" />' . "\n";
    }
}

function illusi_seo_json_ld() {
    if ( ! is_singular('post') ) return;
    global $post;
    $author = get_the_author_meta('display_name', $post->post_author);
    $image  = has_post_thumbnail() ? get_the_post_thumbnail_url($post->ID,'large') : '';
    $schema = [
        '@context'         => 'https://schema.org',
        '@type'            => 'BlogPosting',
        'mainEntityOfPage' => ['@type'=>'WebPage','@id'=>get_permalink()],
        'headline'         => get_the_title(),
        'datePublished'    => get_the_date('c'),
        'dateModified'     => get_the_modified_date('c'),
        'author'           => ['@type'=>'Person','name'=>$author],
        'publisher'        => ['@type'=>'Organization','name'=>get_bloginfo('name'),'logo'=>['@type'=>'ImageObject','url'=>get_site_icon_url()?:'']],
    ];
    if ($image) $schema['image'] = [$image];
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}
