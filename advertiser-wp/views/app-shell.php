<?php
if (!defined('ABSPATH')) exit;
$user = wp_get_current_user();
?>
<!DOCTYPE html>
<html lang="id" style="overflow: hidden; margin: 0; padding: 0;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advertiser Workspace</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: '#003888',
                        'brand-dark': '#002660'
                    }
                }
            }
        }
    </script>
    <style>
        /* Preflight reset overrides */
        #wpadminbar { display: none !important; }
        html.wp-toolbar { padding-top: 0 !important; }
        [v-cloak] { display: none !important; }
    </style>
</head>
<body style="margin: 0; padding: 0; background: #f8fafc;">
    
    <div id="adv-app" class="fixed inset-0 flex bg-slate-50 z-[99999]" v-cloak>
        
        <div v-if="isAppLoading" class="absolute inset-0 z-[100000] flex flex-col items-center justify-center bg-slate-50/80 backdrop-blur-sm">
            <div class="w-12 h-12 border-4 border-brand/20 border-t-brand rounded-full animate-spin mb-4"></div>
            <p class="text-slate-500 font-medium animate-pulse">Loading Workspace...</p>
        </div>
        
        <!-- Mobile Sidebar Overlay -->
        <div v-if="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-slate-900/50 z-40 md:hidden transition-opacity"></div>
        
        <!-- Sidebar Navigation -->
        <div class="fixed inset-y-0 left-0 w-64 bg-brand text-white flex flex-col z-50 transition-transform duration-300 md:relative md:translate-x-0" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            <div class="h-16 shrink-0 flex items-center px-6 border-b border-white/10 space-x-3">
                <?php $site_icon_url = get_site_icon_url(32); ?>
                <?php if ($site_icon_url): ?>
                    <img src="<?php echo esc_url($site_icon_url); ?>" alt="Icon" class="w-8 h-8 rounded-lg object-contain bg-white">
                <?php else: ?>
                    <div class="w-8 h-8 rounded-lg bg-amber-400 flex items-center justify-center text-brand font-bold">SM</div>
                <?php endif; ?>
                <div class="font-extrabold text-xl tracking-tight flex-1">Solusi <span class="text-amber-400">Marketing</span></div>
                <button @click="sidebarOpen = false" class="md:hidden p-2 -mr-2 text-white/70 hover:text-white rounded-lg">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <nav class="flex-1 overflow-y-auto py-4 space-y-1 px-3">
                <div class="px-4 py-2 mt-2 text-xs font-semibold text-white/50 uppercase tracking-wider">Advertiser Area</div>
                <a href="#" @click.prevent="currentView = 'dashboard'; sidebarOpen = false;" class="flex items-center px-4 py-3 rounded-lg text-base font-medium transition-colors" :class="currentView === 'dashboard' ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white'">
                    <i data-lucide="layout-dashboard" class="w-5 h-5 mr-3"></i> Dashboard
                </a>
                <a href="#" @click.prevent="currentView = 'pages'; sidebarOpen = false;" class="flex items-center px-4 py-3 rounded-lg text-base font-medium transition-colors" :class="currentView === 'pages' ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white'">
                    <i data-lucide="layout-template" class="w-5 h-5 mr-3"></i> Landing Pages
                </a>
                <a href="#" @click.prevent="currentView = 'leads'; sidebarOpen = false;" class="flex items-center px-4 py-3 rounded-lg text-base font-medium transition-colors" :class="currentView === 'leads' ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white'">
                    <i data-lucide="users" class="w-5 h-5 mr-3"></i> Leads
                </a>
                <a href="#" @click.prevent="currentView = 'products'; sidebarOpen = false;" class="flex items-center px-4 py-3 rounded-lg text-base font-medium transition-colors" :class="currentView === 'products' ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white'">
                    <i data-lucide="package" class="w-5 h-5 mr-3"></i> Produk
                </a>
                <a href="#" @click.prevent="currentView = 'bank_konten'; sidebarOpen = false;" class="flex items-center px-4 py-3 rounded-lg text-base font-medium transition-colors" :class="currentView === 'bank_konten' ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white'">
                    <i data-lucide="image" class="w-5 h-5 mr-3"></i> Marketing Kit
                </a>
                <a href="#" @click.prevent="currentView = 'test_pixel'; sidebarOpen = false;" class="flex items-center px-4 py-3 rounded-lg text-base font-medium transition-colors" :class="currentView === 'test_pixel' ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white'">
                    <i data-lucide="activity" class="w-5 h-5 mr-3"></i> Test Pixel
                </a>
                <a href="#" @click.prevent="currentView = 'withdraw'; sidebarOpen = false;" class="flex items-center px-4 py-3 rounded-lg text-base font-medium transition-colors" :class="currentView === 'withdraw' ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white'">
                    <i data-lucide="wallet" class="w-5 h-5 mr-3"></i> Withdraw
                </a>

                <template v-if="isAdmin">
                    <div class="px-4 py-2 mt-6 text-xs font-semibold text-white/50 uppercase tracking-wider border-t border-white/10 pt-6">Admin Area</div>
                    <a href="#" @click.prevent="currentView = 'admin_dashboard'; sidebarOpen = false;" class="flex items-center px-4 py-3 rounded-lg text-base font-medium transition-colors" :class="currentView === 'admin_dashboard' ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white'">
                        <i data-lucide="layout-dashboard" class="w-5 h-5 mr-3"></i> All Dashboard
                    </a>
                    <a href="#" @click.prevent="currentView = 'admin_pages'; sidebarOpen = false;" class="flex items-center px-4 py-3 rounded-lg text-base font-medium transition-colors" :class="currentView === 'admin_pages' ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white'">
                        <i data-lucide="layout-template" class="w-5 h-5 mr-3"></i> All Landing Pages
                    </a>
                    <a href="#" @click.prevent="currentView = 'admin_leads'; sidebarOpen = false;" class="flex items-center px-4 py-3 rounded-lg text-base font-medium transition-colors" :class="currentView === 'admin_leads' ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white'">
                        <i data-lucide="users" class="w-5 h-5 mr-3"></i> All Transaksi
                    </a>
                    <a href="#" @click.prevent="currentView = 'admin_advertisers'; sidebarOpen = false;" class="flex items-center px-4 py-3 rounded-lg text-base font-medium transition-colors" :class="currentView === 'admin_advertisers' ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white'">
                        <i data-lucide="user-check" class="w-5 h-5 mr-3"></i> All Advertiser
                    </a>
                    <a href="#" @click.prevent="currentView = 'admin_withdraw'; sidebarOpen = false;" class="flex items-center px-4 py-3 rounded-lg text-base font-medium transition-colors" :class="currentView === 'admin_withdraw' ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white'">
                        <i data-lucide="banknote" class="w-5 h-5 mr-3"></i> All Withdraw
                    </a>
                    <a href="#" @click.prevent="currentView = 'products'; sidebarOpen = false;" class="flex items-center px-4 py-3 rounded-lg text-base font-medium transition-colors" :class="currentView === 'products' ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white'">
                        <i data-lucide="package" class="w-5 h-5 mr-3"></i> Master Produk
                    </a>
                    <a href="#" @click.prevent="currentView = 'settings'; sidebarOpen = false;" class="flex items-center px-4 py-3 rounded-lg text-base font-medium transition-colors" :class="currentView === 'settings' ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white'">
                        <i data-lucide="settings" class="w-5 h-5 mr-3"></i> Integrasi
                    </a>
                </template>
            </nav>
            <div class="p-4 border-t border-white/10">
                <a v-if="isAdmin" href="<?php echo admin_url(); ?>" class="flex items-center px-4 py-3 rounded-lg text-base font-medium text-slate-300 hover:bg-white/5 hover:text-white transition-colors mb-2">
                    <i data-lucide="monitor" class="w-5 h-5 mr-3"></i> WP Dashboard
                </a>
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="flex items-center px-4 py-3 rounded-lg text-base font-medium text-slate-300 hover:bg-red-500/20 hover:text-red-400 transition-colors">
                    <i data-lucide="log-out" class="w-5 h-5 mr-3"></i> Keluar
                </a>
            </div>
        </div>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden bg-slate-50">
            <!-- Header -->
            <header class="h-16 shrink-0 flex items-center justify-between px-4 md:px-8 bg-white border-b border-slate-200">
                <button @click="sidebarOpen = !sidebarOpen" class="md:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <div class="hidden md:block font-semibold text-slate-800">
                    {{ viewTitle }}
                </div>
                <div class="flex items-center space-x-3">
                    <button @click="purgeCache" :disabled="isPurging" class="px-3 py-1.5 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 font-medium text-sm flex items-center transition-colors disabled:opacity-50">
                        <i v-if="!isPurging" data-lucide="refresh-cw" class="w-4 h-4 mr-1.5"></i>
                        <svg v-else class="animate-spin -ml-1 mr-2 h-4 w-4 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        {{ isPurging ? 'Purging...' : 'Purge All Cache' }}
                    </button>
                    <div class="text-sm text-right hidden sm:block">
                        <div class="font-medium text-slate-700"><?php echo esc_html($user->display_name); ?></div>
                        <div class="text-slate-500 text-xs">ID: <?php echo esc_html($user->ID); ?></div>
                    </div>
                    <div class="w-9 h-9 rounded-full bg-brand text-white flex items-center justify-center font-bold">
                        <?php echo strtoupper(substr($user->display_name, 0, 1)); ?>
                    </div>
                </div>
            </header>

            <!-- Main View Space -->
            <main class="flex-1 overflow-auto relative">
                <div class="w-full min-h-full">
                    <!-- Dashboard View -->
                    <div v-if="currentView === 'dashboard' || currentView === 'admin_dashboard'" class="p-4 md:p-8 max-w-7xl mx-auto space-y-6 animate-fade-in">
                        <?php require_once ADV_WP_PATH . 'views/v-dashboard.php'; ?>
                    </div>

                    <!-- Products View -->
                    <div v-if="currentView === 'products'" class="p-4 md:p-8 max-w-7xl mx-auto space-y-6 animate-fade-in">
                        <?php require_once ADV_WP_PATH . 'views/v-products.php'; ?>
                    </div>

                    <!-- Pages View -->
                    <div v-if="currentView === 'pages' || currentView === 'admin_pages'" class="p-4 md:p-8 max-w-7xl mx-auto space-y-6 animate-fade-in">
                        <?php require_once ADV_WP_PATH . 'views/v-pages.php'; ?>
                    </div>

                    <!-- Bank Konten View -->
                    <div v-if="currentView === 'bank_konten'" class="p-4 md:p-8 max-w-7xl mx-auto space-y-6 animate-fade-in">
                        <?php require_once ADV_WP_PATH . 'views/v-bank-konten.php'; ?>
                    </div>

                    <!-- Leads View -->
                    <div v-if="currentView === 'leads' || currentView === 'admin_leads'" class="p-4 md:p-8 max-w-7xl mx-auto space-y-6 animate-fade-in">
                        <?php require_once ADV_WP_PATH . 'views/v-leads.php'; ?>
                    </div>
                    
                    <!-- Test Pixel View -->
                    <div v-if="currentView === 'test_pixel'" class="p-4 md:p-8 max-w-7xl mx-auto space-y-6 animate-fade-in">
                        <?php require_once ADV_WP_PATH . 'views/v-test-pixel.php'; ?>
                    </div>
                    
                    <div v-if="currentView === 'withdraw'" class="p-4 md:p-8 max-w-7xl mx-auto space-y-6 animate-fade-in">
                        <?php require_once ADV_WP_PATH . 'views/v-withdraw.php'; ?>
                    </div>
                    <div v-if="currentView === 'admin_advertisers'" class="p-4 md:p-8 max-w-7xl mx-auto space-y-6 animate-fade-in">
                        <?php require_once ADV_WP_PATH . 'views/v-advertisers.php'; ?>
                    </div>
                    <div v-if="currentView === 'admin_withdraw'" class="p-4 md:p-8 max-w-7xl mx-auto space-y-6 animate-fade-in">
                        <?php require_once ADV_WP_PATH . 'views/v-admin-withdraw.php'; ?>
                    </div>
                    
                    <!-- Settings View -->
                    <div v-if="currentView === 'settings' && isAdmin" class="p-4 md:p-8 max-w-7xl mx-auto space-y-6 animate-fade-in">
                        <?php require_once ADV_WP_PATH . 'views/v-settings.php'; ?>
                    </div>
                    
                    <!-- Builder Full Screen View -->
                    <div v-if="currentView === 'builder'" class="absolute inset-0 z-50 bg-white flex flex-col">
                        <?php require_once ADV_WP_PATH . 'views/v-builder.php'; ?>
                    </div>
                </div>
            </main>
        </div>
        
        <!-- Media Picker Modal -->
        <div v-if="showMediaModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[99999] flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl w-full max-w-4xl shadow-xl overflow-hidden max-h-[90vh] flex flex-col">
                <div class="p-4 border-b border-slate-100 flex justify-between items-center shrink-0">
                    <h3 class="font-bold text-lg text-slate-800">Pilih Media ({{ (editingContent && editingContent.type) ? editingContent.type : 'image' }})</h3>
                    <div class="flex items-center space-x-3">
                        <div class="relative">
                            <button :disabled="isUploadingMediaModal" class="px-4 py-2 bg-brand text-white rounded-lg text-sm font-medium hover:bg-brand-dark disabled:bg-slate-300 disabled:text-slate-500 transition-colors inline-flex items-center">
                                <span v-if="isUploadingMediaModal" class="inline-flex items-center">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Mengupload...
                                </span>
                                <span v-else class="inline-flex items-center">
                                    <i data-lucide="upload" class="w-4 h-4 mr-2"></i> Upload Baru
                                </span>
                            </button>
                            <input v-if="!isUploadingMediaModal" type="file" accept="image/*,video/*" @change="uploadContentMedia" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
                        </div>
                        <button @click="showMediaModal = false" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-5 h-5"></i></button>
                    </div>
                </div>
                
                <div class="p-4 flex-1 overflow-y-auto bg-slate-50">
                    <div v-if="isLoadingMedia" class="flex justify-center py-12">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-brand"></div>
                    </div>
                    
                    <div v-else-if="mediaItems.length === 0" class="text-center py-12 text-slate-500">
                        Tidak ada media ditemukan. Silakan upload terlebih dahulu.
                    </div>
                    
                    <div v-else class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                        <div v-for="media in mediaItems" :key="media.id" @click="selectMedia(media.url)" class="bg-white rounded-xl overflow-hidden border border-slate-200 cursor-pointer hover:border-brand hover:ring-2 hover:ring-brand/30 transition-all group aspect-square relative flex items-center justify-center">
                            <img v-if="media.mime_type.startsWith('image')" :src="media.url" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                            <div v-else class="w-full h-full bg-slate-100 flex flex-col items-center justify-center text-slate-400 group-hover:text-brand transition-colors p-2 text-center">
                                <i data-lucide="video" class="w-8 h-8 mb-2"></i>
                                <span class="text-[10px] font-medium text-slate-500 leading-tight break-all">{{ media.title }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="p-4 border-t border-slate-100 bg-white flex justify-end">
                    <button @click="showMediaModal = false" class="px-5 py-2 rounded-lg font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">Tutup</button>
                </div>
            </div>
        </div>

    </div>

    <!-- Initialization -->
    <script>
        const { createApp, ref, computed, onMounted, watch } = Vue;

        createApp({
            setup() {
                const isAppLoading = ref(true);
                const isPurging = ref(false);
                const sidebarOpen = ref(false);
                const currentView = ref('dashboard');
                const isAdmin = ref(<?php echo current_user_can('manage_options') ? 'true' : 'false'; ?>);
                const currentUserId = ref(<?php echo $user->ID; ?>);
                
                const viewTitle = computed(() => {
                    const titles = {
                        'dashboard': 'Dashboard',
                        'admin_dashboard': 'All Dashboard',
                        'products': 'Master Produk',
                        'pages': 'Landing Pages',
                        'admin_pages': 'All Landing Pages',
                        'bank_konten': 'Marketing Kit',
                        'leads': 'Leads',
                        'admin_leads': 'All Transaksi & Leads',
                        'builder': 'Page Builder',
                        'settings': 'Integrasi',
                        'test_pixel': 'Test Pixel',
                        'withdraw': 'Withdraw',
                        'admin_advertisers': 'All Advertiser',
                        'admin_withdraw': 'All Withdraw'
                    };
                    return titles[currentView.value] || '';
                });

                // Shared State
                const products = ref([]);
                const pages = ref([]);
                const adminPages = ref([]);
                const leads = ref([]);
                const adminLeads = ref([]);
                const bankContents = ref([]);
                const editingPage = ref(null);
                
                const viewPages = computed(() => {
                    return currentView.value.startsWith('admin_') ? adminPages.value : pages.value;
                });
                
                const pageSearchQuery = ref("");
                const pageFilterProduct = ref("");
                const pageFilterAdvertiser = ref("");
                const pageSortBy = ref("newest");
                const uniqueAdvertisers = computed(() => {
                    const advs = [];
                    const map = new Map();
                    viewPages.value.forEach(p => {
                        if (!map.has(p.user_id) && p.advertiser_name) {
                            map.set(p.user_id, true);
                            advs.push({ id: p.user_id, name: p.advertiser_name });
                        }
                    });
                    return advs;
                });
                const filteredPages = computed(() => {
                    let result = viewPages.value;
                    if (pageFilterProduct.value) result = result.filter(p => p.product_id == pageFilterProduct.value);
                    if (pageFilterAdvertiser.value) result = result.filter(p => p.user_id == pageFilterAdvertiser.value);
                    if (pageSearchQuery.value) {
                        const q = pageSearchQuery.value.toLowerCase();
                        result = result.filter(p => (p.title && p.title.toLowerCase().includes(q)) || (p.slug && p.slug.toLowerCase().includes(q)));
                    }
                    result = [...result];
                    if (pageSortBy.value === "newest") result.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
                    else if (pageSortBy.value === "oldest") result.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                    else if (pageSortBy.value === "highest_cr") result.sort((a, b) => (b.cr || 0) - (a.cr || 0));
                    else if (pageSortBy.value === "highest_views") result.sort((a, b) => (b.views || 0) - (a.views || 0));
                    else if (pageSortBy.value === "highest_orders") result.sort((a, b) => (b.total_orders || 0) - (a.total_orders || 0));
                    return result;
                });

                const viewLeads = computed(() => {
                    return currentView.value.startsWith('admin_') ? adminLeads.value : leads.value;
                });
                
                // Bank Konten Logic
                const contentFilterProduct = ref(0);
                const contentSearchQuery = ref('');
                const showContentModal = ref(false);
                const editingContent = ref({ id: 0, title: '', type: 'image', embed_link: '', copy_text: '', product_id: 0 });
                
                const showMediaModal = ref(false);
                const mediaItems = ref([]);
                const isLoadingMedia = ref(false);

                const openMediaPicker = async () => {
                    showMediaModal.value = true;
                    isLoadingMedia.value = true;
                    try {
                        const type = (editingContent.value && editingContent.value.type) ? editingContent.value.type : 'image';
                        const res = await fetch('<?php echo rest_url("adv/v1/attachments?type="); ?>' + type, {
                            headers: { 'X-WP-Nonce': '<?php echo wp_create_nonce("wp_rest"); ?>' }
                        });
                        const data = await res.json();
                        if (data.success) {
                            mediaItems.value = data.data;
                        }
                    } catch (e) {
                        alert('Gagal memuat media');
                    }
                    isLoadingMedia.value = false;
                };

                let currentSelectMediaCallback = null;
                const selectMedia = (url) => {
                    if (currentSelectMediaCallback) {
                        currentSelectMediaCallback(url);
                    } else {
                        editingContent.value.embed_link = url;
                        showMediaModal.value = false;
                    }
                };

                const isUploadingMediaModal = ref(false);
                const uploadContentMedia = async (event) => {
                    const file = event.target.files[0];
                    if (!file) return;
                    
                    isUploadingMediaModal.value = true;
                    const fd = new FormData();
                    fd.append('file', file);
                    
                    try {
                        const res = await fetch('<?php echo rest_url("adv/v1/content-media"); ?>', {
                            method: 'POST',
                            headers: { 'X-WP-Nonce': '<?php echo wp_create_nonce("wp_rest"); ?>' },
                            body: fd
                        });
                        const data = await res.json();
                        if (data && data.success && data.url) {
                            if(typeof selectMedia === 'function') {
                                selectMedia(data.url);
                            } else {
                                editingContent.value.embed_link = data.url;
                                showMediaModal.value = false;
                            }
                        } else {
                            alert(data.message || 'Gagal upload media');
                        }
                    } catch (e) {
                        alert('Error koneksi saat mengupload media');
                    } finally {
                        isUploadingMediaModal.value = false;
                        if (event.target) event.target.value = '';
                    }
                };
                
                const filteredContents = computed(() => {
                    let contents = bankContents.value;
                    if (contentFilterProduct.value != 0) {
                        contents = contents.filter(c => c.product_id == contentFilterProduct.value);
                    }
                    if (contentSearchQuery.value) {
                        const sq = contentSearchQuery.value.toLowerCase();
                        contents = contents.filter(c => c.title.toLowerCase().includes(sq));
                    }
                    return contents;
                });
                
                const editContent = (content) => {
                    editingContent.value = { ...content };
                    showContentModal.value = true;
                };
                
                const saveContent = async () => {
                    const res = await fetch('<?php echo rest_url("adv/v1/contents"); ?>', {
                        method: 'POST',
                        headers: { 
                            'X-WP-Nonce': '<?php echo wp_create_nonce("wp_rest"); ?>',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(editingContent.value)
                    });
                    if (res.ok) {
                        showContentModal.value = false;
                        loadData();
                    }
                };
                
                const deleteContent = async (id) => {
                    if (confirm('Hapus konten ini?')) {
                        await fetch('<?php echo rest_url("adv/v1/contents/"); ?>' + id, {
                            method: 'DELETE',
                            headers: { 'X-WP-Nonce': '<?php echo wp_create_nonce("wp_rest"); ?>' }
                        });
                        loadData();
                    }
                };
                
                const copyText = (text) => {
                    navigator.clipboard.writeText(text).then(() => {
                        alert('Teks berhasil disalin!');
                    }).catch(err => {
                        alert('Gagal menyalin teks: ', err);
                    });
                };
                
                const forceDownload = async (url, title) => {
                    try {
                        const response = await fetch(url);
                        const blob = await response.blob();
                        const blobUrl = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.style.display = 'none';
                        a.href = blobUrl;
                        a.download = title || 'download';
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(blobUrl);
                        document.body.removeChild(a);
                    } catch (e) {
                        // Fallback if CORS prevents fetch
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = title || 'download';
                        a.target = '_blank';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                    }
                };

                const showProductModal = ref(false);
                const editingProduct = ref({ id: 0, name: '', price: 0 });

                const detailProduct = ref(null);
                const showProductDetail = (prod) => {
                    detailProduct.value = prod;
                };
                const openProductModal = (prod = null) => {
                    if (prod) {
                        editingProduct.value = { ...prod, price: Math.round(prod.price), price_coret: Math.round(prod.price_coret) };
                    } else {
                        editingProduct.value = { id: 0, name: "", price: 0, price_coret: 0, product_type: "canvas_app", description: "", access_flow: "", mockup_image: "", affiliate_commission: "" };
                    }
                    showProductModal.value = true;
                };
                const getProductTypeName = (type) => {
                    const types = {
                        "canvas_app": "Canvas App - ShareLink AI",
                        "produk_2": "Produk Digital (Ecourse)",
                        "produk_3": "Tools Web (GASEO/IlluSEO)",
                        "produk_4": "WA Gateway (WaBisnis)",
                        "produk_5": "Registrasi Member Affiliate",
                        "produk_6": "Job Post / Order CV"
                    };
                    return types[type] || type || "Produk";
                };
                const viewMasterLP = (product) => {
                    const slug = product.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
                    window.open('https://solusimarketing.xyz/lp/' + slug, '_blank');
                };
                const productSearchQuery = ref("");
                const productFilterType = ref("");
                const productSortBy = ref("newest");
                const filteredProducts = computed(() => {
                    let result = products.value;
                    if (productFilterType.value) result = result.filter(p => p.product_type == productFilterType.value);
                    if (productSearchQuery.value) {
                        const q = productSearchQuery.value.toLowerCase();
                        result = result.filter(p => p.name.toLowerCase().includes(q));
                    }
                    result = [...result];
                    if (productSortBy.value === "newest") result.sort((a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0));
                    else if (productSortBy.value === "oldest") result.sort((a, b) => new Date(a.created_at || 0) - new Date(b.created_at || 0));
                    else if (productSortBy.value === "price_desc") result.sort((a, b) => b.price - a.price);
                    else if (productSortBy.value === "price_asc") result.sort((a, b) => a.price - b.price);
                    return result;
                });
                const openMediaPickerForProduct = () => {
                    currentSelectMediaCallback = (url) => {
                        editingProduct.value.mockup_image = url;
                        showMediaModal.value = false;
                        currentSelectMediaCallback = null;
                    };
                    openMediaPicker();
                };

                const isUploadingProduct = ref(false);
                const uploadProductImage = async (event) => {
                    const file = event.target.files[0];
                    if (!file) return;
                    
                    isUploadingProduct.value = true;
                    const fd = new FormData();
                    fd.append('file', file);
                    
                    try {
                        const res = await fetch('<?php echo rest_url("adv/v1/content-media"); ?>', {
                            method: 'POST',
                            headers: { 'X-WP-Nonce': '<?php echo wp_create_nonce("wp_rest"); ?>' },
                            body: fd
                        });
                        const data = await res.json();
                        if (data && data.success && data.url) {
                            editingProduct.value.mockup_image = data.url;
                        } else {
                            alert(data.message || 'Gagal upload gambar produk');
                        }
                    } catch (e) {
                        alert('Error koneksi saat mengupload gambar produk');
                    } finally {
                        isUploadingProduct.value = false;
                        if (event.target) event.target.value = '';
                    }
                };

                const withdrawals = ref([]);
                const adminWithdrawals = ref([]);
                
                const wdDatePreset = ref('this_month');
                const wdDateFilterStart = ref('');
                const wdDateFilterEnd = ref('');
                const applyWdDatePreset = () => {
                    const today = new Date();
                    const y = today.getFullYear();
                    const m = today.getMonth();
                    const d = today.getDate();
                    
                    if (wdDatePreset.value === 'today') {
                        wdDateFilterStart.value = `${y}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                        wdDateFilterEnd.value = wdDateFilterStart.value;
                    } else if (wdDatePreset.value === 'yesterday') {
                        const yest = new Date(today);
                        yest.setDate(yest.getDate() - 1);
                        wdDateFilterStart.value = `${yest.getFullYear()}-${String(yest.getMonth() + 1).padStart(2, '0')}-${String(yest.getDate()).padStart(2, '0')}`;
                        wdDateFilterEnd.value = wdDateFilterStart.value;
                    } else if (wdDatePreset.value === 'this_week') {
                        const first = today.getDate() - today.getDay() + (today.getDay() === 0 ? -6 : 1);
                        const start = new Date(today.setDate(first));
                        wdDateFilterStart.value = `${start.getFullYear()}-${String(start.getMonth() + 1).padStart(2, '0')}-${String(start.getDate()).padStart(2, '0')}`;
                        wdDateFilterEnd.value = `${y}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                    } else if (wdDatePreset.value === 'last_week') {
                        const first = today.getDate() - today.getDay() + (today.getDay() === 0 ? -6 : 1) - 7;
                        const start = new Date(new Date().setDate(first));
                        const end = new Date(new Date().setDate(first + 6));
                        wdDateFilterStart.value = `${start.getFullYear()}-${String(start.getMonth() + 1).padStart(2, '0')}-${String(start.getDate()).padStart(2, '0')}`;
                        wdDateFilterEnd.value = `${end.getFullYear()}-${String(end.getMonth() + 1).padStart(2, '0')}-${String(end.getDate()).padStart(2, '0')}`;
                    } else if (wdDatePreset.value === 'this_month') {
                        wdDateFilterStart.value = `${y}-${String(m + 1).padStart(2, '0')}-01`;
                        const lastDay = new Date(y, m + 1, 0).getDate();
                        wdDateFilterEnd.value = `${y}-${String(m + 1).padStart(2, '0')}-${String(lastDay).padStart(2, '0')}`;
                    } else if (wdDatePreset.value === 'last_month') {
                        const prevM = m === 0 ? 11 : m - 1;
                        const prevY = m === 0 ? y - 1 : y;
                        wdDateFilterStart.value = `${prevY}-${String(prevM + 1).padStart(2, '0')}-01`;
                        const lastDay = new Date(prevY, prevM + 1, 0).getDate();
                        wdDateFilterEnd.value = `${prevY}-${String(prevM + 1).padStart(2, '0')}-${String(lastDay).padStart(2, '0')}`;
                    }
                };
                watch(wdDatePreset, applyWdDatePreset);
                applyWdDatePreset();

                const filteredAdminWithdrawals = computed(() => {
                    let result = adminWithdrawals.value;
                    if (wdDateFilterStart.value && wdDateFilterEnd.value) {
                        const [sy, sm, sd] = wdDateFilterStart.value.split('-');
                        const start = new Date(sy, sm - 1, sd);
                        start.setHours(0,0,0,0);
                        const [ey, em, ed] = wdDateFilterEnd.value.split('-');
                        const end = new Date(ey, em - 1, ed);
                        end.setHours(23,59,59,999);
                        result = result.filter(wd => {
                            const d = new Date(wd.created_at.replace(' ', 'T'));
                            return d >= start && d <= end;
                        });
                    }
                    return result;
                });

                const showWdModal = ref(false);
                const wdMinDate = ref("");
                const wdForm = ref({ date_start: "", date_end: "", products: [], report_image: "", ad_spent: 0, omset: 0 });
                const isUploadingWD = ref(false);
                const wdProfitShare = computed(() => {
                    const profit = wdForm.value.omset - (wdForm.value.ad_spent || 0);
                    return Math.round(profit > 0 ? profit / 2 : 0);
                });
                const wdNominal = computed(() => {
                    return Math.round((wdForm.value.ad_spent || 0) + wdProfitShare.value);
                });
                const isWdValid = computed(() => {
                    let dateValid = true;
                    if (wdMinDate.value && wdForm.value.date_start < wdMinDate.value) dateValid = false;
                    if (wdForm.value.date_start > wdForm.value.date_end) dateValid = false;
                    return !isUploadingWD.value && wdForm.value.date_start && wdForm.value.date_end && wdForm.value.products.length > 0 && wdForm.value.report_image && wdForm.value.ad_spent > 0 && dateValid;
                });
                const openWdModal = (wd = null) => {
                    if (wd) {
                        wdForm.value = { 
                            id: wd.id,
                            date_start: wd.date_start, 
                            date_end: wd.date_end, 
                            products: JSON.parse(wd.products || "[]").map(id => parseInt(id)), 
                            report_image: wd.report_image, 
                            ad_spent: Math.round(wd.ad_spent), 
                            omset: Math.round(wd.omset) 
                        };
                        wdMinDate.value = "";
                        showWdModal.value = true;
                        return;
                    }
                    
                    const today = new Date();
                    const yesterday = new Date(today);
                    yesterday.setDate(yesterday.getDate() - 1);
                    
                    let startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                    
                    const approvedWds = withdrawals.value.filter(w => w.status === 'Approved' || w.status === 'Transfered');
                    let hasApproved = false;
                    if (approvedWds.length > 0) {
                        const maxDateEndStr = approvedWds.reduce((maxStr, w) => {
                            return w.date_end > maxStr ? w.date_end : maxStr;
                        }, "");
                        
                        if (maxDateEndStr) {
                            const [y, m, d] = maxDateEndStr.split('-');
                            startDate = new Date(y, m - 1, d);
                            startDate.setDate(startDate.getDate() + 1);
                            hasApproved = true;
                        }
                    }

                    const formatInputDate = (d) => {
                        const year = d.getFullYear();
                        const month = String(d.getMonth() + 1).padStart(2, '0');
                        const day = String(d.getDate()).padStart(2, '0');
                        return `${year}-${month}-${day}`;
                    };
                    
                    if (hasApproved) {
                        wdMinDate.value = formatInputDate(startDate);
                    } else {
                        wdMinDate.value = "";
                    }
                    
                    const allProductIds = products.value.map(p => parseInt(p.id));

                    wdForm.value = { 
                        date_start: formatInputDate(startDate), 
                        date_end: formatInputDate(yesterday), 
                        products: allProductIds, 
                        report_image: "", 
                        ad_spent: 0, 
                        omset: 0 
                    };
                    
                    calculateOmset();
                    showWdModal.value = true;
                };
                const openMediaPickerForWD = () => {
                    currentSelectMediaCallback = (url) => {
                        wdForm.value.report_image = url;
                        showMediaModal.value = false;
                        currentSelectMediaCallback = null;
                    };
                    openMediaPicker();
                };
                const uploadWDImage = async (event) => {
                    const file = event.target.files[0];
                    if (!file) return;
                    
                    isUploadingWD.value = true;
                    const fd = new FormData();
                    fd.append('file', file);
                    
                    try {
                        const res = await fetch('<?php echo rest_url("adv/v1/content-media"); ?>', {
                            method: 'POST',
                            headers: { 'X-WP-Nonce': '<?php echo wp_create_nonce("wp_rest"); ?>' },
                            body: fd
                        });
                        const data = await res.json();
                        if (data && data.success && data.url) {
                            wdForm.value.report_image = data.url;
                        } else {
                            alert(data.message || 'Gagal upload gambar');
                        }
                    } catch (e) {
                        alert('Error koneksi saat mengupload gambar');
                    } finally {
                        isUploadingWD.value = false;
                        if (event.target) event.target.value = '';
                    }
                };

                const calculateOmset = () => {
                    if (!wdForm.value.date_start || !wdForm.value.date_end || wdForm.value.products.length === 0) {
                        wdForm.value.omset = 0;
                        return;
                    }
                    const [sy, sm, sd] = wdForm.value.date_start.split('-');
                    const start = new Date(sy, sm - 1, sd);
                    start.setHours(0,0,0,0);
                    const [ey, em, ed] = wdForm.value.date_end.split('-');
                    const end = new Date(ey, em - 1, ed);
                    end.setHours(23,59,59,999);
                    let total = 0;
                    viewLeads.value.forEach(lead => {
                        if (lead.status === "paid" && wdForm.value.products.includes(parseInt(lead.product_id))) {
                            const leadDate = new Date(lead.created_at.replace(' ', 'T'));
                            if (leadDate >= start && leadDate <= end) {
                                total += parseFloat(lead.price);
                            }
                        }
                    });
                    wdForm.value.omset = total;
                };
                const deleteWithdrawal = async (id) => {
                    if (!confirm("Yakin hapus request withdraw ini?")) return;
                    const res = await fetch("<?php echo rest_url("adv/v1/withdrawals/"); ?>" + id, {
                        method: "DELETE",
                        headers: { "X-WP-Nonce": "<?php echo wp_create_nonce("wp_rest"); ?>" }
                    });
                    if (res.ok) {
                        alert("Withdrawal berhasil dihapus");
                        loadWithdrawals();
                    } else {
                        alert("Gagal hapus withdrawal");
                    }
                };

                const updateWdStatus = async (wd) => {
                    if (wd.status === "Transfered" && !wd.transfer_receipt) {
                        alert("Upload bukti transfer (SSTF) terlebih dahulu sebelum mengubah status menjadi Transfered.");
                        wd.status = "Approved";
                        return;
                    }
                    const res = await fetch("<?php echo rest_url("adv/v1/withdrawals/"); ?>" + wd.id, {
                        method: "PUT",
                        headers: { "X-WP-Nonce": "<?php echo wp_create_nonce("wp_rest"); ?>", "Content-Type": "application/json" },
                        body: JSON.stringify({ status: wd.status, transfer_receipt: wd.transfer_receipt })
                    });
                    if (res.ok) {
                        alert("Status berhasil disimpan");
                    } else {
                        alert("Gagal update status WD");
                    }
                };
                const openMediaPickerForTf = (wd) => {
                    currentSelectMediaCallback = async (url) => {
                        wd.transfer_receipt = url;
                        showMediaModal.value = false;
                        currentSelectMediaCallback = null;
                        await fetch("<?php echo rest_url("adv/v1/withdrawals/"); ?>" + wd.id, {
                            method: "PUT",
                            headers: { "X-WP-Nonce": "<?php echo wp_create_nonce("wp_rest"); ?>", "Content-Type": "application/json" },
                            body: JSON.stringify({ status: wd.status, transfer_receipt: url })
                        });
                    };
                    openMediaPicker();
                };

                const adminAdvertisers = ref([]);
                const showAdvModal = ref(false);
                const editingAdv = ref({ id: 0, name: "", username: "", email: "", password: "" });
                const openAdvertiserModal = (adv = null) => {
                    if (adv) {
                        editingAdv.value = { ...adv, password: "" };
                    } else {
                        editingAdv.value = { id: 0, name: "", username: "", email: "", password: "" };
                    }
                    showAdvModal.value = true;
                };
                const saveAdvertiser = async () => {
                    if (!editingAdv.value.name || !editingAdv.value.username || !editingAdv.value.email) {
                        alert("Nama, Username, dan Email wajib diisi.");
                        return;
                    }
                    if (!editingAdv.value.id && !editingAdv.value.password) {
                        alert("Password wajib diisi untuk advertiser baru.");
                        return;
                    }
                    const res = await fetch("<?php echo rest_url("adv/v1/advertisers"); ?>", {
                        method: "POST",
                        headers: { "X-WP-Nonce": "<?php echo wp_create_nonce("wp_rest"); ?>", "Content-Type": "application/json" },
                        body: JSON.stringify(editingAdv.value)
                    });
                    if (res.ok) {
                        showAdvModal.value = false;
                        loadAdvertisers();
                    } else {
                        const data = await res.json();
                        alert("Error: " + data.message);
                    }
                };
                const deleteAdvertiser = async (id) => {
                    if (!confirm("Yakin hapus advertiser ini?")) return;
                    const res = await fetch("<?php echo rest_url("adv/v1/advertisers/"); ?>" + id, {
                        method: "DELETE",
                        headers: { "X-WP-Nonce": "<?php echo wp_create_nonce("wp_rest"); ?>" }
                    });
                    if (res.ok) loadAdvertisers();
                    else {
                        const data = await res.json();
                        alert("Error: " + data.message);
                    }
                };
                const loadAdvertisers = async () => {
                    if (!isAdmin.value) return;
                    const res = await fetch("<?php echo rest_url("adv/v1/advertisers"); ?>", {
                        headers: { "X-WP-Nonce": "<?php echo wp_create_nonce("wp_rest"); ?>" }
                    });
                    if (res.ok) adminAdvertisers.value = await res.json();
                };

                const submitWd = async () => {
                    if (!isWdValid.value) return;
                    const payload = { 
                        ...wdForm.value, 
                        ad_spent: Math.round(wdForm.value.ad_spent || 0),
                        omset: Math.round(wdForm.value.omset || 0),
                        profit_share: Math.round(wdProfitShare.value), 
                        nominal_wd: Math.round(wdNominal.value) 
                    };
                    const method = payload.id ? "PUT" : "POST";
                    const url = payload.id ? "<?php echo rest_url('adv/v1/withdrawals/'); ?>" + payload.id : "<?php echo rest_url('adv/v1/withdrawals'); ?>";
                    const res = await fetch(url, {
                        method: method,
                        headers: { "X-WP-Nonce": "<?php echo wp_create_nonce("wp_rest"); ?>", "Content-Type": "application/json" },
                        body: JSON.stringify(payload)
                    });
                    if (res.ok) {
                        window.location.hash = "withdraw";
                        window.location.reload();
                    }
                };
                const loadWithdrawals = async () => {
                    if (isAdmin.value) {
                        const resAll = await fetch("<?php echo rest_url('adv/v1/withdrawals?all=1'); ?>", { headers: { "X-WP-Nonce": "<?php echo wp_create_nonce('wp_rest'); ?>" } });
                        if (resAll.ok) adminWithdrawals.value = await resAll.json();
                        
                        const resMy = await fetch("<?php echo rest_url('adv/v1/withdrawals'); ?>", { headers: { "X-WP-Nonce": "<?php echo wp_create_nonce('wp_rest'); ?>" } });
                        if (resMy.ok) withdrawals.value = await resMy.json();
                    } else {
                        const res = await fetch("<?php echo rest_url('adv/v1/withdrawals'); ?>", { headers: { "X-WP-Nonce": "<?php echo wp_create_nonce('wp_rest'); ?>" } });
                        if (res.ok) withdrawals.value = await res.json();
                    }
                };

                const saveProduct = async () => {
                    const res = await fetch('<?php echo rest_url("adv/v1/products"); ?>', {
                        method: 'POST',
                        headers: { 
                            'X-WP-Nonce': '<?php echo wp_create_nonce("wp_rest"); ?>',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(editingProduct.value)
                    });
                    if (res.ok) {
                        showProductModal.value = false;
                        loadData();
                    }
                };

                const deleteProduct = async (id) => {
                    if (confirm('Hapus produk ini?')) {
                        await fetch('<?php echo rest_url("adv/v1/products/"); ?>' + id, {
                            method: 'DELETE',
                            headers: { 'X-WP-Nonce': '<?php echo wp_create_nonce("wp_rest"); ?>' }
                        });
                        loadData();
                    }
                };

                const dateFilterStart = ref('');
                const dateFilterEnd = ref('');
                const datePreset = ref('today');
                const searchQuery = ref('');
                const leadProductFilter = ref('');
                const leadAdvertiserFilter = ref('');

                const applyDatePreset = () => {
                    const today = new Date();
                    const y = today.getFullYear();
                    const m = today.getMonth();
                    const d = today.getDate();
                    
                    if (datePreset.value === 'today') {
                        dateFilterStart.value = `${y}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                        dateFilterEnd.value = dateFilterStart.value;
                    } else if (datePreset.value === 'yesterday') {
                        const yest = new Date(today);
                        yest.setDate(yest.getDate() - 1);
                        dateFilterStart.value = `${yest.getFullYear()}-${String(yest.getMonth() + 1).padStart(2, '0')}-${String(yest.getDate()).padStart(2, '0')}`;
                        dateFilterEnd.value = dateFilterStart.value;
                    } else if (datePreset.value === 'this_week') {
                        const first = today.getDate() - today.getDay() + (today.getDay() === 0 ? -6 : 1);
                        const start = new Date(today.setDate(first));
                        dateFilterStart.value = `${start.getFullYear()}-${String(start.getMonth() + 1).padStart(2, '0')}-${String(start.getDate()).padStart(2, '0')}`;
                        dateFilterEnd.value = `${y}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`; // end is today
                    } else if (datePreset.value === 'last_week') {
                        const first = today.getDate() - today.getDay() + (today.getDay() === 0 ? -6 : 1) - 7;
                        const start = new Date(new Date().setDate(first));
                        const end = new Date(new Date().setDate(first + 6));
                        dateFilterStart.value = `${start.getFullYear()}-${String(start.getMonth() + 1).padStart(2, '0')}-${String(start.getDate()).padStart(2, '0')}`;
                        dateFilterEnd.value = `${end.getFullYear()}-${String(end.getMonth() + 1).padStart(2, '0')}-${String(end.getDate()).padStart(2, '0')}`;
                    } else if (datePreset.value === 'this_month') {
                        dateFilterStart.value = `${y}-${String(m + 1).padStart(2, '0')}-01`;
                        const lastDay = new Date(y, m + 1, 0).getDate();
                        dateFilterEnd.value = `${y}-${String(m + 1).padStart(2, '0')}-${String(lastDay).padStart(2, '0')}`;
                    } else if (datePreset.value === 'last_month') {
                        const prevM = m === 0 ? 11 : m - 1;
                        const prevY = m === 0 ? y - 1 : y;
                        dateFilterStart.value = `${prevY}-${String(prevM + 1).padStart(2, '0')}-01`;
                        const lastDay = new Date(prevY, prevM + 1, 0).getDate();
                        dateFilterEnd.value = `${prevY}-${String(prevM + 1).padStart(2, '0')}-${String(lastDay).padStart(2, '0')}`;
                    }
                };

                watch(datePreset, () => {
                    if (datePreset.value !== 'custom') applyDatePreset();
                });
                watch([dateFilterStart, dateFilterEnd], () => {
                    // if user types date manually, maybe don't change preset unless it matches, but keeping it simple:
                    // let's just let custom be custom if they change the inputs.
                });

                applyDatePreset();

                const filteredLeads = computed(() => {
                    let filtered = viewLeads.value;
                    
                    if (leadProductFilter.value) {
                        filtered = filtered.filter(l => l.product_id == leadProductFilter.value);
                    }
                    
                    if (currentView.value.startsWith('admin_') && leadAdvertiserFilter.value) {
                        filtered = filtered.filter(l => l.advertiser_id == leadAdvertiserFilter.value);
                    }
                    
                    if (dateFilterStart.value && dateFilterEnd.value) {
                        const [sy, sm, sd] = dateFilterStart.value.split('-');
                        const start = new Date(sy, sm - 1, sd);
                        start.setHours(0,0,0,0);
                        const [ey, em, ed] = dateFilterEnd.value.split('-');
                        const end = new Date(ey, em - 1, ed);
                        end.setHours(23,59,59,999);
                        filtered = filtered.filter(l => {
                            const lDate = new Date(l.created_at.replace(' ', 'T'));
                            return lDate >= start && lDate <= end;
                        });
                    }
                    
                    if (searchQuery.value) {
                        const s = searchQuery.value.toLowerCase();
                        filtered = filtered.filter(l => 
                            (l.customer_name && l.customer_name.toLowerCase().includes(s)) ||
                            (l.customer_wa && l.customer_wa.toLowerCase().includes(s)) ||
                            (l.customer_email && l.customer_email.toLowerCase().includes(s)) ||
                            (l.product_name && l.product_name.toLowerCase().includes(s))
                        );
                    }
                    
                    return filtered;
                });

                const adminAdvertiserStats = computed(() => {
                    if (!isAdmin.value) return [];
                    const stats = {};
                    adminPages.value.forEach(page => {
                        if (leadProductFilter.value && page.product_id != leadProductFilter.value) return;
                        if (!stats[page.user_id]) {
                            stats[page.user_id] = { advertiser_id: page.user_id, advertiser_name: page.advertiser_name, views: 0, orders: 0, leads: 0, cr: 0 };
                        }
                        stats[page.user_id].views += parseInt(page.views || 0);
                    });
                    filteredLeads.value.forEach(lead => {
                        if (!stats[lead.advertiser_id]) {
                            stats[lead.advertiser_id] = { advertiser_id: lead.advertiser_id, advertiser_name: lead.advertiser_name, views: 0, orders: 0, leads: 0, cr: 0 };
                        }
                        stats[lead.advertiser_id].leads++;
                        if (lead.status === "paid") {
                            stats[lead.advertiser_id].orders++;
                        }
                    });
                    return Object.values(stats).map(s => {
                        s.cr = s.views > 0 ? ((s.orders / s.views) * 100).toFixed(2) : 0;
                        return s;
                    }).sort((a, b) => b.orders - a.orders);
                });

                const bestSellingProduct = computed(() => {
                    const paidLeads = filteredLeads.value.filter(l => l.status === 'paid');
                    if (paidLeads.length === 0) return 'Belum ada data';
                    
                    const counts = {};
                    let maxCount = 0;
                    let bestProduct = '';
                    
                    for (const l of paidLeads) {
                        const pName = l.product_name || 'Produk ID: ' + l.product_id;
                        counts[pName] = (counts[pName] || 0) + 1;
                        if (counts[pName] > maxCount) {
                            maxCount = counts[pName];
                            bestProduct = pName;
                        }
                    }
                    return `${bestProduct} (${maxCount} sales)`;
                });

                const exportLeadsCSV = () => {
                    if (filteredLeads.value.length === 0) return alert('Tidak ada data untuk diexport.');
                    
                    const headers = ['Tanggal', 'Customer', 'WhatsApp', 'Email', 'Produk', 'Nominal', 'Status'];
                    const rows = filteredLeads.value.map(l => [
                        l.created_at,
                        `"${l.customer_name}"`,
                        `"${l.customer_wa}"`,
                        `"${l.customer_email || ''}"`,
                        `"${l.product_name || 'Produk ID: ' + l.product_id}"`,
                        l.price,
                        l.status
                    ]);
                    
                    const csvContent = "data:text/csv;charset=utf-8," 
                        + headers.join(",") + "\n" 
                        + rows.map(e => e.join(",")).join("\n");
                        
                    const encodedUri = encodeURI(csvContent);
                    const link = document.createElement("a");
                    link.setAttribute("href", encodedUri);
                    link.setAttribute("download", `leads_export_${dateFilterStart.value}_to_${dateFilterEnd.value}.csv`);
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                };

                const formatDate = (dateStr) => {
                    if (!dateStr) return '';
                    const d = new Date(dateStr.replace(' ', 'T'));
                    const day = String(d.getDate()).padStart(2, '0');
                    const month = String(d.getMonth() + 1).padStart(2, '0');
                    const year = d.getFullYear();
                    const hours = String(d.getHours()).padStart(2, '0');
                    const minutes = String(d.getMinutes()).padStart(2, '0');
                    return `${day}-${month}-${year} ${hours}:${minutes}`;
                };

                const loadData = async () => {
                    const t = Date.now();
                    
                    const fetchPromises = [
                        fetch('<?php echo rest_url("adv/v1/products"); ?>?t=' + t, { headers: { 'X-WP-Nonce': '<?php echo wp_create_nonce("wp_rest"); ?>' } }),
                        fetch('<?php echo rest_url("adv/v1/pages"); ?>?t=' + t, { headers: { 'X-WP-Nonce': '<?php echo wp_create_nonce("wp_rest"); ?>' } }),
                        fetch('<?php echo rest_url("adv/v1/leads"); ?>?t=' + t, { headers: { 'X-WP-Nonce': '<?php echo wp_create_nonce("wp_rest"); ?>' } }),
                        fetch('<?php echo rest_url("adv/v1/contents"); ?>?t=' + t, { headers: { 'X-WP-Nonce': '<?php echo wp_create_nonce("wp_rest"); ?>' } })
                    ];
                    
                    if (isAdmin.value) {
                        fetchPromises.push(
                            fetch('<?php echo rest_url("adv/v1/pages"); ?>?all=1&t=' + t, { headers: { 'X-WP-Nonce': '<?php echo wp_create_nonce("wp_rest"); ?>' } })
                        );
                        fetchPromises.push(
                            fetch('<?php echo rest_url("adv/v1/leads"); ?>?all=1&t=' + t, { headers: { 'X-WP-Nonce': '<?php echo wp_create_nonce("wp_rest"); ?>' } })
                        );
                    }
                    
                    const responses = await Promise.all(fetchPromises);
                    
                    products.value = await responses[0].json();
                    pages.value = await responses[1].json();
                    leads.value = await responses[2].json();
                    bankContents.value = await responses[3].json();
                    
                    if (isAdmin.value) {
                        adminPages.value = await responses[4].json();
                        adminLeads.value = await responses[5].json();
                    }
                };

                const purgeCache = async () => {
                    isPurging.value = true;
                    try {
                        const res = await fetch('<?php echo rest_url("adv/v1/purge-cache"); ?>', {
                            method: 'POST',
                            headers: { 'X-WP-Nonce': '<?php echo wp_create_nonce("wp_rest"); ?>' }
                        });
                        const data = await res.json();
                        if (data.success) {
                            window.location.reload();
                        }
                    } catch (e) {
                        alert('Gagal membersihkan cache.');
                        isPurging.value = false;
                    }
                };

                onMounted(async () => {
                    const hash = window.location.hash.substring(1);
                    if (hash) {
                        currentView.value = hash;
                        window.history.replaceState(null, null, window.location.pathname);
                    }
                    await Promise.all([
                        loadData(),
                        loadSettings(),
                        loadAdvertisers(),
                        loadWithdrawals()
                    ]);
                    isAppLoading.value = false;
                    setTimeout(() => lucide.createIcons(), 100);
                });

                const advertisers = ref(<?php 
                    $advs = get_users(['role__in' => ['solusi_advertiser', 'administrator']]);
                    $adv_list = [];
                    foreach ($advs as $a) {
                        $adv_list[] = ['id' => $a->ID, 'name' => $a->display_name . ' (' . $a->user_email . ')'];
                    }
                    echo json_encode($adv_list);
                ?>);

                const settings = ref({
                    solusi_duitku_env: 'sandbox',
                    solusi_duitku_merchant_code: '',
                    solusi_duitku_api_key: '',
                    solusi_sharelink_webhook_url: '',
                    solusi_sharelink_secret: '',
                    adv_turnstile_sitekey: '',
                    adv_turnstile_secret: '',
                    adv_fonnte_token: '',
                    adv_reacher_api_key: ''
                });
                
                const callbackUrl = ref('<?php echo rest_url("adv/v1/duitku/callback"); ?>');

                const loadSettings = async () => {
                    if (isAdmin.value) {
                        const res = await fetch('<?php echo rest_url("adv/v1/settings"); ?>', { headers: { 'X-WP-Nonce': '<?php echo wp_create_nonce("wp_rest"); ?>' } });
                        settings.value = await res.json();
                    }
                };

                const saveSettings = async () => {
                    const res = await fetch('<?php echo rest_url("adv/v1/settings"); ?>', {
                        method: 'POST',
                        headers: { 
                            'X-WP-Nonce': '<?php echo wp_create_nonce("wp_rest"); ?>',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(settings.value)
                    });
                    if (res.ok) {
                        alert('Pengaturan berhasil disimpan');
                    } else {
                        alert('Gagal menyimpan pengaturan');
                    }
                };

                const deletePage = async (id) => {
                    if (confirm('Yakin ingin menghapus halaman ini?')) {
                        await fetch('<?php echo rest_url("adv/v1/pages/"); ?>' + id, {
                            method: 'DELETE',
                            headers: { 'X-WP-Nonce': '<?php echo wp_create_nonce("wp_rest"); ?>' }
                        });
                        loadData();
                    }
                };

                const duplicatePage = async (id) => {
                    await fetch('<?php echo rest_url("adv/v1/pages/duplicate/"); ?>' + id, {
                        method: 'POST',
                        headers: { 'X-WP-Nonce': '<?php echo wp_create_nonce("wp_rest"); ?>' }
                    });
                    loadData();
                };

                const builderTab = ref('design');
                const blocks = ref([]);
                const activeBlock = ref(null);
                const pixelConfig = ref({
                    meta_id: '', tiktok_id: '', event_checkout: 'InitiateCheckout', event_purchase: 'Purchase', tiktok_event_checkout: 'InitiateCheckout', tiktok_event_purchase: 'CompletePayment'
                });

                // Watchers for editingPage
                Vue.watch(editingPage, (newVal) => {
                    if (newVal) {
                        try {
                            blocks.value = typeof newVal.builder_data === 'string' ? JSON.parse(newVal.builder_data || '[]') : newVal.builder_data;
                        } catch (e) { blocks.value = []; }
                        
                        // Enforce checkout form block
                        const hasForm = blocks.value.some(b => b.type === 'checkout_form');
                        if (!hasForm) {
                            blocks.value.push({ type: 'checkout_form', button_text: 'Beli Sekarang', color: '#10b981' });
                        }

                        try {
                            const p = typeof newVal.pixel_data === 'string' ? JSON.parse(newVal.pixel_data || '{}') : newVal.pixel_data;
                            pixelConfig.value = { 
                                meta_id: '', tiktok_id: '', event_checkout: 'InitiateCheckout', event_purchase: 'Purchase', 
                                tiktok_event_checkout: 'InitiateCheckout', tiktok_event_purchase: 'CompletePayment',
                                ...p 
                            };
                        } catch (e) {}
                        activeBlock.value = null;
                        builderTab.value = 'settings';
                    }
                });

                const addBlock = (block) => {
                    if (block.type === 'sticky_button') {
                        const existingIndex = blocks.value.findIndex(b => b.type === 'sticky_button');
                        if (existingIndex !== -1) {
                            activeBlock.value = existingIndex;
                            return;
                        }
                    }
                    blocks.value.push(block);
                    activeBlock.value = blocks.value.length - 1;
                };

                const deleteBlock = (index) => {
                    if (blocks.value[index].type === 'checkout_form') {
                        alert('Form checkout wajib ada dan tidak dapat dihapus.');
                        return;
                    }
                    blocks.value.splice(index, 1);
                    if (activeBlock.value === index) activeBlock.value = null;
                    lucide.createIcons();
                };

                const duplicateBlock = (index) => {
                    if (blocks.value[index].type === 'checkout_form') {
                        alert('Hanya boleh ada 1 Form Checkout.');
                        return;
                    }
                    if (blocks.value[index].type === 'sticky_button') {
                        alert('Hanya boleh ada 1 Sticky Button.');
                        return;
                    }
                    const cloned = JSON.parse(JSON.stringify(blocks.value[index]));
                    blocks.value.splice(index + 1, 0, cloned);
                    activeBlock.value = index + 1;
                    lucide.createIcons();
                };

                const moveBlock = (index, dir) => {
                    const newIndex = index + dir;
                    if (newIndex >= 0 && newIndex < blocks.value.length) {
                        const temp = blocks.value[index];
                        blocks.value[index] = blocks.value[newIndex];
                        blocks.value[newIndex] = temp;
                        activeBlock.value = newIndex;
                    }
                };

                const uploadImage = async (event, blockIndex) => {
                    const file = event.target.files[0];
                    if (!file) return;
                    
                    if (file.size > 204800) {
                        alert('Ukuran gambar maksimal 200kb');
                        return;
                    }

                    const fd = new FormData();
                    fd.append('file', file);
                    
                    try {
                        const res = await fetch('<?php echo rest_url("adv/v1/media"); ?>', {
                            method: 'POST',
                            headers: { 'X-WP-Nonce': '<?php echo wp_create_nonce("wp_rest"); ?>' },
                            body: fd
                        });
                        const data = await res.json();
                        if (data.success) {
                            blocks.value[blockIndex].url = data.url;
                        } else {
                            alert(data.message || 'Gagal upload');
                        }
                    } catch (e) {
                        alert('Error koneksi');
                    }
                };

                const savePage = async () => {
                    if (editingPage.value.product_id == 0) {
                        alert('Mohon pilih produk terlebih dahulu di tab Settings.');
                        builderTab.value = 'settings';
                        return;
                    }

                    const payload = {
                        ...editingPage.value,
                        builder_data: blocks.value,
                        pixel_data: pixelConfig.value
                    };
                    
                    try {
                        const res = await fetch('<?php echo rest_url("adv/v1/pages"); ?>', {
                            method: 'POST',
                            headers: { 
                                'X-WP-Nonce': '<?php echo wp_create_nonce("wp_rest"); ?>',
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        });
                        const data = await res.json();
                        if (data.success) {
                            alert('Halaman berhasil disimpan!');
                            loadData();
                            currentView.value = 'pages';
                        }
                    } catch (e) {
                        alert('Gagal menyimpan.');
                    }
                };

                const testPixelPage = ref('');
                const runPixelTest = () => {
                    if (!testPixelPage.value) return;
                    window.open('<?php echo home_url('/lp/'); ?>' + testPixelPage.value + '?test_pixel=1', '_blank');
                };

                return {
                    isAppLoading,
                    isPurging,
                    sidebarOpen,
                    currentView,
                    viewTitle,
                    isAdmin,
                    currentUserId,
                    products,
                    pages,
                    adminPages,
                    leads,
                    adminLeads,
                    viewPages,
                    pageSearchQuery,
                    pageFilterProduct,
                    pageFilterAdvertiser,
                    pageSortBy,
                    uniqueAdvertisers,
                    filteredPages,
                    viewLeads,
                    filteredLeads,
                    bestSellingProduct,
                    adminAdvertiserStats,
                    searchQuery,
                    exportLeadsCSV,
                    dateFilterStart,
                    dateFilterEnd,
                    datePreset,
                    leadProductFilter,
                    leadAdvertiserFilter,
                    testPixelPage,
                    runPixelTest,
                    settings,
                    callbackUrl,
                    saveSettings,
                    advertisers,
                    editingPage,
                    loadData,
                    deletePage,
                    duplicatePage,
                    showProductModal,
                    editingProduct,
                    productSearchQuery,
                    productFilterType,
                    productSortBy,
                    filteredProducts,
                    openMediaPickerForProduct,
                    wdDatePreset,
                    wdDateFilterStart,
                    wdDateFilterEnd,
                    filteredAdminWithdrawals,
                    withdrawals,
                    adminWithdrawals,
                    showWdModal,
                    wdMinDate,
                    wdForm,
                    wdProfitShare,
                    wdNominal,
                    isWdValid,
                    openWdModal,
                    openMediaPickerForWD,
                    uploadWDImage,
                    isUploadingWD,
                    isUploadingProduct,
                    uploadProductImage,
                    isUploadingMediaModal,
                    calculateOmset,
                    deleteWithdrawal,
                    submitWd,
                    updateWdStatus,
                    openMediaPickerForTf,
                    adminAdvertisers,
                    showAdvModal,
                    editingAdv,
                    openAdvertiserModal,
                    saveAdvertiser,
                    deleteAdvertiser,
                    detailProduct,
                    showProductDetail,
                    openProductModal,
                    getProductTypeName,
                    viewMasterLP,
                    saveProduct,
                    deleteProduct,
                    bankContents,
                    contentFilterProduct,
                    contentSearchQuery,
                    showContentModal,
                    editingContent,
                    filteredContents,
                    showMediaModal,
                    mediaItems,
                    isLoadingMedia,
                    openMediaPicker,
                    selectMedia,
                    uploadContentMedia,
                    editContent,
                    saveContent,
                    deleteContent,
                    copyText,
                    formatDate,
                    forceDownload,
                    builderTab,
                    blocks,
                    activeBlock,
                    pixelConfig,
                    addBlock,
                    deleteBlock,
                    duplicateBlock,
                    moveBlock,
                    uploadImage,
                    savePage,
                    purgeCache
                }
            },
            updated() {
                lucide.createIcons();
            }
        }).mount('#adv-app');
    </script>
</body>
</html>
