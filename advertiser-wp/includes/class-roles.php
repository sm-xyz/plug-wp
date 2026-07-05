<?php
if (!defined('ABSPATH')) exit;

class Adv_WP_Roles {
    public static function init() {
        add_action('admin_init', [__CLASS__, 'restrict_admin_access']);
        add_filter('login_redirect', [__CLASS__, 'login_redirect'], 10, 3);
    }

    public static function add_roles() {
        add_role('solusi_advertiser', 'Advertiser', [
            'read' => true,
        ]);
    }

    public static function restrict_admin_access() {
        if (wp_doing_ajax()) return;

        $user = wp_get_current_user();
        if (in_array('solusi_advertiser', (array) $user->roles)) {
            // Block normal WP admin, force them to our SPA UI
            $page = isset($_GET['page']) ? $_GET['page'] : '';
            if ($page !== 'adv-dashboard') {
                wp_redirect(admin_url('admin.php?page=adv-dashboard'));
                exit;
            }
            show_admin_bar(false);
        }
    }

    public static function login_redirect($redirect_to, $request, $user) {
        if (isset($user->roles) && is_array($user->roles)) {
            if (in_array('solusi_advertiser', $user->roles)) {
                return admin_url('admin.php?page=adv-dashboard');
            }
        }
        return $redirect_to;
    }
}
