<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Illu_Optimize_Admin {

    public function __construct() {
        add_action( 'admin_menu',            [ $this, 'add_menu' ] );
        add_action( 'admin_init',            [ $this, 'handle_settings_save' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'illu-optimize' ) === false ) return;
        wp_enqueue_script( 'tailwindcss', 'https://cdn.tailwindcss.com', [], null, false );
        wp_enqueue_script( 'chartjs',     'https://cdn.jsdelivr.net/npm/chart.js', [], null, true );
    }

    public function add_menu() {
        add_menu_page(
            'Illu Optimize', 'Illu Optimize', 'manage_options',
            'illu-optimize', [ $this, 'render_settings_page' ],
            'dashicons-performance', 30
        );
    }

    public function handle_settings_save() {
        if ( ! isset( $_POST['illu_optimize_save_settings'] ) || ! current_user_can( 'manage_options' ) ) return;
        check_admin_referer( 'illu_optimize_settings_action', 'illu_optimize_nonce' );

        $settings = [
            'minify_html'       => isset( $_POST['minify_html'] )       ? 'yes' : 'no',
            'minify_js'         => isset( $_POST['minify_js'] )         ? 'yes' : 'no',
            'minify_css'        => isset( $_POST['minify_css'] )        ? 'yes' : 'no',
            'defer_js'          => isset( $_POST['defer_js'] )          ? 'yes' : 'no',
            'preload_assets'    => isset( $_POST['preload_assets'] )    ? 'yes' : 'no',
            'responsive_images' => isset( $_POST['responsive_images'] ) ? 'yes' : 'no',
            'convert_webp'      => isset( $_POST['convert_webp'] )      ? 'yes' : 'no',
            'image_format'      => in_array( $_POST['image_format'] ?? '', [ 'webp', 'avif' ] ) ? $_POST['image_format'] : 'webp',
            'image_quality'     => min( 100, max( 1, intval( $_POST['image_quality'] ?? 80 ) ) ),
            'micro_cache'       => isset( $_POST['micro_cache'] )       ? 'yes' : 'no',
            'micro_cache_ttl'   => max( 5, intval( $_POST['micro_cache_ttl'] ?? 60 ) ),
            'remove_feed_links' => isset( $_POST['remove_feed_links'] ) ? 'yes' : 'no',
            'block_ai_crawlers' => isset( $_POST['block_ai_crawlers'] ) ? 'yes' : 'no',
        ];

        update_option( 'illu_optimize_settings', $settings );

        // Update schedule
        $schedule = sanitize_text_field( $_POST['auto_purge_schedule'] ?? 'twicedaily' );
        if ( in_array( $schedule, [ 'hourly', 'twicedaily', 'daily', 'never' ], true ) ) {
            update_option( 'illu_auto_purge_schedule', $schedule );
        }

        add_settings_error( 'illu_optimize', 'ok', 'Pengaturan berhasil disimpan.', 'success' );
    }

    public function render_settings_page() {
        $tab = sanitize_text_field( $_GET['tab'] ?? 'general' );
        $s   = get_option( 'illu_optimize_settings', [] );
        ?>
        <div class="wrap" style="margin:20px 20px 0 0;">
            <?php settings_errors( 'illu_optimize' ); ?>
            <h2 class="nav-tab-wrapper border-b-0 mb-4">
                <?php
                $tabs = [ 'general' => 'General', 'media' => 'Media', 'cache' => 'Cache', 'seo' => 'SEO', 'database' => 'Database' ];
                foreach ( $tabs as $slug => $label ) {
                    $active = $tab === $slug ? 'nav-tab-active' : '';
                    echo "<a href='?page=illu-optimize&tab={$slug}' class='nav-tab {$active}'>{$label}</a>";
                }
                ?>
            </h2>

            <form method="post">
                <?php wp_nonce_field( 'illu_optimize_settings_action', 'illu_optimize_nonce' ); ?>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden" style="max-width:700px;">

                <?php if ( $tab === 'general' ) : ?>
                    <div class="px-6 py-5 border-b bg-slate-50">
                        <h2 class="text-lg font-bold m-0">Performance Settings</h2>
                        <p class="text-sm text-slate-500 mt-1 mb-0">HTML minifier, JS defer, resource hints.</p>
                    </div>
                    <div class="p-6 space-y-3">
                        <?php $this->toggle( 'minify_html', 'Minify HTML', 'Hapus whitespace & komentar HTML. Bypass otomatis untuk template-canvas (AI HTML).', $s ); ?>
                        <?php $this->toggle( 'minify_js',   'Minify Inline JS', 'Strip komentar & whitespace JS inline.', $s ); ?>
                        <?php $this->toggle( 'minify_css',  'Minify Inline CSS', 'Strip komentar & whitespace CSS inline.', $s ); ?>
                        <?php $this->toggle( 'defer_js',    'Defer Non-Critical JS', 'Tambah defer pada semua script selain jQuery/admin.', $s ); ?>
                        <?php $this->toggle( 'preload_assets', 'Preload Critical Assets', 'Preload Google Fonts & hero image.', $s ); ?>
                        <?php $this->toggle( 'remove_feed_links', 'Hapus Feed Links dari <head>', '', $s ); ?>

                        <div class="pt-4 border-t border-slate-100">
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Auto Cache Purge Schedule</label>
                            <select name="auto_purge_schedule" class="border border-slate-300 rounded text-sm py-1.5 px-2">
                                <?php
                                $current_schedule = get_option( 'illu_auto_purge_schedule', 'twicedaily' );
                                $schedules = [ 'never' => 'Nonaktif', 'hourly' => 'Setiap Jam', 'twicedaily' => '2x Sehari', 'daily' => 'Setiap Hari' ];
                                foreach ( $schedules as $val => $lbl ) {
                                    printf( '<option value="%s" %s>%s</option>', esc_attr($val), selected($current_schedule,$val,false), esc_html($lbl) );
                                }
                                ?>
                            </select>
                            <p class="text-xs text-slate-500 mt-1">Menjalankan: purge Smart Cache + Redis + Cloudflare secara serentak.</p>
                        </div>
                    </div>

                <?php elseif ( $tab === 'media' ) : ?>
                    <div class="px-6 py-5 border-b bg-slate-50">
                        <h2 class="text-lg font-bold m-0">Media Optimization</h2>
                    </div>
                    <div class="p-6 space-y-3">
                        <?php $this->toggle( 'responsive_images', 'Lazy Load & srcset otomatis', 'loading=lazy + decoding=async pada semua gambar di content.', $s ); ?>
                        <?php $this->toggle( 'convert_webp', 'Auto Convert ke WebP/AVIF saat upload', 'Memerlukan GD/Imagick dengan WebP support.', $s ); ?>
                        <div class="pl-0 space-y-3">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">Format Output</label>
                                <select name="image_format" class="border border-slate-300 rounded text-sm py-1.5 px-2">
                                    <option value="webp" <?php selected( $s['image_format']??'webp','webp'); ?>>WebP</option>
                                    <option value="avif" <?php selected( $s['image_format']??'webp','avif'); ?>>AVIF (lebih kecil, butuh PHP 8.1+)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">Kualitas (1–100)</label>
                                <input type="number" name="image_quality" min="1" max="100" value="<?php echo intval($s['image_quality']??80); ?>"
                                       class="border border-slate-300 rounded text-sm py-1.5 px-2 w-20">
                            </div>
                        </div>
                        <div class="pt-4 border-t border-slate-100">
                            <h3 class="text-sm font-bold text-slate-700 mb-2">Bulk Convert</h3>
                            <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
                                <?php wp_nonce_field('illu_bulk_convert_action','illu_bulk_convert_nonce'); ?>
                                <input type="hidden" name="action" value="illu_bulk_convert">
                                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2 px-4 rounded text-sm">
                                    Konversi Semua Gambar Sekarang
                                </button>
                            </form>
                            <?php if ( isset($_GET['converted']) ) : ?>
                                <p class="text-green-700 text-sm mt-2">✓ <?php echo intval($_GET['converted']); ?> gambar berhasil dikonversi.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php elseif ( $tab === 'cache' ) : ?>
                    <div class="px-6 py-5 border-b bg-slate-50">
                        <h2 class="text-lg font-bold m-0">Cache & Redis</h2>
                    </div>
                    <div class="p-6 space-y-3">
                        <?php $this->toggle( 'micro_cache', 'REST Micro-Cache (GET only)', 'Cache REST GET response. TIDAK pernah cache POST/sharelink/webhook.', $s ); ?>
                        <div class="pl-0">
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Micro-Cache TTL (detik)</label>
                            <input type="number" name="micro_cache_ttl" min="5" value="<?php echo intval($s['micro_cache_ttl']??60); ?>"
                                   class="border border-slate-300 rounded text-sm py-1.5 px-2 w-24">
                        </div>
                        <?php
                        // Redis Status
                        $redis_manager = new Illu_Optimize_Redis_Manager();
                        $redis_info    = $redis_manager->get_status_info();
                        $status_ok     = $redis_info['status'] === 'connected';
                        $status_color  = $status_ok ? 'green' : 'red';
                        ?>
                        <div class="pt-4 border-t border-slate-100">
                            <h3 class="text-sm font-bold text-slate-700 mb-2">Redis Object Cache Status</h3>
                            <div class="bg-<?php echo $status_color; ?>-50 border border-<?php echo $status_color; ?>-200 rounded-lg p-4">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="inline-block w-3 h-3 rounded-full bg-<?php echo $status_color; ?>-500"></span>
                                    <strong class="text-<?php echo $status_color; ?>-800">
                                        <?php echo $status_ok ? 'Terhubung' : ucfirst($redis_info['status']); ?>
                                    </strong>
                                </div>
                                <?php if ( $status_ok ) : ?>
                                    <div class="text-sm text-<?php echo $status_color; ?>-700 space-y-1">
                                        <div>Version: <?php echo esc_html($redis_info['version']); ?></div>
                                        <div>Memory: <?php echo esc_html($redis_info['memory']); ?></div>
                                        <div>Keys: <?php echo number_format($redis_info['keys']); ?></div>
                                        <div>Uptime: <?php echo esc_html($redis_info['uptime']); ?></div>
                                    </div>
                                <?php else : ?>
                                    <p class="text-sm text-red-700"><?php echo esc_html($redis_info['message']??''); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                <?php elseif ( $tab === 'seo' ) : ?>
                    <div class="px-6 py-5 border-b bg-slate-50">
                        <h2 class="text-lg font-bold m-0">SEO & Crawlers</h2>
                        <p class="text-sm text-slate-500 mt-1 mb-0">illu-optimize adalah SEO owner. Meta tag dari illusi-theme dinonaktifkan otomatis.</p>
                    </div>
                    <div class="p-6 space-y-3">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
                            <strong>Sitemap URL:</strong> <a href="<?php echo home_url('/sitemap.xml'); ?>" target="_blank"><?php echo home_url('/sitemap.xml'); ?></a><br>
                            <strong>Robots.txt:</strong> <a href="<?php echo home_url('/robots.txt'); ?>" target="_blank"><?php echo home_url('/robots.txt'); ?></a>
                        </div>
                        <?php $this->toggle( 'block_ai_crawlers', 'Blokir AI Crawlers (GPTBot, CCBot, dll.)', 'Tambahkan Disallow ke robots.txt untuk semua AI crawler.', $s ); ?>
                    </div>

                <?php elseif ( $tab === 'database' ) : ?>
                    <div class="px-6 py-5 border-b bg-slate-50">
                        <h2 class="text-lg font-bold m-0">Database Optimization</h2>
                    </div>
                    <div class="p-6">
                        <?php
                        $db_optimizer = new Illu_Optimize_DB_Optimizer();
                        $db_size      = $db_optimizer->get_db_size();
                        ?>
                        <div class="grid grid-cols-3 gap-4 mb-6">
                            <div class="bg-slate-50 rounded-lg p-4 border border-slate-200 text-center">
                                <div class="text-2xl font-bold"><?php echo esc_html($db_size['total_mb']); ?> MB</div>
                                <div class="text-xs text-slate-500">Total Database Size</div>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4 border border-slate-200 text-center">
                                <div class="text-2xl font-bold"><?php echo esc_html($db_size['free_mb']); ?> MB</div>
                                <div class="text-xs text-slate-500">Overhead (bisa direclaim)</div>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4 border border-slate-200 text-center">
                                <div class="text-2xl font-bold"><?php echo esc_html($db_size['tables']); ?></div>
                                <div class="text-xs text-slate-500">Tables</div>
                            </div>
                        </div>
                        <?php if ( isset($_GET['cleaned']) ) : ?>
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                                <p class="text-green-800 font-semibold">✓ <?php echo intval($_GET['cleaned']); ?> items dibersihkan & tabel dioptimasi.</p>
                            </div>
                        <?php endif; ?>
                        <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
                            <?php wp_nonce_field('illu_optimize_db_action','illu_optimize_db_nonce'); ?>
                            <input type="hidden" name="action" value="illu_optimize_db">
                            <p class="text-sm text-slate-600 mb-3">Operasi: hapus revisi, auto-draft, spam comments, expired transients, orphaned postmeta, optimize tables.</p>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded text-sm">
                                Jalankan Optimasi Database
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

                </div>

                <?php if ( $tab !== 'database' ) : ?>
                <div class="mt-4">
                    <button type="submit" name="illu_optimize_save_settings" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded text-sm shadow-sm">
                        Simpan Pengaturan
                    </button>
                </div>
                <?php endif; ?>
            </form>
        </div>
        <?php
    }

    private function toggle( string $name, string $label, string $desc, array $s ) {
        $checked = ( $s[$name] ?? 'yes' ) === 'yes'; ?>
        <label class="flex items-start cursor-pointer hover:bg-slate-50 p-2 rounded -ml-2 transition-colors">
            <input type="checkbox" name="<?php echo esc_attr($name); ?>" value="yes" <?php checked($checked); ?> class="mt-1 mr-3">
            <div>
                <span class="font-semibold text-slate-700 block text-sm"><?php echo esc_html($label); ?></span>
                <?php if ($desc) : ?><span class="text-slate-500 text-[13px]"><?php echo wp_kses($desc, ['code'=>[]]); ?></span><?php endif; ?>
            </div>
        </label>
        <?php
    }
}
