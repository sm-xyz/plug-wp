<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Illu_Optimize_Minifier {

    public function __construct() {
        $options = get_option( 'illu_optimize_settings', [] );
        if ( ( $options['minify_html'] ?? 'yes' ) === 'yes' ) {
            add_action( 'template_redirect', [ $this, 'start_output_buffer' ], 0 );
        }
    }

    public function start_output_buffer() {
        // Bypass: admin, AJAX, REST, CLI
        if ( is_admin() || wp_doing_ajax() || ( defined( 'WP_CLI' ) && WP_CLI ) ) return;
        if ( ! empty( $_SERVER['HTTP_X_WP_NONCE'] ) ) return;

        // Bypass: canvas/AI templates (template-canvas.php — output raw HTML)
        global $template;
        if ( isset( $template ) && strpos( $template, 'template-canvas' ) !== false ) return;

        ob_start( [ $this, 'minify_html' ] );
    }

    public function minify_html( string $html ): string {
        if ( empty( $html ) ) return $html;

        $options = get_option( 'illu_optimize_settings', [] );

        // Simpan blok yang tidak boleh diubah
        $preserved  = [];
        $placeholder_base = 'ILLUMIN_PRESERVE_';

        // Preserve: <pre>, <textarea>, <script>, <style>
        foreach ( [ 'pre', 'textarea' ] as $tag ) {
            $html = preg_replace_callback(
                '#<' . $tag . '(\s[^>]*)?>.*?</' . $tag . '>#is',
                function ( $m ) use ( &$preserved, $placeholder_base ) {
                    $key = $placeholder_base . count( $preserved ) . '__';
                    $preserved[ $key ] = $m[0];
                    return $key;
                },
                $html
            );
        }

        // Preserve inline <script> (non-LD+JSON minify separately)
        $html = preg_replace_callback(
            '#<script(\s[^>]*)?>.*?</script>#is',
            function ( $m ) use ( &$preserved, $placeholder_base, $options ) {
                if ( strpos( $m[0], 'application/ld+json' ) !== false ) return $m[0];
                $script = ( $options['minify_js'] ?? 'yes' ) === 'yes'
                    ? $this->minify_inline_js( $m[0] )
                    : $m[0];
                $key = $placeholder_base . count( $preserved ) . '__';
                $preserved[ $key ] = $script;
                return $key;
            },
            $html
        );

        // Preserve <style> blocks
        $html = preg_replace_callback(
            '#<style(\s[^>]*)?>.*?</style>#is',
            function ( $m ) use ( &$preserved, $placeholder_base, $options ) {
                $style = ( $options['minify_css'] ?? 'yes' ) === 'yes'
                    ? $this->minify_inline_css( $m[0] )
                    : $m[0];
                $key = $placeholder_base . count( $preserved ) . '__';
                $preserved[ $key ] = $style;
                return $key;
            },
            $html
        );

        // Strip HTML comments (keep IE conditionals and ko bindings)
        $html = preg_replace( '/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $html );

        // Collapse whitespace (preserve single space between words)
        $html = preg_replace( '/[ \t]+/', ' ', $html );
        $html = preg_replace( '/\n\s*\n+/', "\n", $html );
        $html = preg_replace( '/>\s+</', '><', $html );

        // Restore preserved blocks
        foreach ( $preserved as $key => $val ) {
            $html = str_replace( $key, $val, $html );
        }

        return trim( $html );
    }

    private function minify_inline_js( string $tag ): string {
        return preg_replace_callback(
            '#(<script[^>]*>)(.*?)(</script>)#is',
            function ( $m ) {
                $js = $m[2];
                $js = preg_replace( '#/\*.*?\*/#s', '', $js );           // block comments
                $js = preg_replace( '#(?<![:/])//[^\r\n]*#', '', $js );  // line comments
                $js = preg_replace( '/\s*([{}=:;,\+\-\*\/\(\)\[\]])\s*/', '$1', $js );
                $js = preg_replace( '/[ \t]+/', ' ', $js );
                return $m[1] . trim( $js ) . $m[3];
            },
            $tag
        );
    }

    private function minify_inline_css( string $tag ): string {
        return preg_replace_callback(
            '#(<style[^>]*>)(.*?)(</style>)#is',
            function ( $m ) {
                $css = $m[2];
                $css = preg_replace( '#/\*.*?\*/#s', '', $css );   // comments
                $css = preg_replace( '/\s*([{}:;,])\s*/', '$1', $css );
                $css = preg_replace( '/\s+/', ' ', $css );
                $css = str_replace( [ ';}', '{ ' ], [ '}', '{' ], $css );
                return $m[1] . trim( $css ) . $m[3];
            },
            $tag
        );
    }
}
