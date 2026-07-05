<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Illu_Optimize_Ads_Manager {

    public function __construct() {
        add_shortcode( 'illu_ad', [ $this, 'render_ad' ] );
        add_action( 'admin_menu', [ $this, 'add_ads_submenu' ] );
        add_action( 'admin_init', [ $this, 'handle_save_ad' ] );
    }

    public function render_ad( $atts ): string {
        $atts = shortcode_atts( [ 'id' => '', 'class' => '' ], $atts );
        if ( empty( $atts['id'] ) ) return '';

        $ads = get_option( 'illu_ads_config', [] );
        $ad  = $ads[ $atts['id'] ] ?? null;
        if ( ! $ad || empty( $ad['code'] ) ) return '';

        $class = 'illu-ad ' . sanitize_html_class( $atts['class'] );
        return '<div class="' . esc_attr( $class ) . '">' . $ad['code'] . '</div>';
    }

    public function add_ads_submenu() {
        add_submenu_page(
            'illu-optimize',
            'Ads Manager',
            'Ads Manager',
            'manage_options',
            'illu-ads-manager',
            [ $this, 'render_page' ]
        );
    }

    public function handle_save_ad() {
        if ( ! isset( $_POST['illu_save_ad'] ) || ! current_user_can( 'manage_options' ) ) return;
        check_admin_referer( 'illu_save_ad_action', 'illu_save_ad_nonce' );

        $id   = sanitize_key( $_POST['ad_id'] );
        $name = sanitize_text_field( $_POST['ad_name'] );
        $code = wp_kses_post( $_POST['ad_code'] );
        if ( empty( $id ) || empty( $code ) ) return;

        $ads        = get_option( 'illu_ads_config', [] );
        $ads[ $id ] = [ 'name' => $name, 'code' => $code ];
        update_option( 'illu_ads_config', $ads );

        add_settings_error( 'illu_ads', 'ok', "Ad '{$name}' berhasil disimpan.", 'success' );
    }

    public function render_page() {
        $ads = get_option( 'illu_ads_config', [] );
        if ( isset( $_GET['delete'] ) && current_user_can( 'manage_options' ) ) {
            check_admin_referer( 'illu_delete_ad' );
            $del_id = sanitize_key( $_GET['delete'] );
            unset( $ads[ $del_id ] );
            update_option( 'illu_ads_config', $ads );
        }
        ?>
        <div class="wrap">
            <?php settings_errors( 'illu_ads' ); ?>
            <h1>Ads Manager</h1>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px;">
                <div>
                    <h2>Tambah / Edit Ad</h2>
                    <form method="post">
                        <?php wp_nonce_field('illu_save_ad_action','illu_save_ad_nonce'); ?>
                        <table class="form-table">
                            <tr><th>ID</th><td><input type="text" name="ad_id" class="regular-text" placeholder="sidebar_top" required></td></tr>
                            <tr><th>Nama</th><td><input type="text" name="ad_name" class="regular-text" required></td></tr>
                            <tr><th>Kode Iklan</th><td><textarea name="ad_code" rows="8" style="width:100%;font-family:monospace;" required></textarea></td></tr>
                        </table>
                        <p><button type="submit" name="illu_save_ad" class="button button-primary">Simpan Ad</button></p>
                        <p class="description">Gunakan shortcode: <code>[illu_ad id="sidebar_top"]</code></p>
                    </form>
                </div>
                <div>
                    <h2>Daftar Ads</h2>
                    <?php if ( empty( $ads ) ) : ?>
                        <p>Belum ada ad.</p>
                    <?php else : ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead><tr><th>ID</th><th>Nama</th><th>Shortcode</th><th>Aksi</th></tr></thead>
                            <tbody>
                            <?php foreach ( $ads as $id => $ad ) : ?>
                                <tr>
                                    <td><code><?php echo esc_html( $id ); ?></code></td>
                                    <td><?php echo esc_html( $ad['name'] ); ?></td>
                                    <td><code>[illu_ad id="<?php echo esc_attr( $id ); ?>"]</code></td>
                                    <td>
                                        <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'delete', $id ), 'illu_delete_ad' ) ); ?>"
                                           onclick="return confirm('Hapus ad ini?')" class="button button-small">Hapus</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
}
