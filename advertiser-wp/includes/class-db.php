<?php
if (!defined('ABSPATH')) exit;

class Adv_WP_DB {
    public static function init() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_products = $wpdb->prefix . 'adv_products';
        $sql_products = "CREATE TABLE $table_products (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            price decimal(10,2) NOT NULL DEFAULT 0,
            price_coret decimal(10,2) NOT NULL DEFAULT 0,
            product_type varchar(100) NOT NULL DEFAULT 'canvas_app',
            description text NULL,
            access_flow text NULL,
            mockup_image text NULL,
            affiliate_commission varchar(100) NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $table_pages = $wpdb->prefix . ADV_PAGES_TABLE;
        $sql_pages = "CREATE TABLE $table_pages (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            title varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            builder_data longtext NOT NULL,
            pixel_data text NOT NULL,
            product_id bigint(20) NOT NULL DEFAULT 0,
            views int(11) NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'published',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";

        $table_leads = $wpdb->prefix . ADV_LEADS_TABLE;
        $sql_leads = "CREATE TABLE $table_leads (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            advertiser_id bigint(20) NOT NULL,
            page_id bigint(20) NOT NULL,
            customer_name varchar(255) NOT NULL,
            customer_wa varchar(50) NOT NULL,
            customer_email varchar(255) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            duitku_reference varchar(255) DEFAULT NULL,
            price decimal(10,2) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $table_contents = $wpdb->prefix . 'adv_contents';
        $sql_contents = "CREATE TABLE $table_contents (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL DEFAULT 0,
            type varchar(50) NOT NULL,
            title varchar(255) NOT NULL,
            embed_link text NOT NULL,
            copy_text text NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $table_withdrawals = $wpdb->prefix . 'adv_withdrawals';
        $sql_withdrawals = "CREATE TABLE $table_withdrawals (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            advertiser_id bigint(20) NOT NULL,
            date_start date NOT NULL,
            date_end date NOT NULL,
            products text NOT NULL,
            report_image text NOT NULL,
            ad_spent decimal(15,2) NOT NULL DEFAULT 0,
            omset decimal(15,2) NOT NULL DEFAULT 0,
            profit_share decimal(15,2) NOT NULL DEFAULT 0,
            nominal_wd decimal(15,2) NOT NULL DEFAULT 0,
            status varchar(50) NOT NULL DEFAULT 'Pending',
            transfer_receipt text NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_products);
        dbDelta($sql_pages);
        dbDelta($sql_leads);
        dbDelta($sql_contents);
        dbDelta($sql_withdrawals);
    }
}
