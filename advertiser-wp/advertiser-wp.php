<?php
/**
 * Plugin Name: Advertiser WP (Scalev Clone)
 * Description: Platform custom form checkout & Landing Page Builder drag-and-drop mandiri untuk Advertiser.
 * Version: 2.0.0
 * Author: AI Agent
 */

if (!defined('ABSPATH')) exit;

define('ADV_WP_PATH', plugin_dir_path(__FILE__));
define('ADV_WP_URL', plugin_dir_url(__FILE__));
define('ADV_PAGES_TABLE', 'adv_pages');
define('ADV_LEADS_TABLE', 'adv_leads');

require_once ADV_WP_PATH . 'includes/class-db.php';
require_once ADV_WP_PATH . 'includes/class-roles.php';
require_once ADV_WP_PATH . 'includes/class-admin.php';
require_once ADV_WP_PATH . 'includes/class-api.php';
require_once ADV_WP_PATH . 'includes/class-public-router.php';
require_once ADV_WP_PATH . 'includes/class-login.php';

function adv_wp_activate() {
    Adv_WP_DB::init();
    Adv_WP_Roles::add_roles();
    Adv_WP_Router::add_rewrite();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'adv_wp_activate');

add_action('plugins_loaded', 'adv_wp_init');
function adv_wp_init() {
    Adv_WP_DB::init(); // Ensure tables are created
    Adv_WP_Roles::init();
    Adv_WP_Admin::init();
    Adv_WP_API::init();
    Adv_WP_Router::init();
    Adv_WP_Login::init();
}
