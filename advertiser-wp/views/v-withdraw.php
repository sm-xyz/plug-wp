<div>
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-slate-800">Withdrawal</h2>
        <button @click="openWdModal" class="bg-brand text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-brand-dark transition-colors inline-flex items-center">
            <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Request WD
        </button>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
        <!-- Mobile View (Cards) -->
        <div class="block md:hidden divide-y divide-slate-100">
            <div v-if="withdrawals.length === 0" class="p-8 text-center text-slate-500 text-sm">
                Belum ada request withdrawal.
            </div>
            <div v-for="wd in withdrawals" :key="wd.id" class="p-4 hover:bg-slate-50 transition-colors">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <div class="text-sm font-bold text-slate-800">{{ wd.date_start.split('-').reverse().join('/') }} - {{ wd.date_end.split('-').reverse().join('/') }}</div>
                        <div class="text-xs text-slate-500 mt-1 max-w-[200px] truncate">{{ wd.product_names }}</div>
                    </div>
                    <div class="text-right">
                        <span class="px-2 py-1 rounded text-[10px] font-medium inline-block" :class="{
                            'bg-yellow-100 text-yellow-800': wd.status === 'Pending' || wd.status === 'Need Revision',
                            'bg-blue-100 text-blue-800': wd.status === 'Approved',
                            'bg-green-100 text-green-800': wd.status === 'Transfered'
                        }">{{ wd.status }}</span>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-2 mb-3 bg-slate-50 p-3 rounded-lg border border-slate-100">
                    <div>
                        <div class="text-[10px] text-slate-500 uppercase font-semibold mb-0.5">Ad Spent</div>
                        <div class="text-xs font-semibold text-slate-700">Rp {{ new Intl.NumberFormat('id-ID').format(Math.round(wd.ad_spent)) }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-500 uppercase font-semibold mb-0.5">Revenue</div>
                        <div class="text-xs font-semibold text-slate-700">Rp {{ new Intl.NumberFormat('id-ID').format(Math.round(wd.omset)) }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-500 uppercase font-semibold mb-0.5">Profit</div>
                        <div class="text-xs font-semibold text-slate-700">Rp {{ new Intl.NumberFormat('id-ID').format(Math.round(wd.profit_share)) }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-500 uppercase font-semibold mb-0.5">Nominal WD</div>
                        <div class="text-xs font-bold text-brand">Rp {{ new Intl.NumberFormat('id-ID').format(Math.round(wd.nominal_wd)) }}</div>
                    </div>
                </div>

                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-3">
                        <a :href="wd.report_image" target="_blank" class="text-[11px] text-brand hover:underline flex items-center font-medium bg-brand/5 px-2 py-1 rounded">
                            <i data-lucide="image" class="w-3 h-3 mr-1"></i> SS
                        </a>
                        <a v-if="wd.transfer_receipt" :href="wd.transfer_receipt" target="_blank" class="text-[11px] text-green-600 hover:underline flex items-center font-medium bg-green-50 px-2 py-1 rounded">
                            <i data-lucide="check-circle" class="w-3 h-3 mr-1"></i> TF
                        </a>
                    </div>
                    <button v-if="wd.status === 'Pending' || wd.status === 'Need Revision'" @click="openWdModal(wd)" class="text-[11px] font-semibold px-4 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-md transition-colors inline-flex items-center border border-slate-200">
                        Edit
                    </button>
                </div>
            </div>
        </div>

        <!-- Desktop View (Table) -->
        <div class="hidden md:block overflow-x-auto w-full">
            <table class="w-full text-left border-collapse min-w-[800px]">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="p-3 pl-4 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Tanggal</th>
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
                    <tr v-if="withdrawals.length === 0">
                        <td colspan="8" class="p-8 text-center text-slate-500 text-sm">Belum ada request withdrawal.</td>
                    </tr>
                    <tr v-for="wd in withdrawals" :key="wd.id" class="hover:bg-slate-50 transition-colors">
                        <td class="p-3 pl-4 text-[13px] text-slate-600 align-top whitespace-nowrap">{{ wd.date_start.split('-').reverse().join('/') }}<br/><span class="text-[11px] text-slate-400">s/d</span><br/>{{ wd.date_end.split('-').reverse().join('/') }}</td>
                        <td class="p-3 text-[13px] text-slate-600 max-w-[120px] truncate align-top" :title="wd.product_names">{{ wd.product_names }}</td>
                        <td class="p-3 text-[13px] text-slate-600 align-top whitespace-nowrap">
                            Rp {{ new Intl.NumberFormat('id-ID').format(Math.round(wd.ad_spent)) }}<br/>
                            <a :href="wd.report_image" target="_blank" class="text-[11px] text-brand hover:underline mt-1 inline-block">Lihat SS</a>
                        </td>
                        <td class="p-3 text-[13px] font-semibold text-slate-700 align-top whitespace-nowrap">Rp {{ new Intl.NumberFormat('id-ID').format(Math.round(wd.omset)) }}</td>
                        <td class="p-3 text-[13px] text-slate-600 align-top whitespace-nowrap">Rp {{ new Intl.NumberFormat('id-ID').format(Math.round(wd.profit_share)) }}</td>
                        <td class="p-3 text-[13px] font-bold text-brand align-top whitespace-nowrap">Rp {{ new Intl.NumberFormat('id-ID').format(Math.round(wd.nominal_wd)) }}</td>
                        <td class="p-3 text-sm align-top min-w-[120px]">
                            <span class="px-2 py-1 rounded text-[11px] font-medium inline-block mb-1" :class="{
                                'bg-yellow-100 text-yellow-800': wd.status === 'Pending' || wd.status === 'Need Revision',
                                'bg-blue-100 text-blue-800': wd.status === 'Approved',
                                'bg-green-100 text-green-800': wd.status === 'Transfered'
                            }">{{ wd.status }}</span>
                            <div v-if="wd.transfer_receipt" class="mt-1">
                                <a :href="wd.transfer_receipt" target="_blank" class="text-[11px] text-green-600 hover:underline flex items-center font-medium">
                                    <i data-lucide="check-circle" class="w-3 h-3 mr-1"></i> Bukti TF
                                </a>
                            </div>
                        </td>
                        <td class="p-3 pr-4 text-right align-top">
                            <button v-if="wd.status === 'Pending' || wd.status === 'Need Revision'" @click="openWdModal(wd)" class="text-[11px] font-semibold px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-md transition-colors inline-flex items-center">
                                Edit
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Request WD Modal -->
    <div v-if="showWdModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl max-w-xl w-full shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-lg text-slate-800">{{ wdForm.id ? 'Edit Request Withdraw' : 'Request Withdraw' }}</h3>
                <button @click="showWdModal = false" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="p-6 space-y-4 flex-1 overflow-y-auto">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Tanggal Mulai <span class="text-brand font-normal ml-2" v-if="wdForm.date_start">({{ wdForm.date_start.split('-').reverse().join('/') }})</span></label>
                        <input type="date" :min="wdMinDate" v-model="wdForm.date_start" @change="calculateOmset" class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:border-brand focus:ring-2 focus:ring-brand/20 outline-none text-slate-700">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Tanggal Selesai <span class="text-brand font-normal ml-2" v-if="wdForm.date_end">({{ wdForm.date_end.split('-').reverse().join('/') }})</span></label>
                        <input type="date" :min="wdMinDate" v-model="wdForm.date_end" @change="calculateOmset" class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:border-brand focus:ring-2 focus:ring-brand/20 outline-none text-slate-700">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Produk</label>
                    <div class="max-h-32 overflow-y-auto border border-slate-300 rounded-lg p-2 space-y-2">
                        <label v-for="prod in products" :key="prod.id" class="flex items-center space-x-2">
                            <input type="checkbox" :value="parseInt(prod.id)" v-model="wdForm.products" @change="calculateOmset" class="rounded text-brand focus:ring-brand">
                            <span class="text-sm text-slate-700">{{ prod.name }}</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Report Ads (Ad Spent)</label>
                    
                    <!-- Preview Gambar yang Sudah Diupload -->
                    <div v-if="wdForm.report_image" class="p-3 bg-green-50 border border-green-200 rounded-xl flex items-center justify-between mb-3">
                        <div class="flex items-center space-x-3">
                            <a :href="wdForm.report_image" target="_blank" class="w-16 h-16 rounded-lg border border-slate-300 overflow-hidden shrink-0 relative group block bg-white">
                                <img :src="wdForm.report_image" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-[10px] transition-opacity">Zoom</div>
                            </a>
                            <div>
                                <div class="flex items-center text-xs font-bold text-green-700">
                                    <i data-lucide="check-circle-2" class="w-4 h-4 mr-1 text-green-600"></i> Gambar Berhasil Diupload
                                </div>
                                <a :href="wdForm.report_image" target="_blank" class="text-xs text-brand hover:underline mt-0.5 block font-medium">Klik untuk Preview Gambar Full</a>
                            </div>
                        </div>
                        <button type="button" @click="wdForm.report_image = ''" class="px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 rounded-lg transition-colors border border-red-200">Ganti</button>
                    </div>

                    <!-- Tombol Upload & Pilih Galeri -->
                    <div v-if="!wdForm.report_image" class="flex flex-wrap items-center gap-3">
                        <div class="relative">
                            <button type="button" :disabled="isUploadingWD" class="px-4 py-2.5 bg-brand text-white hover:bg-brand-dark disabled:bg-slate-300 disabled:text-slate-500 rounded-xl text-sm font-semibold transition-all inline-flex items-center shadow-sm">
                                <span v-if="isUploadingWD" class="inline-flex items-center">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Mengupload...
                                </span>
                                <span v-else class="inline-flex items-center">
                                    <i data-lucide="upload" class="w-4 h-4 mr-2"></i> Upload Gambar Report
                                </span>
                            </button>
                            <input v-if="!isUploadingWD" type="file" accept="image/*" @change="uploadWDImage" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
                        </div>

                        <button type="button" @click="openMediaPickerForWD()" :disabled="isUploadingWD" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 disabled:opacity-50 rounded-xl text-sm font-semibold transition-all inline-flex items-center">
                            <i data-lucide="image" class="w-4 h-4 mr-2 text-slate-500"></i> Pilih dari Galeri
                        </button>
                    </div>
                    <p v-if="isUploadingWD" class="text-xs font-semibold text-brand mt-2 flex items-center animate-pulse">
                        Sedang mengupload gambar ke server, mohon tunggu sampai preview muncul...
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Ad Spent + Tax (Rp)</label>
                    <input type="number" step="1" v-model="wdForm.ad_spent" class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:border-brand focus:ring-2 focus:ring-brand/20 outline-none">
                    <p class="text-xs text-slate-500 mt-1">Sesuai dengan angka pada gambar report ads yang dilampirkan.</p>
                </div>

                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-slate-600">Total Revenue <br/><span class="text-xs font-normal text-slate-400">(Otomatis berdasarkan order Paid)</span></span>
                        <span class="font-bold text-slate-800">Rp {{ new Intl.NumberFormat('id-ID').format(Math.round(wdForm.omset)) }}</span>
                    </div>
                    <div class="flex justify-between items-center pt-2 border-t border-slate-200">
                        <span class="text-sm font-medium text-slate-600">Profit Share (50%)</span>
                        <span class="font-bold text-slate-800">Rp {{ new Intl.NumberFormat('id-ID').format(Math.round(wdProfitShare)) }}</span>
                    </div>
                    <div class="flex justify-between items-center pt-2 border-t border-slate-200">
                        <span class="text-sm font-bold text-brand">Nominal WD</span>
                        <span class="text-xl font-bold text-brand">Rp {{ new Intl.NumberFormat('id-ID').format(Math.round(wdNominal)) }}</span>
                    </div>
                </div>

            </div>
            <div class="p-6 border-t border-slate-100 bg-slate-50 flex justify-end space-x-3">
                <button @click="showWdModal = false" class="px-5 py-2 text-sm font-medium text-slate-600 hover:text-slate-800 hover:bg-slate-200 rounded-lg transition-colors">Batal</button>
                <button @click="submitWd" :disabled="!isWdValid" :class="isWdValid ? 'bg-brand text-white hover:bg-brand-dark' : 'bg-slate-300 text-slate-500 cursor-not-allowed'" class="px-5 py-2 text-sm font-medium rounded-lg shadow-sm transition-colors">{{ wdForm.id ? 'Simpan Perubahan' : 'Request WD' }}</button>
            </div>
        </div>
    </div>
</div>
