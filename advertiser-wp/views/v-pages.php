<div>
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-slate-800">{{ viewTitle }}</h2>
        <button v-if="isAdmin" @click="currentView = 'builder'; editingPage = { id: 0, title: 'New Landing Page', slug: '', product_id: 0, user_id: 0, builder_data: '[]', pixel_data: '{}' }" class="bg-brand text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-brand-dark transition-colors inline-flex items-center">
            <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Buat Page Baru
        </button>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
        <div class="p-4 border-b border-slate-100 bg-slate-50 flex flex-wrap gap-4 items-center">
            <div class="flex-1 min-w-[200px]">
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400"></i>
                    <input type="text" v-model="pageSearchQuery" placeholder="Cari halaman..." class="w-full pl-9 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none transition-all">
                </div>
            </div>
            <div class="w-full sm:w-auto">
                <select v-model="pageFilterProduct" class="w-full sm:w-auto border border-slate-300 rounded-lg px-4 py-2 text-sm bg-white focus:ring-2 focus:ring-brand focus:border-brand outline-none transition-all">
                    <option value="">Semua Produk</option>
                    <option v-for="prod in products" :key="prod.id" :value="prod.id">{{ prod.name }}</option>
                </select>
            </div>
            <div class="w-full sm:w-auto">
                <select v-model="pageFilterAdvertiser" class="w-full sm:w-auto border border-slate-300 rounded-lg px-4 py-2 text-sm bg-white focus:ring-2 focus:ring-brand focus:border-brand outline-none transition-all">
                    <option value="">Semua Advertiser</option>
                    <option v-for="adv in uniqueAdvertisers" :key="adv.id" :value="adv.id">{{ adv.name }}</option>
                </select>
            </div>
            <div class="w-full sm:w-auto">
                <select v-model="pageSortBy" class="w-full sm:w-auto border border-slate-300 rounded-lg px-4 py-2 text-sm bg-white focus:ring-2 focus:ring-brand focus:border-brand outline-none transition-all">
                    <option value="newest">Terbaru</option>
                    <option value="oldest">Terlama</option>
                    <option value="highest_cr">CR Tertinggi</option>
                    <option value="highest_views">Views Terbanyak</option>
                    <option value="highest_orders">Order Terbanyak</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[900px]">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="p-4 pl-6 text-xs font-semibold text-slate-500 uppercase tracking-wider">Judul Halaman</th>
                        <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">URL / Link</th>
                        <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Advertiser</th>
                        <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Produk</th>
                        <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">LP View</th>
                        <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Order</th>
                        <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">LP-CR</th>
                        <th class="p-4 pr-6 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="filteredPages.length === 0">
                        <td colspan="8" class="p-8 text-center text-slate-500">Belum ada halaman.</td>
                    </tr>
                    <tr v-for="page in filteredPages" :key="page.id" class="hover:bg-slate-50 transition-colors">
                        <td class="p-4 pl-6 font-medium text-slate-800">{{ page.title }}</td>
                        <td class="p-4 text-sm text-brand">
                            <a :href="'<?php echo home_url('/lp/'); ?>' + page.slug" target="_blank" class="hover:underline flex items-center">
                                /lp/{{ page.slug }} <i data-lucide="external-link" class="w-3 h-3 ml-1"></i>
                            </a>
                        </td>
                        <td class="p-4 text-sm text-slate-600">{{ page.advertiser_name }}</td>
                        <td class="p-4 text-sm text-slate-600">{{ page.product_name || 'Produk ID: ' + page.product_id }}</td>
                        <td class="p-4 text-sm font-semibold text-slate-700">{{ page.views || 0 }}</td>
                        <td class="p-4 text-sm font-semibold text-brand">{{ page.total_orders || 0 }}</td>
                        <td class="p-4 text-sm font-semibold" :class="page.cr > 3 ? 'text-green-600' : 'text-slate-600'">{{ page.cr || 0 }}%</td>
                        <td class="p-4 pr-6 text-right whitespace-nowrap">
                            <button @click="duplicatePage(page.id)" class="text-amber-600 hover:text-amber-800 px-2 py-1 bg-amber-50 rounded text-xs font-medium mr-2">
                                Duplicate
                            </button>
                            <button v-if="isAdmin || page.user_id == currentUserId" @click="editingPage = { ...page }; currentView = 'builder'" class="text-blue-600 hover:text-blue-800 px-2 py-1 bg-blue-50 rounded text-xs font-medium mr-2">
                                Edit Builder
                            </button>
                            <button v-if="isAdmin || page.user_id == currentUserId" @click="deletePage(page.id)" class="text-red-600 hover:text-red-800 px-2 py-1 bg-red-50 rounded text-xs font-medium">
                                Hapus
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
