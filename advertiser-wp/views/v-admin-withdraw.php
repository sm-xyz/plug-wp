<div>
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <h2 class="text-xl font-bold text-slate-800">All Withdraw</h2>
        <div class="flex flex-col md:flex-row items-center gap-3 w-full md:w-auto">
            <select v-model="wdDatePreset" class="px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:border-brand focus:ring-1 focus:ring-brand outline-none w-full md:w-32">
                <option value="today">Hari Ini</option>
                <option value="yesterday">Kemarin</option>
                <option value="this_week">Minggu Ini</option>
                <option value="last_week">Minggu Lalu</option>
                <option value="this_month">Bulan Ini</option>
                <option value="last_month">Bulan Lalu</option>
                <option value="custom">Custom</option>
            </select>
            <div v-if="wdDatePreset === 'custom'" class="flex items-center space-x-2 bg-white p-1 rounded-lg border border-slate-200 shadow-sm w-full md:w-auto justify-center">
                <div class="flex flex-col text-center">
                    <span v-if="wdDateFilterStart" class="text-[10px] text-brand -mb-1">{{ wdDateFilterStart.split('-').reverse().join('/') }}</span>
                    <input type="date" v-model="wdDateFilterStart" class="px-2 py-1 bg-transparent text-sm font-medium text-slate-600 outline-none">
                </div>
                <span class="text-slate-400">-</span>
                <div class="flex flex-col text-center">
                    <span v-if="wdDateFilterEnd" class="text-[10px] text-brand -mb-1">{{ wdDateFilterEnd.split('-').reverse().join('/') }}</span>
                    <input type="date" v-model="wdDateFilterEnd" class="px-2 py-1 bg-transparent text-sm font-medium text-slate-600 outline-none">
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <!-- Mobile View (Cards) -->
        <div class="block md:hidden divide-y divide-slate-100">
            <div v-if="filteredAdminWithdrawals.length === 0" class="p-8 text-center text-slate-500 text-sm">
                Belum ada request withdrawal.
            </div>
            <div v-for="wd in filteredAdminWithdrawals" :key="wd.id" class="p-4 hover:bg-slate-50 transition-colors">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <div class="text-sm font-bold text-brand mb-1">{{ wd.advertiser_name }}</div>
                        <div class="text-xs font-semibold text-slate-700">{{ wd.date_start.split('-').reverse().join('/') }} - {{ wd.date_end.split('-').reverse().join('/') }}</div>
                        <div class="text-[11px] text-slate-500 mt-0.5">{{ wd.product_names }}</div>
                    </div>
                    <div class="text-right">
                        <select v-model="wd.status" class="border border-slate-300 rounded px-2 py-1 text-[11px] font-medium bg-white focus:ring-2 focus:ring-brand focus:border-brand outline-none transition-all w-28 mb-2">
                            <option value="Pending">Pending</option>
                            <option value="Need Revision">Need Revision</option>
                            <option value="Approved">Approved</option>
                            <option value="Transfered" :disabled="!wd.transfer_receipt">Transfered</option>
                        </select>
                        <div v-if="wd.transfer_receipt" class="flex flex-col items-end">
                            <a :href="wd.transfer_receipt" target="_blank" class="text-[10px] text-green-600 hover:underline font-medium flex items-center mb-1"><i data-lucide="check-circle" class="w-3 h-3 mr-1"></i> Bukti TF</a>
                            <button @click="openMediaPickerForTf(wd)" class="text-[10px] text-slate-500 hover:text-slate-700">Ubah</button>
                        </div>
                        <button v-else @click="openMediaPickerForTf(wd)" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 px-2 py-1 rounded text-[10px] font-medium transition-colors border border-slate-200">
                            Upload Bukti
                        </button>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-2 mb-3 bg-slate-50 p-3 rounded-lg border border-slate-100">
                    <div>
                        <div class="text-[10px] text-slate-500 uppercase font-semibold mb-0.5">Ad Spent</div>
                        <div class="text-[11px] font-semibold text-slate-700">Rp {{ new Intl.NumberFormat('id-ID').format(Math.round(wd.ad_spent)) }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-500 uppercase font-semibold mb-0.5">Revenue</div>
                        <div class="text-[11px] font-semibold text-slate-700">Rp {{ new Intl.NumberFormat('id-ID').format(Math.round(wd.omset)) }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-500 uppercase font-semibold mb-0.5">Profit</div>
                        <div class="text-[11px] font-semibold text-slate-700">Rp {{ new Intl.NumberFormat('id-ID').format(Math.round(wd.profit_share)) }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-500 uppercase font-semibold mb-0.5">Nominal WD</div>
                        <div class="text-[11px] font-bold text-brand">Rp {{ new Intl.NumberFormat('id-ID').format(Math.round(wd.nominal_wd)) }}</div>
                    </div>
                </div>

                <div class="flex justify-between items-center">
                    <a :href="wd.report_image" target="_blank" class="text-[11px] text-brand hover:underline flex items-center font-medium bg-brand/5 px-2 py-1.5 rounded">
                        <i data-lucide="image" class="w-3 h-3 mr-1"></i> SS
                    </a>
                    
                    <div class="flex items-center space-x-2">
                        <button @click="updateWdStatus(wd)" class="bg-brand text-white hover:bg-brand-dark px-3 py-1.5 rounded text-[11px] font-medium transition-colors">
                            Save
                        </button>
                        <button @click="deleteWithdrawal(wd.id)" class="text-red-600 hover:text-red-700 hover:bg-red-50 border border-red-100 px-3 py-1.5 rounded text-[11px] font-medium transition-colors">
                            Hapus
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Desktop View (Table) -->
        <div class="hidden md:block overflow-x-auto w-full">
            <table class="w-full text-left border-collapse min-w-[900px]">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="p-3 pl-4 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Advertiser</th>
                        <th class="p-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Tanggal</th>
                        <th class="p-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Produk</th>
                        <th class="p-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Ad Spent</th>
                        <th class="p-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Revenue</th>
                        <th class="p-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Profit</th>
                        <th class="p-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Nominal WD</th>
                        <th class="p-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Status & Bukti</th>
                        <th class="p-3 pr-4 text-right text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="filteredAdminWithdrawals.length === 0">
                        <td colspan="9" class="p-8 text-center text-slate-500 text-sm">Belum ada request withdrawal.</td>
                    </tr>
                    <tr v-for="wd in filteredAdminWithdrawals" :key="wd.id" class="hover:bg-slate-50 transition-colors">
                        <td class="p-3 pl-4 font-medium text-slate-800 text-[13px] align-top">{{ wd.advertiser_name }}</td>
                        <td class="p-3 text-[13px] text-slate-600 align-top whitespace-nowrap">{{ wd.date_start.split('-').reverse().join('/') }}<br/><span class="text-[11px] text-slate-400">s/d</span><br/>{{ wd.date_end.split('-').reverse().join('/') }}</td>
                        <td class="p-3 text-[13px] text-slate-600 max-w-[120px] truncate align-top" :title="wd.product_names">{{ wd.product_names }}</td>
                        <td class="p-3 text-[13px] text-slate-600 align-top whitespace-nowrap">
                            Rp {{ new Intl.NumberFormat('id-ID').format(Math.round(wd.ad_spent)) }}<br/>
                            <a :href="wd.report_image" target="_blank" class="text-[11px] text-brand hover:underline mt-1 inline-block">Lihat SS</a>
                        </td>
                        <td class="p-3 text-[13px] font-semibold text-slate-700 align-top whitespace-nowrap">Rp {{ new Intl.NumberFormat('id-ID').format(Math.round(wd.omset)) }}</td>
                        <td class="p-3 text-[13px] text-slate-600 align-top whitespace-nowrap">Rp {{ new Intl.NumberFormat('id-ID').format(Math.round(wd.profit_share)) }}</td>
                        <td class="p-3 text-[13px] font-bold text-brand align-top whitespace-nowrap">Rp {{ new Intl.NumberFormat('id-ID').format(Math.round(wd.nominal_wd)) }}</td>
                        <td class="p-3 text-sm align-top min-w-[130px]">
                            <select v-model="wd.status" class="border border-slate-300 rounded-md px-2 py-1 text-[12px] bg-white focus:ring-2 focus:ring-brand focus:border-brand outline-none transition-all mb-2 w-full">
                                <option value="Pending">Pending</option>
                                <option value="Need Revision">Need Revision</option>
                                <option value="Approved">Approved</option>
                                <option value="Transfered" :disabled="!wd.transfer_receipt">Transfered</option>
                            </select>
                            <div v-if="wd.transfer_receipt" class="flex flex-col">
                                <a :href="wd.transfer_receipt" target="_blank" class="text-[11px] text-green-600 hover:underline font-medium"><i data-lucide="check-circle" class="w-3 h-3 inline mr-0.5"></i> Bukti TF</a>
                                <button @click="openMediaPickerForTf(wd)" class="text-[11px] text-slate-500 hover:text-slate-700 text-left mt-0.5">Ubah Bukti TF</button>
                            </div>
                            <button v-else @click="openMediaPickerForTf(wd)" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 px-2 py-1 rounded text-[11px] font-medium transition-colors border border-slate-200">
                                Upload Bukti
                            </button>
                        </td>
                        <td class="p-3 pr-4 text-right align-top">
                            <div class="flex flex-col gap-1 items-end">
                                <button @click="updateWdStatus(wd)" class="bg-brand text-white hover:bg-brand-dark px-3 py-1.5 rounded text-[12px] font-medium w-[65px] transition-colors">
                                    Save
                                </button>
                                <button @click="deleteWithdrawal(wd.id)" class="text-red-600 hover:text-red-700 hover:bg-red-50 border border-red-100 px-3 py-1.5 rounded text-[12px] font-medium w-[65px] transition-colors">
                                    Hapus
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
