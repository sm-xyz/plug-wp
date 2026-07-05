<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Illu_Shield_Admin {

    public function __construct() {
        add_action( 'admin_menu',            [ $this, 'add_menu_pages' ] );
        add_action( 'admin_init',            [ $this, 'handle_form_submission' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_dashboard_setup',    [ $this, 'add_dashboard_widget' ] );
        add_action( 'admin_post_illu_quick_blacklist_get',   [ $this, 'quick_blacklist_get' ] );
        add_action( 'admin_post_illu_quick_unblacklist',     [ $this, 'quick_unblacklist_get' ] );
    }

    // ── Quick actions ──────────────────────────────────────────────────────────

    public function quick_blacklist_get() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        check_admin_referer( 'illu_quick_blacklist_nonce' );
        $ip = sanitize_text_field( $_GET['ip'] ?? '' );
        if ( ! empty( $ip ) && filter_var( $ip, FILTER_VALIDATE_IP ) ) {
            $bl = get_option( 'illu_shield_blacklist_ips', [] );
            if ( ! in_array( $ip, $bl, true ) ) {
                $bl[] = $ip;
                update_option( 'illu_shield_blacklist_ips', array_values( $bl ) );
            }
        }
        wp_safe_redirect( wp_get_referer() ?: admin_url( 'admin.php?page=illu-shield-logs' ) );
        exit;
    }

    public function quick_unblacklist_get() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        check_admin_referer( 'illu_quick_unblacklist_nonce' );
        $ip = sanitize_text_field( $_GET['ip'] ?? '' );
        if ( ! empty( $ip ) ) {
            $bl = get_option( 'illu_shield_blacklist_ips', [] );
            $bl = array_values( array_diff( $bl, [ $ip ] ) );
            update_option( 'illu_shield_blacklist_ips', $bl );
        }
        wp_safe_redirect( wp_get_referer() ?: admin_url( 'admin.php?page=illu-shield-logs' ) );
        exit;
    }

    // ── Dashboard widget ───────────────────────────────────────────────────────

    public function add_dashboard_widget() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        wp_add_dashboard_widget( 'illu_shield_widget', 'Illu Shield — Security Events', [ $this, 'render_dashboard_widget' ] );
    }

    public function render_dashboard_widget() {
        global $wpdb;
        $table = $wpdb->prefix . 'illu_shield_logs';
        $has   = $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) === $table;
        $logs  = $has ? $wpdb->get_results( "SELECT * FROM $table ORDER BY time DESC LIMIT 5" ) : [];

        if ( empty( $logs ) ) {
            echo '<p>Tidak ada security event baru-baru ini.</p>';
        } else {
            echo '<table style="width:100%;text-align:left;border-collapse:collapse;font-size:13px;">';
            echo '<thead style="border-bottom:1px solid #ddd;"><tr><th style="padding:8px 4px;">Waktu</th><th style="padding:8px 4px;">IP</th><th style="padding:8px 4px;">Event</th></tr></thead><tbody>';
            foreach ( $logs as $log ) {
                echo '<tr style="border-bottom:1px solid #eee;">';
                echo '<td style="padding:8px 4px;color:#666;">' . esc_html( substr( $log->time, 5, 11 ) ) . '</td>';
                echo '<td style="padding:8px 4px;font-family:monospace;">' . esc_html( $log->ip ) . '</td>';
                echo '<td style="padding:8px 4px;"><span style="background:#fee2e2;color:#991b1b;padding:2px 6px;border-radius:4px;font-size:11px;">' . esc_html( $log->event_type ) . '</span></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
        echo '<p style="margin-top:10px;"><a href="' . admin_url( 'admin.php?page=illu-shield-logs' ) . '" class="button">Lihat Semua Log</a></p>';
    }

    // ── Assets ─────────────────────────────────────────────────────────────────

    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'illu-shield' ) === false ) return;
        wp_enqueue_script( 'tailwindcss', 'https://cdn.tailwindcss.com', [], null, false );
        wp_enqueue_script( 'lucide',      'https://unpkg.com/lucide@latest', [], null, true );
        wp_enqueue_script( 'chartjs',     'https://cdn.jsdelivr.net/npm/chart.js', [], null, true );
    }

    // ── Menu ───────────────────────────────────────────────────────────────────

    public function add_menu_pages() {
        add_menu_page( 'Illu Shield', 'Illu Shield', 'manage_options', 'illu-shield-logs',
            [ $this, 'render_logs_page' ], 'dashicons-shield', 31 );
        add_submenu_page( 'illu-shield-logs', 'Analytics & Logs', 'Analytics & Logs',
            'manage_options', 'illu-shield-logs', [ $this, 'render_logs_page' ] );
        add_submenu_page( 'illu-shield-logs', 'Settings', 'Settings',
            'manage_options', 'illu-shield', [ $this, 'render_settings_page' ] );
    }

    // ── Form submission handler ────────────────────────────────────────────────

    public function handle_form_submission() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        // Manual blacklist
        if ( isset( $_POST['illu_shield_manual_blacklist_ip'] ) ) {
            check_admin_referer( 'illu_shield_manual_blacklist_action', 'illu_shield_manual_blacklist_nonce' );
            $ip = sanitize_text_field( $_POST['blacklist_ip'] );
            if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                $bl = get_option( 'illu_shield_blacklist_ips', [] );
                if ( ! in_array( $ip, $bl, true ) ) {
                    $bl[] = $ip;
                    update_option( 'illu_shield_blacklist_ips', array_values( $bl ) );
                    add_settings_error( 'illu_shield', 'ok', "IP $ip ditambahkan ke blacklist.", 'success' );
                }
            }
        }

        // Manual unblacklist
        if ( isset( $_POST['illu_shield_unblacklist_ip'] ) ) {
            check_admin_referer( 'illu_shield_unblacklist_action', 'illu_shield_unblacklist_nonce' );
            $ip = sanitize_text_field( $_POST['unblacklist_ip'] );
            $bl = get_option( 'illu_shield_blacklist_ips', [] );
            $bl = array_values( array_diff( $bl, [ $ip ] ) );
            update_option( 'illu_shield_blacklist_ips', $bl );
            add_settings_error( 'illu_shield', 'ok', "IP $ip dihapus dari blacklist.", 'success' );
        }

        // Bulk unblacklist
        if ( isset( $_POST['illu_shield_bulk_unblacklist'] ) ) {
            check_admin_referer( 'illu_shield_bulk_unblacklist_action', 'illu_shield_bulk_unblacklist_nonce' );
            $ips = array_map( 'sanitize_text_field', $_POST['bulk_unblacklist_ips'] ?? [] );
            if ( ! empty( $ips ) ) {
                $bl = array_values( array_diff( get_option( 'illu_shield_blacklist_ips', [] ), $ips ) );
                update_option( 'illu_shield_blacklist_ips', $bl );
                add_settings_error( 'illu_shield', 'ok', count( $ips ) . " IP dihapus dari blacklist.", 'success' );
            }
        }

        // Wildcard blacklist
        if ( isset( $_POST['illu_shield_manual_wildcard_ip'] ) ) {
            check_admin_referer( 'illu_shield_manual_wildcard_action', 'illu_shield_manual_wildcard_nonce' );
            $ip = sanitize_text_field( $_POST['wildcard_ip'] );
            if ( ! empty( $ip ) ) {
                $wc = get_option( 'illu_shield_wildcard_ips', [] );
                if ( ! in_array( $ip, $wc, true ) ) {
                    $wc[] = $ip;
                    update_option( 'illu_shield_wildcard_ips', array_values( $wc ) );
                    add_settings_error( 'illu_shield', 'ok', "Wildcard $ip ditambahkan.", 'success' );
                }
            }
        }

        // Wildcard unblacklist
        if ( isset( $_POST['illu_shield_unblacklist_wildcard'] ) ) {
            check_admin_referer( 'illu_shield_unblacklist_wildcard_action', 'illu_shield_unblacklist_wildcard_nonce' );
            $ip = sanitize_text_field( $_POST['unblacklist_wildcard'] );
            $wc = array_values( array_diff( get_option( 'illu_shield_wildcard_ips', [] ), [ $ip ] ) );
            update_option( 'illu_shield_wildcard_ips', $wc );
            add_settings_error( 'illu_shield', 'ok', "Wildcard $ip dihapus.", 'success' );
        }

        // Whitelist
        if ( isset( $_POST['illu_shield_manual_whitelist_ip'] ) ) {
            check_admin_referer( 'illu_shield_manual_whitelist_action', 'illu_shield_manual_whitelist_nonce' );
            $ip = sanitize_text_field( $_POST['whitelist_ip'] );
            if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                $wl = get_option( 'illu_shield_whitelist_ips', [] );
                if ( ! in_array( $ip, $wl, true ) ) { $wl[] = $ip; }
                update_option( 'illu_shield_whitelist_ips', array_values( $wl ) );
                // Clear lockout data for this IP
                $this->clear_ip_records( $ip );
                add_settings_error( 'illu_shield', 'ok', "IP $ip ditambahkan ke whitelist & lockout dibersihkan.", 'success' );
            }
        }

        // Unwhitelist
        if ( isset( $_POST['illu_shield_unwhitelist_ip'] ) ) {
            check_admin_referer( 'illu_shield_unwhitelist_action', 'illu_shield_unwhitelist_nonce' );
            $ip = sanitize_text_field( $_POST['unwhitelist_ip'] );
            $wl = array_values( array_diff( get_option( 'illu_shield_whitelist_ips', [] ), [ $ip ] ) );
            update_option( 'illu_shield_whitelist_ips', $wl );
            add_settings_error( 'illu_shield', 'ok', "IP $ip dihapus dari whitelist.", 'success' );
        }

        // Wildcard whitelist
        if ( isset( $_POST['illu_shield_manual_wildcard_whitelist_ip'] ) ) {
            check_admin_referer( 'illu_shield_manual_wildcard_whitelist_action', 'illu_shield_manual_wildcard_whitelist_nonce' );
            $ip = sanitize_text_field( $_POST['wildcard_whitelist_ip'] );
            if ( strpos( $ip, '*' ) !== false ) {
                $wc = get_option( 'illu_shield_wildcard_whitelist_ips', [] );
                if ( ! in_array( $ip, $wc, true ) ) { $wc[] = $ip; }
                update_option( 'illu_shield_wildcard_whitelist_ips', array_values( $wc ) );
                add_settings_error( 'illu_shield', 'ok', "Wildcard whitelist $ip ditambahkan.", 'success' );
            }
        }

        // Unwhitelist wildcard
        if ( isset( $_POST['illu_shield_unwhitelist_wildcard'] ) ) {
            check_admin_referer( 'illu_shield_unwhitelist_wildcard_action', 'illu_shield_unwhitelist_wildcard_nonce' );
            $ip = sanitize_text_field( $_POST['unwhitelist_wildcard'] );
            $wc = array_values( array_diff( get_option( 'illu_shield_wildcard_whitelist_ips', [] ), [ $ip ] ) );
            update_option( 'illu_shield_wildcard_whitelist_ips', $wc );
            add_settings_error( 'illu_shield', 'ok', "Wildcard whitelist $ip dihapus.", 'success' );
        }

        // Bulk log actions
        if ( isset( $_POST['illu_shield_bulk_action_logs'] ) ) {
            check_admin_referer( 'illu_shield_bulk_logs_action', 'illu_shield_bulk_logs_nonce' );
            $action  = sanitize_text_field( $_POST['bulk_action'] );
            $log_ids = array_map( 'intval', $_POST['log_ids'] ?? [] );
            if ( ! empty( $log_ids ) ) {
                global $wpdb;
                $table       = $wpdb->prefix . 'illu_shield_logs';
                $placeholder = implode( ',', array_fill( 0, count( $log_ids ), '%d' ) );
                if ( $action === 'delete' ) {
                    $wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE id IN ($placeholder)", ...$log_ids ) );
                    add_settings_error( 'illu_shield', 'ok', count( $log_ids ) . " log dihapus.", 'success' );
                } elseif ( $action === 'blacklist' ) {
                    $ips = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT ip FROM $table WHERE id IN ($placeholder) AND ip != ''", ...$log_ids ) );
                    if ( ! empty( $ips ) ) {
                        $bl    = get_option( 'illu_shield_blacklist_ips', [] );
                        $added = 0;
                        foreach ( $ips as $ip ) {
                            if ( filter_var( $ip, FILTER_VALIDATE_IP ) && ! in_array( $ip, $bl, true ) ) {
                                $bl[] = $ip; $added++;
                            }
                        }
                        if ( $added > 0 ) {
                            update_option( 'illu_shield_blacklist_ips', array_values( $bl ) );
                            add_settings_error( 'illu_shield', 'ok', "$added IP diblacklist.", 'success' );
                        }
                    }
                }
            }
        }

        // Export CSV
        if ( isset( $_POST['illu_shield_export_csv'] ) ) {
            $this->export_logs_csv();
        }

        // Save settings
        if ( isset( $_POST['illu_shield_save_settings'] ) ) {
            check_admin_referer( 'illu_shield_settings_action', 'illu_shield_nonce' );

            $old = get_option( 'illu_shield_settings', [] );
            $cf_key = ( ! empty( $_POST['cloudflare_api_key'] ) && $_POST['cloudflare_api_key'] !== '******' )
                ? illu_encrypt_secret( sanitize_text_field( $_POST['cloudflare_api_key'] ) )
                : ( $old['cloudflare_api_key'] ?? '' );

            $settings = [
                'enable_2fa'               => isset( $_POST['enable_2fa'] )               ? 'yes' : 'no',
                'require_2fa_admin'        => isset( $_POST['require_2fa_admin'] )        ? 'yes' : 'no',
                'enable_firewall'          => isset( $_POST['enable_firewall'] )          ? 'yes' : 'no',
                'enable_login_protection'  => isset( $_POST['enable_login_protection'] )  ? 'yes' : 'no',
                'disable_xmlrpc'           => isset( $_POST['disable_xmlrpc'] )           ? 'yes' : 'no',
                'enable_cache'             => isset( $_POST['enable_cache'] )             ? 'yes' : 'no',
                'max_failures'             => intval( $_POST['max_failures'] ?? 3 ),
                'lockout_tier1_minutes'    => intval( $_POST['lockout_tier1_minutes'] ?? 15 ),
                'lockout_tier2_hours'      => intval( $_POST['lockout_tier2_hours']   ?? 1  ),
                'lockout_tier3_days'       => intval( $_POST['lockout_tier3_days']    ?? 1  ),
                'auto_blacklist_days'      => intval( $_POST['auto_blacklist_days']   ?? 0  ),
                'auto_clean_logs_days'     => intval( $_POST['auto_clean_logs_days']  ?? 30 ),
                'disable_app_passwords'    => isset( $_POST['disable_app_passwords'] )    ? 'yes' : 'no',
                'disable_file_edit'        => isset( $_POST['disable_file_edit'] )        ? 'yes' : 'no',
                'disable_file_mods'        => isset( $_POST['disable_file_mods'] )        ? 'yes' : 'no',
                'hide_wp_version'          => isset( $_POST['hide_wp_version'] )          ? 'yes' : 'no',
                'disable_author_archives'  => isset( $_POST['disable_author_archives'] )  ? 'yes' : 'no',
                'protect_rest_api'         => isset( $_POST['protect_rest_api'] )         ? 'yes' : 'no',
                'prevent_concurrent_logins'=> isset( $_POST['prevent_concurrent_logins'] )? 'yes' : 'no',
                'auto_ban_404'             => isset( $_POST['auto_ban_404'] )             ? 'yes' : 'no',
                'block_malicious_queries'  => isset( $_POST['block_malicious_queries'] )  ? 'yes' : 'no',
                'security_headers'         => isset( $_POST['security_headers'] )         ? 'yes' : 'no',
                'email_alert_blacklist'    => isset( $_POST['email_alert_blacklist'] )    ? 'yes' : 'no',
                'email_alert_fim'          => isset( $_POST['email_alert_fim'] )          ? 'yes' : 'no',
                'email_alert_new_login'    => isset( $_POST['email_alert_new_login'] )    ? 'yes' : 'no',
                'fim_webhook_url'          => esc_url_raw(      $_POST['fim_webhook_url']    ?? '' ),
                'custom_login_slug'        => sanitize_text_field( trim( $_POST['custom_login_slug'] ?? '' ) ),
                'cloudflare_email'         => sanitize_email(    $_POST['cloudflare_email']   ?? '' ),
                'cloudflare_api_key'       => $cf_key,
                'cloudflare_zone_id'       => sanitize_text_field( $_POST['cloudflare_zone_id'] ?? '' ),
                'blocked_countries'        => sanitize_text_field( $_POST['blocked_countries']  ?? '' ),
            ];

            update_option( 'illu_shield_settings', $settings );
            // Reset memoized whitelist cache after settings save
            Illu_Shield_Request_Context::reset();
            add_settings_error( 'illu_shield', 'ok', 'Pengaturan keamanan berhasil disimpan.', 'success' );
        }
    }

    private function clear_ip_records( string $ip ) {
        $hash = md5( $ip );
        delete_transient( 'illu_lockout_' . $hash );
        delete_transient( 'illu_lockout_level_' . $hash );
        delete_transient( 'illu_lf_' . $hash );
        $bl = get_option( 'illu_shield_blacklist_ips', [] );
        if ( ( $key = array_search( $ip, $bl, true ) ) !== false ) {
            unset( $bl[ $key ] );
            update_option( 'illu_shield_blacklist_ips', array_values( $bl ) );
        }
    }

    private function export_logs_csv() {
        global $wpdb;
        $table = $wpdb->prefix . 'illu_shield_logs';
        $logs  = $wpdb->get_results( "SELECT * FROM $table ORDER BY time DESC", ARRAY_A );
        if ( empty( $logs ) ) return;

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=illu-shield-logs-' . date( 'Y-m-d' ) . '.csv' );
        $out = fopen( 'php://output', 'w' );
        fputcsv( $out, [ 'ID', 'Time', 'IP', 'Event Type', 'Description', 'User ID' ] );
        foreach ( $logs as $log ) fputcsv( $out, $log );
        fclose( $out );
        exit;
    }

    // ── Settings page ──────────────────────────────────────────────────────────

    public function render_settings_page() {
        $s = get_option( 'illu_shield_settings', [] );
        ?>
        <div class="wrap" style="margin:20px 20px 0 0;">
            <?php settings_errors( 'illu_shield' ); ?>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden" style="max-width:800px;">
                <div class="px-6 py-5 border-b border-slate-100 bg-slate-50">
                    <h2 class="text-xl font-bold text-slate-800 m-0">Illu Shield v<?php echo ILLU_SHIELD_VERSION; ?></h2>
                    <p class="text-sm text-slate-500 mt-1 mb-0">Zero-conflict security untuk Sharelink AI — whitelist path dikelola terpusat di <code>class-request-context.php</code>.</p>
                </div>

                <!-- Self Audit -->
                <?php
                $wp_ok    = version_compare( get_bloginfo( 'version' ), '6.0', '>=' );
                $php_ok   = version_compare( PHP_VERSION, '7.4', '>=' );
                $debug_ok = ! ( defined( 'WP_DEBUG' ) && WP_DEBUG );
                $log_exposed = file_exists( WP_CONTENT_DIR . '/debug.log' );
                ?>
                <div class="p-6 border-b border-slate-100">
                    <h3 class="text-sm font-bold text-slate-700 mb-3">System Audit</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <?php
                        $checks = [
                            [ 'WordPress Core', $wp_ok,           $wp_ok ? 'Up to date' : 'Outdated'   ],
                            [ 'PHP ' . PHP_VERSION, $php_ok,      $php_ok ? 'OK' : 'Perbarui PHP'      ],
                            [ 'WP_DEBUG', $debug_ok,              $debug_ok ? 'Disabled ✓' : 'Aktif ⚠' ],
                            [ 'Debug Log', !$log_exposed,         !$log_exposed ? 'Secure ✓' : 'Exposed ⚠'],
                        ];
                        foreach ( $checks as [ $label, $ok, $val ] ) :
                            $bg = $ok ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50';
                            $tc = $ok ? 'text-green-700' : 'text-red-700';
                        ?>
                        <div class="border <?php echo $bg; ?> p-3 rounded">
                            <div class="text-xs text-slate-500"><?php echo esc_html( $label ); ?></div>
                            <div class="font-semibold <?php echo $tc; ?> text-sm"><?php echo esc_html( $val ); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <form method="post" action="">
                    <?php wp_nonce_field( 'illu_shield_settings_action', 'illu_shield_nonce' ); ?>
                    <div class="p-6 space-y-6">

                        <!-- Security -->
                        <div>
                            <h3 class="text-sm font-bold text-slate-700 mb-3 border-b border-slate-100 pb-2">Security & Authentication</h3>
                            <?php $this->toggle( 'enable_2fa', 'Enable Two-Factor Authentication (2FA)', 'Izinkan user aktifkan 2FA di profil mereka.', $s ); ?>
                            <?php $this->toggle( 'require_2fa_admin', 'Force 2FA untuk Administrator', 'Wajibkan Admin memasang 2FA sebelum akses dashboard.', $s ); ?>
                            <?php $this->toggle( 'prevent_concurrent_logins', 'Cegah Login Bersamaan', 'Session lama dihapus saat login dari perangkat baru.', $s ); ?>
                            <div class="flex items-start p-2 -ml-2">
                                <div class="mt-1 mr-3 w-4"></div>
                                <div class="flex-1">
                                    <span class="font-semibold text-slate-700 block text-sm">Custom Login URL</span>
                                    <span class="text-slate-500 text-[13px] block mb-2">Ganti <code>wp-login.php</code> dengan slug custom. Kosongkan untuk disable.</span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-slate-500"><?php echo site_url(); ?>/</span>
                                        <input type="text" name="custom_login_slug" value="<?php echo esc_attr( $s['custom_login_slug'] ?? '' ); ?>" placeholder="masuk" class="border border-slate-300 rounded text-sm py-1 px-2 w-40">
                                    </div>
                                </div>
                            </div>
                            <?php $this->toggle( 'enable_login_protection', 'Brute-Force Protection', 'Blokir IP setelah gagal login berulang kali.', $s ); ?>
                            <div class="pl-7 space-y-2 text-sm">
                                <?php $this->number_input( 'max_failures', 'Batas Gagal', $s['max_failures'] ?? 3, 2, 10 ); ?>
                                <?php $this->number_input( 'lockout_tier1_minutes', 'Tier 1 Lockout (menit)', $s['lockout_tier1_minutes'] ?? 15, 1 ); ?>
                                <?php $this->number_input( 'lockout_tier2_hours', 'Tier 2 Lockout (jam)', $s['lockout_tier2_hours'] ?? 1, 1 ); ?>
                                <?php $this->number_input( 'lockout_tier3_days', 'Tier 3 Lockout (hari)', $s['lockout_tier3_days'] ?? 1, 1 ); ?>
                                <?php $this->number_input( 'auto_blacklist_days', 'Auto Unban (hari, 0=permanen)', $s['auto_blacklist_days'] ?? 0, 0 ); ?>
                                <?php $this->number_input( 'auto_clean_logs_days', 'Retensi Log (hari)', $s['auto_clean_logs_days'] ?? 30, 1 ); ?>
                            </div>
                        </div>

                        <!-- Hardening -->
                        <div>
                            <h3 class="text-sm font-bold text-slate-700 mb-3 border-b border-slate-100 pb-2">Advanced Hardening</h3>
                            <?php $this->toggle( 'disable_app_passwords',   'Nonaktifkan Application Passwords', '', $s ); ?>
                            <?php $this->toggle( 'disable_file_edit',        'Nonaktifkan Theme/Plugin Editor', '', $s ); ?>
                            <?php $this->toggle( 'disable_file_mods',        'Nonaktifkan Instalasi Plugin/Theme (DISALLOW_FILE_MODS)', '', $s ); ?>
                            <?php $this->toggle( 'hide_wp_version',          'Sembunyikan Versi WordPress', '', $s ); ?>
                            <?php $this->toggle( 'disable_author_archives',  'Cegah Enumerasi User (Author Archives)', '', $s ); ?>
                            <?php $this->toggle( 'protect_rest_api',         'Proteksi REST API (block unauthenticated)', '', $s ); ?>
                            <?php $this->toggle( 'security_headers',         'HTTP Security Headers + CSP Nonce', '', $s ); ?>
                            <?php $this->toggle( 'auto_ban_404',             'Auto-Ban 404 Scanner', '20+ 404/menit = bot scanner.', $s ); ?>
                            <?php $this->toggle( 'block_malicious_queries',  'Blokir Malicious Query (WAF ringan)', 'SQLi/XSS pattern di URL.', $s ); ?>
                        </div>

                        <!-- Firewall -->
                        <div>
                            <h3 class="text-sm font-bold text-slate-700 mb-3 border-b border-slate-100 pb-2">Micro-Firewall</h3>
                            <?php $this->toggle( 'enable_firewall', 'XSS & SQLi Input Filter', 'Scan $_GET/$_POST — auto-bypass sharelink/webhook via <code>class-request-context.php</code>.', $s ); ?>
                            <?php $this->toggle( 'disable_xmlrpc',  'Disable XML-RPC', 'Blokir xmlrpc.php yang dieksploitasi botnet.', $s ); ?>
                        </div>

                        <!-- Cache -->
                        <div>
                            <h3 class="text-sm font-bold text-slate-700 mb-3 border-b border-slate-100 pb-2">Smart Cache</h3>
                            <?php $this->toggle( 'enable_cache', 'Static HTML Caching', 'Auto-bypass untuk sharelink/webhook/login/AJAX — 1 jam TTL.', $s ); ?>
                        </div>

                        <!-- Notifications -->
                        <div>
                            <h3 class="text-sm font-bold text-slate-700 mb-3 border-b border-slate-100 pb-2">Notifikasi Email</h3>
                            <?php $this->toggle( 'email_alert_blacklist',  'Alert IP Diblacklist Permanen', '', $s ); ?>
                            <?php $this->toggle( 'email_alert_fim',        'Alert File Integrity (FIM)', '', $s ); ?>
                            <?php $this->toggle( 'email_alert_new_login',  'Alert Login dari IP Baru', '', $s ); ?>
                            <div class="pl-7 mt-2">
                                <label class="block text-xs font-semibold text-slate-600 mb-1">External FIM Webhook URL</label>
                                <input type="url" name="fim_webhook_url" value="<?php echo esc_attr( $s['fim_webhook_url'] ?? '' ); ?>" placeholder="https://..." class="border border-slate-300 rounded text-sm py-1 px-2 w-full max-w-md">
                            </div>
                        </div>

                        <!-- Cloudflare -->
                        <div>
                            <h3 class="text-sm font-bold text-slate-700 mb-3 border-b border-slate-100 pb-2">Cloudflare Integration</h3>
                            <div class="bg-blue-50 p-4 rounded-xl border border-blue-100 space-y-3">
                                <?php
                                $cf_fields = [
                                    [ 'cloudflare_email',   'Email Cloudflare',        'email', '' ],
                                    [ 'cloudflare_api_key', 'Global API Key',           'password', ! empty( $s['cloudflare_api_key'] ) ? '******' : '' ],
                                    [ 'cloudflare_zone_id', 'Zone ID',                  'text', '' ],
                                    [ 'blocked_countries',  'Blokir Negara (CF header)', 'text', '' ],
                                ];
                                foreach ( $cf_fields as [ $name, $label, $type, $placeholder ] ) :
                                    $val = $name === 'cloudflare_api_key' && ! empty( $s[ $name ] ) ? '******' : esc_attr( $s[ $name ] ?? '' );
                                ?>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1"><?php echo esc_html( $label ); ?></label>
                                    <input type="<?php echo esc_attr( $type ); ?>" name="<?php echo esc_attr( $name ); ?>"
                                           value="<?php echo $val; ?>"
                                           placeholder="<?php echo esc_attr( $placeholder ); ?>"
                                           class="border border-slate-300 rounded text-sm py-1 px-2 w-full max-w-md">
                                </div>
                                <?php endforeach; ?>
                                <p class="text-xs text-slate-500">Blocked countries: pisahkan dengan koma, misal: <code>RU,CN</code>. Memerlukan Cloudflare (CF-IPCountry header).</p>
                            </div>
                        </div>

                    </div>

                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-100">
                        <button type="submit" name="illu_shield_save_settings" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded shadow-sm text-sm inline-flex items-center">
                            Simpan Pengaturan
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <script>if(typeof lucide!=='undefined')lucide.createIcons();</script>
        <?php
    }

    // ── Logs page ──────────────────────────────────────────────────────────────

    public function render_logs_page() {
        global $wpdb;
        $table      = $wpdb->prefix . 'illu_shield_logs';
        $active_tab = sanitize_text_field( $_GET['tab'] ?? 'logs' );
        $page       = max( 1, intval( $_GET['paged'] ?? 1 ) );
        $per_page   = 20;
        $offset     = ( $page - 1 ) * $per_page;
        $has_table  = $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) === $table;
        $total      = $has_table ? (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table" ) : 0;
        $logs       = $has_table ? $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table ORDER BY time DESC LIMIT %d OFFSET %d", $per_page, $offset ) ) : [];

        ?>
        <div class="wrap" style="margin:20px 20px 0 0;">
            <?php settings_errors( 'illu_shield' ); ?>
            <h2 class="nav-tab-wrapper border-b-0 mb-4">
                <a href="?page=illu-shield-logs&tab=logs"      class="nav-tab <?php echo $active_tab === 'logs'      ? 'nav-tab-active' : ''; ?>">Analytics & Logs</a>
                <a href="?page=illu-shield-logs&tab=blacklist" class="nav-tab <?php echo $active_tab === 'blacklist' ? 'nav-tab-active' : ''; ?>">IP Blacklist</a>
                <a href="?page=illu-shield-logs&tab=whitelist" class="nav-tab <?php echo $active_tab === 'whitelist' ? 'nav-tab-active' : ''; ?>">IP Whitelist</a>
            </h2>

            <?php
            if ( $active_tab === 'blacklist' ) {
                $this->render_blacklist_tab();
            } elseif ( $active_tab === 'whitelist' ) {
                $this->render_whitelist_tab();
            } else {
                $this->render_logs_tab( $table, $logs, $total, $page, $per_page, $has_table );
            }
            ?>
        </div>
        <script>if(typeof lucide!=='undefined')lucide.createIcons();</script>
        <?php
    }

    private function render_logs_tab( $table, $logs, $total, $page, $per_page, $has_table ) {
        global $wpdb;
        // Stats
        if ( $has_table ) :
            $total_events = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table WHERE time > DATE_SUB(NOW(), INTERVAL 7 DAY)" );
            $unique_ips   = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT ip) FROM $table" );
            $top_events   = $wpdb->get_results( "SELECT event_type, COUNT(*) as count FROM $table GROUP BY event_type ORDER BY count DESC LIMIT 3" );
            $chart_data   = $wpdb->get_results( "SELECT DATE(time) as date, COUNT(*) as count FROM $table WHERE time >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(time) ORDER BY DATE(time) ASC" );
            $chart_dates  = array_column( $chart_data, 'date' );
            $chart_counts = array_column( $chart_data, 'count' );
        endif;
        ?>
        <?php if ( $has_table ) : ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                <div class="text-slate-500 text-sm mb-1">Event 7 Hari</div>
                <div class="text-3xl font-bold"><?php echo number_format( $total_events ); ?></div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                <div class="text-slate-500 text-sm mb-1">Unique IP Penyerang</div>
                <div class="text-3xl font-bold"><?php echo number_format( $unique_ips ); ?></div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                <div class="text-slate-500 text-sm mb-1">Top Events</div>
                <?php foreach ( $top_events as $ev ) : ?>
                    <div class="flex justify-between text-sm">
                        <span class="truncate pr-2"><?php echo esc_html( $ev->event_type ); ?></span>
                        <span class="font-mono bg-slate-100 px-1 rounded text-xs"><?php echo intval( $ev->count ); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 mb-6">
            <h3 class="font-bold text-slate-800 mb-3 text-sm">Threat Timeline (7 Hari)</h3>
            <div style="position:relative;height:220px;">
                <canvas id="illuThreatChart"></canvas>
            </div>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function(){
            var ctx = document.getElementById('illuThreatChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode( array_map( fn($d) => date('M d', strtotime($d)), $chart_dates ) ); ?>,
                    datasets: [{
                        label: 'Threats Blocked',
                        data: <?php echo json_encode( array_map('intval', $chart_counts) ); ?>,
                        backgroundColor: 'rgba(59,130,246,.1)',
                        borderColor: 'rgba(59,130,246,1)',
                        borderWidth: 2, fill: true, tension: 0.3,
                        pointBackgroundColor: '#fff', pointBorderColor: 'rgba(59,130,246,1)', pointRadius: 4
                    }]
                },
                options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,ticks:{precision:0}}} }
            });
        });
        </script>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                <h3 class="font-bold text-slate-800">Security Logs</h3>
                <div class="flex gap-2">
                    <form method="post" style="margin:0;">
                        <?php wp_nonce_field( 'illu_shield_bulk_logs_action', 'illu_shield_bulk_logs_nonce' ); ?>
                        <input type="hidden" name="illu_shield_export_csv" value="1">
                        <button class="text-sm bg-green-600 hover:bg-green-700 text-white py-1.5 px-4 rounded inline-flex items-center">Export CSV</button>
                    </form>
                </div>
            </div>
            <form method="post" id="illu_logs_form">
                <?php wp_nonce_field( 'illu_shield_bulk_logs_action', 'illu_shield_bulk_logs_nonce' ); ?>
                <div class="p-3 border-b border-slate-100 bg-slate-50 flex gap-2">
                    <select name="bulk_action" class="border border-slate-300 rounded text-sm py-1 pl-2 pr-6">
                        <option value="">Bulk Actions</option>
                        <option value="blacklist">Blacklist Selected IPs</option>
                        <option value="delete">Delete Selected Logs</option>
                    </select>
                    <button type="submit" name="illu_shield_bulk_action_logs" class="bg-white border border-slate-300 text-slate-700 px-3 py-1 rounded text-sm">Apply</button>
                </div>
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 w-8"><input type="checkbox" id="cb-all" onchange="document.querySelectorAll('.cb-item').forEach(c=>c.checked=this.checked)"></th>
                            <th class="px-4 py-3">Waktu</th>
                            <th class="px-4 py-3">IP</th>
                            <th class="px-4 py-3">Event</th>
                            <th class="px-4 py-3">Deskripsi</th>
                            <th class="px-4 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if ( ! empty( $logs ) ) :
                            $bl = get_option( 'illu_shield_blacklist_ips', [] );
                            foreach ( $logs as $log ) : ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3"><input type="checkbox" name="log_ids[]" value="<?php echo intval($log->id); ?>" class="cb-item rounded"></td>
                            <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap"><?php echo esc_html( $log->time ); ?></td>
                            <td class="px-4 py-3 font-mono text-xs"><?php echo esc_html( $log->ip ); ?></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800"><?php echo esc_html( $log->event_type ); ?></span></td>
                            <td class="px-4 py-3 text-slate-600 max-w-xs truncate"><?php echo esc_html( $log->description ); ?></td>
                            <td class="px-4 py-3">
                                <?php if ( filter_var( $log->ip, FILTER_VALIDATE_IP ) ) : ?>
                                    <?php if ( in_array( $log->ip, $bl, true ) ) : ?>
                                        <a href="<?php echo esc_url( wp_nonce_url( admin_url('admin-post.php?action=illu_quick_unblacklist&ip='.urlencode($log->ip)), 'illu_quick_unblacklist_nonce' ) ); ?>" class="text-xs text-green-600 hover:underline">Unban</a>
                                    <?php else : ?>
                                        <a href="<?php echo esc_url( wp_nonce_url( admin_url('admin-post.php?action=illu_quick_blacklist_get&ip='.urlencode($log->ip)), 'illu_quick_blacklist_nonce' ) ); ?>" class="text-xs text-red-600 hover:underline">Block IP</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else : ?>
                        <tr><td colspan="6" class="px-6 py-8 text-center text-slate-500">Belum ada log keamanan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>

            <?php if ( $total > $per_page ) :
                $num_pages = ceil( $total / $per_page );
            ?>
            <div class="px-6 py-4 border-t border-slate-100 flex justify-between items-center">
                <span class="text-sm text-slate-500">Total: <?php echo $total; ?> events</span>
                <div><?php echo paginate_links( [ 'base' => add_query_arg('paged','%#%'), 'format' => '', 'total' => $num_pages, 'current' => $page, 'prev_text' => '&laquo;', 'next_text' => '&raquo;', 'type' => 'plain' ] ); ?></div>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_blacklist_tab() {
        $blacklist = get_option( 'illu_shield_blacklist_ips', [] );
        $wildcards = get_option( 'illu_shield_wildcard_ips', [] );
        ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Exact Blacklist -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50"><h3 class="font-bold text-slate-800 m-0">Exact IP Blacklist</h3></div>
                <div class="p-5">
                    <form method="post" class="flex gap-2 mb-5">
                        <?php wp_nonce_field('illu_shield_manual_blacklist_action','illu_shield_manual_blacklist_nonce'); ?>
                        <input type="text" name="blacklist_ip" placeholder="IP Address..." class="flex-1 border border-slate-300 rounded text-sm py-1.5 px-3">
                        <button type="submit" name="illu_shield_manual_blacklist_ip" class="bg-red-600 text-white px-3 py-1.5 rounded text-sm font-semibold">Blokir</button>
                    </form>
                    <form method="post">
                        <?php wp_nonce_field('illu_shield_bulk_unblacklist_action','illu_shield_bulk_unblacklist_nonce'); ?>
                        <div class="flex items-center gap-2 mb-2">
                            <input type="checkbox" onchange="document.querySelectorAll('.bl-cb').forEach(c=>c.checked=this.checked)">
                            <label class="text-sm text-slate-600">Pilih Semua</label>
                            <button type="submit" name="illu_shield_bulk_unblacklist" class="ml-auto text-xs border border-red-200 text-red-600 px-2 py-1 rounded hover:bg-red-50">Hapus Terpilih</button>
                        </div>
                        <ul class="space-y-2 max-h-80 overflow-y-auto">
                        <?php foreach ( $blacklist as $ip ) : ?>
                            <li class="flex items-center justify-between bg-red-50 px-3 py-2 rounded border border-red-100">
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" name="bulk_unblacklist_ips[]" value="<?php echo esc_attr($ip); ?>" class="bl-cb rounded">
                                    <span class="font-mono text-red-800 text-sm"><?php echo esc_html($ip); ?></span>
                                </div>
                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=illu_quick_unblacklist&ip='.urlencode($ip)),'illu_quick_unblacklist_nonce')); ?>" class="text-xs text-red-600 hover:underline">Hapus</a>
                            </li>
                        <?php endforeach; ?>
                        <?php if ( empty($blacklist) ) : ?><li class="text-sm text-slate-500">Tidak ada IP di blacklist.</li><?php endif; ?>
                        </ul>
                    </form>
                </div>
            </div>
            <!-- Wildcard Blacklist -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50"><h3 class="font-bold text-slate-800 m-0">Wildcard/Range Blacklist</h3></div>
                <div class="p-5">
                    <form method="post" class="flex gap-2 mb-5">
                        <?php wp_nonce_field('illu_shield_manual_wildcard_action','illu_shield_manual_wildcard_nonce'); ?>
                        <input type="text" name="wildcard_ip" placeholder="173.239.240.*" class="flex-1 border border-slate-300 rounded text-sm py-1.5 px-3">
                        <button type="submit" name="illu_shield_manual_wildcard_ip" class="bg-orange-600 text-white px-3 py-1.5 rounded text-sm font-semibold">Blokir Range</button>
                    </form>
                    <ul class="space-y-2 max-h-80 overflow-y-auto">
                    <?php foreach ( $wildcards as $ip ) : ?>
                        <li class="flex items-center justify-between bg-orange-50 px-3 py-2 rounded border border-orange-100">
                            <span class="font-mono text-orange-800 text-sm"><?php echo esc_html($ip); ?></span>
                            <form method="post" style="margin:0;">
                                <?php wp_nonce_field('illu_shield_unblacklist_wildcard_action','illu_shield_unblacklist_wildcard_nonce'); ?>
                                <input type="hidden" name="unblacklist_wildcard" value="<?php echo esc_attr($ip); ?>">
                                <button type="submit" name="illu_shield_unblacklist_wildcard" class="text-xs text-orange-600 hover:underline">Hapus</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                    <?php if ( empty($wildcards) ) : ?><li class="text-sm text-slate-500">Tidak ada wildcard di blacklist.</li><?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_whitelist_tab() {
        $whitelist = get_option( 'illu_shield_whitelist_ips', [] );
        $wc_wl     = get_option( 'illu_shield_wildcard_whitelist_ips', [] );
        ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50"><h3 class="font-bold text-slate-800 m-0">Exact IP Whitelist</h3></div>
                <div class="p-5">
                    <form method="post" class="flex gap-2 mb-5">
                        <?php wp_nonce_field('illu_shield_manual_whitelist_action','illu_shield_manual_whitelist_nonce'); ?>
                        <input type="text" name="whitelist_ip" placeholder="IP VPS/Kantor..." class="flex-1 border border-slate-300 rounded text-sm py-1.5 px-3">
                        <button type="submit" name="illu_shield_manual_whitelist_ip" class="bg-green-600 text-white px-3 py-1.5 rounded text-sm font-semibold">Tambah</button>
                    </form>
                    <ul class="space-y-2 max-h-80 overflow-y-auto">
                    <?php foreach ( $whitelist as $ip ) : ?>
                        <li class="flex items-center justify-between bg-green-50 px-3 py-2 rounded border border-green-100">
                            <span class="font-mono text-green-800 text-sm"><?php echo esc_html($ip); ?></span>
                            <form method="post" style="margin:0;">
                                <?php wp_nonce_field('illu_shield_unwhitelist_action','illu_shield_unwhitelist_nonce'); ?>
                                <input type="hidden" name="unwhitelist_ip" value="<?php echo esc_attr($ip); ?>">
                                <button type="submit" name="illu_shield_unwhitelist_ip" class="text-xs text-green-700 hover:underline">Hapus</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                    <?php if ( empty($whitelist) ) : ?><li class="text-sm text-slate-500">Tidak ada IP di whitelist.</li><?php endif; ?>
                    </ul>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50"><h3 class="font-bold text-slate-800 m-0">Wildcard Whitelist</h3></div>
                <div class="p-5">
                    <form method="post" class="flex gap-2 mb-5">
                        <?php wp_nonce_field('illu_shield_manual_wildcard_whitelist_action','illu_shield_manual_wildcard_whitelist_nonce'); ?>
                        <input type="text" name="wildcard_whitelist_ip" placeholder="173.239.*" class="flex-1 border border-slate-300 rounded text-sm py-1.5 px-3">
                        <button type="submit" name="illu_shield_manual_wildcard_whitelist_ip" class="bg-teal-600 text-white px-3 py-1.5 rounded text-sm font-semibold">Tambah Range</button>
                    </form>
                    <ul class="space-y-2 max-h-80 overflow-y-auto">
                    <?php foreach ( $wc_wl as $ip ) : ?>
                        <li class="flex items-center justify-between bg-teal-50 px-3 py-2 rounded border border-teal-100">
                            <span class="font-mono text-teal-800 text-sm"><?php echo esc_html($ip); ?></span>
                            <form method="post" style="margin:0;">
                                <?php wp_nonce_field('illu_shield_unwhitelist_wildcard_action','illu_shield_unwhitelist_wildcard_nonce'); ?>
                                <input type="hidden" name="unwhitelist_wildcard" value="<?php echo esc_attr($ip); ?>">
                                <button type="submit" name="illu_shield_unwhitelist_wildcard" class="text-xs text-teal-700 hover:underline">Hapus</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                    <?php if ( empty($wc_wl) ) : ?><li class="text-sm text-slate-500">Tidak ada wildcard di whitelist.</li><?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function toggle( string $name, string $label, string $desc, array $s ) {
        $checked = ( $s[ $name ] ?? 'yes' ) === 'yes'; ?>
        <label class="flex items-start cursor-pointer hover:bg-slate-50 p-2 rounded -ml-2 transition-colors">
            <input type="checkbox" name="<?php echo esc_attr($name); ?>" value="yes" <?php checked($checked); ?> class="mt-1 mr-3 border-slate-300 rounded text-blue-600">
            <div>
                <span class="font-semibold text-slate-700 block text-sm"><?php echo esc_html($label); ?></span>
                <?php if ($desc) : ?><span class="text-slate-500 text-[13px]"><?php echo wp_kses($desc, ['code'=>[]]); ?></span><?php endif; ?>
            </div>
        </label>
        <?php
    }

    private function number_input( string $name, string $label, $value, int $min = 0, int $max = 9999 ) { ?>
        <div class="flex items-center gap-3">
            <label class="text-xs text-slate-600 w-52"><?php echo esc_html($label); ?></label>
            <input type="number" name="<?php echo esc_attr($name); ?>" value="<?php echo intval($value); ?>"
                   min="<?php echo $min; ?>" max="<?php echo $max; ?>"
                   class="border border-slate-300 rounded text-sm py-1 px-2 w-20">
        </div>
        <?php
    }
}
