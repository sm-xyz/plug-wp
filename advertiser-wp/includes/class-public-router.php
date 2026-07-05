<?php
if (!defined('ABSPATH')) exit;

class Adv_WP_Router {
    public static function init() {
        add_action('init', [__CLASS__, 'add_rewrite']);
        add_filter('query_vars', [__CLASS__, 'add_query_vars']);
        add_action('parse_request', [__CLASS__, 'handle_lp_request']);
        add_action('template_redirect', [__CLASS__, 'render_lp']);
    }

    public static function add_rewrite() {
        add_rewrite_rule('^lp/([^/]+)/?$', 'index.php?adv_lp=$matches[1]', 'top');
    }

    public static function add_query_vars($vars) {
        $vars[] = 'adv_lp';
        return $vars;
    }

    public static function handle_lp_request($wp) {
        // Just in case rewrite rules didn't catch it
        $req = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#^/lp/([^/]+)/?$#', $req, $matches)) {
            $wp->query_vars['adv_lp'] = $matches[1];
        }
    }

    public static function render_lp() {
        $slug = get_query_var('adv_lp');
        if (!empty($slug)) {
            global $wpdb;
            $table = $wpdb->prefix . ADV_PAGES_TABLE;
            $table_products = $wpdb->prefix . 'adv_products';
            
            $sql = "SELECT p.*, pr.price as product_price, pr.price_coret as product_price_coret, pr.name as product_name FROM $table p LEFT JOIN $table_products pr ON p.product_id = pr.id WHERE p.slug = %s";
            $page = $wpdb->get_row($wpdb->prepare($sql, $slug));
            
            if ($page) {
                // Bypass cache
                if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE', true);
                if (!defined('DONOTCACHEOBJECT')) define('DONOTCACHEOBJECT', true);
                if (!defined('DONOTCACHEDB')) define('DONOTCACHEDB', true);
                
                // Track view if it's not a success page
                if (!isset($_GET['success'])) {
                    $wpdb->query($wpdb->prepare("UPDATE $table SET views = views + 1 WHERE id = %d", $page->id));
                }
                
                // Render the public page builder output
                require_once ADV_WP_PATH . 'views/public-page.php';
                exit;
            } else {
                wp_die('Landing page tidak ditemukan.', 'Not Found', ['response' => 404]);
            }
        }
    }
}
