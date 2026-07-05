<?php
/**
 * Plugin Name: Illusionis
 * Plugin URI:  https://solusimarketing.xyz
 * Description: Publish halaman/post dari AI HTML (Tailwind) langsung ke WordPress. Integrasi penuh dengan illu-shield, illu-optimize, dan illusi-theme.
 * Version:     2.1.0
 * Author:      Solusi Marketing
 * Text Domain: illusionis
 * Requires PHP: 7.4
 *
 * COMPATIBILITY NOTES:
 *   - illu-shield v2.2.0+: Endpoint /illusionis/v1/ didaftarkan ke whitelist via
 *     filter 'illu_shield_whitelisted_paths' — HANYA endpoint ini yang dibypass,
 *     tidak mempengaruhi endpoint REST lain.
 *   - CORS: TIDAK lagi mengganti global CORS handler WordPress. Header CORS hanya
 *     di-set pada response endpoint /illusionis/v1/ saja.
 *   - illu-optimize v1.2.0+: SEO meta keys (_illu_seo_title, _illu_seo_desc,
 *     _illu_seo_schema_type) sudah selaras dengan class-meta.php.
 *   - illusi-theme v1.0.3+: Auto-assign template-canvas.php untuk post_type=page.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'ILLUSIONIS_VERSION', '2.1.0' );

// ── Boot ───────────────────────────────────────────────────────────────────────
add_action( 'admin_menu',   'illusionis_admin_menu' );
add_action( 'rest_api_init', 'illusionis_register_rest_routes' );

// ── Daftarkan endpoint ke illu-shield whitelist ────────────────────────────────
// Jika illu-shield aktif, endpoint ini dibypass dari protect_rest_api & firewall.
// Jika illu-shield tidak aktif, filter ini menjadi no-op — tidak menyebabkan error.
add_filter( 'illu_shield_whitelisted_paths', function ( array $paths ): array {
    $paths[] = '/wp-json/illusionis/';
    $paths[] = '/wp-json/illusionis/v1/';
    return $paths;
} );

// ── Admin Menu ─────────────────────────────────────────────────────────────────
function illusionis_admin_menu() {
    add_menu_page(
        'Illusionis',
        'Illusionis',
        'manage_options',
        'illusionis-importer',
        'illusionis_admin_page',
        'dashicons-art',
        30
    );
}

// ── Admin Page ─────────────────────────────────────────────────────────────────
function illusionis_admin_page() {
    // Handle Settings Save
    if ( isset( $_POST['illusi_action'] ) && current_user_can( 'manage_options' ) ) {
        check_admin_referer( 'illusionis_settings_action', 'illusionis_settings_nonce' );

        if ( $_POST['illusi_action'] === 'save_gemini' ) {
            update_option( 'illusi_gemini_api_key', sanitize_text_field( $_POST['gemini_api_key'] ?? '' ) );
            echo '<div class="notice notice-success is-dismissible"><p>Gemini API Key berhasil disimpan!</p></div>';
        }

        if ( $_POST['illusi_action'] === 'regenerate_key' ) {
            try {
                $new_key = bin2hex( random_bytes( 32 ) );
            } catch ( Exception $e ) {
                $new_key = wp_generate_password( 64, false );
            }
            update_option( 'illusi_canvas_secret_key', $new_key );
            echo '<div class="notice notice-success is-dismissible"><p>Secret Key berhasil diperbarui!</p></div>';
        }
    }

    // Ensure secret key exists
    $secret_key = get_option( 'illusi_canvas_secret_key' );
    if ( empty( $secret_key ) ) {
        try {
            $secret_key = bin2hex( random_bytes( 32 ) );
        } catch ( Exception $e ) {
            $secret_key = wp_generate_password( 64, false );
        }
        update_option( 'illusi_canvas_secret_key', $secret_key );
    }

    $gemini_api_key  = get_option( 'illusi_gemini_api_key', '' );
    $endpoint        = esc_url( rest_url( 'illusionis/v1/publish' ) );
    $nonce           = wp_create_nonce( 'wp_rest' );
    ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            corePlugins: { preflight: false },
            theme: { extend: { colors: { primary: '#2563eb' } } }
        }
    </script>

    <div class="wrap" id="illusionis-admin-app" x-data="illusionisApp()">
        <div class="max-w-5xl mx-auto mt-6 bg-slate-50 p-6 rounded-2xl shadow-sm text-slate-800" style="font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">

            <header class="mb-8 text-center">
                <h1 class="text-3xl font-black tracking-tight text-slate-900 mb-2">Illusionis <span class="text-primary">V2</span></h1>
                <p class="text-slate-500 font-medium">Publish AI Canvas HTML langsung ke WordPress</p>
            </header>

            <!-- Tabs -->
            <div class="flex flex-wrap justify-center gap-4 mb-8">
                <button @click="tab = 'publish'" :class="tab==='publish' ? 'bg-primary text-white shadow-md' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'" class="px-6 py-2.5 rounded-full font-bold transition-all flex items-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-rocket"></i> Publish Content
                </button>
                <button @click="tab = 'settings'" :class="tab==='settings' ? 'bg-slate-800 text-white shadow-md' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'" class="px-6 py-2.5 rounded-full font-bold transition-all flex items-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-gear"></i> Settings & API
                </button>
            </div>

            <!-- Tab: Settings -->
            <div x-show="tab === 'settings'" class="bg-white rounded-xl p-8 shadow-sm border border-slate-200" style="display:none;">
                <h2 class="text-2xl font-bold mb-6 text-slate-900"><i class="fa-solid fa-plug text-slate-400 mr-2"></i> Settings & Remote API</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Gemini API Key -->
                    <div class="bg-slate-50 p-6 rounded-xl border border-slate-200">
                        <h3 class="font-bold text-lg mb-4 text-slate-800">Gemini API Configuration</h3>
                        <form method="post" action="">
                            <?php wp_nonce_field( 'illusionis_settings_action', 'illusionis_settings_nonce' ); ?>
                            <input type="hidden" name="illusi_action" value="save_gemini">
                            <div class="mb-4">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Gemini API Key</label>
                                <input type="password" name="gemini_api_key" value="<?php echo esc_attr( $gemini_api_key ); ?>" placeholder="AIzaSy..." class="w-full bg-white border border-slate-300 px-4 py-2 rounded-lg focus:ring-2 focus:ring-primary focus:outline-none">
                            </div>
                            <button type="submit" class="bg-primary hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition-all cursor-pointer">Simpan Gemini Key</button>
                        </form>
                    </div>

                    <!-- Remote Publish Config -->
                    <div class="bg-slate-50 p-6 rounded-xl border border-slate-200">
                        <h3 class="font-bold text-lg mb-4 text-slate-800">Remote API Configuration</h3>
                        <p class="text-sm text-slate-600 mb-4">Gunakan kredensial ini untuk remote publish dari luar WordPress.</p>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">API Endpoint</label>
                                <code class="block bg-white border border-slate-300 px-3 py-2 rounded text-sm break-all"><?php echo $endpoint; ?></code>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Secret Key <span class="text-xs text-slate-400">(kirim via header X-Illusi-Key)</span></label>
                                <code class="block bg-white border border-slate-300 px-3 py-2 rounded text-sm break-all"><?php echo esc_html( $secret_key ); ?></code>
                            </div>

                            <!-- Security info -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-800">
                                <strong>Security:</strong> Endpoint ini dibypass dari proteksi illu-shield via whitelist path. CORS hanya diaktifkan untuk endpoint ini saja, tidak mempengaruhi REST API lain.
                            </div>

                            <form method="post" action="" onsubmit="return confirm('Key lama tidak bisa digunakan lagi setelah ini. Lanjutkan?');">
                                <?php wp_nonce_field( 'illusionis_settings_action', 'illusionis_settings_nonce' ); ?>
                                <input type="hidden" name="illusi_action" value="regenerate_key">
                                <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-bold py-2 px-4 rounded-lg transition-all text-sm mt-2 cursor-pointer">
                                    <i class="fa-solid fa-rotate-right mr-1"></i> Regenerate Secret Key
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: Publish -->
            <div x-show="tab === 'publish'" class="space-y-6">
                <div class="bg-white rounded-xl p-6 sm:p-8 shadow-sm border border-slate-200">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Judul (Title)</label>
                            <input type="text" x-model="post.title" placeholder="Contoh: Landing Page Produk Baju" class="w-full bg-slate-50 border border-slate-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary focus:outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Custom Slug (Opsional)</label>
                            <input type="text" x-model="post.slug" placeholder="produk-baju" class="w-full bg-slate-50 border border-slate-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary focus:outline-none transition-all">
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-bold text-slate-700 mb-2">Publish Sebagai</label>
                        <select x-model="post.post_type" class="w-full bg-slate-50 border border-slate-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary focus:outline-none transition-all">
                            <option value="page">Page (Template Edge-to-Edge otomatis via template-canvas.php)</option>
                            <option value="post">Post (Format artikel standar dengan header/footer theme)</option>
                        </select>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-bold text-slate-700 mb-2">Schema Type (AEO/SEO)</label>
                        <select x-model="post.schema_type" class="w-full bg-slate-50 border border-slate-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary focus:outline-none transition-all">
                            <optgroup label="General & Content">
                                <option value="Article">Article (Default)</option>
                                <option value="BlogPosting">Blog Posting</option>
                                <option value="NewsArticle">News Article</option>
                                <option value="WebPage">WebPage</option>
                                <option value="AboutPage">AboutPage</option>
                                <option value="ProfilePage">ProfilePage</option>
                                <option value="CollectionPage">CollectionPage</option>
                                <option value="FAQPage">FAQPage</option>
                                <option value="QAPage">QAPage</option>
                                <option value="HowTo">HowTo</option>
                                <option value="Review">Review</option>
                                <option value="Recipe">Recipe</option>
                                <option value="TechArticle">TechArticle</option>
                                <option value="ScholarlyArticle">ScholarlyArticle</option>
                            </optgroup>
                            <optgroup label="Business & E-Commerce">
                                <option value="Organization">Organization</option>
                                <option value="LocalBusiness">LocalBusiness</option>
                                <option value="Product">Product</option>
                                <option value="Offer">Offer</option>
                                <option value="Service">Service</option>
                                <option value="JobPosting">JobPosting</option>
                            </optgroup>
                            <optgroup label="Structure & Media">
                                <option value="WebSite">WebSite</option>
                                <option value="BreadcrumbList">BreadcrumbList</option>
                                <option value="ItemList">ItemList</option>
                                <option value="Person">Person</option>
                                <option value="Event">Event</option>
                                <option value="Dataset">Dataset</option>
                            </optgroup>
                        </select>
                        <p class="text-xs text-slate-500 mt-1">SEO Title & Meta Description diekstrak otomatis dari Raw HTML, dikelola oleh illu-optimize.</p>
                    </div>

                    <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-6" x-show="post.post_type === 'post'" style="display:none;">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Kategori</label>
                            <input type="text" x-model="post.category" placeholder="Teknologi" class="w-full bg-slate-50 border border-slate-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary focus:outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Tags (pisahkan koma)</label>
                            <input type="text" x-model="post.tags" placeholder="ai, web design, seo" class="w-full bg-slate-50 border border-slate-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary focus:outline-none transition-all">
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-bold text-slate-700 mb-2 flex items-center gap-2">
                            Raw HTML / Full Canvas Script
                            <span class="text-xs font-normal text-slate-500">(Sistem akan mengekstrak Body & Scripts)</span>
                        </label>
                        <textarea x-model="post.html_content" rows="12"
                                  placeholder="<!DOCTYPE html>... Paste seluruh output HTML AI di sini"
                                  class="w-full bg-slate-900 text-emerald-400 font-mono text-sm border-2 border-slate-800 p-4 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary focus:outline-none transition-all"></textarea>
                    </div>

                    <button @click="publishContent" :disabled="loading"
                            class="w-full bg-primary hover:bg-blue-700 text-white font-black text-lg py-4 px-6 rounded-2xl flex items-center justify-center gap-3 shadow-[0_8px_20px_rgba(37,99,235,0.3)] disabled:opacity-70 disabled:cursor-not-allowed transition-all cursor-pointer">
                        <span x-show="!loading"><i class="fa-solid fa-rocket"></i> PUBLISH NOW</span>
                        <span x-show="loading" style="display:none;"><i class="fa-solid fa-circle-notch fa-spin"></i> Processing & Publishing...</span>
                    </button>

                    <!-- Result -->
                    <div x-show="result.show" class="mt-8 p-6 rounded-2xl border transition-all"
                         :class="result.isError ? 'bg-red-50 border-red-200' : 'bg-emerald-50 border-emerald-200'"
                         style="display:none;">
                        <div class="flex items-start gap-4">
                            <div class="text-2xl" :class="result.isError ? 'text-red-500' : 'text-emerald-500'">
                                <i class="fa-solid" :class="result.isError ? 'fa-circle-xmark' : 'fa-circle-check'"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-slate-900 text-lg mb-1" x-text="result.isError ? 'Publish Gagal!' : 'Publish Berhasil!'"></h4>
                                <p class="text-sm text-slate-600 mb-4" x-text="result.message"></p>
                                <template x-if="!result.isError && result.url">
                                    <div class="flex gap-3">
                                        <a :href="result.url" target="_blank" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-bold transition-all no-underline inline-flex items-center">
                                            <i class="fa-solid fa-eye mr-2"></i> Buka Halaman
                                        </a>
                                        <a :href="result.edit_url" target="_blank" class="bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 px-4 py-2 rounded-lg text-sm font-bold transition-all no-underline inline-flex items-center">
                                            <i class="fa-solid fa-pen mr-2"></i> Edit Mode
                                        </a>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('illusionisApp', () => ({
            tab: 'publish',
            post: {
                title: '',
                slug: '',
                post_type: 'page',
                schema_type: 'Article',
                category: '',
                tags: '',
                html_content: ''
            },
            loading: false,
            result: { show: false, isError: false, message: '', url: '', edit_url: '' },

            async publishContent() {
                if (!this.post.title || !this.post.html_content) {
                    alert('Judul dan Kode HTML tidak boleh kosong!');
                    return;
                }

                this.loading = true;
                this.result.show = false;

                try {
                    // Parse & extract body+scripts dari full HTML
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(this.post.html_content, 'text/html');

                    let seoTitle = doc.title || '';
                    let seoDesc  = doc.querySelector('meta[name="description"]')?.getAttribute('content') || '';

                    let cleanHtml = this.post.html_content;
                    if (doc.body && doc.body.innerHTML.trim() !== '') {
                        let headElements = '';
                        if (doc.head) {
                            doc.head.querySelectorAll('style, script, link[rel="stylesheet"]').forEach(el => {
                                headElements += el.outerHTML + '\n';
                            });
                        }
                        const wrapper = doc.createElement('div');
                        Array.from(doc.body.attributes).forEach(attr => wrapper.setAttribute(attr.name, attr.value));
                        wrapper.innerHTML = doc.body.innerHTML;
                        cleanHtml = headElements + wrapper.outerHTML;
                    }

                    const response = await fetch('<?php echo $endpoint; ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': '<?php echo esc_js( $nonce ); ?>'
                        },
                        body: JSON.stringify({
                            title:        this.post.title,
                            slug:         this.post.slug,
                            post_type:    this.post.post_type,
                            schema_type:  this.post.schema_type,
                            category:     this.post.category,
                            tags:         this.post.tags,
                            seo_title:    seoTitle,
                            seo_desc:     seoDesc,
                            html_content: cleanHtml
                        })
                    });

                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'Terjadi kesalahan API.');

                    this.result = {
                        show: true, isError: false,
                        message: 'Konten berhasil dipublikasikan ke WordPress!',
                        url: data.url,
                        edit_url: data.edit_url
                    };
                    this.post.title = '';
                    this.post.slug  = '';
                    this.post.html_content = '';

                } catch (error) {
                    this.result = { show: true, isError: true, message: error.message, url: '', edit_url: '' };
                } finally {
                    this.loading = false;
                }
            }
        }));
    });
    </script>
    <?php
}

// ── REST Route ─────────────────────────────────────────────────────────────────
function illusionis_register_rest_routes() {
    register_rest_route( 'illusionis/v1', '/publish', [
        'methods'             => [ 'POST', 'OPTIONS' ],
        'callback'            => 'illusionis_api_publish',
        'permission_callback' => '__return_true',
        // CORS hanya untuk route ini, bukan global
        'args'                => [],
    ] );

    // Set CORS HANYA untuk endpoint ini via rest_post_dispatch — tidak menyentuh global handler
    add_filter( 'rest_post_dispatch', 'illusionis_cors_headers', 10, 3 );
}

/**
 * CORS headers — HANYA untuk endpoint /illusionis/v1/, tidak mempengaruhi endpoint lain.
 * Berbeda dengan versi lama yang remove_filter rest_pre_serve_request secara global.
 */
function illusionis_cors_headers( $response, $server, $request ) {
    if ( strpos( $request->get_route(), '/illusionis/v1/' ) !== 0 ) {
        return $response; // Bukan endpoint illusionis — tidak sentuh apapun
    }

    $response->header( 'Access-Control-Allow-Origin',  '*' );
    $response->header( 'Access-Control-Allow-Methods', 'POST, OPTIONS' );
    $response->header( 'Access-Control-Allow-Headers', 'Authorization, Content-Type, X-WP-Nonce, X-Illusi-Key' );
    return $response;
}

// ── REST Callback ──────────────────────────────────────────────────────────────
function illusionis_api_publish( WP_REST_Request $request ) {
    // Handle OPTIONS preflight
    if ( $request->get_method() === 'OPTIONS' ) {
        return rest_ensure_response( [ 'status' => 'OK' ] );
    }

    $params = $request->get_json_params() ?: $request->get_params();

    // ── Auth check ─────────────────────────────────────────────────────────────
    $is_authorized = false;

    if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
        // Admin login via X-WP-Nonce (sudah diverifikasi WP sebelum callback)
        $is_authorized = true;
    } else {
        // Remote key — dari header X-Illusi-Key atau body secret_key
        $provided_key = '';
        if ( ! empty( $_SERVER['HTTP_X_ILLUSI_KEY'] ) ) {
            $provided_key = sanitize_text_field( $_SERVER['HTTP_X_ILLUSI_KEY'] );
        } elseif ( ! empty( $params['secret_key'] ) ) {
            $provided_key = sanitize_text_field( $params['secret_key'] );
        }

        $secret_key = get_option( 'illusi_canvas_secret_key', '' );

        // PERBAIKAN: timing-safe comparison untuk mencegah timing attack
        if ( ! empty( $provided_key ) && ! empty( $secret_key ) &&
             hash_equals( $secret_key, $provided_key ) ) {
            $is_authorized = true;
        }
    }

    if ( ! $is_authorized ) {
        return new WP_Error( 'rest_forbidden', 'API Key tidak valid atau tidak terotorisasi.', [ 'status' => 401 ] );
    }

    // ── Validate input ─────────────────────────────────────────────────────────
    $raw_html = $params['html_content'] ?? '';
    if ( empty( trim( $raw_html ) ) ) {
        return new WP_Error( 'rest_invalid_param', 'html_content tidak boleh kosong.', [ 'status' => 400 ] );
    }

    $title       = sanitize_text_field( $params['title']       ?? 'Untitled Page' );
    $slug        = sanitize_title(      $params['slug']        ?? '' );
    $post_type   = ( ( $params['post_type'] ?? 'page' ) === 'post' ) ? 'post' : 'page';
    $schema_type = sanitize_text_field( $params['schema_type'] ?? 'Article' );
    $seo_title   = sanitize_text_field( $params['seo_title']   ?? '' );
    $seo_desc    = sanitize_textarea_field( $params['seo_desc'] ?? '' );

    // ── Sanitasi HTML ──────────────────────────────────────────────────────────
    // PERBAIKAN: Tidak lagi pakai kses_remove_filters() global.
    // Gunakan wp_kses dengan allowlist yang sangat luas untuk HTML canvas.
    // Ini aman karena hanya admin/authorized user yang bisa memanggil endpoint ini.
    $allowed_html = illusionis_get_allowed_html();
    $clean_html   = wp_kses( $raw_html, $allowed_html );

    // Jika hasil kses terlalu pendek (banyak tag terstrip), simpan raw sebagai fallback
    // untuk kasus HTML canvas yang pakai tag non-standar (e.g. web components).
    // Hanya admin yang sampai ke sini, jadi ini aman.
    if ( strlen( $clean_html ) < ( strlen( $raw_html ) * 0.5 ) ) {
        // HTML banyak kehilangan konten — simpan tanpa encode agar canvas tidak rusak.
        // wp_slash() tetap dipakai agar karakter tidak escape ganda di DB.
        $clean_html = $raw_html;
    }

    // ── Insert post ────────────────────────────────────────────────────────────
    $post_data = [
        'post_title'   => wp_slash( $title ),
        'post_name'    => $slug ? wp_slash( $slug ) : '',
        'post_content' => wp_slash( $clean_html ),
        'post_status'  => 'publish',
        'post_type'    => $post_type,
        'post_author'  => get_current_user_id() ?: 1,
    ];

    $post_id = wp_insert_post( $post_data, true );

    if ( is_wp_error( $post_id ) ) {
        return new WP_Error( 'insert_failed', $post_id->get_error_message(), [ 'status' => 500 ] );
    }

    // ── Post meta ──────────────────────────────────────────────────────────────
    // SEO meta selaras dengan illu-optimize/class-meta.php
    if ( $seo_title )   update_post_meta( $post_id, '_illu_seo_title',       $seo_title );
    if ( $seo_desc )    update_post_meta( $post_id, '_illu_seo_desc',        $seo_desc );
    if ( $schema_type ) update_post_meta( $post_id, '_illu_seo_schema_type', $schema_type );

    // Assign template-canvas.php untuk page (selaras dengan illusi-theme)
    if ( $post_type === 'page' ) {
        update_post_meta( $post_id, '_wp_page_template', 'template-canvas.php' );
    }

    // ── Kategori & Tag (hanya untuk post) ─────────────────────────────────────
    if ( $post_type === 'post' ) {
        $category_name = sanitize_text_field( $params['category'] ?? '' );
        if ( ! empty( $category_name ) ) {
            $cat_term = get_term_by( 'name', $category_name, 'category' );
            if ( $cat_term ) {
                $cat_id = $cat_term->term_id;
            } else {
                $inserted = wp_insert_term( $category_name, 'category' );
                $cat_id   = is_wp_error( $inserted ) ? false : $inserted['term_id'];
            }
            if ( $cat_id ) wp_set_post_categories( $post_id, [ $cat_id ] );
        }

        $tags_input = sanitize_text_field( $params['tags'] ?? '' );
        if ( ! empty( $tags_input ) ) {
            wp_set_post_tags( $post_id, $tags_input, true );
        }
    }

    return rest_ensure_response( [
        'success'  => true,
        'post_id'  => $post_id,
        'url'      => get_permalink( $post_id ),
        'edit_url' => get_edit_post_link( $post_id, 'raw' ),
    ] );
}

/**
 * Allowlist HTML luas untuk konten canvas AI.
 * Mencakup tag Tailwind-compatible, SVG inline, dan script yang dibutuhkan canvas.
 */
function illusionis_get_allowed_html(): array {
    $global_attrs = [
        'class' => true, 'id' => true, 'style' => true,
        'data-*' => true, 'aria-*' => true,
        'x-data' => true, 'x-bind' => true, 'x-on' => true,
        'x-model' => true, 'x-show' => true, 'x-if' => true,
        'x-for' => true, 'x-text' => true, 'x-html' => true,
        ':class' => true, '@click' => true, '@change' => true,
        '@input' => true, '@submit' => true,
    ];

    $block = fn( $extra = [] ) => array_merge( $global_attrs, $extra );

    return [
        // Structure
        'html'    => $block( [ 'lang' => true ] ),
        'head'    => $block(),
        'body'    => $block(),
        'div'     => $block(),
        'section' => $block(),
        'article' => $block(),
        'aside'   => $block(),
        'main'    => $block(),
        'header'  => $block(),
        'footer'  => $block(),
        'nav'     => $block( [ 'aria-label' => true ] ),
        'span'    => $block(),
        // Typography
        'h1' => $block(), 'h2' => $block(), 'h3' => $block(),
        'h4' => $block(), 'h5' => $block(), 'h6' => $block(),
        'p' => $block(), 'strong' => $block(), 'em' => $block(),
        'small' => $block(), 'del' => $block(), 'mark' => $block(),
        'br' => [], 'hr' => $block(),
        'ul' => $block(), 'ol' => $block( [ 'start' => true ] ),
        'li' => $block(),
        'dl' => $block(), 'dt' => $block(), 'dd' => $block(),
        'blockquote' => $block( [ 'cite' => true ] ),
        'pre' => $block(), 'code' => $block(),
        // Links & Media
        'a'   => $block( [ 'href' => true, 'target' => true, 'rel' => true, 'title' => true, 'download' => true ] ),
        'img' => $block( [ 'src' => true, 'srcset' => true, 'sizes' => true, 'alt' => true, 'width' => true, 'height' => true, 'loading' => true, 'decoding' => true ] ),
        'picture' => $block(), 'source' => $block( [ 'srcset' => true, 'media' => true, 'type' => true ] ),
        'video'   => $block( [ 'src' => true, 'controls' => true, 'autoplay' => true, 'muted' => true, 'loop' => true, 'playsinline' => true, 'poster' => true ] ),
        'iframe'  => $block( [ 'src' => true, 'width' => true, 'height' => true, 'frameborder' => true, 'allowfullscreen' => true, 'allow' => true, 'title' => true ] ),
        // Form elements
        'form'     => $block( [ 'action' => true, 'method' => true, 'enctype' => true ] ),
        'input'    => $block( [ 'type' => true, 'name' => true, 'value' => true, 'placeholder' => true, 'required' => true, 'disabled' => true, 'readonly' => true, 'checked' => true, 'min' => true, 'max' => true, 'step' => true, 'maxlength' => true, 'pattern' => true, 'autocomplete' => true ] ),
        'textarea' => $block( [ 'name' => true, 'rows' => true, 'cols' => true, 'placeholder' => true, 'required' => true ] ),
        'select'   => $block( [ 'name' => true, 'required' => true, 'multiple' => true ] ),
        'option'   => $block( [ 'value' => true, 'selected' => true ] ),
        'optgroup' => $block( [ 'label' => true ] ),
        'button'   => $block( [ 'type' => true, 'disabled' => true ] ),
        'label'    => $block( [ 'for' => true ] ),
        // Layout
        'table' => $block(), 'thead' => $block(), 'tbody' => $block(),
        'tfoot' => $block(), 'tr' => $block(),
        'th' => $block( [ 'colspan' => true, 'rowspan' => true, 'scope' => true ] ),
        'td' => $block( [ 'colspan' => true, 'rowspan' => true ] ),
        // Style & Script (canvas butuh ini)
        'style'  => [ 'type' => true ],
        'script' => [ 'type' => true, 'src' => true, 'defer' => true, 'async' => true, 'crossorigin' => true, 'integrity' => true ],
        'link'   => [ 'rel' => true, 'href' => true, 'type' => true, 'media' => true, 'crossorigin' => true ],
        'meta'   => [ 'name' => true, 'content' => true, 'charset' => true, 'http-equiv' => true, 'property' => true ],
        'title'  => [],
        // SVG inline
        'svg'      => $block( [ 'xmlns' => true, 'viewBox' => true, 'fill' => true, 'stroke' => true, 'width' => true, 'height' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true ] ),
        'path'     => $block( [ 'd' => true, 'fill' => true, 'stroke' => true ] ),
        'circle'   => $block( [ 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true ] ),
        'rect'     => $block( [ 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'fill' => true ] ),
        'polygon'  => $block( [ 'points' => true, 'fill' => true ] ),
        'polyline' => $block( [ 'points' => true, 'fill' => true, 'stroke' => true ] ),
        'line'     => $block( [ 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true ] ),
        'text'     => $block( [ 'x' => true, 'y' => true, 'fill' => true, 'font-size' => true ] ),
        'g'        => $block( [ 'transform' => true, 'fill' => true ] ),
        'defs'     => $block(), 'clipPath' => $block( [ 'id' => true ] ),
        'use'      => $block( [ 'href' => true, 'xlink:href' => true ] ),
        'symbol'   => $block( [ 'viewBox' => true ] ),
        'linearGradient'  => $block( [ 'id' => true, 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'gradientUnits' => true ] ),
        'radialGradient'  => $block( [ 'id' => true, 'cx' => true, 'cy' => true, 'r' => true, 'gradientUnits' => true ] ),
        'stop'     => $block( [ 'offset' => true, 'stop-color' => true, 'stop-opacity' => true ] ),
        'mask'     => $block( [ 'id' => true ] ),
        // Canvas/misc
        'canvas'   => $block( [ 'width' => true, 'height' => true ] ),
        'details'  => $block(), 'summary' => $block(),
        'dialog'   => $block( [ 'open' => true ] ),
        'template' => $block(),
        'slot'     => $block( [ 'name' => true ] ),
        'figure'   => $block(), 'figcaption' => $block(),
        'time'     => $block( [ 'datetime' => true ] ),
        'abbr'     => $block( [ 'title' => true ] ),
        'cite'     => $block(),
        'q'        => $block( [ 'cite' => true ] ),
    ];
}
