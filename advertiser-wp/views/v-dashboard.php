<div>
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 space-y-4 md:space-y-0">
        <h2 class="text-2xl font-bold text-slate-800">{{ viewTitle }}</h2>
        <div class="flex flex-col md:flex-row items-center space-y-2 md:space-y-0 md:space-x-3 w-full md:w-auto">
            <select v-model="leadProductFilter" class="px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:border-brand focus:ring-1 focus:ring-brand outline-none w-full md:w-48">
                <option value="">Semua Produk</option>
                <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
            <select v-model="datePreset" class="px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:border-brand focus:ring-1 focus:ring-brand outline-none w-full md:w-40">
                <option value="today">Hari Ini (Today)</option>
                <option value="yesterday">Kemarin (Yesterday)</option>
                <option value="this_week">Minggu Ini</option>
                <option value="last_week">Minggu Lalu</option>
                <option value="this_month">Bulan Ini</option>
                <option value="last_month">Bulan Lalu</option>
                <option value="custom">Custom Tanggal</option>
            </select>
            <div v-if="datePreset === 'custom'" class="flex items-center space-x-2 bg-white p-1 rounded-xl border border-slate-200 shadow-sm w-full md:w-auto justify-center">
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
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex items-center">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center mr-4 shrink-0">
                <i data-lucide="users"></i>
            </div>
            <div class="min-w-0">
                <div class="text-sm font-medium text-slate-500 mb-1 truncate">Total Leads Masuk</div>
                <div class="text-2xl lg:text-3xl font-bold text-slate-800">{{ filteredLeads.length }}</div>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex items-center">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center mr-4 shrink-0">
                <i data-lucide="check-circle"></i>
            </div>
            <div class="min-w-0">
                <div class="text-sm font-medium text-slate-500 mb-1 truncate">Total Sales (Paid)</div>
                <div class="text-2xl lg:text-3xl font-bold text-slate-800">{{ filteredLeads.filter(l => l.status === 'paid').length }}</div>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex items-center">
            <div class="w-12 h-12 rounded-xl bg-brand/5 text-brand flex items-center justify-center mr-4 shrink-0">
                <i data-lucide="wallet"></i>
            </div>
            <div class="min-w-0">
                <div class="text-sm font-medium text-slate-500 mb-1 truncate">Total Revenue</div>
                <div class="text-2xl lg:text-3xl font-bold text-slate-800">
                    Rp {{ new Intl.NumberFormat('id-ID').format(filteredLeads.filter(l => l.status === 'paid').reduce((sum, l) => sum + parseFloat(l.price), 0)) }}
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex items-center">
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center mr-4 shrink-0">
                <i data-lucide="trending-up"></i>
            </div>
            <div class="min-w-0 overflow-hidden">
                <div class="text-sm font-medium text-slate-500 mb-1 truncate">Produk Terlaris</div>
                <div class="text-base font-bold text-slate-800 truncate" :title="bestSellingProduct">{{ bestSellingProduct }}</div>
            </div>
        </div>
    </div>

    <!-- For Admin, show Advertiser Performance Instead of Recent Transactions -->
    <div class="mt-8" v-if="isAdmin">
        <h2 class="text-lg font-bold text-slate-800 mb-4">Performa Advertiser (Berdasarkan Filter)</h2>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[700px]">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="p-4 pl-6 text-xs font-semibold text-slate-500 uppercase tracking-wider">Advertiser</th>
                            <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">LP Views Total</th>
                            <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Orders (Paid)</th>
                            <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Leads</th>
                            <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Average LP-CR</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="adminAdvertiserStats.length === 0">
                            <td colspan="5" class="p-8 text-center text-slate-500">Belum ada data.</td>
                        </tr>
                        <tr v-for="stat in adminAdvertiserStats" :key="stat.advertiser_id" class="hover:bg-slate-50 transition-colors">
                            <td class="p-4 pl-6">
                                <div class="font-medium text-slate-800">{{ stat.advertiser_name }}</div>
                            </td>
                            <td class="p-4 text-sm text-slate-600 font-medium">{{ stat.views }}</td>
                            <td class="p-4 text-sm text-brand font-bold">{{ stat.orders }}</td>
                            <td class="p-4 text-sm text-slate-600">{{ stat.leads }}</td>
                            <td class="p-4 text-sm font-semibold" :class="stat.cr > 3 ? 'text-green-600' : 'text-slate-600'">{{ stat.cr }}%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- For Advertiser, show their own recent transactions -->
    <div class="mt-8" v-else>
        <h2 class="text-lg font-bold text-slate-800 mb-4">Transaksi Terbaru (Berdasarkan Filter)</h2>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[700px]">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="p-4 pl-6 text-xs font-semibold text-slate-500 uppercase tracking-wider">Tanggal & Waktu</th>
                            <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Customer</th>
                            <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Kontak (WA/Email)</th>
                            <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Produk</th>
                            <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Nominal</th>
                            <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="filteredLeads.length === 0">
                            <td colspan="6" class="p-8 text-center text-slate-500">Belum ada transaksi.</td>
                        </tr>
                        <tr v-for="lead in filteredLeads.slice(0, 20)" :key="lead.id" class="hover:bg-slate-50 transition-colors">
                            <td class="p-4 pl-6 text-sm text-slate-600">{{ formatDate(lead.created_at) }}</td>
                            <td class="p-4">
                                <div class="font-medium text-slate-800">{{ lead.customer_name }}</div>
                            </td>
                            <td class="p-4 text-sm text-slate-600">
                                <div>{{ lead.customer_wa }}</div>
                                <div class="text-xs text-slate-400">{{ lead.customer_email }}</div>
                            </td>
                            <td class="p-4 text-sm text-slate-600">{{ lead.product_name || 'Produk' }}</td>
                            <td class="p-4 text-sm font-medium text-slate-800">Rp {{ new Intl.NumberFormat('id-ID').format(lead.price) }}</td>
                            <td class="p-4">
                                <span v-if="lead.status === 'paid'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                    Paid
                                </span>
                                <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                    Pending
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
