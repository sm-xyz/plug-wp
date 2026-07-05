<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Illu_Optimize_DB_Optimizer {

    public function __construct() {
        add_action( 'admin_post_illu_optimize_db', [ $this, 'handle_optimize' ] );
        add_action( 'illu_optimize_weekly_db',     [ $this, 'run_all_optimizations' ] );

        if ( ! wp_next_scheduled( 'illu_optimize_weekly_db' ) ) {
            wp_schedule_event( time(), 'weekly', 'illu_optimize_weekly_db' );
        }
    }

    public function handle_optimize() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        check_admin_referer( 'illu_optimize_db_action', 'illu_optimize_db_nonce' );

        $results = $this->run_all_optimizations();
        $count   = array_sum( $results );

        wp_safe_redirect( add_query_arg( [
            'page'     => 'illu-optimize',
            'tab'      => 'database',
            'cleaned'  => $count,
        ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public function run_all_optimizations(): array {
        return [
            'revisions'    => $this->delete_post_revisions(),
            'drafts'       => $this->delete_auto_drafts(),
            'spam'         => $this->delete_spam_comments(),
            'trash'        => $this->delete_trashed_comments(),
            'expired_meta' => $this->delete_expired_transients(),
            'orphan_meta'  => $this->delete_orphaned_postmeta(),
            'overhead'     => $this->optimize_tables(),
        ];
    }

    private function delete_post_revisions(): int {
        global $wpdb;
        $ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'revision'" );
        $count = 0;
        foreach ( $ids as $id ) {
            if ( wp_delete_post_revision( $id ) ) $count++;
        }
        return $count;
    }

    private function delete_auto_drafts(): int {
        global $wpdb;
        return (int) $wpdb->query(
            "DELETE FROM {$wpdb->posts} WHERE post_status = 'auto-draft' AND post_modified < DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
    }

    private function delete_spam_comments(): int {
        global $wpdb;
        return (int) $wpdb->query(
            "DELETE FROM {$wpdb->comments} WHERE comment_approved = 'spam'"
        );
    }

    private function delete_trashed_comments(): int {
        global $wpdb;
        return (int) $wpdb->query(
            "DELETE FROM {$wpdb->comments} WHERE comment_approved = 'trash'"
        );
    }

    private function delete_expired_transients(): int {
        global $wpdb;
        $now   = time();
        $count = 0;

        // Expired timeouts
        $keys = $wpdb->get_col( $wpdb->prepare(
            "SELECT option_name FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_timeout_%'
             AND option_value < %d", $now
        ) );

        foreach ( $keys as $key ) {
            $name = str_replace( '_transient_timeout_', '_transient_', $key );
            $wpdb->delete( $wpdb->options, [ 'option_name' => $key ] );
            $wpdb->delete( $wpdb->options, [ 'option_name' => $name ] );
            $count++;
        }

        // Illu caches older than 24h
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_illu_%' AND option_name NOT LIKE '_transient_timeout_%'" );

        return $count;
    }

    private function delete_orphaned_postmeta(): int {
        global $wpdb;
        return (int) $wpdb->query(
            "DELETE pm FROM {$wpdb->postmeta} pm
             LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE p.ID IS NULL"
        );
    }

    private function optimize_tables(): int {
        global $wpdb;
        $tables = [
            $wpdb->posts, $wpdb->postmeta, $wpdb->options,
            $wpdb->comments, $wpdb->commentmeta,
        ];
        $count = 0;
        foreach ( $tables as $table ) {
            if ( $wpdb->query( "OPTIMIZE TABLE $table" ) !== false ) $count++;
        }
        return $count;
    }

    public function get_db_size(): array {
        global $wpdb;
        $db_name = DB_NAME;
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT table_name, data_length, index_length, data_free
             FROM information_schema.TABLES
             WHERE table_schema = %s AND table_name LIKE %s",
            $db_name, $wpdb->prefix . '%'
        ) );

        $total_size = $data_free = 0;
        foreach ( $results as $r ) {
            $total_size += $r->data_length + $r->index_length;
            $data_free  += $r->data_free;
        }

        return [
            'total_mb'  => round( $total_size / 1048576, 2 ),
            'free_mb'   => round( $data_free  / 1048576, 2 ),
            'tables'    => count( $results ),
        ];
    }
}
