<?php
/**
 * Illu_SEO_Meta
 *
 * Single owner untuk semua output meta tag & JSON-LD.
 * Pada saat init, menghapus fungsi SEO bawaan illusi-theme agar tidak ada
 * double-output. illusi-theme tetap berfungsi normal jika plugin ini tidak aktif
 * karena theme memiliki fallback SEO sendiri di inc/seo.php.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Illu_SEO_Meta {

    public function __construct() {
        // Ambil alih SEO dari theme — documented soft-dependency
        add_action( 'init', [ $this, 'remove_theme_seo' ] );

        // Meta boxes di post editor
        add_action( 'add_meta_boxes', [ $this, 'add_seo_meta_box' ] );
        add_action( 'save_post',      [ $this, 'save_seo_meta' ] );

        // Output head & footer
        add_action( 'wp_head',   [ $this, 'render_meta_tags' ], 1 );
        add_action( 'wp_footer', [ $this, 'render_json_ld' ], 100 );

        // Bersihkan sitemap cache saat post disimpan
        add_action( 'save_post', [ $this, 'clear_sitemap_cache' ] );
    }

    // ── Theme override ─────────────────────────────────────────────────────────

    public function remove_theme_seo() {
        // illusi-theme/inc/seo.php mendaftarkan kedua fungsi ini
        remove_action( 'wp_head',   'illusi_seo_meta_tags', 1 );
        remove_action( 'wp_footer', 'illusi_seo_json_ld', 100 );
    }

    public function clear_sitemap_cache() {
        wp_cache_delete( 'illu_sitemap_xml', 'illu_seo' );
    }

    // ── Meta Box ───────────────────────────────────────────────────────────────

    public function add_seo_meta_box() {
        foreach ( [ 'post', 'page' ] as $screen ) {
            add_meta_box(
                'illu_seo_meta_box',
                'Illu-SEO Settings',
                [ $this, 'render_meta_box_html' ],
                $screen, 'normal', 'high'
            );
        }
    }

    public function render_meta_box_html( $post ) {
        wp_nonce_field( 'illu_seo_save_meta', 'illu_seo_meta_nonce' );

        $title       = get_post_meta( $post->ID, '_illu_seo_title',       true );
        $desc        = get_post_meta( $post->ID, '_illu_seo_desc',        true );
        $schema_type = get_post_meta( $post->ID, '_illu_seo_schema_type', true ) ?: 'Article';
        $noindex     = get_post_meta( $post->ID, '_illu_seo_noindex',     true );
        ?>
        <div style="margin-top:10px;">
            <label for="illu_seo_title" style="display:block;font-weight:bold;margin-bottom:5px;">SEO Title</label>
            <input type="text" id="illu_seo_title" name="illu_seo_title"
                   value="<?php echo esc_attr( $title ); ?>" style="width:100%;"
                   placeholder="Kosongkan untuk pakai judul post">
        </div>
        <div style="margin-top:10px;">
            <label for="illu_seo_desc" style="display:block;font-weight:bold;margin-bottom:5px;">Meta Description</label>
            <textarea id="illu_seo_desc" name="illu_seo_desc" style="width:100%;height:80px;"
                      placeholder="Kosongkan untuk auto-excerpt"><?php echo esc_textarea( $desc ); ?></textarea>
        </div>
        <div style="margin-top:10px;">
            <label for="illu_seo_schema_type" style="display:block;font-weight:bold;margin-bottom:5px;">Schema Type</label>
            <select id="illu_seo_schema_type" name="illu_seo_schema_type" style="width:100%;">
                <?php
                $types = [ 'Article', 'BlogPosting', 'NewsArticle', 'JobPosting', 'Review', 'Recipe', 'WebPage', 'FAQPage', 'HowTo' ];
                foreach ( $types as $type ) {
                    printf( '<option value="%1$s" %2$s>%1$s</option>', esc_attr( $type ), selected( $schema_type, $type, false ) );
                }
                ?>
            </select>
            <p style="font-size:12px;color:#666;margin-top:4px;">Membantu search engine & AI engine memahami struktur konten.</p>
        </div>
        <div style="margin-top:10px;">
            <label>
                <input type="checkbox" name="illu_seo_noindex" value="yes" <?php checked( $noindex, 'yes' ); ?>>
                NoIndex — cegah search engine mengindex halaman ini
            </label>
        </div>
        <?php
    }

    public function save_seo_meta( $post_id ) {
        if ( ! isset( $_POST['illu_seo_meta_nonce'] ) ||
             ! wp_verify_nonce( $_POST['illu_seo_meta_nonce'], 'illu_seo_save_meta' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        if ( isset( $_POST['illu_seo_title'] ) )
            update_post_meta( $post_id, '_illu_seo_title',       sanitize_text_field( $_POST['illu_seo_title'] ) );
        if ( isset( $_POST['illu_seo_desc'] ) )
            update_post_meta( $post_id, '_illu_seo_desc',        sanitize_textarea_field( $_POST['illu_seo_desc'] ) );
        if ( isset( $_POST['illu_seo_schema_type'] ) )
            update_post_meta( $post_id, '_illu_seo_schema_type', sanitize_text_field( $_POST['illu_seo_schema_type'] ) );

        update_post_meta( $post_id, '_illu_seo_noindex', isset( $_POST['illu_seo_noindex'] ) ? 'yes' : 'no' );
    }

    // ── Meta tag output ────────────────────────────────────────────────────────

    public function render_meta_tags() {
        $site_name = get_bloginfo( 'name' );

        if ( is_singular() ) {
            global $post;

            $custom_title = get_post_meta( $post->ID, '_illu_seo_title', true );
            $title        = $custom_title ?: get_the_title();

            $custom_desc  = get_post_meta( $post->ID, '_illu_seo_desc', true );
            $excerpt      = $custom_desc
                ? $custom_desc
                : ( has_excerpt() ? get_the_excerpt() : wp_trim_words( $post->post_content, 25, '...' ) );
            $excerpt      = esc_attr( strip_tags( strip_shortcodes( $excerpt ) ) );

            $url     = get_permalink();
            $image   = has_post_thumbnail() ? get_the_post_thumbnail_url( $post->ID, 'large' ) : '';
            $noindex = get_post_meta( $post->ID, '_illu_seo_noindex', true );

            echo $noindex === 'yes'
                ? '<meta name="robots" content="noindex, nofollow" />' . "\n"
                : '<meta name="robots" content="index, follow" />' . "\n";

            echo '<title>' . esc_html( $title . ' - ' . $site_name ) . '</title>' . "\n";
            echo '<link rel="canonical" href="' . esc_url( $url ) . '" />' . "\n";
            echo '<meta name="description" content="' . $excerpt . '" />' . "\n";

            // Open Graph
            echo '<meta property="og:title" content="'     . esc_attr( $title )     . '" />' . "\n";
            echo '<meta property="og:description" content="' . $excerpt             . '" />' . "\n";
            echo '<meta property="og:url" content="'       . esc_url( $url )        . '" />' . "\n";
            echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '" />' . "\n";
            echo '<meta property="og:type" content="article" />' . "\n";
            if ( $image ) echo '<meta property="og:image" content="' . esc_url( $image ) . '" />' . "\n";

            // Twitter Cards
            echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
            echo '<meta name="twitter:title" content="'       . esc_attr( $title ) . '" />' . "\n";
            echo '<meta name="twitter:description" content="' . $excerpt           . '" />' . "\n";
            if ( $image ) echo '<meta name="twitter:image" content="' . esc_url( $image ) . '" />' . "\n";

        } elseif ( is_front_page() || is_home() ) {
            $title = get_bloginfo( 'name' );
            $desc  = get_bloginfo( 'description' );
            $url   = home_url( '/' );

            echo '<title>' . esc_html( $title . ( $desc ? ' - ' . $desc : '' ) ) . '</title>' . "\n";
            echo '<link rel="canonical" href="' . esc_url( $url ) . '" />' . "\n";
            echo '<meta name="description" content="'       . esc_attr( $desc )  . '" />' . "\n";
            echo '<meta property="og:title" content="'      . esc_attr( $title ) . '" />' . "\n";
            echo '<meta property="og:description" content="'. esc_attr( $desc )  . '" />' . "\n";
            echo '<meta property="og:type" content="website" />' . "\n";
            echo '<meta property="og:url" content="'        . esc_url( $url )    . '" />' . "\n";
        }
    }

    // ── JSON-LD ────────────────────────────────────────────────────────────────

    public function render_json_ld() {
        if ( ! is_singular( 'post' ) ) return;

        global $post;
        $image       = has_post_thumbnail() ? get_the_post_thumbnail_url( $post->ID, 'large' ) : '';
        $author_name = get_the_author_meta( 'display_name', $post->post_author );
        $schema_type = get_post_meta( $post->ID, '_illu_seo_schema_type', true ) ?: 'BlogPosting';

        $schema = [
            '@context'        => 'https://schema.org',
            '@type'           => $schema_type,
            'mainEntityOfPage'=> [ '@type' => 'WebPage', '@id' => get_permalink() ],
            'headline'        => get_the_title(),
            'datePublished'   => get_the_date( 'c' ),
            'dateModified'    => get_the_modified_date( 'c' ),
            'author'          => [ '@type' => 'Person', 'name' => $author_name ],
            'publisher'       => [
                '@type' => 'Organization',
                'name'  => get_bloginfo( 'name' ),
                'logo'  => [ '@type' => 'ImageObject', 'url' => get_site_icon_url() ?: '' ],
            ],
        ];

        if ( $image ) $schema['image'] = [ $image ];

        echo '<script type="application/ld+json">'
            . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
            . '</script>' . "\n";
    }
}
