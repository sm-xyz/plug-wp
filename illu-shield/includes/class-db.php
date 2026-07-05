<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Illu_Shield_DB {

    public static function install() {
        global $wpdb;

        $table         = $wpdb->prefix . 'illu_shield_logs';
        $charset_coll  = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table (
            id         bigint(20)   NOT NULL AUTO_INCREMENT,
            time       datetime     DEFAULT CURRENT_TIMESTAMP NOT NULL,
            ip         varchar(100) NOT NULL,
            event_type varchar(50)  NOT NULL,
            description text        NOT NULL,
            user_id    bigint(20)   DEFAULT 0,
            PRIMARY KEY (id),
            KEY idx_ip    (ip),
            KEY idx_event (event_type),
            KEY idx_time  (time)
        ) $charset_coll;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        if ( ! wp_next_scheduled( 'illu_shield_daily_cleanup' ) ) {
            wp_schedule_event( time(), 'daily', 'illu_shield_daily_cleanup' );
        }

        if ( get_option( 'illu_shield_settings' ) === false ) {
            update_option( 'illu_shield_settings', [
                'enable_2fa'             => 'yes',
                'require_2fa_admin'      => 'yes',
                'enable_firewall'        => 'yes',
                'enable_login_protection'=> 'yes',
                'disable_xmlrpc'         => 'yes',
                'enable_cache'           => 'yes',
            ] );
        }
        update_option( 'illu_shield_db_version', '2.2.0' );
    }

    public static function ensure_table_exists() {
        if ( get_option( 'illu_shield_db_version' ) !== ILLU_SHIELD_VERSION ) {
            self::install();
        }
    }

    /**
     * Get real client IP — Cloudflare aware, X-Forwarded-For aware.
     * Satu-satunya definisi; tidak ada duplikat di class lain.
     */
    public static function get_client_ip(): string {
        $remote = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        if ( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
            $cf = trim( $_SERVER['HTTP_CF_CONNECTING_IP'] );
            if ( filter_var( $cf, FILTER_VALIDATE_IP ) ) return $cf;
        }

        if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            foreach ( array_reverse( array_map( 'trim', explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) as $ip ) {
                if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
                    return $ip;
                }
            }
        }

        return filter_var( $remote, FILTER_VALIDATE_IP ) ? $remote : '127.0.0.1';
    }

    public static function log( string $event_type, string $description, int $user_id = 0 ) {
        global $wpdb;
        self::ensure_table_exists();

        $wpdb->insert(
            $wpdb->prefix . 'illu_shield_logs',
            [
                'time'        => current_time( 'mysql' ),
                'ip'          => sanitize_text_field( trim( self::get_client_ip() ) ),
                'event_type'  => sanitize_text_field( $event_type ),
                'description' => sanitize_textarea_field( $description ),
                'user_id'     => intval( $user_id ),
            ]
        );
    }

    public static function cleanup_old_logs() {
        global $wpdb;
        $settings    = get_option( 'illu_shield_settings', [] );
        $days        = isset( $settings['auto_clean_logs_days'] ) ? intval( $settings['auto_clean_logs_days'] ) : 30;
        if ( $days > 0 ) {
            $wpdb->query( $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}illu_shield_logs WHERE time < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            ) );
        }
    }

    public static function send_weekly_report() {
        global $wpdb;
        $table      = $wpdb->prefix . 'illu_shield_logs';
        $has_table  = $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) === $table;

        $total = $brute = $scanner = $firewall = $spam = 0;
        if ( $has_table ) {
            $total   = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table WHERE time > DATE_SUB(NOW(), INTERVAL 7 DAY)" );
            $brute   = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table WHERE event_type LIKE '%Brute Force%' AND time > DATE_SUB(NOW(), INTERVAL 7 DAY)" );
            $scanner = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table WHERE (event_type LIKE '%Scanner%' OR event_type LIKE '%Bot%') AND time > DATE_SUB(NOW(), INTERVAL 7 DAY)" );
            $firewall= (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table WHERE event_type LIKE '%Firewall%' AND time > DATE_SUB(NOW(), INTERVAL 7 DAY)" );
            $spam    = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table WHERE event_type LIKE '%Spam%' AND time > DATE_SUB(NOW(), INTERVAL 7 DAY)" );
        }
        $blocked_ips = count( get_option( 'illu_shield_blacklist_ips', [] ) );

        $p = fn( $n ) => $total > 0 ? round( ( $n / $total ) * 100 ) : 0;

        $msg  = "📊 LAPORAN MINGGUAN ILLU SHIELD\n================================\n";
        $msg .= "Periode: " . date( 'd M', strtotime( '-7 days' ) ) . " - " . date( 'd M Y' ) . "\n\n";
        $msg .= "🚨 SERANGAN TERBLOKIR: " . number_format( $total ) . "\n";
        $msg .= "   Brute Force:    " . number_format( $brute )    . " ({$p($brute)}%)\n";
        $msg .= "   Scanner/Bot:    " . number_format( $scanner )  . " ({$p($scanner)}%)\n";
        $msg .= "   Firewall Block: " . number_format( $firewall ) . " ({$p($firewall)}%)\n";
        $msg .= "   Spam Comment:   " . number_format( $spam )     . " ({$p($spam)}%)\n\n";
        $msg .= "🔴 IP BLACKLIST PERMANEN: " . number_format( $blocked_ips ) . "\n\n";
        $msg .= "✅ STATUS SISTEM: Semua proteksi aktif\n";

        $core_updates   = get_site_transient( 'update_core' );
        $plugin_updates = get_site_transient( 'update_plugins' );
        if ( isset( $core_updates->updates[0]->response ) && $core_updates->updates[0]->response === 'upgrade' ) {
            $msg .= "\n⚠️ WordPress Core: update tersedia — segera perbarui.";
        }
        if ( isset( $plugin_updates->response ) && count( $plugin_updates->response ) > 0 ) {
            $msg .= "\n⚠️ " . count( $plugin_updates->response ) . " plugin memiliki update tersedia.";
        }

        wp_mail( get_option( 'admin_email' ), '[Illu Shield] Laporan Keamanan Mingguan', $msg );
    }
}

add_action( 'illu_shield_daily_cleanup', [ 'Illu_Shield_DB', 'cleanup_old_logs' ] );
