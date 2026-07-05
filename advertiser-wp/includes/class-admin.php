<?php
if (!defined('ABSPATH')) exit;

class Adv_WP_Admin {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'register_menu']);
    }

    public static function register_menu() {
        $hook = add_menu_page(
            'Advertiser Workspace',
            'Advertiser Workspace',
            'read', // Allow both admin and advertiser
            'adv-dashboard',
            [__CLASS__, 'render_app_shell'],
            'dashicons-superhero',
            3
        );
        add_action("load-$hook", [__CLASS__, 'render_app_shell_clean']);
    }
    
    public static function render_app_shell_clean() {
        $user = wp_get_current_user();
        if (!in_array('solusi_advertiser', (array) $user->roles) && !in_array('administrator', (array) $user->roles)) {
            wp_die('Akses ditolak.');
        }

        // Render Tailwind SPA Shell completely clean
        require_once ADV_WP_PATH . 'views/app-shell.php';
        exit;
    }

    public static function render_app_shell() {
        // Fallback if load hook didn't catch it
    }
}
