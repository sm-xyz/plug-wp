<div>
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-slate-800">Integrasi & Pengaturan</h2>
        <button @click="saveSettings" class="bg-brand text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-brand-dark transition-colors inline-flex items-center shadow-sm">
            <i data-lucide="save" class="w-4 h-4 mr-2"></i> Simpan Pengaturan
        </button>
    </div>

    <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-slate-200 max-w-3xl space-y-8">
        
        <div class="space-y-4">
            <h3 class="text-lg font-bold text-slate-800 border-b pb-3">Cloudflare Turnstile (Login Page)</h3>
            
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Site Key</label>
                <input type="text" v-model="settings.adv_turnstile_sitekey" class="w-full border border-slate-200 rounded-lg px-4 py-3 focus:border-brand focus:ring-1 focus:ring-brand outline-none" placeholder="1x00000000000000000000AA">
                <p class="text-xs text-slate-500 mt-1">Dapatkan dari dashboard Cloudflare Turnstile.</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Secret Key</label>
                <input type="password" v-model="settings.adv_turnstile_secret" class="w-full border border-slate-200 rounded-lg px-4 py-3 focus:border-brand focus:ring-1 focus:ring-brand outline-none" placeholder="1x0000000000000000000000000000000AA">
            </div>
        </div>
        
        <div class="space-y-4 pt-4">
            <h3 class="text-lg font-bold text-slate-800 border-b pb-3">Fonnte API (Validasi WhatsApp)</h3>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Fonnte API Token</label>
                <input type="text" v-model="settings.adv_fonnte_token" class="w-full border border-slate-200 rounded-lg px-4 py-3 focus:border-brand focus:ring-1 focus:ring-brand outline-none" placeholder="Isi token API Fonnte disini">
                <p class="text-xs text-slate-500 mt-1">Digunakan untuk memastikan nomor pembeli terdaftar di WA.</p>
            </div>
        </div>

        <div class="space-y-4 pt-4">
            <h3 class="text-lg font-bold text-slate-800 border-b pb-3">Reacher API (Validasi Email)</h3>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Reacher API Key</label>
                <input type="text" v-model="settings.adv_reacher_api_key" class="w-full border border-slate-200 rounded-lg px-4 py-3 focus:border-brand focus:ring-1 focus:ring-brand outline-none" placeholder="Isi X-API-KEY Reacher disini">
                <p class="text-xs text-slate-500 mt-1">Digunakan untuk memastikan alamat email aktif (deliverable). Kosongkan jika tidak ingin memvalidasi email.</p>
            </div>
        </div>

        <div class="space-y-4 pt-4">
            <h3 class="text-lg font-bold text-slate-800 border-b pb-3">Konfigurasi Duitku (Fase 2)</h3>
            
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Url Callback Proyek (Duitku)</label>
                <div class="flex items-center space-x-2">
                    <input type="text" readonly :value="callbackUrl" class="w-full border border-slate-200 bg-slate-50 text-slate-500 rounded-lg px-4 py-3 outline-none font-mono text-sm">
                    <button @click="copyText(callbackUrl)" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-3 rounded-lg text-sm font-bold transition-colors inline-flex items-center shrink-0">
                        <i data-lucide="copy" class="w-4 h-4 mr-2"></i> Copy
                    </button>
                </div>
                <p class="text-xs text-slate-500 mt-1">Copy URL ini dan paste ke kolom "Url Callback Proyek" di Dashboard Duitku.</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Environment</label>
                <select v-model="settings.solusi_duitku_env" class="w-full border border-slate-200 rounded-lg px-4 py-3 focus:border-brand focus:ring-1 focus:ring-brand outline-none bg-white">
                    <option value="sandbox">Sandbox</option>
                    <option value="production">Production</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Merchant Code</label>
                <input type="text" v-model="settings.solusi_duitku_merchant_code" class="w-full border border-slate-200 rounded-lg px-4 py-3 focus:border-brand focus:ring-1 focus:ring-brand outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">API Key</label>
                <input type="password" v-model="settings.solusi_duitku_api_key" class="w-full border border-slate-200 rounded-lg px-4 py-3 focus:border-brand focus:ring-1 focus:ring-brand outline-none">
            </div>
        </div>

        <div class="space-y-4 pt-4">
            <h3 class="text-lg font-bold text-slate-800 border-b pb-3">Webhook ke Sharelink (Fase 4)</h3>
            
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Sharelink Webhook URL</label>
                <input type="url" v-model="settings.solusi_sharelink_webhook_url" class="w-full border border-slate-200 rounded-lg px-4 py-3 focus:border-brand focus:ring-1 focus:ring-brand outline-none" placeholder="https://sharelink.web.id/wp-json/sharelink/v1/license/generate">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Sharelink Webhook Secret</label>
                <input type="password" v-model="settings.solusi_sharelink_secret" class="w-full border border-slate-200 rounded-lg px-4 py-3 focus:border-brand focus:ring-1 focus:ring-brand outline-none">
                <p class="text-xs text-slate-500 mt-1">Secret key HMAC-SHA256 untuk memvalidasi request di Sharelink.</p>
            </div>
        </div>

    </div>
</div>
