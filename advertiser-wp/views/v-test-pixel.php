<div>
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-slate-800">Test Pixel</h2>
    </div>
    
    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm max-w-2xl">
        <h3 class="text-lg font-bold text-slate-800 mb-2 flex items-center">
            <i data-lucide="activity" class="w-5 h-5 mr-2 text-brand"></i> Simulator Pixel
        </h3>
        <p class="text-sm text-slate-600 mb-6">Pilih Landing Page untuk melakukan simulasi trigger pixel. Buka console browser (tekan F12) pada halaman test untuk melihat log aktivitas pixel yang dikirim ke Meta atau TikTok.</p>
        
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Pilih Landing Page</label>
                <select v-model="testPixelPage" class="w-full border border-slate-200 rounded-lg px-4 py-3 focus:border-brand focus:ring-1 focus:ring-brand outline-none bg-white">
                    <option value="">-- Pilih Landing Page --</option>
                    <option v-for="p in pages" :key="p.id" :value="p.slug">{{ p.title }}</option>
                </select>
            </div>
            
            <div class="pt-4 border-t border-slate-100 flex justify-end">
                <button @click="runPixelTest" :disabled="!testPixelPage" class="bg-brand text-white px-6 py-3 rounded-lg text-sm font-bold hover:bg-brand-dark transition-colors disabled:opacity-50 inline-flex items-center shadow-md">
                    <i data-lucide="external-link" class="w-4 h-4 mr-2"></i> Buka Mode Test
                </button>
            </div>
        </div>
    </div>
</div>
