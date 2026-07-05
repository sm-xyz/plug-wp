<div>
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-slate-800">Master Produk</h2>
        <button v-if="isAdmin" @click="openProductModal()" class="bg-brand text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-brand-dark transition-colors inline-flex items-center">
            <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Tambah Produk
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 mb-6 flex flex-wrap gap-4 items-center bg-slate-50">
        <div class="flex-1 min-w-[200px]">
            <div class="relative">
                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400"></i>
                <input type="text" v-model="productSearchQuery" placeholder="Cari produk..." class="w-full pl-9 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none transition-all">
            </div>
        </div>
        <div class="w-full sm:w-auto">
            <select v-model="productFilterType" class="w-full sm:w-auto border border-slate-300 rounded-lg px-4 py-2 text-sm bg-white focus:ring-2 focus:ring-brand focus:border-brand outline-none transition-all">
                <option value="">Semua Jenis Produk</option>
                <option value="canvas_app">Canvas App - ShareLink AI</option>
                <option value="produk_2">Produk Digital (Ecourse)</option>
                <option value="produk_3">Tools Web (GASEO/IlluSEO)</option>
                <option value="produk_4">WA Gateway (WaBisnis)</option>
                <option value="produk_5">Registrasi Member Affiliate</option>
                <option value="produk_6">Job Post / Order CV</option>
            </select>
        </div>
        <div class="w-full sm:w-auto">
            <select v-model="productSortBy" class="w-full sm:w-auto border border-slate-300 rounded-lg px-4 py-2 text-sm bg-white focus:ring-2 focus:ring-brand focus:border-brand outline-none transition-all">
                <option value="newest">Terbaru</option>
                <option value="oldest">Terlama</option>
                <option value="price_asc">Harga Terendah</option>
                <option value="price_desc">Harga Tertinggi</option>
            </select>
        </div>
    </div>

    <div v-if="filteredProducts.length === 0" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-12 text-center text-slate-500">
        Belum ada produk.
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <div v-for="product in filteredProducts" :key="product.id" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col group cursor-pointer transition-all hover:shadow-md" @click="showProductDetail(product)">
            <!-- Mockup Image -->
            <div class="relative w-full pt-[100%] bg-slate-100">
                <img v-if="product.mockup_image" :src="product.mockup_image" class="absolute inset-0 w-full h-full object-cover" :alt="product.name">
                <div v-else class="absolute inset-0 flex items-center justify-center text-slate-400">
                    <i data-lucide="image" class="w-12 h-12 opacity-50"></i>
                </div>
                <!-- Admin Actions Overlay -->
                <div v-if="isAdmin" class="absolute top-3 right-3 flex space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button @click.stop="openProductModal(product)" class="bg-white text-blue-600 p-2 rounded-lg shadow-sm hover:bg-blue-50 transition-colors">
                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                    </button>
                    <button @click.stop="deleteProduct(product.id)" class="bg-white text-red-600 p-2 rounded-lg shadow-sm hover:bg-red-50 transition-colors">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <div class="p-5 flex-1 flex flex-col">
                <div class="text-xs font-semibold text-brand mb-1 uppercase tracking-wider">{{ getProductTypeName(product.product_type) }}</div>
                <h3 class="font-bold text-slate-800 text-lg mb-2 line-clamp-2 leading-tight">{{ product.name }}</h3>
                
                <div class="mt-auto pt-3 border-t border-slate-100">
                    <div class="flex items-baseline mb-1">
                        <span class="text-xs text-slate-400 line-through mr-2" v-if="product.price_coret > 0">Rp {{ new Intl.NumberFormat('id-ID').format(product.price_coret) }}</span>
                        <span class="text-lg font-bold text-slate-800">Rp {{ new Intl.NumberFormat('id-ID').format(product.price) }}</span>
                    </div>
                    
                    <div v-if="product.affiliate_commission" class="text-xs text-amber-600 bg-amber-50 px-2 py-1 rounded-md inline-block font-medium mt-1">
                        Komisi: {{ product.affiliate_commission }}
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-slate-100 flex gap-2">
                    <button @click.stop="viewMasterLP(product)" class="w-full flex-1 bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium py-2 px-3 rounded-lg transition-colors flex items-center justify-center">
                        <i data-lucide="external-link" class="w-4 h-4 mr-2"></i> Detail Master LP
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Detail Modal -->
    <div v-if="detailProduct" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" @click.self="detailProduct = null">
        <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-white sticky top-0 z-10">
                <h3 class="font-bold text-xl text-slate-800">{{ detailProduct.name }}</h3>
                <button @click="detailProduct = null" class="text-slate-400 hover:text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-full p-1 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="p-6 overflow-y-auto">
                <div class="flex flex-col md:flex-row gap-6">
                    <div class="w-full md:w-1/3 shrink-0">
                        <div class="w-full pt-[100%] relative bg-slate-100 rounded-xl overflow-hidden border border-slate-200">
                             <img v-if="detailProduct.mockup_image" :src="detailProduct.mockup_image" class="absolute inset-0 w-full h-full object-cover">
                             <div v-else class="absolute inset-0 flex items-center justify-center text-slate-400">
                                <i data-lucide="image" class="w-12 h-12 opacity-30"></i>
                            </div>
                        </div>
                    </div>
                    <div class="flex-1 space-y-4">
                        <div>
                            <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Jenis Produk</h4>
                            <div class="font-medium text-slate-800 flex items-center">
                                <i data-lucide="box" class="w-4 h-4 mr-2 text-brand"></i>
                                {{ getProductTypeName(detailProduct.product_type) }}
                            </div>
                        </div>
                        <div>
                            <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Deskripsi Produk</h4>
                            <p class="text-sm text-slate-600 whitespace-pre-wrap">{{ detailProduct.description || 'Tidak ada deskripsi.' }}</p>
                        </div>
                        <div>
                            <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Alur Akses Produk</h4>
                            <div class="bg-slate-50 p-3 rounded-lg border border-slate-100 text-sm text-slate-600 whitespace-pre-wrap">{{ detailProduct.access_flow || 'Belum diatur.' }}</div>
                        </div>
                        <div class="pt-2">
                             <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Informasi Harga</h4>
                             <div class="grid grid-cols-2 gap-3">
                                 <div class="bg-slate-50 p-3 rounded-lg border border-slate-100">
                                     <div class="text-xs text-slate-500 mb-1">Harga Jual</div>
                                     <div class="font-bold text-slate-800">Rp {{ new Intl.NumberFormat('id-ID').format(detailProduct.price) }}</div>
                                 </div>
                                 <div class="bg-slate-50 p-3 rounded-lg border border-slate-100">
                                     <div class="text-xs text-slate-500 mb-1">Harga Normal</div>
                                     <div class="font-semibold text-slate-500 line-through">Rp {{ new Intl.NumberFormat('id-ID').format(detailProduct.price_coret) }}</div>
                                 </div>
                             </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-6 border-t border-slate-100 bg-slate-50 flex justify-end space-x-3">
                <button @click="viewMasterLP(detailProduct)" class="px-5 py-2 text-sm font-medium bg-slate-800 text-white hover:bg-slate-900 rounded-lg shadow-sm transition-colors flex items-center">
                    <i data-lucide="external-link" class="w-4 h-4 mr-2"></i> Lihat Master LP
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Form Product (Admin) -->
    <div v-if="showProductModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl max-w-xl w-full shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-lg text-slate-800">{{ editingProduct.id ? 'Edit' : 'Tambah' }} Produk</h3>
                <button @click="showProductModal = false" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="p-6 space-y-5 flex-1 overflow-y-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Produk</label>
                        <input type="text" v-model="editingProduct.name" class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:border-brand focus:ring-2 focus:ring-brand/20 outline-none transition-all">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Jenis Produk / Integrasi Webhook</label>
                        <select v-model="editingProduct.product_type" class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:border-brand focus:ring-2 focus:ring-brand/20 outline-none transition-all bg-white">
                            <option value="canvas_app">Canvas App - ShareLink AI</option>
                            <option value="produk_2">Produk Digital (Ecourse)</option>
                            <option value="produk_3">Tools Web (GASEO/IlluSEO)</option>
                            <option value="produk_4">WA Gateway (WaBisnis)</option>
                            <option value="produk_5">Registrasi Member Affiliate</option>
                            <option value="produk_6">Job Post / Order CV</option>
                        </select>
                        <p class="text-xs text-slate-500 mt-1">Menentukan aliran webhook setelah pembayaran sukses.</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Gambar Mockup (Square)</label>
                        
                        <!-- Preview Gambar yang Sudah Ada / Diupload -->
                        <div v-if="editingProduct.mockup_image" class="p-3 bg-green-50 border border-green-200 rounded-xl flex items-center justify-between mb-3">
                            <div class="flex items-center space-x-3">
                                <a :href="editingProduct.mockup_image" target="_blank" class="w-16 h-16 rounded-lg border border-slate-300 overflow-hidden shrink-0 relative group block bg-white">
                                    <img :src="editingProduct.mockup_image" class="w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-[10px] transition-opacity">Zoom</div>
                                </a>
                                <div>
                                    <div class="flex items-center text-xs font-bold text-green-700">
                                        <i data-lucide="check-circle-2" class="w-4 h-4 mr-1 text-green-600"></i> Gambar Berhasil Diupload
                                    </div>
                                    <a :href="editingProduct.mockup_image" target="_blank" class="text-xs text-brand hover:underline mt-0.5 block font-medium">Klik untuk Preview Gambar Full</a>
                                </div>
                            </div>
                            <button type="button" @click="editingProduct.mockup_image = ''" class="px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 rounded-lg transition-colors border border-red-200">Ganti</button>
                        </div>

                        <!-- Tombol Upload Langsung & Pilih Galeri -->
                        <div v-if="!editingProduct.mockup_image" class="flex flex-wrap items-center gap-3">
                            <div class="relative">
                                <button type="button" :disabled="isUploadingProduct" class="px-4 py-2.5 bg-brand text-white hover:bg-brand-dark disabled:bg-slate-300 disabled:text-slate-500 rounded-xl text-sm font-semibold transition-all inline-flex items-center shadow-sm">
                                    <span v-if="isUploadingProduct" class="inline-flex items-center">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        Mengupload...
                                    </span>
                                    <span v-else class="inline-flex items-center">
                                        <i data-lucide="upload" class="w-4 h-4 mr-2"></i> Upload Gambar Produk
                                    </span>
                                </button>
                                <input v-if="!isUploadingProduct" type="file" accept="image/*" @change="uploadProductImage" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
                            </div>

                            <button type="button" @click="openMediaPickerForProduct()" :disabled="isUploadingProduct" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 disabled:opacity-50 rounded-xl text-sm font-semibold transition-all inline-flex items-center">
                                <i data-lucide="image" class="w-4 h-4 mr-2 text-slate-500"></i> Pilih dari Galeri
                            </button>
                        </div>
                        <p v-if="isUploadingProduct" class="text-xs font-semibold text-brand mt-2 flex items-center animate-pulse">
                            Sedang mengupload gambar ke server, mohon tunggu sampai preview muncul...
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Harga Jual (Rp)</label>
                        <input type="number" v-model="editingProduct.price" class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:border-brand focus:ring-2 focus:ring-brand/20 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Harga Normal / Coret (Rp)</label>
                        <input type="number" v-model="editingProduct.price_coret" class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:border-brand focus:ring-2 focus:ring-brand/20 outline-none transition-all">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Komisi Affiliate</label>
                        <input type="text" v-model="editingProduct.affiliate_commission" placeholder="Contoh: 50% atau Rp 100.000" class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:border-brand focus:ring-2 focus:ring-brand/20 outline-none transition-all">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Deskripsi Singkat</label>
                        <textarea v-model="editingProduct.description" rows="2" class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:border-brand focus:ring-2 focus:ring-brand/20 outline-none transition-all"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Alur Akses Produk</label>
                        <textarea v-model="editingProduct.access_flow" rows="2" placeholder="Jelaskan cara mengakses produk setelah beli..." class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:border-brand focus:ring-2 focus:ring-brand/20 outline-none transition-all"></textarea>
                    </div>
                </div>
            </div>
            <div class="p-6 border-t border-slate-100 bg-slate-50 flex justify-end space-x-3">
                <button @click="showProductModal = false" class="px-5 py-2 text-sm font-medium text-slate-600 hover:text-slate-800 hover:bg-slate-200 rounded-lg transition-colors">Batal</button>
                <button @click="saveProduct" class="px-5 py-2 text-sm font-medium bg-brand text-white hover:bg-brand-dark rounded-lg shadow-sm transition-colors">Simpan Produk</button>
            </div>
        </div>
    </div>
</div>
