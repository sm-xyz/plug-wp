<!-- Builder View (Full Screen Overlay) -->
<div class="flex flex-col h-full bg-slate-100">
    <!-- Builder Header -->
    <header class="h-14 bg-white border-b border-slate-200 flex items-center justify-between px-4 shrink-0 shadow-sm z-10">
        <div class="flex items-center space-x-4">
            <button @click="currentView = 'pages'" class="text-slate-500 hover:text-slate-800">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </button>
            <div class="flex flex-col">
                <input type="text" v-model="editingPage.title" class="font-bold text-lg bg-transparent border-none focus:ring-0 w-64 p-0 text-slate-800 leading-none" placeholder="Nama Halaman (Internal)">
                <div class="flex items-center text-xs text-slate-500 mt-1">
                    <span>/lp/</span>
                    <input type="text" v-model="editingPage.slug" class="bg-transparent border-b border-dashed border-slate-300 focus:border-brand focus:ring-0 p-0 text-xs text-slate-600 outline-none w-48" placeholder="custom-slug">
                </div>
            </div>
        </div>
        <div class="flex items-center space-x-2">
            <a v-if="editingPage.id" :href="'<?php echo home_url('/lp/'); ?>' + editingPage.slug" target="_blank" class="text-slate-500 hover:text-brand px-3 text-sm font-medium inline-flex items-center">
                <i data-lucide="external-link" class="w-4 h-4 mr-1"></i> Preview
            </a>
            <button @click="builderTab = 'design'" :class="builderTab === 'design' ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600'" class="px-4 py-1.5 rounded-lg text-sm font-medium transition-colors">Design</button>
            <button @click="builderTab = 'settings'" :class="builderTab === 'settings' ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600'" class="px-4 py-1.5 rounded-lg text-sm font-medium transition-colors">Settings & Pixel</button>
            <div class="w-px h-6 bg-slate-300 mx-2"></div>
            <button @click="savePage" class="bg-brand text-white px-5 py-1.5 rounded-lg text-sm font-medium hover:bg-brand-dark transition-colors inline-flex items-center shadow-sm">
                <i data-lucide="save" class="w-4 h-4 mr-2"></i> Simpan
            </button>
        </div>
    </header>

    <div class="flex-1 flex overflow-hidden">
        
        <!-- Tab: Settings & Pixel -->
        <div v-if="builderTab === 'settings'" class="w-full max-w-3xl mx-auto p-4 md:p-8 overflow-y-auto">
            <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-slate-200 space-y-8">
                
                <div v-if="isAdmin" class="space-y-4">
                    <h3 class="text-lg font-bold text-slate-800 border-b pb-3">Admin: Assign Advertiser</h3>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase mb-2">Pilih Advertiser</label>
                        <select v-model="editingPage.user_id" class="w-full border border-slate-200 rounded-lg px-4 py-3 focus:border-brand focus:ring-1 focus:ring-brand outline-none bg-white">
                            <option value="0">-- Default (Admin) --</option>
                            <option v-for="adv in advertisers" :key="adv.id" :value="adv.id">{{ adv.name }}</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-lg font-bold text-slate-800 border-b pb-3">Informasi Produk</h3>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase mb-2">Pilih Produk</label>
                        <select v-model="editingPage.product_id" class="w-full border border-slate-200 rounded-lg px-4 py-3 focus:border-brand focus:ring-1 focus:ring-brand outline-none bg-white">
                            <option value="0">-- Pilih Produk --</option>
                            <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} (Rp {{ new Intl.NumberFormat('id-ID').format(p.price) }})</option>
                        </select>
                        <p class="text-xs text-red-500 mt-1" v-if="editingPage.product_id == 0">Produk wajib dipilih agar form checkout bisa digunakan.</p>
                    </div>
                </div>

                <div class="space-y-4 pt-2" v-if="pixelConfig">
                    <h3 class="text-lg font-bold text-slate-800 border-b pb-3">Pixel Tracking (Per Form)</h3>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase mb-2">Meta/FB Pixel ID</label>
                        <input type="text" v-model="pixelConfig.meta_id" class="w-full border border-slate-200 rounded-lg px-4 py-3 focus:border-brand focus:ring-1 focus:ring-brand outline-none" placeholder="Misal: 123456789012345">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase mb-2">Tiktok Pixel ID</label>
                        <input type="text" v-model="pixelConfig.tiktok_id" class="w-full border border-slate-200 rounded-lg px-4 py-3 focus:border-brand focus:ring-1 focus:ring-brand outline-none" placeholder="Misal: C1234567890">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase mb-2">Event Checkout (Meta/FB)</label>
                            <select v-model="pixelConfig.event_checkout" class="w-full border border-slate-200 rounded-lg px-4 py-3 focus:border-brand focus:ring-1 focus:ring-brand outline-none bg-white mb-1">
                                <option value="PageView">PageView</option>
                                <option value="ViewContent">ViewContent</option>
                                <option value="AddToCart">AddToCart</option>
                                <option value="InitiateCheckout">InitiateCheckout (Disarankan)</option>
                            </select>
                            <p class="text-[11px] text-slate-500">Memicu event saat form order terlihat di layar.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase mb-2">Event Purchase (Meta/FB)</label>
                            <select v-model="pixelConfig.event_purchase" class="w-full border border-slate-200 rounded-lg px-4 py-3 focus:border-brand focus:ring-1 focus:ring-brand outline-none bg-white mb-1">
                                <option value="Purchase">Purchase (Disarankan)</option>
                                <option value="Lead">Lead</option>
                                <option value="CompleteRegistration">CompleteRegistration</option>
                                <option value="AddPaymentInfo">AddPaymentInfo</option>
                            </select>
                            <p class="text-[11px] text-slate-500">Memicu event saat customer berhasil order/bayar.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase mb-2">Event Checkout (TikTok)</label>
                            <select v-model="pixelConfig.tiktok_event_checkout" class="w-full border border-slate-200 rounded-lg px-4 py-3 focus:border-brand focus:ring-1 focus:ring-brand outline-none bg-white mb-1">
                                <option value="PageView">PageView</option>
                                <option value="ViewContent">ViewContent</option>
                                <option value="InitiateCheckout">InitiateCheckout (Disarankan)</option>
                                <option value="AddToCart">AddToCart</option>
                            </select>
                            <p class="text-[11px] text-slate-500">Memicu event saat form order terlihat di layar.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase mb-2">Event Purchase (TikTok)</label>
                            <select v-model="pixelConfig.tiktok_event_purchase" class="w-full border border-slate-200 rounded-lg px-4 py-3 focus:border-brand focus:ring-1 focus:ring-brand outline-none bg-white mb-1">
                                <option value="CompletePayment">CompletePayment (Disarankan)</option>
                                <option value="SubmitForm">SubmitForm</option>
                                <option value="PlaceAnOrder">PlaceAnOrder</option>
                            </select>
                            <p class="text-[11px] text-slate-500">Memicu event saat customer berhasil order/bayar.</p>
                        </div>
                    </div>

                    <div class="mt-6 p-4 bg-blue-50/50 border border-blue-100 rounded-xl">
                        <h4 class="text-sm font-bold text-blue-800 mb-2 flex items-center">
                            <i data-lucide="info" class="w-4 h-4 mr-2"></i> Rekomendasi Flow Ads
                        </h4>
                        <ul class="text-xs text-blue-700 space-y-2 list-disc pl-4">
                            <li>Gunakan <strong>InitiateCheckout</strong> untuk event form view. Ini membantu algoritma mencari audiens yang punya intensi membeli (warm audience).</li>
                            <li>Gunakan <strong>Purchase / CompletePayment</strong> sebagai Objective Utama saat membuat campaign Conversion.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: Design (Builder) -->
        <div v-if="builderTab === 'design'" class="flex w-full h-full relative overflow-hidden">
            <!-- Canvas -->
            <div class="flex-1 p-4 md:p-8 flex justify-center items-center bg-slate-100 overflow-hidden">
                <div class="w-full max-w-md bg-white shadow-2xl h-full max-h-[850px] border border-slate-200 relative overflow-hidden flex flex-col" style="border-radius: 40px; border: 8px solid #1e293b;">
                    <!-- Notch -->
                    <div class="absolute top-0 inset-x-0 h-6 bg-[#1e293b] rounded-b-xl w-32 mx-auto z-50"></div>
                    
                    <div class="p-4 pt-10 space-y-4 flex-1 overflow-y-auto relative pb-24 scroll-smooth">
                        <div v-if="blocks.length === 0" class="text-center py-20 text-slate-400">
                            <i data-lucide="layout-template" class="w-12 h-12 mx-auto mb-3 opacity-50"></i>
                            <p>Canvas kosong. Tambahkan blok dari panel kanan.</p>
                        </div>
                        
                        <div v-for="(block, index) in blocks" :key="index" class="group border-2 border-transparent hover:border-brand/30 rounded-lg p-1 -m-1 transition-colors cursor-pointer" :class="block.type === 'sticky_button' ? 'sticky bottom-4 z-40' : 'relative'" @click="activeBlock = index">
                            <!-- Block Controls -->
                            <div class="absolute top-0 right-0 -translate-y-full opacity-0 group-hover:opacity-100 transition-opacity bg-brand text-white text-xs rounded-t-lg flex">
                                <button @click.stop="duplicateBlock(index)" class="p-1.5 hover:bg-white/20" title="Duplicate"><i data-lucide="copy" class="w-3 h-3"></i></button>
                                <button @click.stop="moveBlock(index, -1)" class="p-1.5 hover:bg-white/20" title="Move Up"><i data-lucide="chevron-up" class="w-3 h-3"></i></button>
                                <button @click.stop="moveBlock(index, 1)" class="p-1.5 hover:bg-white/20" title="Move Down"><i data-lucide="chevron-down" class="w-3 h-3"></i></button>
                                <button v-if="block.type !== 'checkout_form'" @click.stop="deleteBlock(index)" class="p-1.5 hover:bg-red-500 rounded-tr-lg" title="Delete"><i data-lucide="trash-2" class="w-3 h-3"></i></button>
                            </div>
                            
                            <!-- Render Content -->
                            <div v-if="block.type === 'heading'" :class="block.align" class="font-bold text-slate-800" :style="{ fontSize: block.size + 'px', color: block.color || '#1e293b' }" v-html="block.content.replace(/\n/g, '<br>')"></div>
                            
                            <div v-if="block.type === 'paragraph'" :class="block.align" class="text-slate-600" :style="{ fontSize: block.size + 'px', color: block.color || '#475569' }" v-html="block.content.replace(/\n/g, '<br>')"></div>
                            
                            <div v-if="block.type === 'image'" class="rounded-xl overflow-hidden shadow-sm">
                                <img :src="block.url || 'https://via.placeholder.com/400x300?text=Upload+Gambar'" class="w-full h-auto">
                            </div>
                            
                            <div v-if="block.type === 'video'" class="rounded-xl overflow-hidden shadow-sm aspect-video bg-black flex items-center justify-center relative">
                                <template v-if="block.url">
                                    <iframe v-if="block.url.includes('youtube.com') || block.url.includes('youtu.be')" 
                                        :src="'https://www.youtube.com/embed/' + (block.url.split('v=')[1] || block.url.split('youtu.be/')[1] || '').split('&')[0]" 
                                        class="absolute inset-0 w-full h-full" frameborder="0" allowfullscreen></iframe>
                                    <iframe v-else-if="block.url.includes('wistia')" 
                                        :src="block.url" 
                                        class="absolute inset-0 w-full h-full" frameborder="0" allowfullscreen></iframe>
                                    <div v-else class="text-white text-xs p-4 text-center">URL video tidak dikenali (Gunakan Youtube/Wistia)</div>
                                </template>
                                <template v-else>
                                    <i data-lucide="play-circle" class="w-12 h-12 text-white/50"></i>
                                </template>
                            </div>

                            <div v-if="block.type === 'html'" :class="[block.zero_padding ? 'w-full' : 'max-w-2xl mx-auto']">
                                <div class="bg-slate-100 border-2 border-dashed border-slate-300 p-8 text-center text-slate-500 rounded-xl">
                                    <i data-lucide="code" class="w-8 h-8 mx-auto mb-2 text-slate-400"></i>
                                    <div class="font-medium">Custom HTML Block</div>
                                    <div class="text-xs mt-1">Preview dinonaktifkan di editor untuk mencegah konflik style.</div>
                                </div>
                            </div>
                            
                            <div v-if="block.type === 'checkout_form'" class="max-w-lg mx-auto p-5 rounded-2xl border border-slate-200 mt-4 shadow-sm" :style="{ backgroundColor: block.bg_color || '#f8fafc' }" id="checkout-section">
                                <h4 class="font-bold text-slate-800 mb-4 text-center text-lg">Form Pemesanan</h4>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-xs font-semibold mb-1 text-slate-700">Nama Lengkap</label>
                                        <input type="text" placeholder="Masukkan nama lengkap Anda" class="w-full p-3 border border-slate-200 rounded-xl text-sm pointer-events-none bg-white">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold mb-1 text-slate-700">Nomor WhatsApp</label>
                                        <input type="text" placeholder="Contoh: 081234567890" class="w-full p-3 border border-slate-200 rounded-xl text-sm pointer-events-none bg-white">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold mb-1 text-slate-700">Email Valid</label>
                                        <input type="email" placeholder="Contoh: email@anda.com" class="w-full p-3 border border-slate-200 rounded-xl text-sm pointer-events-none bg-white">
                                    </div>
                                    <div class="bg-white p-3 rounded-xl border border-slate-200 mt-4">
                                        <div class="mb-2 pb-2 border-b border-slate-100">
                                            <div class="text-xs text-slate-500 font-medium">Produk:</div>
                                            <div class="font-bold text-slate-800 text-sm">{{ products.find(p => p.id == editingPage.product_id)?.name || 'Pilih Produk' }}</div>
                                        </div>
                                        <div class="pt-1 font-bold flex justify-between items-center text-slate-800">
                                            <span class="text-sm">Total:</span>
                                            <div class="text-right">
                                                <div v-if="products.find(p => p.id == editingPage.product_id)?.price_coret > 0" class="text-xs text-slate-400 font-normal">
                                                    <span class="text-red-500 font-bold mr-1">Promo</span>
                                                    <span class="line-through">Rp {{ new Intl.NumberFormat('id-ID').format(products.find(p => p.id == editingPage.product_id).price_coret) }}</span>
                                                </div>
                                                <div class="text-lg text-brand">
                                                    Rp {{ products.find(p => p.id == editingPage.product_id)?.price ? new Intl.NumberFormat('id-ID').format(products.find(p => p.id == editingPage.product_id).price) : '0' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button class="w-full text-white py-3 mt-2 rounded-xl font-extrabold pointer-events-none text-base transition-colors shadow-lg" :style="{ backgroundColor: block.color || '#10b981' }">
                                        {{ block.button_text }}
                                    </button>
                                </div>
                            </div>
                            
                            <div v-if="block.type === 'sticky_button'" class="w-full relative mt-0">
                                <div class="bg-white border border-slate-200 shadow-[0_10px_25px_-5px_rgba(0,0,0,0.15)] rounded-2xl p-3 flex items-center justify-between">
                                    <div class="flex flex-col">
                                        <div v-if="products.find(p => p.id == editingPage.product_id)?.price_coret > 0" class="text-[11px] text-slate-400 font-normal">
                                            <span class="text-red-500 font-bold mr-1">Promo</span>
                                            <span class="line-through">Rp {{ new Intl.NumberFormat('id-ID').format(products.find(p => p.id == editingPage.product_id).price_coret) }}</span>
                                        </div>
                                        <div class="text-base font-bold text-brand leading-none mt-0.5">
                                            Rp {{ products.find(p => p.id == editingPage.product_id)?.price ? new Intl.NumberFormat('id-ID').format(products.find(p => p.id == editingPage.product_id).price) : '0' }}
                                        </div>
                                    </div>
                                    <button class="text-white px-4 py-2 rounded-xl font-bold transition-colors shadow-md text-sm" :style="{ backgroundColor: block.color || '#10b981' }">
                                        {{ block.button_text }}
                                    </button>
                                </div>
                            </div>

                            <!-- Selection Highlight -->
                            <div v-if="activeBlock === index" class="absolute inset-0 border-2 border-brand rounded-lg pointer-events-none"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar Editor -->
            <div class="w-80 bg-white border-l border-slate-200 flex flex-col shrink-0">
                <div class="p-4 border-b font-bold text-slate-800 flex justify-between items-center bg-slate-50">
                    <span>Editor Blok</span>
                    <button v-if="activeBlock !== null" @click="activeBlock = null" class="text-xs text-brand hover:underline">Tutup</button>
                </div>
                
                <div class="flex-1 overflow-y-auto p-4 space-y-4">
                    <!-- Add Blocks Panel -->
                    <div v-if="activeBlock === null" class="space-y-2">
                        <div class="text-xs font-semibold text-slate-500 uppercase mb-3 px-1">Tambah Elemen</div>
                        <button @click="addBlock({type: 'heading', content: 'Judul Baru', size: 24, align: 'text-left', color: '#1e293b'})" class="w-full flex items-center p-3 border rounded-xl hover:border-brand hover:text-brand transition-colors text-left text-sm text-slate-700 bg-white hover:bg-brand/5 shadow-sm">
                            <i data-lucide="heading-1" class="w-4 h-4 mr-3"></i> Heading
                        </button>
                        <button @click="addBlock({type: 'paragraph', content: 'Teks deskripsi Anda di sini...', size: 16, align: 'text-left', color: '#475569'})" class="w-full flex items-center p-3 border rounded-xl hover:border-brand hover:text-brand transition-colors text-left text-sm text-slate-700 bg-white hover:bg-brand/5 shadow-sm">
                            <i data-lucide="align-left" class="w-4 h-4 mr-3"></i> Paragraf
                        </button>
                        <button @click="addBlock({type: 'image', url: ''})" class="w-full flex items-center p-3 border rounded-xl hover:border-brand hover:text-brand transition-colors text-left text-sm text-slate-700 bg-white hover:bg-brand/5 shadow-sm">
                            <i data-lucide="image" class="w-4 h-4 mr-3"></i> Gambar
                        </button>
                        <button @click="addBlock({type: 'video', url: ''})" class="w-full flex items-center p-3 border rounded-xl hover:border-brand hover:text-brand transition-colors text-left text-sm text-slate-700 bg-white hover:bg-brand/5 shadow-sm">
                            <i data-lucide="youtube" class="w-4 h-4 mr-3"></i> Video
                        </button>
                        <button @click="addBlock({type: 'html', content: '<!-- Custom HTML -->' })" class="w-full flex items-center p-3 border rounded-xl hover:border-brand hover:text-brand transition-colors text-left text-sm text-slate-700 bg-white hover:bg-brand/5 shadow-sm">
                            <i data-lucide="code" class="w-4 h-4 mr-3"></i> Custom HTML
                        </button>
                        <button @click="addBlock({type: 'sticky_button', button_text: 'Beli Sekarang', color: '#10b981' })" class="w-full flex items-center p-3 border rounded-xl hover:border-brand hover:text-brand transition-colors text-left text-sm text-slate-700 bg-white hover:bg-brand/5 shadow-sm">
                            <i data-lucide="mouse-pointer-click" class="w-4 h-4 mr-3"></i> Sticky Button
                        </button>
                    </div>
                    
                    <!-- Edit Block Panel -->
                    <div v-else class="space-y-4">
                        <div class="text-xs font-semibold text-brand uppercase mb-3 flex items-center bg-brand/10 p-2 rounded-lg">
                            <i data-lucide="edit-3" class="w-3 h-3 mr-2"></i> Edit {{ blocks[activeBlock].type }}
                        </div>
                        
                        <div v-if="blocks[activeBlock].type === 'heading' || blocks[activeBlock].type === 'paragraph'">
                            <label class="block text-xs font-medium text-slate-600 mb-1">Konten (Mendukung enter)</label>
                            <textarea v-model="blocks[activeBlock].content" class="w-full p-3 border rounded-xl text-sm min-h-[120px] outline-none focus:border-brand focus:ring-1 focus:ring-brand"></textarea>
                            
                            <div class="grid grid-cols-2 gap-3 mt-3">
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Ukuran Font (px)</label>
                                    <input type="number" v-model="blocks[activeBlock].size" class="w-full p-2.5 border rounded-lg text-sm outline-none focus:border-brand">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Warna</label>
                                    <input type="color" v-model="blocks[activeBlock].color" class="w-full h-10 p-1 border rounded-lg outline-none cursor-pointer">
                                </div>
                            </div>
                            
                            <label class="block text-xs font-medium text-slate-600 mt-3 mb-1">Perataan Teks</label>
                            <select v-model="blocks[activeBlock].align" class="w-full p-2.5 border rounded-lg text-sm outline-none focus:border-brand bg-white">
                                <option value="text-left">Kiri</option>
                                <option value="text-center">Tengah</option>
                                <option value="text-right">Kanan</option>
                            </select>
                        </div>
                        
                        <div v-if="blocks[activeBlock].type === 'image'">
                            <label class="block text-xs font-medium text-slate-600 mb-2">Upload Gambar (Max 200kb)</label>
                            <div class="border-2 border-dashed border-slate-300 rounded-xl p-6 flex flex-col items-center justify-center hover:border-brand hover:bg-brand/5 transition-colors cursor-pointer relative">
                                <input type="file" accept="image/*" @change="uploadImage($event, activeBlock)" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
                                <i data-lucide="upload-cloud" class="w-8 h-8 text-slate-400 mb-2"></i>
                                <span class="text-xs text-slate-500 font-medium">Klik untuk upload</span>
                            </div>
                            <div v-if="blocks[activeBlock].url" class="mt-3 relative rounded-lg overflow-hidden border">
                                <img :src="blocks[activeBlock].url" class="w-full h-auto">
                            </div>
                        </div>

                        <div v-if="blocks[activeBlock].type === 'video'">
                            <label class="block text-xs font-medium text-slate-600 mb-1">URL Youtube / Wistia</label>
                            <input type="text" v-model="blocks[activeBlock].url" placeholder="https://www.youtube.com/watch?v=..." class="w-full p-3 border rounded-xl text-sm outline-none focus:border-brand">
                        </div>

                        <div v-if="blocks[activeBlock].type === 'html'">
                            <label class="block text-xs font-medium text-slate-600 mb-1">HTML Code</label>
                            <textarea v-model="blocks[activeBlock].content" class="w-full p-3 border rounded-xl text-sm min-h-[200px] outline-none focus:border-brand font-mono bg-slate-900 text-green-400"></textarea>
                            
                            <label class="flex items-center mt-3 cursor-pointer">
                                <input type="checkbox" v-model="blocks[activeBlock].zero_padding" class="w-4 h-4 text-brand rounded border-slate-300 focus:ring-brand">
                                <span class="ml-2 text-xs font-medium text-slate-600">Zero Padding & Margin (Full Width)</span>
                            </label>
                        </div>
                        
                        <div v-if="blocks[activeBlock].type === 'checkout_form'">
                            <label class="block text-xs font-medium text-slate-600 mb-1">Teks Tombol</label>
                            <input type="text" v-model="blocks[activeBlock].button_text" class="w-full p-3 border rounded-xl text-sm outline-none focus:border-brand">
                            
                            <div class="grid grid-cols-2 gap-3 mt-3">
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Warna Latar Form</label>
                                    <input type="color" v-model="blocks[activeBlock].bg_color" class="w-full h-10 p-1 border rounded-lg outline-none cursor-pointer">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Warna Tombol</label>
                                    <input type="color" v-model="blocks[activeBlock].color" class="w-full h-10 p-1 border rounded-lg outline-none cursor-pointer">
                                </div>
                            </div>
                        </div>

                        <div v-if="blocks[activeBlock].type === 'sticky_button'">
                            <label class="block text-xs font-medium text-slate-600 mb-1">Teks Tombol</label>
                            <input type="text" v-model="blocks[activeBlock].button_text" class="w-full p-3 border rounded-xl text-sm outline-none focus:border-brand">
                            
                            <label class="block text-xs font-medium text-slate-600 mt-3 mb-1">Warna Tombol</label>
                            <input type="color" v-model="blocks[activeBlock].color" class="w-full h-10 p-1 border rounded-lg outline-none cursor-pointer">

                            <div class="mt-4 p-3 bg-blue-50 border border-blue-100 rounded-xl">
                                <label class="block text-xs font-bold text-blue-800 mb-1">Info Anchor Form Pemesanan</label>
                                <p class="text-xs text-blue-600 mb-2">Gunakan link anchor ini jika ingin mengarahkan button (Custom HTML) ke form checkout:</p>
                                <div class="flex items-center space-x-2">
                                    <code class="flex-1 bg-white px-2 py-1.5 rounded border border-blue-200 text-xs text-slate-700 select-all">#checkout-section</code>
                                    <button @click="copyText('#checkout-section')" class="p-1.5 bg-white border border-blue-200 rounded text-blue-600 hover:bg-blue-100 transition-colors" title="Copy Anchor">
                                        <i data-lucide="copy" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
