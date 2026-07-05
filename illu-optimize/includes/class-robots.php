<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Illu_SEO_Robots {

    public function __construct() {
        add_filter( 'robots_txt', [ $this, 'customize_robots_txt' ], 10, 2 );
    }

    public function customize_robots_txt( string $output, bool $public ): string {
        if ( ! $public ) return $output;

        $output  = "User-agent: *\n";
        $output .= "Allow: /\n";
        $output .= "Disallow: /wp-admin/\n";
        $output .= "Disallow: /wp-includes/\n";
        $output .= "Disallow: /wp-login.php\n";
        $output .= "Disallow: /xmlrpc.php\n";
        $output .= "Disallow: /wp-json/\n";                 // block REST enumeration
        $output .= "Allow: /wp-json/sharelink/\n";          // allow sharelink REST
        $output .= "Allow: /wp-json/canvas-app/\n";         // allow canvas-app REST
        $output .= "Disallow: /*?s=\n";                     // search pages
        $output .= "Disallow: /*?author=\n";                // author enumeration
        $output .= "Disallow: /uploads/illu-cache/\n";      // cache dir
        $output .= "Crawl-delay: 1\n\n";

        // GPTBot / AI crawlers — allow by default
        $options  = get_option( 'illu_optimize_settings', [] );
        if ( ( $options['block_ai_crawlers'] ?? 'no' ) === 'yes' ) {
            $output .= "User-agent: GPTBot\nDisallow: /\n\n";
            $output .= "User-agent: ChatGPT-User\nDisallow: /\n\n";
            $output .= "User-agent: CCBot\nDisallow: /\n\n";
            $output .= "User-agent: anthropic-ai\nDisallow: /\n\n";
            $output .= "User-agent: Claude-Web\nDisallow: /\n\n";
        }

        $sitemap_url = home_url( '/sitemap.xml' );
        $output .= "Sitemap: $sitemap_url\n";

        return $output;
    }
}
