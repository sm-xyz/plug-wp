<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Illu_Shield_Security {

    private int   $max_login_attempts;
    private int   $lockout_duration = 900;
    private array $settings;

    public function __construct() {
        $this->settings           = get_option( 'illu_shield_settings', [] );
        $this->max_login_attempts = max( 1, intval( $this->settings['max_failures'] ?? 3 ) );

        // ── Micro-Firewall ────────────────────────────────────────────────────
        if ( ( $this->settings['enable_firewall'] ?? 'yes' ) === 'yes' ) {
            add_action( 'init', [ $this, 'micro_firewall' ],       1 );
            add_action( 'init', [ $this, 'block_bad_bots' ],       1 );
            add_filter( 'preprocess_comment', [ $this, 'anti_spam_comment' ], 1 );
        }

        // ── Global IP Block ───────────────────────────────────────────────────
        add_action( 'init', [ $this, 'block_blacklisted_ips' ], 0 );

        // ── Login Protection ──────────────────────────────────────────────────
        if ( ( $this->settings['enable_login_protection'] ?? 'yes' ) === 'yes' ) {
            add_filter( 'authenticate',     [ $this, 'check_login_attempts' ], 10, 3 );
            add_action( 'wp_login_failed',  [ $this, 'log_failed_attempt' ] );
            add_action( 'wp_login',         [ $this, 'clear_failed_attempts' ], 10, 2 );
            add_action( 'login_form',       [ $this, 'add_login_honeypot' ] );
            add_filter( 'authenticate',     [ $this, 'check_login_honeypot' ], 5, 3 );
        }

        // ── Hardening ─────────────────────────────────────────────────────────
        add_action( 'admin_init', [ $this, 'secure_upload_folder' ] );
        add_action( 'admin_init', [ $this, 'protect_wp_config' ] );

        // ── Audit Logs ────────────────────────────────────────────────────────
        add_action( 'updated_option',    [ $this, 'audit_option_updates' ],   10, 3 );
        add_action( 'profile_update',    [ $this, 'audit_profile_update' ],   10, 2 );
        add_action( 'delete_user',       [ $this, 'audit_delete_user' ] );
        add_action( 'activated_plugin',  [ $this, 'audit_plugin_activated' ], 10, 2 );
        add_action( 'deactivated_plugin',[ $this, 'audit_plugin_deactivated'],10, 2 );
        add_action( 'user_register',     [ $this, 'audit_new_user' ] );
        add_action( 'set_user_role',     [ $this, 'audit_role_change' ],      10, 3 );

        // ── XML-RPC ───────────────────────────────────────────────────────────
        if ( ( $this->settings['disable_xmlrpc'] ?? 'yes' ) === 'yes' ) {
            add_filter( 'xmlrpc_enabled', '__return_false' );
            add_filter( 'wp_headers',     [ $this, 'remove_x_pingback' ] );
            add_action( 'init',           [ $this, 'block_xmlrpc_requests' ], 1 );
        }

        // ── Advanced Hardening ────────────────────────────────────────────────
        if ( ( $this->settings['disable_app_passwords'] ?? 'yes' ) === 'yes' ) {
            add_filter( 'wp_is_application_passwords_available', '__return_false' );
        }
        if ( ( $this->settings['disable_file_edit'] ?? 'yes' ) === 'yes' ) {
            if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) define( 'DISALLOW_FILE_EDIT', true );
        }
        if ( ( $this->settings['disable_file_mods'] ?? 'no' ) === 'yes' ) {
            if ( ! defined( 'DISALLOW_FILE_MODS' ) ) define( 'DISALLOW_FILE_MODS', true );
        }
        if ( ( $this->settings['hide_wp_version'] ?? 'yes' ) === 'yes' ) {
            remove_action( 'wp_head', 'wp_generator' );
            add_filter( 'the_generator', '__return_empty_string' );
        }
        if ( ( $this->settings['disable_author_archives'] ?? 'yes' ) === 'yes' ) {
            add_action( 'template_redirect', [ $this, 'disable_author_archives' ] );
        }
        if ( ( $this->settings['protect_rest_api'] ?? 'yes' ) === 'yes' ) {
            add_filter( 'rest_authentication_errors', [ $this, 'restrict_rest_api_global' ] );
        }
        if ( ( $this->settings['prevent_concurrent_logins'] ?? 'yes' ) === 'yes' ) {
            add_filter( 'authenticate',    [ $this, 'check_concurrent_logins' ], 30, 3 );
            add_action( 'wp_login',        [ $this, 'set_concurrent_login_token' ], 10, 2 );
            add_action( 'clear_auth_cookie', [ $this, 'clear_concurrent_login_token' ] );
        }
        if ( ( $this->settings['auto_ban_404'] ?? 'yes' ) === 'yes' ) {
            add_action( 'template_redirect', [ $this, 'track_404_requests' ] );
        }
        if ( ( $this->settings['block_malicious_queries'] ?? 'yes' ) === 'yes' ) {
            add_action( 'init', [ $this, 'block_malicious_queries' ], 1 );
        }
        if ( ( $this->settings['security_headers'] ?? 'yes' ) === 'yes' ) {
            add_action( 'send_headers',           [ $this, 'add_security_headers' ] );
            add_filter( 'script_loader_tag',      [ $this, 'inject_csp_nonce' ], 10, 3 );
            add_filter( 'wp_inline_script_attributes', [ $this, 'inject_inline_csp_nonce' ] );
        }

        // ── URL Honeypot & Session ────────────────────────────────────────────
        add_action( 'init', [ $this, 'check_url_honeypot' ],       1 );
        add_action( 'init', [ $this, 'check_session_idle_timeout' ] );
        add_action( 'wp_login', [ $this, 'reset_last_activity' ], 10, 2 );

        // ── Custom Login URL ──────────────────────────────────────────────────
        if ( ! empty( $this->settings['custom_login_slug'] ) ) {
            add_action( 'init',           [ $this, 'custom_login_obfuscation' ], 1 );
            add_filter( 'site_url',       [ $this, 'filter_login_url' ], 10, 3 );
            add_filter( 'network_site_url',[ $this, 'filter_login_url' ], 10, 3 );
            add_filter( 'wp_redirect',    [ $this, 'filter_wp_redirect' ], 10, 2 );
        }

        // ── REST endpoints (rate limit + CSP report) ──────────────────────────
        add_action( 'rest_api_init', [ $this, 'setup_rest_endpoints' ] );

        // ── Realtime FIM ──────────────────────────────────────────────────────
        add_action( 'init', [ $this, 'start_realtime_fim' ], 1 );

        // ── Country Block (Cloudflare) ────────────────────────────────────────
        add_action( 'init', [ $this, 'check_country_block' ], 0 );

        // ── security.txt ─────────────────────────────────────────────────────
        add_action( 'init', [ $this, 'serve_security_txt' ] );
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // WHITELIST CHECK — satu-satunya titik pemanggilan ke Request_Context
    // ═══════════════════════════════════════════════════════════════════════════

    private function is_whitelisted(): bool {
        return Illu_Shield_Request_Context::is_whitelisted();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // CUSTOM LOGIN URL
    // ═══════════════════════════════════════════════════════════════════════════

    public function filter_login_url( $url, $path, $scheme ) {
        if ( strpos( $url, 'wp-login.php' ) !== false ) {
            $slug = trim( $this->settings['custom_login_slug'], '/' );
            $url  = str_replace( 'wp-login.php', $slug, $url );
        }
        return $url;
    }

    public function filter_wp_redirect( $location, $status ) {
        if ( strpos( $location, 'wp-login.php' ) !== false ) {
            $slug     = trim( $this->settings['custom_login_slug'], '/' );
            $location = str_replace( 'wp-login.php', $slug, $location );
        }
        return $location;
    }

    public function custom_login_obfuscation() {
        $slug = trim( $this->settings['custom_login_slug'] ?? '', '/' );
        if ( empty( $slug ) ) return;

        $path = trim( parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );

        if ( $path === $slug ) {
            $GLOBALS['illu_custom_login_accessed'] = true;
            $_SERVER['REQUEST_URI'] = '/' . $slug;
            require_once ABSPATH . 'wp-login.php';
            exit;
        }

        if ( $path === 'wp-login.php' && ! isset( $GLOBALS['illu_custom_login_accessed'] ) ) {
            $action = $_GET['action'] ?? '';
            if ( in_array( $action, [ 'logout', 'postpass' ], true ) ) return;
            if ( wp_doing_ajax() ) return;

            $this->register_violation( 'Scanner Detected', 'Direct wp-login.php access blocked (obfuscated).' );
            global $wp_query;
            $wp_query->set_404();
            status_header( 404 );
            nocache_headers();
            require get_query_template( '404' );
            exit;
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // SECURITY.TXT
    // ═══════════════════════════════════════════════════════════════════════════

    public function serve_security_txt() {
        $path = parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
        if ( $path === '/.well-known/security.txt' || $path === '/security.txt' ) {
            header( 'Content-Type: text/plain' );
            $email = get_option( 'admin_email' );
            $url   = home_url();
            echo "Contact: mailto:{$email}\n";
            echo "Expires: " . gmdate( 'Y-m-d\TH:i:s\Z', strtotime( '+1 year' ) ) . "\n";
            echo "Preferred-Languages: id, en\n";
            echo "Canonical: {$url}/.well-known/security.txt\n";
            exit;
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // REST API — RATE LIMIT + CSP REPORT
    // ═══════════════════════════════════════════════════════════════════════════

    public function setup_rest_endpoints() {
        $this->rate_limit_rest_api();

        register_rest_route( 'illu-shield/v1', '/csp-report', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'log_csp_violation' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function rate_limit_rest_api() {
        // ▶ Bypass untuk sharelink/webhook (single call, consolidated)
        if ( $this->is_whitelisted() ) return;
        if ( current_user_can( 'edit_posts' ) ) return;

        $ip          = Illu_Shield_DB::get_client_ip();
        $option_name = 'illu_rest_rl_' . md5( $ip );
        $requests    = intval( get_transient( $option_name ) ) + 1;

        if ( $requests === 1 ) {
            set_transient( $option_name, $requests, MINUTE_IN_SECONDS );
        } else {
            $ttl = (int) get_option( '_transient_timeout_' . $option_name ) - time();
            if ( $ttl > 0 ) set_transient( $option_name, $requests, $ttl );
            if ( $requests > 60 ) {
                header( 'HTTP/1.1 429 Too Many Requests' );
                header( 'Retry-After: 60' );
                $this->register_violation( 'REST API Rate Limit', 'Over 60 requests/min.' );
                die( json_encode( [ 'code' => 'rest_rate_limited', 'message' => 'Too many requests.', 'data' => [ 'status' => 429 ] ] ) );
            }
        }
    }

    public function log_csp_violation( WP_REST_Request $request ) {
        $body = $request->get_body();
        if ( strlen( $body ) > 2000 ) {
            return new WP_REST_Response( [ 'status' => 'payload_too_large' ], 400 );
        }
        $data = json_decode( $body, true );
        if ( $data && isset( $data['csp-report'] ) && is_array( $data['csp-report'] ) ) {
            $report   = $data['csp-report'];
            $blocked  = sanitize_text_field( $report['blocked-uri']          ?? 'unknown' );
            $directive= sanitize_text_field( $report['violated-directive']   ?? 'unknown' );
            $locked   = $this->register_violation( 'CSP Violation', "Blocked URI: {$blocked}, Directive: {$directive}" );
            return new WP_REST_Response( [ 'status' => $locked ? 'locked' : 'logged' ], $locked ? 403 : 200 );
        }
        return new WP_REST_Response( [ 'status' => 'invalid_format' ], 400 );
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // COUNTRY BLOCK
    // ═══════════════════════════════════════════════════════════════════════════

    public function check_country_block() {
        if ( $this->is_whitelisted() ) return;

        $blocked_raw = $this->settings['blocked_countries'] ?? '';
        if ( empty( $blocked_raw ) ) return;

        $blocked         = array_map( 'trim', explode( ',', strtoupper( $blocked_raw ) ) );
        $visitor_country = $_SERVER['HTTP_CF_IPCOUNTRY'] ?? '';

        if ( ! empty( $visitor_country ) && in_array( $visitor_country, $blocked, true ) ) {
            $this->register_violation( 'Country Blocked', "Blocked country: $visitor_country" );
            header( 'HTTP/1.1 403 Forbidden' );
            die( 'Access Denied: Your country is not permitted.' );
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // AUTHOR ARCHIVES
    // ═══════════════════════════════════════════════════════════════════════════

    public function disable_author_archives() {
        if ( is_author() ) {
            global $wp_query;
            $wp_query->set_404();
            status_header( 404 );
            nocache_headers();
            $this->register_violation( 'Author Archive Access', 'User enumeration via author archive blocked.' );
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // REST API — GLOBAL PROTECTION
    // ═══════════════════════════════════════════════════════════════════════════

    public function restrict_rest_api_global( $result ) {
        if ( ! empty( $result ) ) return $result;
        if ( ! is_user_logged_in() ) {
            // Allow sharelink/canvas-app/webhook endpoints (single consolidated check)
            if ( $this->is_whitelisted() ) return $result;

            $path = $_SERVER['REQUEST_URI'] ?? '';
            // Allow public WP content endpoints
            if ( strpos( $path, '/wp-json/wp/v2/posts' ) !== false ||
                 strpos( $path, '/wp-json/wp/v2/pages' ) !== false ) {
                return $result;
            }

            $this->register_violation( 'REST API Blocked', 'Unauthenticated access to protected REST endpoint.' );
            return new WP_Error(
                'rest_not_logged_in',
                'Authentication required. Endpoint protected by Illu Shield.',
                [ 'status' => 401 ]
            );
        }
        return $result;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // HONEYPOT
    // ═══════════════════════════════════════════════════════════════════════════

    public function add_login_honeypot() {
        echo '<p style="display:none!important" aria-hidden="true">
            <input type="text" name="illu_honeypot_field" value="" tabindex="-1" autocomplete="off">
        </p>';
    }

    public function check_login_honeypot( $user, $username, $password ) {
        if ( ! empty( $_POST['illu_honeypot_field'] ) ) {
            $this->register_violation( 'Bot Detected (Honeypot)', "Login honeypot triggered by: $username" );
            return new WP_Error( 'honeypot_triggered', 'Bot detected. Access denied.' );
        }
        return $user;
    }

    public function check_url_honeypot() {
        $honeypot_paths = [
            '/.env', '/.git/HEAD', '/phpinfo.php', '/wp-config.php.bak',
            '/xmlrpc.php', '/.htaccess', '/wp-content/debug.log',
            '/backup.zip', '/dump.sql', '/wp-admin/install.php',
            '/server-status', '/server-info',
        ];
        $current_path = parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
        if ( in_array( $current_path, $honeypot_paths, true ) ) {
            for ( $i = 0; $i < $this->max_login_attempts; $i++ ) {
                $this->register_violation( 'Scanner Detected', "Honeypot path accessed: $current_path" );
            }
            header( 'HTTP/1.1 403 Forbidden' );
            die( 'Access Denied' );
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // SESSION IDLE TIMEOUT
    // ═══════════════════════════════════════════════════════════════════════════

    public function check_session_idle_timeout() {
        if ( ! is_user_logged_in() ) return;
        if ( wp_doing_ajax() && isset( $_POST['action'] ) && $_POST['action'] === 'heartbeat' ) return;

        $uid           = get_current_user_id();
        $last_activity = get_user_meta( $uid, 'illu_last_activity', true );
        $timeout       = 60 * MINUTE_IN_SECONDS;

        if ( $last_activity && ( time() - $last_activity ) > $timeout ) {
            wp_logout();
            wp_redirect( wp_login_url() . '?timeout=1' );
            exit;
        }
        update_user_meta( $uid, 'illu_last_activity', time() );
    }

    public function reset_last_activity( $user_login, $user ) {
        update_user_meta( $user->ID, 'illu_last_activity', time() );
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // CONCURRENT LOGINS
    // ═══════════════════════════════════════════════════════════════════════════

    public function set_concurrent_login_token( $user_login, $user ) {
        if ( ! $user instanceof WP_User ) return;
        $token = wp_generate_password( 20, false );
        update_user_meta( $user->ID, 'illu_concurrent_token', $token );
        setcookie( 'illu_concurrent_token', $token, time() + 14 * DAY_IN_SECONDS,
            COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
    }

    public function clear_concurrent_login_token() {
        if ( isset( $_COOKIE['illu_concurrent_token'] ) ) {
            setcookie( 'illu_concurrent_token', ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
        }
    }

    public function check_concurrent_logins( $user, $username, $password ) {
        if ( is_a( $user, 'WP_User' ) ) {
            $saved  = get_user_meta( $user->ID, 'illu_concurrent_token', true );
            $cookie = $_COOKIE['illu_concurrent_token'] ?? '';
            if ( ! empty( $saved ) && $saved !== $cookie ) {
                $manager = WP_Session_Tokens::get_instance( $user->ID );
                $manager->destroy_all();
                Illu_Shield_DB::log( 'Concurrent Login Prevented', "User {$user->user_login} new session; previous sessions destroyed." );
            }
        }
        return $user;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 404 AUTO-BAN
    // ═══════════════════════════════════════════════════════════════════════════

    public function track_404_requests() {
        if ( ! is_404() ) return;
        $ip    = $this->get_client_ip();
        $key   = 'illu_404_' . md5( $ip );
        $count = (int) get_transient( $key ) + 1;
        set_transient( $key, $count, 60 );
        if ( $count >= 20 ) {
            $this->register_violation( 'Scanner Detected', '20+ 404 requests/min. Bot scanner detected.' );
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // MALICIOUS QUERIES
    // ═══════════════════════════════════════════════════════════════════════════

    public function block_malicious_queries() {
        $uri      = $_SERVER['REQUEST_URI'] ?? '';
        $patterns = [
            '/<script.*?>/i', '/UNION.*?SELECT/i', '/CONCAT\(/i',
            '/base64_decode\(/i', '/eval\(/i', '/etc\/passwd/i', '/wp-config\.php/i',
        ];
        foreach ( $patterns as $pattern ) {
            if ( preg_match( $pattern, urldecode( $uri ) ) ) {
                $this->register_violation( 'Malicious Query', "Injection payload in URL: " . esc_html( $uri ) );
                wp_die( 'Request blocked by Illu Shield WAF.', 'Access Denied', [ 'response' => 403 ] );
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // SECURITY HEADERS + CSP NONCE
    // ═══════════════════════════════════════════════════════════════════════════

    public function add_security_headers() {
        if ( headers_sent() ) return;
        if ( ! defined( 'ILLU_CSP_NONCE' ) ) {
            try {
                define( 'ILLU_CSP_NONCE', base64_encode( random_bytes( 16 ) ) );
            } catch ( Exception $e ) {
                define( 'ILLU_CSP_NONCE', base64_encode( wp_generate_password( 16, true ) ) );
            }
        }

        header( 'X-Frame-Options: SAMEORIGIN' );
        header( 'X-Content-Type-Options: nosniff' );
        header( 'X-XSS-Protection: 1; mode=block' );
        header( 'Referrer-Policy: strict-origin-when-cross-origin' );
        header( 'Permissions-Policy: geolocation=(), microphone=(), camera=()' );
        header( 'Strict-Transport-Security: max-age=63072000; includeSubDomains; preload' );

        $report_uri = esc_url( home_url( '/wp-json/illu-shield/v1/csp-report' ) );
        $csp = "default-src 'self' https: data: blob:; "
             . "script-src 'self' 'nonce-" . ILLU_CSP_NONCE . "' https:; "
             . "style-src 'self' 'unsafe-inline' https:; "
             . "img-src 'self' data: https:; "
             . "font-src 'self' data: https:; "
             . "report-uri $report_uri;";
        header( "Content-Security-Policy-Report-Only: $csp" );
    }

    public function inject_csp_nonce( $tag, $handle, $src ) {
        if ( defined( 'ILLU_CSP_NONCE' ) ) {
            return str_replace( '<script ', '<script nonce="' . ILLU_CSP_NONCE . '" ', $tag );
        }
        return $tag;
    }

    public function inject_inline_csp_nonce( $attributes ) {
        if ( defined( 'ILLU_CSP_NONCE' ) ) {
            $attributes['nonce'] = ILLU_CSP_NONCE;
        }
        return $attributes;
    }

    public function remove_x_pingback( $headers ) {
        unset( $headers['X-Pingback'] );
        return $headers;
    }

    public function block_xmlrpc_requests() {
        if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
            $this->register_violation( 'XML-RPC Blocked', 'XML-RPC request blocked.' );
            header( 'HTTP/1.1 403 Forbidden' );
            die( 'XML-RPC is disabled by Illu Shield.' );
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // BAD BOTS
    // ═══════════════════════════════════════════════════════════════════════════

    public function block_bad_bots() {
        if ( $this->is_whitelisted() ) return;

        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if ( empty( trim( $ua ) ) ) {
            $this->register_violation( 'Bad Bot Blocked', 'Empty user-agent.' );
            header( 'HTTP/1.1 403 Forbidden' );
            die( 'Access Denied: Empty User-Agent.' );
        }

        $good_bots = [ 'Googlebot', 'Bingbot', 'Slurp', 'DuckDuckBot', 'Baiduspider',
                       'YandexBot', 'GPTBot', 'ClaudeBot', 'Applebot' ];
        foreach ( $good_bots as $bot ) {
            if ( stripos( $ua, $bot ) !== false ) return;
        }

        $bad_bots = [ 'curl', 'wget', 'python-requests', 'nikto', 'sqlmap', 'nmap',
                      'zgrab', 'masscan', 'libwww-perl', 'scrapy', 'postman', 'java',
                      'acunetix', 'dirbuster', 'go-http-client' ];
        foreach ( $bad_bots as $bot ) {
            if ( stripos( $ua, $bot ) !== false ) {
                $this->register_violation( 'Bad Bot Blocked', "UA: $ua" );
                header( 'HTTP/1.1 403 Forbidden' );
                die( 'Access Denied: Bad Bot detected.' );
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // ANTI-SPAM COMMENT
    // ═══════════════════════════════════════════════════════════════════════════

    public function anti_spam_comment( $commentdata ) {
        $spam_keywords   = [ 'viagra', 'cialis', 'casino', 'porn', 'xxx',
                              'seo services', 'buy followers', 'crypto investment' ];
        $content         = strtolower( $commentdata['comment_content'] );
        $author_url      = strtolower( $commentdata['comment_author_url'] );
        $clean_content   = preg_replace( '/[^a-z0-9]/', '', $content );

        foreach ( $spam_keywords as $kw ) {
            $clean_kw = preg_replace( '/[^a-z0-9]/', '', $kw );
            if ( strpos( $clean_content, $clean_kw ) !== false ||
                 strpos( $author_url, $kw ) !== false ) {
                $this->register_violation( 'Spam Comment', "Blocked spam keyword: $kw" );
                wp_die( 'Comment blocked due to spam content.' );
            }
        }
        return $commentdata;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // MICRO FIREWALL
    // ═══════════════════════════════════════════════════════════════════════════

    private function scan_input_array( array $data, array $patterns, string $source = 'INPUT', array $whitelist = [] ) {
        foreach ( $data as $key => $value ) {
            if ( is_admin() && in_array( $key, $whitelist, true ) ) continue;
            if ( is_array( $value ) ) {
                $this->scan_input_array( $value, $patterns, "{$source}[{$key}]", $whitelist );
            } elseif ( is_string( $value ) ) {
                foreach ( $patterns as $pattern ) {
                    if ( preg_match( $pattern, $value ) ) {
                        $this->register_violation( 'Firewall Blocked', "Suspicious pattern in {$source}[{$key}]" );
                        header( 'HTTP/1.1 403 Forbidden' );
                        die( 'Request blocked by Illu Shield Micro-Firewall.' );
                    }
                }
            }
        }
    }

    public function micro_firewall() {
        // ▶ Bypass untuk sharelink/webhook (single consolidated check)
        if ( $this->is_whitelisted() ) return;

        $bad_patterns = [
            '/<script.*?>.*?<\/script>/is',
            '/UNION\s+ALL\s+SELECT/is',
            '/base64_decode\(/is',
            '/eval\(/is',
            '/(?:%3C|<)iframe/is',
            '/(?:%3C|<)object/is',
            '/document\.cookie/is',
        ];

        if ( ! empty( $_GET ) ) {
            $this->scan_input_array( $_GET, $bad_patterns, 'GET' );
        }
        if ( ! empty( $_POST ) ) {
            $admin_whitelist = [ 'content', 'post_content', 'excerpt', 'illu_shield_settings' ];
            $this->scan_input_array( $_POST, $bad_patterns, 'POST', $admin_whitelist );
        }

        $uri = urldecode( $_SERVER['REQUEST_URI'] );
        if ( strpos( $uri, '../' ) !== false || strpos( $uri, '..\\'  ) !== false ||
             strpos( $uri, '/etc/passwd' ) !== false ) {
            $this->register_violation( 'Firewall Blocked', 'Directory traversal / LFI blocked.' );
            header( 'HTTP/1.1 403 Forbidden' );
            die( 'Directory traversal blocked.' );
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // IP BLACKLIST
    // ═══════════════════════════════════════════════════════════════════════════

    public function block_blacklisted_ips() {
        if ( $this->is_whitelisted() ) return;

        $ip = $this->get_client_ip();

        // 0. Whitelist check
        if ( $this->is_ip_whitelisted( $ip ) ) return;

        // 1. Exact blacklist
        $blacklist = get_option( 'illu_shield_blacklist_ips', [] );
        if ( in_array( $ip, $blacklist, true ) ) {
            $auto_days = intval( $this->settings['auto_blacklist_days'] ?? 0 );
            if ( $auto_days > 0 ) {
                $timestamps = get_option( 'illu_shield_blacklist_timestamps', [] );
                $banned_at  = $timestamps[ $ip ] ?? 0;
                if ( $banned_at > 0 && ( time() - $banned_at ) > ( $auto_days * 86400 ) ) {
                    $blacklist = array_values( array_diff( $blacklist, [ $ip ] ) );
                    update_option( 'illu_shield_blacklist_ips', $blacklist );
                    unset( $timestamps[ $ip ] );
                    update_option( 'illu_shield_blacklist_timestamps', $timestamps );
                    Illu_Shield_DB::log( 'IP Unbanned', "IP {$ip} auto-removed after {$auto_days} days." );
                    return;
                }
            }
            header( 'HTTP/1.1 403 Forbidden' );
            die( 'Access Denied: Your IP has been blacklisted.' );
        }

        // 2. Wildcard blacklist
        $wildcards = get_option( 'illu_shield_wildcard_ips', [] );
        foreach ( $wildcards as $pattern ) {
            $pattern = trim( $pattern );
            if ( empty( $pattern ) ) continue;
            $regex = str_replace( '\*', '.*', preg_quote( $pattern, '/' ) );
            if ( preg_match( '/^' . $regex . '$/', $ip ) ) {
                header( 'HTTP/1.1 403 Forbidden' );
                die( 'Access Denied: Your IP range is blacklisted.' );
            }
        }

        // 3. Temporary lockout
        $lockout_end = get_transient( 'illu_lockout_' . md5( $ip ) );
        if ( $lockout_end && time() < $lockout_end ) {
            $remaining = ceil( ( $lockout_end - time() ) / 60 );
            header( 'HTTP/1.1 429 Too Many Requests' );
            die( "Access Denied: Temporarily locked out. Try again in $remaining minutes." );
        }
    }

    private function is_ip_whitelisted( string $ip ): bool {
        $whitelist = get_option( 'illu_shield_whitelist_ips', [] );
        if ( in_array( $ip, $whitelist, true ) ) return true;

        $wildcards = get_option( 'illu_shield_wildcard_whitelist_ips', [] );
        foreach ( $wildcards as $pattern ) {
            $pattern = trim( $pattern );
            if ( empty( $pattern ) ) continue;
            $regex = str_replace( '\*', '.*', preg_quote( $pattern, '/' ) );
            if ( preg_match( '/^' . $regex . '$/', $ip ) ) return true;
        }
        return false;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // LOGIN ATTEMPTS
    // ═══════════════════════════════════════════════════════════════════════════

    public function check_login_attempts( $user, $username, $password ) {
        $ip          = $this->get_client_ip();
        $lockout_end = get_transient( 'illu_lockout_' . md5( $ip ) );
        if ( $lockout_end && time() < $lockout_end ) {
            $remaining = ceil( ( $lockout_end - time() ) / 60 );
            return new WP_Error( 'too_many_retries',
                "<strong>ERROR</strong>: Terlalu banyak percobaan login. Coba lagi dalam $remaining menit." );
        }

        $attempts = intval( get_transient( 'illu_lf_' . md5( $ip ) ) );
        if ( $attempts >= $this->max_login_attempts ) {
            Illu_Shield_DB::log( 'Brute Force Blocked', "Blocked login from IP. Failures: $attempts." );
            return new WP_Error( 'too_many_retries',
                '<strong>ERROR</strong>: Terlalu banyak percobaan login gagal. Coba lagi nanti.' );
        }
        return $user;
    }

    public function log_failed_attempt( $username ) {
        $this->register_violation( 'Login Failed', "Failed login: $username" );
    }

    public function clear_failed_attempts( $username, $user = null ) {
        $ip = $this->get_client_ip();
        delete_transient( 'illu_lf_' . md5( $ip ) );

        if ( $user && user_can( $user->ID, 'edit_posts' ) &&
             ( $this->settings['email_alert_new_login'] ?? 'no' ) === 'yes' ) {
            $known_ips = get_user_meta( $user->ID, 'illu_known_ips', true ) ?: [];
            if ( ! in_array( $ip, $known_ips, true ) ) {
                $known_ips[] = $ip;
                update_user_meta( $user->ID, 'illu_known_ips', $known_ips );
                $role = implode( ', ', (array) $user->roles );
                wp_mail( get_option( 'admin_email' ),
                    '[Illu Shield] Login dari IP Baru',
                    "User {$username} (Role: {$role}) login dari IP baru: {$ip}\nJika bukan Anda, segera ganti password." );
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // VIOLATION REGISTER — tiered lockout logic
    // ═══════════════════════════════════════════════════════════════════════════

    public function register_violation( string $event_type, string $description = '' ): bool {
        $ip = $this->get_client_ip();

        if ( $this->is_ip_whitelisted( $ip ) ) return false;

        $blacklist = get_option( 'illu_shield_blacklist_ips', [] );
        if ( in_array( $ip, $blacklist, true ) ) return true;

        $lf_key    = 'illu_lf_' . md5( $ip );
        $level_key = 'illu_lockout_level_' . md5( $ip );
        $attempts  = intval( get_transient( $lf_key ) ) + 1;
        set_transient( $lf_key, $attempts, 24 * HOUR_IN_SECONDS );

        if ( $attempts >= $this->max_login_attempts ) {
            $level = intval( get_transient( $level_key ) ) + 1;
            set_transient( $level_key, $level, 90 * DAY_IN_SECONDS );
            delete_transient( $lf_key );

            $t1 = intval( $this->settings['lockout_tier1_minutes'] ?? 15 );
            $t2 = intval( $this->settings['lockout_tier2_hours']   ?? 1  );
            $t3 = intval( $this->settings['lockout_tier3_days']    ?? 1  );

            if ( $level === 1 ) {
                $d = $t1 * 60;
                set_transient( 'illu_lockout_' . md5( $ip ), time() + $d, $d );
                Illu_Shield_DB::log( "IP Lockout ({$t1}m)", "Locked {$t1}min. Event: $event_type." );
            } elseif ( $level === 2 ) {
                $d = $t2 * 3600;
                set_transient( 'illu_lockout_' . md5( $ip ), time() + $d, $d );
                Illu_Shield_DB::log( "IP Lockout ({$t2}h)", "Locked {$t2}hr. Event: $event_type." );
            } elseif ( $level === 3 ) {
                $d = $t3 * 86400;
                set_transient( 'illu_lockout_' . md5( $ip ), time() + $d, $d );
                Illu_Shield_DB::log( "IP Lockout ({$t3}d)", "Locked {$t3}day. Event: $event_type." );
            } else {
                // Tier 4: permanent blacklist
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    $blacklist[] = $ip;
                    update_option( 'illu_shield_blacklist_ips', array_unique( $blacklist ) );
                    $ts       = get_option( 'illu_shield_blacklist_timestamps', [] );
                    $ts[$ip]  = time();
                    update_option( 'illu_shield_blacklist_timestamps', $ts );
                    do_action( 'illu_shield_ip_blacklisted', $ip );
                    if ( ( $this->settings['email_alert_blacklist'] ?? 'yes' ) === 'yes' ) {
                        wp_mail( get_option( 'admin_email' ),
                            '[Illu Shield] IP Baru Diblokir: ' . $ip,
                            "IP $ip diblokir (Tier 4).\nEvent: $event_type\nDesc: $description" );
                    }
                }
                delete_transient( $level_key );
                Illu_Shield_DB::log( 'IP Blacklisted', "Permanent blacklist. Event: $event_type. IP: $ip" );
            }
            return true;
        }

        if ( ! empty( $description ) ) {
            Illu_Shield_DB::log( $event_type, $description );
        } else {
            Illu_Shield_DB::log( $event_type, "Violation from IP. Count: $attempts" );
        }
        return false;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // FILE INTEGRITY MONITORING
    // ═══════════════════════════════════════════════════════════════════════════

    public static function snapshot_files() {
        $critical = array_merge(
            glob( ABSPATH . 'wp-includes/*.php' ) ?: [],
            [
                ABSPATH . 'wp-login.php',
                ABSPATH . 'wp-config.php',
                ABSPATH . 'wp-settings.php',
                ABSPATH . 'index.php',
            ],
            file_exists( ABSPATH . '.htaccess' ) ? [ ABSPATH . '.htaccess' ] : [],
            glob( ABSPATH . 'wp-admin/*.php' ) ?: [],
            glob( WP_CONTENT_DIR . '/mu-plugins/*.php' ) ?: [],
            glob( get_template_directory() . '/*.php' ) ?: [],
            [ WP_PLUGIN_DIR . '/illu-shield/illu-shield.php' ]
        );

        // Plugin main files
        foreach ( glob( WP_PLUGIN_DIR . '/*/' ) ?: [] as $plugin_dir ) {
            $php_files = glob( $plugin_dir . '*.php' ) ?: [];
            $critical  = array_merge( $critical, array_slice( $php_files, 0, 3 ) );
        }

        $hashes = [];
        foreach ( $critical as $file ) {
            if ( file_exists( $file ) && is_file( $file ) ) {
                $hashes[ $file ] = hash_file( 'sha256', $file );
            }
        }
        update_option( 'illu_shield_file_hashes', $hashes );

        $settings = get_option( 'illu_shield_settings', [] );
        if ( ! empty( $settings['fim_webhook_url'] ) ) {
            wp_remote_post( esc_url_raw( $settings['fim_webhook_url'] ), [
                'body'     => json_encode( [ 'timestamp' => time(), 'hashes' => $hashes ] ),
                'headers'  => [ 'Content-Type' => 'application/json' ],
                'blocking' => false,
            ] );
        }
    }

    public static function verify_files() {
        $settings = get_option( 'illu_shield_settings', [] );
        if ( ( $settings['email_alert_fim'] ?? 'yes' ) !== 'yes' ) return;

        $original = get_option( 'illu_shield_file_hashes', [] );
        if ( empty( $original ) ) { self::snapshot_files(); return; }

        foreach ( $original as $file => $hash ) {
            if ( file_exists( $file ) && hash_file( 'sha256', $file ) !== $hash ) {
                wp_mail(
                    get_option( 'admin_email' ),
                    '[Illu Shield] ⚠️ File Dimodifikasi: ' . basename( $file ),
                    "File {$file} telah dimodifikasi. Periksa segera — kemungkinan malware/backdoor."
                );
                Illu_Shield_DB::log( 'File Modified (FIM)', "Integrity violation: {$file}" );
                $original[ $file ] = hash_file( 'sha256', $file );
                update_option( 'illu_shield_file_hashes', $original );
            }
        }
    }

    public function start_realtime_fim() {
        if ( empty( $_POST ) && empty( $_FILES ) ) return;
        register_shutdown_function( [ __CLASS__, 'end_realtime_fim' ] );
    }

    public static function end_realtime_fim() {
        $upload_dir = wp_upload_dir();
        if ( empty( $upload_dir['basedir'] ) ) return;

        $php_files = array_merge(
            glob( $upload_dir['basedir'] . '/*.php' )     ?: [],
            glob( $upload_dir['basedir'] . '/*/*.php' )   ?: [],
            glob( $upload_dir['basedir'] . '/*/*/*.php' ) ?: []
        );

        foreach ( $php_files as $file ) {
            if ( time() - filemtime( $file ) < 60 ) {
                Illu_Shield_DB::log( 'Webshell Detected', "Auto-deleted: $file" );
                @unlink( $file );
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // HARDENING HELPERS
    // ═══════════════════════════════════════════════════════════════════════════

    public function protect_wp_config() {
        $htaccess = ABSPATH . '.htaccess';
        if ( ! file_exists( $htaccess ) || ! is_writable( $htaccess ) ) return;

        $content   = file_get_contents( $htaccess );
        $new_rules = '';

        if ( strpos( $content, 'Protect wp-config' ) === false ) {
            $new_rules .= "\n# Illu Shield: Protect wp-config.php\n<Files wp-config.php>\norder allow,deny\ndeny from all\n</Files>\n";
            Illu_Shield_DB::log( 'wp-config Secured', 'Added .htaccess protection for wp-config.php.' );
        }
        if ( strpos( $content, 'Remove WP Fingerprints' ) === false ) {
            $new_rules .= "\n# Illu Shield: Remove WP Fingerprints\n<FilesMatch \"^(readme\\.html|license\\.txt|readme\\.txt|wp-config\\.php\\.bak)$\">\norder allow,deny\ndeny from all\n</FilesMatch>\n";
        }
        if ( ! empty( $new_rules ) ) {
            file_put_contents( $htaccess, $content . $new_rules );
        }
    }

    public function secure_upload_folder() {
        $upload_dir = wp_upload_dir();
        if ( empty( $upload_dir['basedir'] ) ) return;
        $htaccess = trailingslashit( $upload_dir['basedir'] ) . '.htaccess';
        if ( ! file_exists( $htaccess ) ) {
            @file_put_contents( $htaccess, "<Files *.php>\nDeny from all\n</Files>\n" );
            Illu_Shield_DB::log( 'Upload Secured', 'Created .htaccess in uploads dir.' );
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // AUDIT LOG HOOKS
    // ═══════════════════════════════════════════════════════════════════════════

    public function audit_option_updates( $option, $old_value, $value ) {
        $tracked = [ 'illu_shield_settings', 'illu_shield_blacklist_ips', 'default_role', 'users_can_register' ];
        if ( in_array( $option, $tracked, true ) ) {
            Illu_Shield_DB::log( 'Audit: Option Updated', "Option '$option' updated by user " . get_current_user_id() . '.', get_current_user_id() );
        }
    }

    public function audit_profile_update( $user_id, $old_user_data ) {
        $by = get_current_user_id();
        Illu_Shield_DB::log( 'Audit: Profile Updated', "User $user_id profile updated by $by.", $by );
    }

    public function audit_delete_user( $id ) {
        $by = get_current_user_id();
        Illu_Shield_DB::log( 'Audit: User Deleted', "User $id deleted by $by.", $by );
    }

    public function audit_plugin_activated( $plugin, $network_wide ) {
        $by = get_current_user_id();
        Illu_Shield_DB::log( 'Audit: Plugin Activated', "Plugin '$plugin' activated by $by.", $by );
    }

    public function audit_plugin_deactivated( $plugin, $network_wide ) {
        $by = get_current_user_id();
        Illu_Shield_DB::log( 'Audit: Plugin Deactivated', "Plugin '$plugin' deactivated by $by.", $by );
    }

    public function audit_new_user( $user_id ) {
        $by = get_current_user_id();
        Illu_Shield_DB::log( 'Audit: User Registered', "User $user_id registered. By: $by.", $by );
    }

    public function audit_role_change( $user_id, $role, $old_roles ) {
        $by  = get_current_user_id();
        $old = implode( ',', (array) $old_roles );
        Illu_Shield_DB::log( 'Audit: User Role Changed', "User $user_id role changed from '$old' to '$role' by $by.", $by );
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // HELPER
    // ═══════════════════════════════════════════════════════════════════════════

    private function get_client_ip(): string {
        return Illu_Shield_DB::get_client_ip();
    }
}
