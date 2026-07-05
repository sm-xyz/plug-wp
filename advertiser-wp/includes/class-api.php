<?php
if (!defined('ABSPATH')) exit;

class Adv_WP_API {
    public static function init() {
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
        add_action('rest_api_init', function() {
            if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE', true);
        });
    }

    public static function register_routes() {
        // Products
        register_rest_route('adv/v1', '/products', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_products'],
            'permission_callback' => [__CLASS__, 'check_auth']
        ]);
        register_rest_route('adv/v1', '/products', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'save_product'],
            'permission_callback' => [__CLASS__, 'check_admin']
        ]);
        register_rest_route('adv/v1', '/products/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [__CLASS__, 'delete_product'],
            'permission_callback' => [__CLASS__, 'check_admin']
        ]);

        // Pages
        register_rest_route('adv/v1', '/pages', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_pages'],
            'permission_callback' => [__CLASS__, 'check_auth']
        ]);
        register_rest_route('adv/v1', '/pages', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'save_page'],
            'permission_callback' => [__CLASS__, 'check_auth']
        ]);
        register_rest_route('adv/v1', '/pages/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [__CLASS__, 'delete_page'],
            'permission_callback' => [__CLASS__, 'check_auth']
        ]);
        register_rest_route('adv/v1', '/pages/duplicate/(?P<id>\d+)', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'duplicate_page'],
            'permission_callback' => [__CLASS__, 'check_auth']
        ]);

        // Leads
        register_rest_route('adv/v1', '/leads', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_leads'],
            'permission_callback' => [__CLASS__, 'check_auth']
        ]);
        
        // Contents
        register_rest_route('adv/v1', '/contents', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_contents'],
            'permission_callback' => [__CLASS__, 'check_auth']
        ]);
        register_rest_route('adv/v1', '/contents', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'save_content'],
            'permission_callback' => [__CLASS__, 'check_admin']
        ]);
        register_rest_route('adv/v1', '/contents/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [__CLASS__, 'delete_content'],
            'permission_callback' => [__CLASS__, 'check_admin']
        ]);
        
        // Checkout Form Submit Endpoint
        register_rest_route('adv/v1', '/checkout', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'submit_checkout'],
            'permission_callback' => '__return_true'
        ]);
        // Media Upload
        register_rest_route('adv/v1', '/media', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'upload_media'],
            'permission_callback' => [__CLASS__, 'check_auth']
        ]);
        
        // Content Media (No 200kb limit)
        register_rest_route('adv/v1', '/content-media', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'upload_content_media'],
            'permission_callback' => [__CLASS__, 'check_auth']
        ]);
        
        // Withdrawals
        register_rest_route('adv/v1', '/withdrawals', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_withdrawals'],
            'permission_callback' => [__CLASS__, 'check_auth']
        ]);
        register_rest_route('adv/v1', '/withdrawals', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'save_withdrawal'],
            'permission_callback' => [__CLASS__, 'check_auth']
        ]);
        register_rest_route('adv/v1', '/withdrawals/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [__CLASS__, 'update_withdrawal_status'],
            'permission_callback' => [__CLASS__, 'check_auth']
        ]);
        register_rest_route('adv/v1', '/withdrawals/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [__CLASS__, 'delete_withdrawal'],
            'permission_callback' => [__CLASS__, 'check_admin']
        ]);
        
        // Advertisers
        register_rest_route('adv/v1', '/advertisers', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_advertisers'],
            'permission_callback' => [__CLASS__, 'check_admin']
        ]);
        register_rest_route('adv/v1', '/advertisers', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'save_advertiser'],
            'permission_callback' => [__CLASS__, 'check_admin']
        ]);
        register_rest_route('adv/v1', '/advertisers/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [__CLASS__, 'delete_advertiser'],
            'permission_callback' => [__CLASS__, 'check_admin']
        ]);

        // Get WP Attachments
        register_rest_route('adv/v1', '/attachments', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_attachments'],
            'permission_callback' => [__CLASS__, 'check_admin']
        ]);

        // Settings
        register_rest_route('adv/v1', '/settings', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_settings'],
            'permission_callback' => [__CLASS__, 'check_admin']
        ]);
        register_rest_route('adv/v1', '/settings', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'save_settings'],
            'permission_callback' => [__CLASS__, 'check_admin']
        ]);

        // Duitku Callback
        register_rest_route('adv/v1', '/duitku/callback', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle_duitku_callback'],
            'permission_callback' => '__return_true'
        ]);

        // Duitku Payment Methods
        register_rest_route('adv/v1', '/duitku/payment-methods', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_duitku_payment_methods'],
            'permission_callback' => '__return_true'
        ]);

        // Purge Cache
        register_rest_route('adv/v1', '/purge-cache', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'purge_all_cache'],
            'permission_callback' => [__CLASS__, 'check_auth']
        ]);

        // WA Check
        register_rest_route('adv/v1', '/wa-check', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'wa_check'],
            'permission_callback' => '__return_true'
        ]);

        // Email Check
        register_rest_route('adv/v1', '/email-check', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'email_check'],
            'permission_callback' => '__return_true'
        ]);
    }

    public static function purge_all_cache(WP_REST_Request $request) {
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        if (has_action('litespeed_purge_all')) {
            do_action('litespeed_purge_all');
        }
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
        }
        return rest_ensure_response(['success' => true]);
    }

    public static function get_settings(WP_REST_Request $request) {
        return rest_ensure_response([
            'solusi_duitku_env' => get_option('solusi_duitku_env', 'sandbox'),
            'solusi_duitku_merchant_code' => get_option('solusi_duitku_merchant_code', ''),
            'solusi_duitku_api_key' => get_option('solusi_duitku_api_key', ''),
            'solusi_sharelink_webhook_url' => get_option('solusi_sharelink_webhook_url', ''),
            'solusi_sharelink_secret' => get_option('solusi_sharelink_secret', ''),
            'adv_turnstile_sitekey' => get_option('adv_turnstile_sitekey', ''),
            'adv_fonnte_token' => get_option('adv_fonnte_token', ''),
            'adv_reacher_api_key' => get_option('adv_reacher_api_key', ''),
            'adv_turnstile_secret' => get_option('adv_turnstile_secret', '')
        ]);
    }

    public static function save_settings(WP_REST_Request $request) {
        $params = $request->get_json_params();
        update_option('solusi_duitku_env', sanitize_text_field($params['solusi_duitku_env'] ?? 'sandbox'));
        update_option('solusi_duitku_merchant_code', sanitize_text_field($params['solusi_duitku_merchant_code'] ?? ''));
        update_option('solusi_duitku_api_key', sanitize_text_field($params['solusi_duitku_api_key'] ?? ''));
        update_option('solusi_sharelink_webhook_url', sanitize_url($params['solusi_sharelink_webhook_url'] ?? ''));
        update_option('solusi_sharelink_secret', sanitize_text_field($params['solusi_sharelink_secret'] ?? ''));
        update_option('adv_turnstile_sitekey', sanitize_text_field($params['adv_turnstile_sitekey'] ?? ''));
        update_option('adv_fonnte_token', sanitize_text_field($params['adv_fonnte_token'] ?? ''));
        update_option('adv_reacher_api_key', sanitize_text_field($params['adv_reacher_api_key'] ?? ''));
        update_option('adv_turnstile_secret', sanitize_text_field($params['adv_turnstile_secret'] ?? ''));
        
        return rest_ensure_response(['success' => true]);
    }

    public static function get_duitku_api_base_url() {
        $env = get_option('solusi_duitku_env', 'sandbox');
        return $env === 'production' ? 'https://passport.duitku.com' : 'https://sandbox.duitku.com';
    }

    public static function check_auth() {
        return current_user_can('solusi_advertiser') || current_user_can('manage_options');
    }

    public static function check_admin() {
        return current_user_can('manage_options');
    }

    public static function get_attachments(WP_REST_Request $request) {
        $type = $request->get_param('type'); // 'image' or 'video'
        
        $args = [
            'post_type'      => 'attachment',
            'post_mime_type' => $type === 'video' ? 'video' : 'image',
            'post_status'    => 'inherit',
            'posts_per_page' => 50,
            'orderby'        => 'date',
            'order'          => 'DESC'
        ];
        
        $query = new WP_Query($args);
        $items = [];
        
        foreach ($query->posts as $post) {
            $url = wp_get_attachment_url($post->ID);
            $items[] = [
                'id' => $post->ID,
                'url' => $url,
                'title' => $post->post_title,
                'mime_type' => $post->post_mime_type
            ];
        }
        
        return rest_ensure_response(['success' => true, 'data' => $items]);
    }

    public static function upload_content_media(WP_REST_Request $request) {
        if (empty($_FILES['file'])) {
            return new WP_REST_Response(['success' => false, 'message' => 'No file uploaded'], 400);
        }
        
        $file = $_FILES['file'];
        
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        // Temporarily allow upload_files capability for this request
        add_filter('user_has_cap', function($allcaps) {
            $allcaps['upload_files'] = true;
            return $allcaps;
        });

        // Tier 1: Standard upload handler for HTTP POST $_FILES
        $attachment_id = @media_handle_upload('file', 0);
        if (is_wp_error($attachment_id)) {
            // Tier 2: Fallback to sideload
            $attachment_id = @media_handle_sideload($file, 0);
        }

        if (!is_wp_error($attachment_id) && $attachment_id > 0) {
            $url = wp_get_attachment_url($attachment_id);
            return rest_ensure_response(['success' => true, 'url' => $url]);
        }

        // Tier 3: Direct filesystem copy fallback if WP attachment database insertion fails
        $upload_dir = wp_upload_dir();
        if (!empty($upload_dir['path']) && !empty($file['tmp_name'])) {
            $ext = pathinfo(sanitize_file_name($file['name']), PATHINFO_EXTENSION);
            if (!$ext) $ext = 'jpg';
            $filename = 'adv_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $filepath = trailingslashit($upload_dir['path']) . $filename;
            if (@move_uploaded_file($file['tmp_name'], $filepath) || @copy($file['tmp_name'], $filepath)) {
                $url = trailingslashit($upload_dir['url']) . $filename;
                return rest_ensure_response(['success' => true, 'url' => $url]);
            }
        }

        $err_msg = is_wp_error($attachment_id) ? $attachment_id->get_error_message() : 'Gagal menyimpan file ke server';
        return new WP_REST_Response(['success' => false, 'message' => $err_msg], 400);
    }

    public static function upload_media(WP_REST_Request $request) {
        if (empty($_FILES['file'])) {
            return new WP_REST_Response(['success' => false, 'message' => 'No file uploaded'], 400);
        }
        
        $file = $_FILES['file'];
        
        // Check size (200kb max)
        if ($file['size'] > 204800) {
            return new WP_REST_Response(['success' => false, 'message' => 'Ukuran gambar maksimal 200kb'], 400);
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        // Temporarily allow upload_files capability for this request
        add_filter('user_has_cap', function($allcaps) {
            $allcaps['upload_files'] = true;
            return $allcaps;
        });

        // Tier 1: Standard upload handler for HTTP POST $_FILES
        $attachment_id = @media_handle_upload('file', 0);
        if (is_wp_error($attachment_id)) {
            // Tier 2: Fallback to sideload
            $attachment_id = @media_handle_sideload($file, 0);
        }

        if (!is_wp_error($attachment_id) && $attachment_id > 0) {
            $url = wp_get_attachment_url($attachment_id);
            return rest_ensure_response(['success' => true, 'url' => $url]);
        }

        // Tier 3: Direct filesystem copy fallback
        $upload_dir = wp_upload_dir();
        if (!empty($upload_dir['path']) && !empty($file['tmp_name'])) {
            $ext = pathinfo(sanitize_file_name($file['name']), PATHINFO_EXTENSION);
            if (!$ext) $ext = 'jpg';
            $filename = 'adv_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $filepath = trailingslashit($upload_dir['path']) . $filename;
            if (@move_uploaded_file($file['tmp_name'], $filepath) || @copy($file['tmp_name'], $filepath)) {
                $url = trailingslashit($upload_dir['url']) . $filename;
                return rest_ensure_response(['success' => true, 'url' => $url]);
            }
        }

        $err_msg = is_wp_error($attachment_id) ? $attachment_id->get_error_message() : 'Gagal menyimpan file ke server';
        return new WP_REST_Response(['success' => false, 'message' => $err_msg], 400);
    }

    public static function get_duitku_payment_methods(WP_REST_Request $request) {
        $amount = $request->get_param('amount');
        if (empty($amount)) {
            return new WP_REST_Response(['success' => false, 'message' => 'Amount is required'], 400);
        }

        $merchantCode = get_option('solusi_duitku_merchant_code');
        $apiKey = get_option('solusi_duitku_api_key');
        
        if (empty($merchantCode) || empty($apiKey)) {
            return new WP_REST_Response(['success' => false, 'message' => 'Duitku config not found'], 400);
        }

        $datetime = date('Y-m-d H:i:s');
        $signature = hash('sha256', $merchantCode . $amount . $datetime . $apiKey);

        $payload = [
            'merchantCode' => $merchantCode,
            'amount' => $amount,
            'datetime' => $datetime,
            'signature' => $signature
        ];

        $url = self::get_duitku_api_base_url() . '/webapi/api/merchant/paymentmethod/getpaymentmethod';
        
        $response = wp_remote_post($url, [
            'body' => json_encode($payload),
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            return new WP_REST_Response(['success' => false, 'message' => $response->get_error_message()], 500);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        return rest_ensure_response(['success' => true, 'data' => $data]);
    }

    public static function get_products(WP_REST_Request $request) {
        global $wpdb;
        // Auto-migrate table schema temporarily
        require_once dirname(__FILE__) . '/class-db.php';
        Adv_WP_DB::init();
        
        $table = $wpdb->prefix . 'adv_products';
        $products = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");
        return rest_ensure_response($products);
    }

    public static function save_product(WP_REST_Request $request) {
        global $wpdb;
        $table = $wpdb->prefix . 'adv_products';
        $params = $request->get_json_params();

        $id = isset($params['id']) ? intval($params['id']) : 0;
        $name = sanitize_text_field($params['name'] ?? 'Untitled');
        $price = floatval($params['price'] ?? 0);
        $price_coret = floatval($params['price_coret'] ?? 0);
        $product_type = sanitize_text_field($params['product_type'] ?? 'canvas_app');
        $description = sanitize_textarea_field($params['description'] ?? '');
        $access_flow = sanitize_textarea_field($params['access_flow'] ?? '');
        $mockup_image = esc_url_raw($params['mockup_image'] ?? '');
        $affiliate_commission = sanitize_text_field($params['affiliate_commission'] ?? '');

        if ($id > 0) {
            $wpdb->update($table, [
                'name' => $name,
                'price' => $price,
                'price_coret' => $price_coret,
                'product_type' => $product_type,
                'description' => $description,
                'access_flow' => $access_flow,
                'mockup_image' => $mockup_image,
                'affiliate_commission' => $affiliate_commission,
                'updated_at' => current_time('mysql')
            ], ['id' => $id]);
        } else {
            $wpdb->insert($table, [
                'name' => $name,
                'price' => $price,
                'price_coret' => $price_coret,
                'product_type' => $product_type,
                'description' => $description,
                'access_flow' => $access_flow,
                'mockup_image' => $mockup_image,
                'affiliate_commission' => $affiliate_commission,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ]);
        }
        return rest_ensure_response(['success' => true]);
    }

    public static function delete_product(WP_REST_Request $request) {
        global $wpdb;
        $table = $wpdb->prefix . 'adv_products';
        $id = $request->get_param('id');
        $wpdb->delete($table, ['id' => $id]);
        return rest_ensure_response(['success' => true]);
    }

    public static function get_pages(WP_REST_Request $request) {
        global $wpdb;
        $table = $wpdb->prefix . ADV_PAGES_TABLE;
        $table_products = $wpdb->prefix . 'adv_products';
        $table_leads = $wpdb->prefix . ADV_LEADS_TABLE;
        $users = $wpdb->users;
        $user_id = get_current_user_id();
        
        $sql = "SELECT p.*, pr.name as product_name, u.display_name as advertiser_name,
                (SELECT COUNT(*) FROM $table_leads l WHERE l.page_id = p.id AND l.status = 'paid') as total_orders
                FROM $table p 
                LEFT JOIN $table_products pr ON p.product_id = pr.id
                LEFT JOIN $users u ON p.user_id = u.ID";
        
        // As requested: advertiser bisa view dan melihat landing page advertiser lain. 
        // Jadi kita tampilkan semua, atau kita filter di frontend saja.
        $pages = $wpdb->get_results("$sql ORDER BY p.created_at DESC");
        
        foreach ($pages as $page) {
            $page->cr = $page->views > 0 ? round(($page->total_orders / $page->views) * 100, 2) : 0;
        }
        
        return rest_ensure_response($pages);
    }

    public static function save_page(WP_REST_Request $request) {
        global $wpdb;
        $table = $wpdb->prefix . ADV_PAGES_TABLE;
        $user_id = get_current_user_id();
        $params = $request->get_json_params();

        $id = isset($params['id']) ? intval($params['id']) : 0;
        $title = sanitize_text_field($params['title'] ?? 'Untitled');
        
        // Admin can assign advertiser
        $page_user_id = $user_id;
        if (current_user_can('manage_options') && isset($params['user_id']) && $params['user_id'] > 0) {
            $page_user_id = intval($params['user_id']);
        }
        
        $slug = sanitize_title(isset($params['slug']) && !empty($params['slug']) ? $params['slug'] : $title);
        // Ensure slug is unique
        if ($id > 0) {
            $slug_check = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE slug = %s AND id != %d", $slug, $id));
        } else {
            $slug_check = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE slug = %s", $slug));
        }
        if ($slug_check > 0) $slug .= '-' . time();
        
        $product_id = intval($params['product_id'] ?? 0);
        
        // Raw JSON string
        $builder_data = wp_json_encode($params['builder_data'] ?? []);
        $pixel_data = wp_json_encode($params['pixel_data'] ?? []);

        if ($id > 0) {
            // Update
            $where = ['id' => $id];
            if (!current_user_can('manage_options')) {
                $where['user_id'] = $user_id;
            }
            
            $wpdb->update($table, [
                'title' => $title,
                'slug' => $slug,
                'builder_data' => $builder_data,
                'pixel_data' => $pixel_data,
                'product_id' => $product_id,
                'user_id' => $page_user_id,
                'updated_at' => current_time('mysql')
            ], $where);
        } else {
            // Insert
            $wpdb->insert($table, [
                'user_id' => $page_user_id,
                'title' => $title,
                'slug' => $slug,
                'builder_data' => $builder_data,
                'pixel_data' => $pixel_data,
                'product_id' => $product_id,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ]);
            $id = $wpdb->insert_id;
        }

        return rest_ensure_response(['success' => true, 'id' => $id]);
    }
    
    public static function delete_page(WP_REST_Request $request) {
        global $wpdb;
        $table = $wpdb->prefix . ADV_PAGES_TABLE;
        $id = $request->get_param('id');
        $user_id = get_current_user_id();
        
        $where = ['id' => $id];
        if (!current_user_can('manage_options')) {
            $where['user_id'] = $user_id;
        }
        
        $wpdb->delete($table, $where);
        return rest_ensure_response(['success' => true]);
    }
    
    public static function duplicate_page(WP_REST_Request $request) {
        global $wpdb;
        $table = $wpdb->prefix . ADV_PAGES_TABLE;
        $id = $request->get_param('id');
        $user_id = get_current_user_id();
        
        $where = "id = %d";
        $args = [$id];
        if (!current_user_can('manage_options')) {
            $where .= " AND user_id = %d";
            $args[] = $user_id;
        }
        
        $page = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE $where", $args));
        if ($page) {
            // Count duplicates to append " Copy X"
            $like_title = $wpdb->esc_like($page->title) . ' Copy%';
            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE title LIKE %s AND user_id = %d", $like_title, $page->user_id));
            
            $new_title = $page->title . ' Copy ' . ($count + 1);
            $new_slug = sanitize_title($new_title) . '-' . time();
            
            $wpdb->insert($table, [
                'user_id' => $page->user_id,
                'title' => $new_title,
                'slug' => $new_slug,
                'builder_data' => $page->builder_data,
                'pixel_data' => $page->pixel_data,
                'product_id' => $page->product_id,
                'status' => $page->status,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ]);
            return rest_ensure_response(['success' => true]);
        }
        return rest_ensure_response(['success' => false], 404);
    }

    public static function get_leads(WP_REST_Request $request) {
        global $wpdb;
        $table = $wpdb->prefix . ADV_LEADS_TABLE;
        $table_pages = $wpdb->prefix . ADV_PAGES_TABLE;
        $table_products = $wpdb->prefix . 'adv_products';
        $users = $wpdb->users;
        $user_id = get_current_user_id();
        $is_all = $request->get_param('all');
        
        $sql = "SELECT l.*, pr.name as product_name, p.product_id, u.display_name as advertiser_name 
                FROM $table l 
                LEFT JOIN $table_pages p ON l.page_id = p.id 
                LEFT JOIN $table_products pr ON p.product_id = pr.id
                LEFT JOIN $users u ON l.advertiser_id = u.ID";
        
        if (current_user_can('manage_options') && $is_all === '1') {
            $leads = $wpdb->get_results("$sql ORDER BY l.created_at DESC LIMIT 100");
        } else {
            $leads = $wpdb->get_results($wpdb->prepare("$sql WHERE l.advertiser_id = %d ORDER BY l.created_at DESC", $user_id));
        }
        
        return rest_ensure_response($leads);
    }
    
    public static function get_contents(WP_REST_Request $request) {
        global $wpdb;
        $table = $wpdb->prefix . 'adv_contents';
        $contents = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");
        return rest_ensure_response($contents);
    }

    public static function save_content(WP_REST_Request $request) {
        global $wpdb;
        $table = $wpdb->prefix . 'adv_contents';
        $params = $request->get_json_params();

        $id = isset($params['id']) ? intval($params['id']) : 0;
        $title = sanitize_text_field($params['title'] ?? '');
        $type = sanitize_text_field($params['type'] ?? 'image');
        $product_id = intval($params['product_id'] ?? 0);
        $embed_link = wp_kses_post($params['embed_link'] ?? '');
        $copy_text = wp_kses_post($params['copy_text'] ?? '');

        if ($id > 0) {
            $wpdb->update($table, [
                'title' => $title,
                'type' => $type,
                'product_id' => $product_id,
                'embed_link' => $embed_link,
                'copy_text' => $copy_text,
                'updated_at' => current_time('mysql')
            ], ['id' => $id]);
        } else {
            $wpdb->insert($table, [
                'title' => $title,
                'type' => $type,
                'product_id' => $product_id,
                'embed_link' => $embed_link,
                'copy_text' => $copy_text,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ]);
        }
        return rest_ensure_response(['success' => true]);
    }

    public static function delete_content(WP_REST_Request $request) {
        global $wpdb;
        $table = $wpdb->prefix . 'adv_contents';
        $id = $request->get_param('id');
        $wpdb->delete($table, ['id' => $id]);
        return rest_ensure_response(['success' => true]);
    }

    public static function submit_checkout(WP_REST_Request $request) {
        $params = $request->get_json_params();
        if(empty($params)){
            $params = $request->get_body_params();
        }
        
        if (!empty($params['slw_website_url'])) {
            return new WP_REST_Response(['success'=>false, 'message' => 'Spam terdeteksi.'], 400);
        }
        
        global $wpdb;
        $page_id = intval($params['page_id'] ?? 0);
        $name = sanitize_text_field($params['customer_name'] ?? '');
        $email = sanitize_email($params['customer_email'] ?? '');
        $wa = sanitize_text_field($params['customer_wa'] ?? '');
        
        if (!$page_id || !$name || !$wa) {
            return new WP_REST_Response(['success'=>false, 'message' => 'Data tidak lengkap.'], 400);
        }
        
        $wa = preg_replace('/[^0-9]/', '', $wa);
        if (strpos($wa, '0') === 0) $wa = '62' . substr($wa, 1);
        
        $page_table = $wpdb->prefix . ADV_PAGES_TABLE;
        $product_table = $wpdb->prefix . 'adv_products';
        
        $page = $wpdb->get_row($wpdb->prepare("SELECT * FROM $page_table WHERE id = %d", $page_id));
        if (!$page) return new WP_REST_Response(['success'=>false, 'message' => 'Page tidak valid.'], 404);
        
        $product = $wpdb->get_row($wpdb->prepare("SELECT price FROM $product_table WHERE id = %d", $page->product_id));
        $price = $product ? $product->price : 0;
        
        $leads_table = $wpdb->prefix . ADV_LEADS_TABLE;
        $wpdb->insert($leads_table, [
            'advertiser_id' => $page->user_id,
            'page_id' => $page->id,
            'customer_name' => $name,
            'customer_email' => $email,
            'customer_wa' => $wa,
            'price' => $price,
            'status' => 'pending',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ]);
        
        $lead_id = $wpdb->insert_id;
        
        // Integrasi Duitku
        $merchant_code = get_option('solusi_duitku_merchant_code');
        $api_key = get_option('solusi_duitku_api_key');
        
        $payment_method = sanitize_text_field($params['payment_method'] ?? '');
        
        if (!empty($merchant_code) && !empty($api_key)) {
            $base_url = self::get_duitku_api_base_url();
            $endpoint = $base_url . '/webapi/api/merchant/v2/inquiry';
            
            $merchant_order_id = 'SLS-' . $lead_id . '-' . time();
            $amount = (int) $price;
            
            $signature = md5($merchant_code . $merchant_order_id . $amount . $api_key);
            
            $product_title = $page->title;
            if (isset($product) && isset($product->name)) {
                $product_title = $product->name;
            }

            $payload = [
                'merchantCode' => $merchant_code,
                'paymentAmount' => $amount,
                'merchantOrderId' => $merchant_order_id,
                'productDetails' => 'Pembelian ' . $product_title,
                'email' => $email,
                'customerVaName' => $name,
                'phoneNumber' => $wa,
                'returnUrl' => home_url('/lp/' . $page->slug . '?success=1&order=' . $merchant_order_id),
                'callbackUrl' => rest_url('adv/v1/duitku/callback'),
                'expiryPeriod' => 1440,
                'signature' => $signature
            ];
            
            if (!empty($payment_method)) {
                $payload['paymentMethod'] = $payment_method;
            }

            $response = wp_remote_post($endpoint, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                'body' => json_encode($payload),
                'timeout' => 30
            ]);

            if (is_wp_error($response)) {
                return rest_ensure_response(['success' => false, 'message' => 'Duitku Error: ' . $response->get_error_message()]);
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (isset($data['paymentUrl']) && !empty($data['paymentUrl'])) {
                $wpdb->update(
                    $leads_table,
                    ['duitku_reference' => $merchant_order_id],
                    ['id' => $lead_id]
                );
                return rest_ensure_response(['success' => true, 'redirect_url' => $data['paymentUrl']]);
            } else {
                $error_msg = isset($data['Message']) ? $data['Message'] : (isset($data['Message']) ? $data['Message'] : 'Unknown Duitku Error');
                // Kadang error dari duitku formatnya beda
                if(empty($error_msg) && isset($data['statusMessage'])) {
                    $error_msg = $data['statusMessage'];
                }
                if(empty($error_msg) && is_string($body)) {
                    $error_msg = substr($body, 0, 150);
                }
                return rest_ensure_response(['success' => false, 'message' => 'Duitku Error: ' . $error_msg]);
            }
        }
        
        return rest_ensure_response(['success' => true, 'message' => 'Pesanan berhasil dibuat, namun payment gateway belum dikonfigurasi.']);
    }

    public static function handle_duitku_callback(WP_REST_Request $request) {
        $merchant_code = get_option('solusi_duitku_merchant_code');
        $api_key = get_option('solusi_duitku_api_key');
        
        $params = $request->get_params();
        
        $merchant_order_id = sanitize_text_field($params['merchantOrderId'] ?? '');
        $amount = sanitize_text_field($params['amount'] ?? '');
        $result_code = sanitize_text_field($params['resultCode'] ?? '');
        $signature_received = sanitize_text_field($params['signature'] ?? '');
        
        $stringToSign = $merchant_code . $amount . $merchant_order_id . $api_key;
        $signature_calc = md5($stringToSign);
        
        if ($signature_calc !== $signature_received) {
            return new WP_REST_Response(['status' => 'Bad Request', 'message' => 'Invalid Signature'], 400);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . ADV_LEADS_TABLE;
        
        $lead = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE duitku_reference = %s", $merchant_order_id));

        if (!$lead) {
            return new WP_REST_Response(['status' => 'Not Found', 'message' => 'Order not found'], 404);
        }

        if ($lead->status === 'paid') {
            return new WP_REST_Response(['status' => 'OK', 'message' => 'Already paid'], 200);
        }

        if ($result_code === '00') {
            $wpdb->update(
                $table_name,
                ['status' => 'paid', 'updated_at' => current_time('mysql')],
                ['id' => $lead->id]
            );

            // Webhook ke Sharelink
            self::send_to_sharelink($lead);
        } elseif ($result_code === '01') {
            $wpdb->update(
                $table_name,
                ['status' => 'failed', 'updated_at' => current_time('mysql')],
                ['id' => $lead->id]
            );
        }

        return new WP_REST_Response(['status' => 'OK'], 200);
    }

    public static function send_to_sharelink($lead) {
        $webhook_url = get_option('solusi_sharelink_webhook_url');
        $secret_key  = get_option('solusi_sharelink_secret');

        if (empty($webhook_url) || empty($secret_key)) {
            return false;
        }

        $timestamp = time();
        global $wpdb;
        $page_table = $wpdb->prefix . ADV_PAGES_TABLE;
        $page = $wpdb->get_row($wpdb->prepare("SELECT * FROM $page_table WHERE id = %d", $lead->page_id));
        $product_name = 'Produk Digital';
        
        if ($page) {
            $product_table = $wpdb->prefix . 'adv_products';
            $prod = $wpdb->get_row($wpdb->prepare("SELECT name, product_type FROM $product_table WHERE id = %d", $page->product_id));
            if ($prod) {
                $product_name = $prod->name;
                // Only send to ShareLink if product type is canvas_app
                if ($prod->product_type !== 'canvas_app') {
                    return true;
                }
            }
        }

        $payload = [
            'advertiser_id' => (int) $lead->advertiser_id,
            'source_landing_page_id' => (int) $lead->page_id,
            'product_id' => (int) ($page ? $page->product_id : 0),
            'product_name' => $product_name,
            'customer' => [
                'name' => $lead->customer_name,
                'email' => $lead->customer_email,
                'whatsapp' => $lead->customer_wa
            ],
            'order_reference' => $lead->duitku_reference,
            'timestamp' => $timestamp
        ];

        $json_payload = wp_json_encode($payload);
        $signature = hash_hmac('sha256', $json_payload, $secret_key);

        wp_remote_post($webhook_url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Sharelink-Signature' => $signature
            ],
            'body' => $json_payload,
            'timeout' => 30,
            'blocking' => false
        ]);

        return true;
    }

    public static function wa_check(WP_REST_Request $request) {
        $phone_raw = $request->get_param('phone');
        if (empty($phone_raw)) {
            return rest_ensure_response(['valid' => false, 'reason' => 'empty']);
        }
           
        $phone = preg_replace('/\D/', '', $phone_raw);
        if (str_starts_with($phone, '620')) {
            $phone = '62' . substr($phone, 3);
        } elseif (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (str_starts_with($phone, '8') && strlen($phone) >= 9) {
            $phone = '62' . $phone;
        }

        if (empty($phone) || strlen($phone) < 8) {
            return rest_ensure_response(['valid' => false, 'reason' => 'format_invalid']);
        }

        // Advanced Validation: Indonesian Prefix & Fake Pattern Detection
        if (!preg_match('/^628(1[1-9]|2[1-3]|3[1-38]|5[1-35-9]|7[78]|8[1-9]|9[5-9])[0-9]{6,10}$/', $phone)) {
            return rest_ensure_response(['valid' => false, 'reason' => 'format_invalid', 'message' => 'Prefix tidak valid']);
        }

        $local_part = substr($phone, 3);
        if (
            preg_match('/(.)\1{5,}/', $local_part) || 
            preg_match('/123456|234567|345678|456789|567890|098765|987654|876543|765432|654321/', $local_part)
        ) {
            return rest_ensure_response(['valid' => false, 'reason' => 'format_invalid', 'message' => 'Nomor terdeteksi tidak valid']);
        }

        // Cache via WP transient
        $cache_key = 'wa_check_' . md5($phone);
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return rest_ensure_response(['valid' => (bool)$cached, 'cached' => true]);
        }

        $isValid = true; // Default to true
        $fonnte_msg = '';

        // Use Fonnte API for exact verification if token is available
        $fonnte_token = get_option('adv_fonnte_token', '');
        if (!empty($fonnte_token)) {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.fonnte.com/validate',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => array(
                    'target' => $phone,
                    'countryCode' => '62'
                ),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: ' . $fonnte_token
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if (!$err && $response) {
                $data = json_decode($response, true);
                if (is_array($data)) {
                    if (isset($data['status']) && $data['status'] === true) {
                        if (!empty($data['not_registered'])) {
                            $isValid = false;
                            $fonnte_msg = 'Nomor WhatsApp tidak terdaftar di sistem.';
                        }
                    } elseif (isset($data['status']) && $data['status'] === false) {
                        $reason = isset($data['reason']) ? strtolower($data['reason']) : '';
                        if (strpos($reason, 'invalid') !== false || strpos($reason, 'required') !== false) {
                            $isValid = false;
                            $fonnte_msg = 'Nomor tidak valid (Fonnte API).';
                        }
                    }
                }
            }
        }

        if (!$isValid) {
            return rest_ensure_response([
                'valid' => false,
                'reason' => 'fonnte_validation_failed',
                'message' => $fonnte_msg
            ]);
        }

        set_transient($cache_key, 1, HOUR_IN_SECONDS);

        return rest_ensure_response([
            'valid' => true,
            'phone' => $phone
        ]);
    }

    public static function email_check(WP_REST_Request $request) {
        $email_raw = $request->get_param('email') ?? '';
        $email = strtolower(trim($email_raw));

        if (empty($email)) {
            return rest_ensure_response(['valid' => false, 'reason' => 'empty']);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return rest_ensure_response(['valid' => false, 'reason' => 'format_invalid']);
        }

        $parts = explode('@', $email, 2);
        if (count($parts) !== 2) {
            return rest_ensure_response(['valid' => false, 'reason' => 'format_invalid']);
        }
        $domain = $parts[1];

        $trusted_domains = [
            'gmail.com', 'yahoo.com', 'yahoo.co.id',
            'outlook.com', 'hotmail.com', 'live.com',
            'icloud.com', 'me.com', 'mac.com',
            'proton.me', 'protonmail.com',
            'zoho.com', 'petalmail.com',
            'aol.com', 'mail.com', 'gmx.com', 'gmx.net',
            'yandex.com', 'yandex.ru',
            'tutanota.com', 'tuta.io',
            'rocketmail.com',
        ];

        $isTrusted = in_array($domain, $trusted_domains);

        $hasMX = false;
        if (checkdnsrr($domain, 'MX')) {
            $hasMX = true;
        } elseif (checkdnsrr($domain, 'A')) {
            $hasMX = true;
        }

        if (!$isTrusted && !$hasMX) {
            return rest_ensure_response([
                'valid'     => false,
                'reason'    => 'domain_not_found',
                'domain'    => $domain,
                'trusted'   => false,
            ]);
        }

        $reacher_key = get_option('adv_reacher_api_key', '');
        if (!empty($reacher_key)) {
            $url = 'https://api.solusimarketing.xyz/reacher';
            
            $response = wp_remote_post($url, [
                'timeout' => 15,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-API-KEY' => trim($reacher_key)
                ],
                'body' => json_encode(['to_email' => $email]),
                'sslverify' => false
            ]);

            if (!is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
                $result = json_decode($body, true);
                
                if (is_array($result) && isset($result['is_reachable'])) {
                    $is_reachable = $result['is_reachable'];
                    
                    $bad_states = ['invalid']; // we reject only 'invalid', let 'risky' and 'unknown' pass for safety
                    
                    if ($is_reachable === false || $is_reachable === 'false' || in_array(strtolower((string)$is_reachable), $bad_states)) {
                        return rest_ensure_response([
                            'valid'     => false,
                            'reason'    => 'email_bounced',
                            'domain'    => $domain,
                            'trusted'   => $isTrusted,
                            'message'   => 'Alamat email tidak aktif atau salah ketik'
                        ]);
                    }
                }
            } else {
                // Fallback to valid if connection fails so users can still submit form
                return rest_ensure_response([
                    'valid'     => true,
                    'reason'    => 'reacher_timeout',
                    'domain'    => $domain,
                    'trusted'   => $isTrusted,
                ]);
            }
        }

        return rest_ensure_response([
            'valid'   => true,
            'trusted' => $isTrusted,
            'domain'  => $domain,
        ]);
    }

    public static function get_withdrawals(WP_REST_Request $request) {
        global $wpdb;
        $table = $wpdb->prefix . 'adv_withdrawals';
        $table_products = $wpdb->prefix . 'adv_products';
        $users = $wpdb->users;
        $user_id = get_current_user_id();
        $is_all = $request->get_param('all');
        
        $sql = "SELECT w.*, u.display_name as advertiser_name FROM $table w LEFT JOIN $users u ON w.advertiser_id = u.ID";
        
        if (current_user_can('manage_options') && $is_all === '1') {
            $withdrawals = $wpdb->get_results("$sql ORDER BY w.created_at DESC");
        } else {
            $withdrawals = $wpdb->get_results($wpdb->prepare("$sql WHERE w.advertiser_id = %d ORDER BY w.created_at DESC", $user_id));
        }

        // Map product IDs to names
        foreach ($withdrawals as $wd) {
            $product_ids = json_decode($wd->products, true);
            if (is_array($product_ids) && count($product_ids) > 0) {
                $ids = implode(',', array_map('intval', $product_ids));
                $names = $wpdb->get_col("SELECT name FROM $table_products WHERE id IN ($ids)");
                $wd->product_names = implode(', ', $names);
            } else {
                $wd->product_names = '';
            }
        }
        
        return rest_ensure_response($withdrawals);
    }

    public static function save_withdrawal(WP_REST_Request $request) {
        global $wpdb;
        $table = $wpdb->prefix . 'adv_withdrawals';
        $user_id = get_current_user_id();
        $params = $request->get_json_params();

        $data = [
            'advertiser_id' => $user_id,
            'date_start' => sanitize_text_field($params['date_start']),
            'date_end' => sanitize_text_field($params['date_end']),
            'products' => wp_json_encode($params['products']),
            'report_image' => esc_url_raw($params['report_image']),
            'ad_spent' => floatval($params['ad_spent']),
            'omset' => floatval($params['omset']),
            'profit_share' => floatval($params['profit_share']),
            'nominal_wd' => floatval($params['nominal_wd']),
            'status' => 'Pending',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        $wpdb->insert($table, $data);
        return rest_ensure_response(['success' => true]);
    }

    public static function update_withdrawal_status(WP_REST_Request $request) {
        global $wpdb;
        $table = $wpdb->prefix . 'adv_withdrawals';
        $id = intval($request->get_param('id'));
        $params = $request->get_json_params();

        $wd = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
        if (!$wd) {
            return new WP_Error('not_found', 'Withdrawal not found', ['status' => 404]);
        }

        $is_admin = current_user_can('manage_options');
        $user_id = get_current_user_id();

        if (!$is_admin && $wd->advertiser_id != $user_id) {
            return new WP_Error('forbidden', 'You are not allowed to edit this withdrawal', ['status' => 403]);
        }

        $data = [
            'updated_at' => current_time('mysql')
        ];

        // If advertiser, only allow updating if status is Pending or Need Revision
        if (!$is_admin) {
            if ($wd->status !== 'Pending' && $wd->status !== 'Need Revision') {
                return new WP_Error('forbidden', 'Cannot edit withdrawal in this status', ['status' => 403]);
            }
            if (isset($params['date_start'])) $data['date_start'] = sanitize_text_field($params['date_start']);
            if (isset($params['date_end'])) $data['date_end'] = sanitize_text_field($params['date_end']);
            if (isset($params['products'])) $data['products'] = json_encode($params['products']);
            if (isset($params['report_image'])) $data['report_image'] = esc_url_raw($params['report_image']);
            if (isset($params['ad_spent'])) $data['ad_spent'] = floatval($params['ad_spent']);
            if (isset($params['omset'])) $data['omset'] = floatval($params['omset']);
            if (isset($params['profit_share'])) $data['profit_share'] = floatval($params['profit_share']);
            if (isset($params['nominal_wd'])) $data['nominal_wd'] = floatval($params['nominal_wd']);
        } else {
            // Admin updates status and receipt
            if (isset($params['status'])) $data['status'] = sanitize_text_field($params['status']);
            if (isset($params['transfer_receipt'])) $data['transfer_receipt'] = esc_url_raw($params['transfer_receipt']);
        }

        $wpdb->update($table, $data, ['id' => $id]);
        return rest_ensure_response(['success' => true]);
    }


    public static function get_advertisers(WP_REST_Request $request) {
        if (!current_user_can('manage_options')) return new WP_Error('rest_forbidden', 'Sorry, you are not allowed to do that.', ['status' => 401]);
        
        // Fetch users who can act as advertisers (solusi_advertiser and administrator)
        $unique_users = get_users(['role__in' => ['solusi_advertiser', 'administrator']]);
        
        $result = [];
        foreach ($unique_users as $user) {
            $result[] = [
                'id' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email,
                'username' => $user->user_login,
                'registered' => $user->user_registered
            ];
        }
        return rest_ensure_response($result);
    }

    public static function save_advertiser(WP_REST_Request $request) {
        if (!current_user_can('manage_options')) return new WP_Error('rest_forbidden', 'Sorry, you are not allowed to do that.', ['status' => 401]);
        
        $params = $request->get_json_params();
        $id = isset($params['id']) ? intval($params['id']) : 0;
        
        $userdata = [
            'user_login'  => sanitize_user($params['username']),
            'user_email'  => sanitize_email($params['email']),
            'display_name'=> sanitize_text_field($params['name']),
            'role'        => 'advertiser'
        ];

        if (!empty($params['password'])) {
            $userdata['user_pass'] = $params['password'];
        }

        if ($id > 0) {
            $userdata['ID'] = $id;
            $user_id = wp_update_user($userdata);
        } else {
            if (empty($params['password'])) {
                return new WP_Error('missing_password', 'Password is required for new user.', ['status' => 400]);
            }
            $user_id = wp_insert_user($userdata);
        }

        if (is_wp_error($user_id)) {
            return $user_id;
        }

        return rest_ensure_response(['success' => true, 'id' => $user_id]);
    }

    public static function delete_withdrawal(WP_REST_Request $request) {
        if (!current_user_can('manage_options')) return new WP_Error('rest_forbidden', 'Sorry, you are not allowed to do that.', ['status' => 401]);
        
        global $wpdb;
        $table = $wpdb->prefix . 'adv_withdrawals';
        $id = intval($request->get_param('id'));
        
        $wpdb->delete($table, ['id' => $id]);
        return rest_ensure_response(['success' => true]);
    }

    public static function delete_advertiser(WP_REST_Request $request) {
        if (!current_user_can('manage_options')) return new WP_Error('rest_forbidden', 'Sorry, you are not allowed to do that.', ['status' => 401]);
        
        $id = intval($request->get_param('id'));
        if ($id === get_current_user_id()) {
            return new WP_Error('cant_delete_self', 'Cannot delete yourself.', ['status' => 400]);
        }
        
        require_once(ABSPATH . 'wp-admin/includes/user.php');
        wp_delete_user($id);
        
        return rest_ensure_response(['success' => true]);
    }
}
