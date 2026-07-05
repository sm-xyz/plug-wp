<div>
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <h2 class="text-xl font-bold text-slate-800">{{ viewTitle }}</h2>
        <div class="flex flex-col md:flex-row items-center gap-3 w-full md:w-auto">
            <select v-if="currentView === 'admin_leads'" v-model="leadAdvertiserFilter" class="px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:border-brand focus:ring-1 focus:ring-brand outline-none w-full md:w-40">
                <option value="">Semua Advertiser</option>
                <option v-for="a in advertisers" :key="a.id" :value="a.id">{{ a.name }}</option>
            </select>
            <select v-model="leadProductFilter" class="px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:border-brand focus:ring-1 focus:ring-brand outline-none w-full md:w-40">
                <option value="">Semua Produk</option>
                <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
            <select v-model="datePreset" class="px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:border-brand focus:ring-1 focus:ring-brand outline-none w-full md:w-32">
                <option value="today">Hari Ini</option>
                <option value="yesterday">Kemarin</option>
                <option value="this_week">Minggu Ini</option>
                <option value="last_week">Minggu Lalu</option>
                <option value="this_month">Bulan Ini</option>
                <option value="last_month">Bulan Lalu</option>
                <option value="custom">Custom</option>
            </select>
            <div v-if="datePreset === 'custom'" class="flex items-center space-x-2 bg-white p-1 rounded-lg border border-slate-200 shadow-sm w-full md:w-auto justify-center">
                <div class="flex flex-col text-center">
                    <span v-if="dateFilterStart" class="text-[10px] text-brand -mb-1">{{ dateFilterStart.split('-').reverse().join('/') }}</span>
                    <input type="date" v-model="dateFilterStart" class="px-2 py-1 bg-transparent text-sm font-medium text-slate-600 outline-none">
                </div>
                <span class="text-slate-400">-</span>
                <div class="flex flex-col text-center">
                    <span v-if="dateFilterEnd" class="text-[10px] text-brand -mb-1">{{ dateFilterEnd.split('-').reverse().join('/') }}</span>
                    <input type="date" v-model="dateFilterEnd" class="px-2 py-1 bg-transparent text-sm font-medium text-slate-600 outline-none">
                </div>
            </div>
            <div class="relative w-full md:w-48">
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                <input type="text" v-model="searchQuery" placeholder="Cari..." class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:border-brand focus:ring-1 focus:ring-brand outline-none bg-white">
            </div>
            <button @click="exportLeadsCSV" class="w-full md:w-auto bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-700 transition-colors inline-flex justify-center items-center">
                <i data-lucide="download" class="w-4 h-4 mr-2"></i> Export CSV
            </button>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[700px]">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="p-4 pl-6 text-xs font-semibold text-slate-500 uppercase tracking-wider">Tanggal</th>
                        <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Customer</th>
                        <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Kontak (WA/Email)</th>
                        <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Produk</th>
                        <th v-if="currentView === 'admin_leads'" class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Source</th>
                        <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Nominal</th>
                        <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="p-4 pr-6 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="filteredLeads.length === 0">
                        <td :colspan="currentView === 'admin_leads' ? 8 : 7" class="p-8 text-center text-slate-500">Belum ada data.</td>
                    </tr>
                    <tr v-for="lead in filteredLeads" :key="lead.id" class="hover:bg-slate-50 transition-colors">
                        <td class="p-4 pl-6 text-sm text-slate-600">{{ formatDate(lead.created_at) }}</td>
                        <td class="p-4 font-medium text-slate-800">{{ lead.customer_name }}</td>
                        <td class="p-4 text-sm text-slate-600">
                            <div>{{ lead.customer_wa }}</div>
                            <div class="text-xs text-slate-400">{{ lead.customer_email }}</div>
                        </td>
                        <td class="p-4 text-sm text-slate-600">{{ lead.product_name || 'Produk' }}</td>
                        <td v-if="currentView === 'admin_leads'" class="p-4 text-sm text-slate-600">
                            <div class="font-medium text-slate-800">{{ lead.advertiser_name || 'Unknown' }}</div>
                            <div class="text-xs text-slate-400">ID: {{ lead.advertiser_id }}</div>
                        </td>
                        <td class="p-4 text-sm font-medium text-slate-800">Rp {{ new Intl.NumberFormat('id-ID').format(lead.price) }}</td>
                        <td class="p-4">
                            <span v-if="lead.status === 'paid'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                Paid
                            </span>
                            <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                Pending
                            </span>
                        </td>
                        <td class="p-4 pr-6 text-right">
                            <a :href="'https://wa.me/' + lead.customer_wa + '?text=Halo%20' + encodeURIComponent(lead.customer_name) + ',%20terima%20kasih%20sudah%20memesan%20' + encodeURIComponent(lead.product_name || 'produk') + '.'" target="_blank" class="inline-flex items-center px-3 py-1.5 bg-green-500 text-white rounded-lg text-xs font-medium hover:bg-green-600 transition-colors shadow-sm">
                                <i data-lucide="message-circle" class="w-3.5 h-3.5 mr-1.5"></i> Chat WA
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
