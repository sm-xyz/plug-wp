<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Illu_SEO_Sitemap {

    public function __construct() {
        add_action( 'init',      [ __CLASS__, 'add_rewrite_rules' ] );
        add_action( 'template_redirect', [ $this, 'serve_sitemap' ] );
        add_action( 'save_post', [ $this, 'clear_sitemap_cache' ] );
    }

    public static function add_rewrite_rules() {
        add_rewrite_rule( '^sitemap\.xml$',         'index.php?illu_sitemap=1',        'top' );
        add_rewrite_rule( '^sitemap-posts\.xml$',   'index.php?illu_sitemap=posts',    'top' );
        add_rewrite_rule( '^sitemap-pages\.xml$',   'index.php?illu_sitemap=pages',    'top' );
        add_rewrite_rule( '^sitemap-category\.xml$','index.php?illu_sitemap=category', 'top' );
        add_filter( 'query_vars', fn( $vars ) => array_merge( $vars, [ 'illu_sitemap' ] ) );
    }

    public function serve_sitemap() {
        $sitemap_type = get_query_var( 'illu_sitemap' );
        if ( ! $sitemap_type ) return;

        $cache_key = 'illu_sitemap_xml_' . $sitemap_type;
        $xml       = wp_cache_get( $cache_key, 'illu_seo' );

        if ( false === $xml ) {
            $xml = $sitemap_type === '1'
                ? $this->generate_sitemap_index()
                : $this->generate_sitemap_section( $sitemap_type );
            wp_cache_set( $cache_key, $xml, 'illu_seo', HOUR_IN_SECONDS * 12 );
        }

        header( 'Content-Type: application/xml; charset=UTF-8' );
        header( 'X-Robots-Tag: noindex, follow' );
        echo $xml;
        exit;
    }

    private function generate_sitemap_index(): string {
        $base = home_url();
        $date = date( 'Y-m-d' );
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<?xml-stylesheet type="text/xsl" href="{$base}/wp-content/plugins/illu-optimize/assets/sitemap.xsl"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <sitemap><loc>{$base}/sitemap-posts.xml</loc><lastmod>{$date}</lastmod></sitemap>
  <sitemap><loc>{$base}/sitemap-pages.xml</loc><lastmod>{$date}</lastmod></sitemap>
  <sitemap><loc>{$base}/sitemap-category.xml</loc><lastmod>{$date}</lastmod></sitemap>
</sitemapindex>
XML;
    }

    private function generate_sitemap_section( string $type ): string {
        $items = [];

        if ( $type === 'posts' ) {
            $posts = get_posts( [ 'numberposts' => 500, 'post_status' => 'publish' ] );
            foreach ( $posts as $p ) {
                $noindex = get_post_meta( $p->ID, '_illu_seo_noindex', true );
                if ( $noindex === 'yes' ) continue;
                $items[] = [
                    'loc'     => get_permalink( $p ),
                    'lastmod' => get_the_modified_date( 'Y-m-d', $p ),
                    'freq'    => 'weekly',
                    'prio'    => '0.8',
                ];
            }
        } elseif ( $type === 'pages' ) {
            $pages = get_posts( [ 'post_type' => 'page', 'numberposts' => 200, 'post_status' => 'publish' ] );
            foreach ( $pages as $p ) {
                $noindex = get_post_meta( $p->ID, '_illu_seo_noindex', true );
                if ( $noindex === 'yes' ) continue;
                $prio = ( $p->ID === intval( get_option( 'page_on_front' ) ) ) ? '1.0' : '0.7';
                $items[] = [
                    'loc'     => get_permalink( $p ),
                    'lastmod' => get_the_modified_date( 'Y-m-d', $p ),
                    'freq'    => 'monthly',
                    'prio'    => $prio,
                ];
            }
        } elseif ( $type === 'category' ) {
            $cats = get_categories( [ 'hide_empty' => true ] );
            foreach ( $cats as $cat ) {
                $items[] = [
                    'loc'     => get_category_link( $cat->term_id ),
                    'lastmod' => date( 'Y-m-d' ),
                    'freq'    => 'weekly',
                    'prio'    => '0.5',
                ];
            }
        }

        $entries = '';
        foreach ( $items as $item ) {
            $entries .= sprintf(
                "  <url>\n    <loc>%s</loc>\n    <lastmod>%s</lastmod>\n    <changefreq>%s</changefreq>\n    <priority>%s</priority>\n  </url>\n",
                esc_url( $item['loc'] ), esc_html( $item['lastmod'] ),
                esc_html( $item['freq'] ), esc_html( $item['prio'] )
            );
        }

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
{$entries}</urlset>
XML;
    }

    public function clear_sitemap_cache() {
        foreach ( [ '1', 'posts', 'pages', 'category' ] as $type ) {
            wp_cache_delete( 'illu_sitemap_xml_' . $type, 'illu_seo' );
        }
    }
}
