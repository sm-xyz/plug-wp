<div>
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-slate-800">All Advertiser</h2>
        <button @click="openAdvertiserModal()" class="bg-brand text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-brand-dark transition-colors inline-flex items-center">
            <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Tambah Advertiser
        </button>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[800px]">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="p-4 pl-6 text-xs font-semibold text-slate-500 uppercase tracking-wider">Nama</th>
                        <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Username</th>
                        <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Email</th>
                        <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Terdaftar</th>
                        <th class="p-4 pr-6 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="adminAdvertisers.length === 0">
                        <td colspan="5" class="p-8 text-center text-slate-500">Belum ada advertiser.</td>
                    </tr>
                    <tr v-for="adv in adminAdvertisers" :key="adv.id" class="hover:bg-slate-50 transition-colors">
                        <td class="p-4 pl-6 font-medium text-slate-800">{{ adv.name }}</td>
                        <td class="p-4 text-sm text-slate-600">{{ adv.username }}</td>
                        <td class="p-4 text-sm text-slate-600">{{ adv.email }}</td>
                        <td class="p-4 text-sm text-slate-600">{{ new Date(adv.registered).toLocaleDateString('id-ID') }}</td>
                        <td class="p-4 pr-6 text-right">
                            <button @click="openAdvertiserModal(adv)" class="text-blue-600 hover:text-blue-800 px-2 py-1 bg-blue-50 rounded text-xs font-medium mr-2">
                                Edit
                            </button>
                            <button @click="deleteAdvertiser(adv.id)" class="text-red-600 hover:text-red-800 px-2 py-1 bg-red-50 rounded text-xs font-medium">
                                Hapus
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Advertiser Modal -->
    <div v-if="showAdvModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl overflow-hidden flex flex-col">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-lg text-slate-800">{{ editingAdv.id ? 'Edit' : 'Tambah' }} Advertiser</h3>
                <button @click="showAdvModal = false" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Lengkap</label>
                    <input type="text" v-model="editingAdv.name" class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:border-brand focus:ring-2 focus:ring-brand/20 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Username (Login)</label>
                    <input type="text" v-model="editingAdv.username" :disabled="!!editingAdv.id" class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:border-brand focus:ring-2 focus:ring-brand/20 outline-none disabled:bg-slate-100 disabled:text-slate-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Email</label>
                    <input type="email" v-model="editingAdv.email" class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:border-brand focus:ring-2 focus:ring-brand/20 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Password</label>
                    <input type="password" v-model="editingAdv.password" :placeholder="editingAdv.id ? 'Kosongkan jika tidak ingin mengubah' : 'Password wajib diisi'" class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:border-brand focus:ring-2 focus:ring-brand/20 outline-none">
                </div>
            </div>
            <div class="p-6 border-t border-slate-100 bg-slate-50 flex justify-end space-x-3">
                <button @click="showAdvModal = false" class="px-5 py-2 text-sm font-medium text-slate-600 hover:text-slate-800 hover:bg-slate-200 rounded-lg transition-colors">Batal</button>
                <button @click="saveAdvertiser" class="px-5 py-2 text-sm font-medium bg-brand text-white hover:bg-brand-dark rounded-lg shadow-sm transition-colors">Simpan</button>
            </div>
        </div>
    </div>
</div>
