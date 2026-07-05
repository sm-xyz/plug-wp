<div>
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <h2 class="text-xl font-bold text-slate-800">Marketing Kit</h2>
        <div class="flex flex-wrap gap-3 items-center">
            <div class="relative">
                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" v-model="contentSearchQuery" placeholder="Cari konten..." class="pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:border-brand focus:ring-1 focus:ring-brand outline-none w-40 md:w-48 bg-white">
            </div>
            
            <div class="w-40 md:w-48">
                <select v-model="contentFilterProduct" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand outline-none bg-white">
                    <option value="0">Semua Produk</option>
                    <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
            </div>

            <button v-if="isAdmin" @click="showContentModal = true; editingContent = { id: 0, title: '', type: 'image', embed_link: '', copy_text: '', product_id: 0 }" class="bg-brand text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-brand-dark transition-colors inline-flex items-center shrink-0">
                <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Tambah Konten
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <div v-if="filteredContents.length === 0" class="col-span-full py-12 text-center text-slate-500 bg-white rounded-2xl border border-slate-200 border-dashed">
            Belum ada konten di bank konten.
        </div>
        
        <div v-for="content in filteredContents" :key="content.id" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col group h-full">
            <div class="w-full relative bg-slate-50 flex items-center justify-center min-h-[200px] border-b border-slate-100">
                <!-- Preview / Embed -->
                <template v-if="content.type === 'image'">
                    <img v-if="content.embed_link" :src="content.embed_link" class="w-full h-auto max-h-[400px] object-contain" alt="Preview">
                    <div v-else class="text-slate-300"><i data-lucide="image" class="w-10 h-10"></i></div>
                </template>
                <template v-if="content.type === 'video'">
                    <video v-if="content.embed_link && !content.embed_link.includes('youtube')" :src="content.embed_link" class="w-full aspect-video border-0 bg-black" controls preload="none"></video>
                    <iframe v-else-if="content.embed_link" :src="content.embed_link" class="w-full aspect-video border-0" allowfullscreen></iframe>
                    <div v-else class="text-slate-300"><i data-lucide="video" class="w-10 h-10"></i></div>
                </template>
                
                <!-- Overlay Actions for Admin -->
                <div v-if="isAdmin" class="absolute top-2 right-2 flex space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button @click="editContent(content)" class="bg-white/90 p-1.5 rounded text-blue-600 hover:text-blue-800 shadow-sm"><i data-lucide="edit-2" class="w-4 h-4"></i></button>
                    <button @click="deleteContent(content.id)" class="bg-white/90 p-1.5 rounded text-red-600 hover:text-red-800 shadow-sm"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                </div>
            </div>
            
            <div class="p-4 flex flex-col flex-1">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500" :class="{'bg-blue-50 text-blue-600': content.type === 'video', 'bg-green-50 text-green-600': content.type === 'image'}">
                        {{ content.type }}
                    </span>
                    <span class="text-xs text-slate-400 font-medium">{{ products.find(p => p.id == content.product_id)?.name || 'Umum' }}</span>
                </div>
                <h3 class="font-bold text-slate-800 text-sm mb-3">{{ content.title }}</h3>
                
                <div class="mt-auto pt-4 flex space-x-2">
                    <button @click="copyText(content.copy_text || 'Tidak ada teks copy untuk konten ini.')" class="flex-1 bg-slate-100 text-slate-700 py-2 rounded-lg text-sm font-bold hover:bg-slate-200 transition-colors flex items-center justify-center">
                        <i data-lucide="copy" class="w-4 h-4 mr-2"></i> Copy Ads
                    </button>
                    <button @click.prevent="forceDownload(content.embed_link, content.title)" class="flex-1 bg-brand/10 text-brand py-2 rounded-lg text-sm font-bold hover:bg-brand hover:text-white transition-colors flex items-center justify-center cursor-pointer">
                        <i data-lucide="download" class="w-4 h-4 mr-2"></i> Download
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Form -->
    <div v-if="showContentModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl w-full max-w-lg shadow-xl overflow-hidden max-h-[90vh] flex flex-col">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center shrink-0">
                <h3 class="font-bold text-lg text-slate-800">{{ editingContent.id ? 'Edit Konten' : 'Tambah Konten' }}</h3>
                <button @click="showContentModal = false" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <form @submit.prevent="saveContent" class="p-6 space-y-4 overflow-y-auto">
                <div>
                    <label class="block text-sm font-semibold mb-1 text-slate-700">Judul Konten</label>
                    <input type="text" v-model="editingContent.title" required class="w-full border border-slate-200 rounded-lg px-3 py-2 outline-none focus:border-brand focus:ring-1 focus:ring-brand">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1 text-slate-700">Tipe</label>
                        <select v-model="editingContent.type" class="w-full border border-slate-200 rounded-lg px-3 py-2 outline-none focus:border-brand focus:ring-1 focus:ring-brand bg-white">
                            <option value="image">Gambar</option>
                            <option value="video">Video</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1 text-slate-700">Produk Terkait</label>
                        <select v-model="editingContent.product_id" required class="w-full border border-slate-200 rounded-lg px-3 py-2 outline-none focus:border-brand focus:ring-1 focus:ring-brand bg-white">
                            <option value="0">-- Pilih Produk --</option>
                            <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1 text-slate-700">Link / Media Lokal (Media Library)</label>
                    <div class="flex gap-2 mb-2">
                        <button type="button" @click="openMediaPicker" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 py-2 px-4 rounded-lg text-sm font-bold transition-colors flex items-center justify-center">
                            <i data-lucide="image" class="w-4 h-4 mr-2"></i> Pilih dari Media
                        </button>
                        <div class="flex-1 relative overflow-hidden bg-brand hover:bg-brand-dark text-white py-2 px-4 rounded-lg text-sm font-bold transition-colors flex items-center justify-center cursor-pointer">
                            <i data-lucide="upload-cloud" class="w-4 h-4 mr-2"></i> Upload Media
                            <input type="file" :accept="editingContent.type === 'video' ? 'video/*' : 'image/*'" @change="uploadContentMedia" class="absolute inset-0 opacity-0 cursor-pointer">
                        </div>
                    </div>
                    <textarea v-model="editingContent.embed_link" required rows="2" class="w-full border border-slate-200 rounded-lg px-3 py-2 outline-none focus:border-brand focus:ring-1 focus:ring-brand text-xs text-slate-600" placeholder="Atau paste link URL media lokal..."></textarea>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1 text-slate-700">Teks Copy Ads (Opsional)</label>
                    <textarea v-model="editingContent.copy_text" rows="4" class="w-full border border-slate-200 rounded-lg px-3 py-2 outline-none focus:border-brand focus:ring-1 focus:ring-brand" placeholder="Masukkan teks copy untuk mendampingi konten ini..."></textarea>
                </div>
                <div class="pt-4 flex justify-end space-x-3">
                    <button type="button" @click="showContentModal = false" class="px-5 py-2 rounded-lg font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-lg font-bold text-white bg-brand hover:bg-brand-dark transition-colors">Simpan</button>
                </div>
            </form>
        </div>
    </div>


</div>
