<?php
if (!defined('ABSPATH')) exit;
// $page is passed from router

$blocks = json_decode($page->builder_data, true) ?: [];
$pixels = json_decode($page->pixel_data, true) ?: [];

$meta_id = $pixels['meta_id'] ?? '';
$tiktok_id = $pixels['tiktok_id'] ?? '';
$event_checkout = $pixels['event_checkout'] ?? 'InitiateCheckout';
$event_purchase = $pixels['event_purchase'] ?? 'Purchase';
$tiktok_event_checkout = $pixels['tiktok_event_checkout'] ?? 'InitiateCheckout';
$tiktok_event_purchase = $pixels['tiktok_event_purchase'] ?? 'CompletePayment';

$is_success = isset($_GET['success']) && $_GET['success'] == 1;
$is_test = isset($_GET['test_pixel']) && $_GET['test_pixel'] == 1;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($page->title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Pixels Setup -->
    <?php if ($meta_id): ?>
    <script>
    <?php if ($is_test): ?>console.log('Testing Meta Pixel: Init <?php echo esc_js($meta_id); ?>');<?php endif; ?>
    !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '<?php echo esc_js($meta_id); ?>');
    fbq('track', 'PageView');
    <?php if ($is_success): ?>
    <?php if ($is_test): ?>console.log('Testing Meta Pixel: Track <?php echo esc_js($event_purchase); ?>');<?php endif; ?>
    fbq('track', '<?php echo esc_js($event_purchase); ?>', {value: <?php echo floatval($page->product_price); ?>, currency: 'IDR'});
    <?php endif; ?>
    </script>
    <?php endif; ?>
    
    <?php if ($tiktok_id): ?>
    <script>
    <?php if ($is_test): ?>console.log('Testing TikTok Pixel: Load <?php echo esc_js($tiktok_id); ?>');<?php endif; ?>
    !function (w, d, t) {
      w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"];ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e};ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};n=document.createElement("script");n.type="text/javascript",n.async=!0,n.src=i+"?sdkid="+e+"&lib="+t;e=document.getElementsByTagName("script")[0];e.parentNode.insertBefore(n,e)};
      ttq.load('<?php echo esc_js($tiktok_id); ?>');
      ttq.page();
      <?php if ($is_success): ?>
      <?php if ($is_test): ?>console.log('Testing TikTok Pixel: Track <?php echo esc_js($tiktok_event_purchase); ?>');<?php endif; ?>
      ttq.track('<?php echo esc_js($tiktok_event_purchase); ?>', {value: <?php echo floatval($page->product_price); ?>, currency: 'IDR'});
      <?php endif; ?>
    }(window, document, 'ttq');
    </script>
    <?php endif; ?>
    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-up { animation: fadeInUp 0.5s ease-out forwards; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased selection:bg-brand/20">

    <div class="bg-white min-h-screen relative overflow-x-hidden">
        
        <?php if ($is_test): ?>
        <div class="sticky top-0 z-50 bg-amber-400 text-amber-900 px-4 py-2 text-center text-sm font-bold shadow-md flex justify-between items-center max-w-2xl mx-auto">
            <span>MODE TEST PIXEL AKTIF - Cek Console Browser (F12) untuk melihat log.</span>
            <?php if (!$is_success): ?>
            <a href="?test_pixel=1&success=1" class="bg-amber-900 text-amber-400 px-3 py-1 rounded text-xs hover:bg-amber-800 transition-colors">Simulasikan Sukses Bayar (Purchase)</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($is_success): ?>
        <div class="max-w-2xl mx-auto mt-8 p-8 text-center bg-emerald-50 border border-emerald-100 rounded-2xl shadow-sm mx-4 md:mx-auto">
            <i data-lucide="check-circle" class="w-16 h-16 text-emerald-500 mx-auto mb-4"></i>
            <h1 class="text-2xl font-bold text-emerald-800 mb-2">Pembayaran Berhasil!</h1>
            <p class="text-emerald-600">Terima kasih atas pesanan Anda. Akses/Produk akan segera diproses.</p>
        </div>
        <?php endif; ?>

        <div class="w-full flex flex-col items-center">
            <?php foreach ($blocks as $block): ?>
                
                <?php 
                $is_html_zero = ($block['type'] === 'html' && !empty($block['zero_padding'])); 
                $wrapper_class = $is_html_zero ? 'w-full' : 'max-w-2xl w-full mx-auto px-4 md:px-8 py-3';
                ?>
                <div class="<?php echo $wrapper_class; ?>">
                    <?php if ($block['type'] === 'heading'): ?>
                        <div class="<?php echo esc_attr($block['align'] ?? 'text-left'); ?> font-bold" style="font-size: <?php echo intval($block['size'] ?? 24); ?>px; color: <?php echo esc_attr($block['color'] ?? '#1e293b'); ?>">
                            <?php echo nl2br(esc_html($block['content'])); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($block['type'] === 'paragraph'): ?>
                        <div class="<?php echo esc_attr($block['align'] ?? 'text-left'); ?>" style="font-size: <?php echo intval($block['size'] ?? 16); ?>px; color: <?php echo esc_attr($block['color'] ?? '#475569'); ?>">
                            <?php echo nl2br(esc_html($block['content'])); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($block['type'] === 'image'): ?>
                        <div class="rounded-xl overflow-hidden">
                            <img src="<?php echo esc_url($block['url'] ?? ''); ?>" class="w-full h-auto" onerror="this.style.display='none'">
                        </div>
                    <?php endif; ?>

                    <?php if ($block['type'] === 'video' && !empty($block['url'])): ?>
                        <div class="rounded-xl overflow-hidden aspect-video bg-black relative">
                            <?php if (strpos($block['url'], 'youtube.com') !== false || strpos($block['url'], 'youtu.be') !== false): 
                                $ytid = '';
                                if (strpos($block['url'], 'v=') !== false) {
                                    $parts = explode('v=', $block['url']);
                                    $ytid = explode('&', $parts[1])[0];
                                } else {
                                    $parts = explode('youtu.be/', $block['url']);
                                    $ytid = explode('?', $parts[1])[0];
                                }
                            ?>
                                <iframe src="https://www.youtube.com/embed/<?php echo esc_attr($ytid); ?>" class="absolute inset-0 w-full h-full" frameborder="0" allowfullscreen></iframe>
                            <?php elseif (strpos($block['url'], 'wistia') !== false): ?>
                                <iframe src="<?php echo esc_url($block['url']); ?>" class="absolute inset-0 w-full h-full" frameborder="0" allowfullscreen></iframe>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($block['type'] === 'html'): ?>
                        <div class="w-full overflow-hidden">
                            <?php echo $block['content']; // Output raw HTML without wp_kses_post to allow scripts/styles ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($block['type'] === 'checkout_form' && !$is_success): ?>
                        <div class="max-w-lg mx-auto p-5 md:p-6 rounded-2xl border border-slate-200 mt-4 mb-4 shadow-sm" style="background-color: <?php echo esc_attr($block['bg_color'] ?? '#f8fafc'); ?>;" id="checkout-section">
                            <h3 class="font-bold text-lg text-center mb-4 text-slate-800">Form Pemesanan</h3>
                            
                            <form id="checkoutForm" class="space-y-3">
                                <!-- Honeypot -->
                                <input type="text" name="slw_website_url" style="display:none;" value="">
                                <input type="hidden" name="page_id" value="<?php echo esc_attr($page->id); ?>">
                                
                                <div>
                                    <label class="block text-xs font-semibold mb-1 text-slate-700">Nama Lengkap</label>
                                    <input type="text" name="customer_name" placeholder="Masukkan nama lengkap Anda" required class="w-full p-3 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand focus:border-brand outline-none bg-white transition-all shadow-sm">
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-semibold mb-1 text-slate-700">Nomor WhatsApp</label>
                                    <input type="tel" name="customer_wa" id="wa_number" placeholder="Contoh: 081234567890" required class="w-full p-3 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand focus:border-brand outline-none bg-white transition-all shadow-sm">
                                    <p class="text-xs text-red-500 mt-1 hidden" id="wa-error">Nomor WA tidak valid. Pastikan input nomor WA yang aktif.</p>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-semibold mb-1 text-slate-700">Email Valid</label>
                                    <input type="email" id="email" name="customer_email" placeholder="Contoh: email@anda.com" class="w-full p-3 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand focus:border-brand outline-none bg-white transition-all shadow-sm" required>
                                    <p class="text-xs text-red-500 mt-1 hidden" id="email-error"></p>
                                </div>
                                
                                <div class="bg-white p-3 rounded-xl border border-slate-200 mt-4 shadow-sm">
                                    <div class="mb-2 pb-2 border-b border-slate-100">
                                        <div class="text-xs text-slate-500 font-medium">Produk:</div>
                                        <div class="font-bold text-slate-800 text-sm"><?php echo esc_html($page->product_name ?? 'Produk ID: ' . $page->product_id); ?></div>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="font-semibold text-slate-600 text-sm">Total:</span>
                                        <div class="text-right">
                                            <?php if (!empty($page->product_price_coret) && (float)$page->product_price_coret > 0): ?>
                                                <div class="text-xs text-slate-400 font-normal">
                                                    <span class="text-red-500 font-bold mr-1">Promo</span>
                                                    <span class="line-through">Rp <?php echo number_format((float)$page->product_price_coret, 0, ',', '.'); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="font-bold text-lg text-brand" id="totalAmount">
                                                Rp <?php echo number_format((float)($page->product_price ?? 0), 0, ',', '.'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="payment-methods-container" class="mt-4 hidden">
                                    <label class="block text-xs font-semibold mb-2 text-slate-700">Metode Pembayaran Otomatis & Tanpa Biaya Admin:</label>
                                    <div id="payment-methods-list" class="grid grid-cols-2 gap-2">
                                        <!-- Dimuat via JS -->
                                    </div>
                                </div>
                                <input type="hidden" name="payment_method" id="selected_payment_method" value="">
                                
                                <button type="submit" id="submit-btn" class="w-full text-white font-extrabold py-3 rounded-xl mt-2 transition-colors text-base shadow-lg" style="background-color: <?php echo esc_attr($block['color'] ?? '#10b981'); ?>">
                                    <?php echo esc_html($block['button_text'] ?? 'Beli Sekarang'); ?>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <?php if ($block['type'] === 'sticky_button' && !$is_success): ?>
                        <div class="fixed bottom-2 md:bottom-3 left-0 right-0 z-50 mx-auto w-full max-w-2xl px-3 md:px-4 animate-fade-in-up">
                            <div class="bg-white border border-slate-200 shadow-[0_10px_25px_-5px_rgba(0,0,0,0.15)] rounded-2xl p-3 flex items-center justify-between">
                                <div class="flex flex-col">
                                    <?php if (!empty($page->product_price_coret) && (float)$page->product_price_coret > 0): ?>
                                        <div class="text-[11px] md:text-xs text-slate-400 font-normal">
                                            <span class="text-red-500 font-bold mr-1">Promo</span>
                                            <span class="line-through">Rp <?php echo number_format((float)$page->product_price_coret, 0, ',', '.'); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="font-extrabold text-base md:text-lg text-brand leading-none mt-0.5">
                                        Rp <?php echo number_format((float)($page->product_price ?? 0), 0, ',', '.'); ?>
                                    </div>
                                </div>
                                <button onclick="document.getElementById('checkout-section').scrollIntoView({behavior: 'smooth'})" class="text-white font-bold py-2 md:py-2.5 px-4 md:px-5 rounded-xl transition-all hover:scale-105 active:scale-95 text-sm shadow-md" style="background-color: <?php echo esc_attr($block['color'] ?? '#10b981'); ?>">
                                    <?php echo esc_html($block['button_text'] ?? 'Beli Sekarang'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <?php 
            $has_sticky = false;
            foreach ($blocks as $b) {
                if ($b['type'] === 'sticky_button') {
                    $has_sticky = true;
                    break;
                }
            }
            if ($has_sticky): 
            ?>
                <!-- Spacer to prevent content hidden behind sticky bar at the bottom of the page -->
                <div class="h-32"></div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        lucide.createIcons();
        
        const form = document.getElementById('checkoutForm');
        const waInput = document.getElementById('wa_number');
        const waError = document.getElementById('wa-error');
        const btnSubmit = document.getElementById('submit-btn');
        
        let isWaValid = false;
        let lastWaChecked = '';
        let waCheckTimeout = null;

        if (waInput) {
            waInput.addEventListener('blur', () => {
                const val = waInput.value.trim();
                if (!val || val === lastWaChecked) return;

                lastWaChecked = val;
                
                waError.classList.remove('hidden');
                waError.classList.remove('text-red-500');
                waError.classList.add('text-slate-500');
                waError.textContent = 'Memeriksa nomor WA...';
                waInput.classList.remove('border-red-500', 'border-green-500');
                waInput.classList.add('border-amber-400');
                if (btnSubmit) btnSubmit.disabled = true;

                clearTimeout(waCheckTimeout);
                waCheckTimeout = setTimeout(() => {
                    const fd = new FormData();
                    fd.append('phone', val);
                    
                    fetch('<?php echo rest_url("adv/v1/wa-check"); ?>', {
                        method: 'POST',
                        body: fd
                    })
                    .then(res => res.json())
                    .then(data => {
                        isWaValid = data.valid;
                        if (isWaValid) {
                            waError.classList.add('hidden');
                            waInput.classList.remove('border-amber-400', 'border-red-500');
                            waInput.classList.add('border-green-500');
                            if (btnSubmit) btnSubmit.disabled = false;
                        } else {
                            waError.classList.remove('hidden', 'text-slate-500');
                            waError.classList.add('text-red-500');
                            waError.textContent = 'Nomor WA tidak valid. Pastikan input nomor WA yang aktif.';
                            waInput.classList.remove('border-amber-400', 'border-green-500');
                            waInput.classList.add('border-red-500');
                            if (btnSubmit) btnSubmit.disabled = true;
                        }
                    })
                    .catch(() => {
                        isWaValid = true; // Fallback
                        waError.classList.add('hidden');
                        waInput.classList.remove('border-amber-400', 'border-red-500');
                        waInput.classList.add('border-slate-300');
                        if (btnSubmit) btnSubmit.disabled = false;
                    });
                }, 300);
            });
        }
        
        // Email Validation
        const emailInput = document.getElementById('email');
        const emailError = document.getElementById('email-error');
        let isEmailValid = false;
        let lastEmailChecked = '';
        
        function isValidEmailFormat(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email);
        }

        if (emailInput) {
            emailInput.addEventListener('blur', () => {
                const val = emailInput.value.trim().toLowerCase();
                
                if (!val) {
                    setEmailStatus('empty');
                    return;
                }
                
                if (!isValidEmailFormat(val)) {
                    setEmailStatus('invalid_format');
                    return;
                }
                
                if (val === lastEmailChecked) return;
                lastEmailChecked = val;
                
                setEmailStatus('checking');
                
                const fd = new FormData();
                fd.append('email', val);
                
                fetch('<?php echo rest_url("adv/v1/email-check"); ?>', {
                    method: 'POST',
                    body: fd
                })
                .then(res => res.json())
                .then(data => {
                    isEmailValid = data.valid;
                    if (!data.valid) {
                        if (data.reason === 'domain_not_found') {
                            setEmailStatus('domain_invalid');
                        } else if (data.reason === 'email_bounced') {
                            setEmailStatus('email_bounced', data.message);
                        } else {
                            setEmailStatus('invalid_format');
                        }
                    } else {
                        setEmailStatus('valid');
                    }
                })
                .catch(() => {
                    isEmailValid = true;
                    setEmailStatus('valid');
                });
            });
        }
        
        function setEmailStatus(state, msg = '') {
            if (!emailInput) return;
            emailInput.classList.remove('border-amber-400', 'border-green-500', 'border-red-500', 'border-slate-300');
            emailError.classList.remove('hidden', 'text-slate-500', 'text-red-500');
            
            if (btnSubmit) btnSubmit.disabled = (state === 'checking' || state.includes('invalid') || state === 'empty' || state === 'email_bounced' || (!isWaValid && waInput && waInput.value.trim() !== ''));

            switch (state) {
                case 'checking':
                    emailInput.classList.add('border-amber-400');
                    emailError.classList.add('text-slate-500');
                    emailError.textContent = 'Memeriksa email...';
                    break;
                case 'invalid_format':
                    emailInput.classList.add('border-red-500');
                    emailError.classList.add('text-red-500');
                    emailError.textContent = 'Format email tidak valid. Contoh: nama@gmail.com';
                    break;
                case 'domain_invalid':
                    emailInput.classList.add('border-red-500');
                    emailError.classList.add('text-red-500');
                    emailError.textContent = 'Domain email tidak dikenali. Pastikan email kamu aktif.';
                    break;
                case 'email_bounced':
                    emailInput.classList.add('border-red-500');
                    emailError.classList.add('text-red-500');
                    emailError.textContent = msg || 'Alamat email tidak aktif atau salah ketik.';
                    break;
                case 'empty':
                    emailInput.classList.add('border-red-500');
                    emailError.classList.add('text-red-500');
                    emailError.textContent = 'Email wajib diisi.';
                    break;
                case 'valid':
                    emailInput.classList.add('border-green-500');
                    emailError.classList.add('hidden');
                    break;
            }
        }

        // Fetch payment methods
        async function loadPaymentMethods() {
            try {
                const amount = <?php echo floatval($page->product_price); ?>;
                if (!amount) return;
                
                const res = await fetch(`<?php echo rest_url("adv/v1/duitku/payment-methods"); ?>?amount=${amount}`);
                const data = await res.json();
                
                if (data.success && data.data.responseCode === "00") {
                    const methods = data.data.paymentFee;
                    if (methods && methods.length > 0) {
                        const container = document.getElementById('payment-methods-container');
                        const list = document.getElementById('payment-methods-list');
                        
                        container.classList.remove('hidden');
                        
                        methods.forEach((method, index) => {
                            // Cuma nampilin method yang valid
                            const el = document.createElement('div');
                            el.className = `border rounded-xl p-2 cursor-pointer flex flex-col items-center justify-center text-center transition-all hover:bg-slate-50 ${index === 0 ? 'border-brand bg-brand/5 ring-1 ring-brand' : 'border-slate-200'}`;
                            el.onclick = () => {
                                document.querySelectorAll('#payment-methods-list > div').forEach(d => {
                                    d.className = 'border border-slate-200 rounded-xl p-2 cursor-pointer flex flex-col items-center justify-center text-center transition-all hover:bg-slate-50';
                                });
                                el.className = 'border border-brand bg-brand/5 ring-1 ring-brand rounded-xl p-2 cursor-pointer flex flex-col items-center justify-center text-center transition-all';
                                document.getElementById('selected_payment_method').value = method.paymentMethod;
                            };
                            
                            el.innerHTML = `
                                <img src="${method.paymentImage}" alt="${method.paymentName}" class="h-8 object-contain mb-1">
                                <div class="text-[10px] font-medium text-slate-700 leading-tight">${method.paymentName}</div>
                            `;
                            
                            list.appendChild(el);
                            
                            // Pilih yang pertama secara default
                            if (index === 0) {
                                document.getElementById('selected_payment_method').value = method.paymentMethod;
                            }
                        });
                    }
                }
            } catch (err) {
                console.error("Gagal load payment method", err);
            }
        }
        
        if (form) {
            loadPaymentMethods();
            
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                if (!isWaValid && waInput && waInput.value.trim() !== '') {
                    waError.classList.remove('hidden', 'text-slate-500');
                    waError.classList.add('text-red-500');
                    waError.textContent = 'Nomor WA tidak valid. Pastikan input nomor WA yang aktif.';
                    waInput.classList.remove('border-amber-400', 'border-green-500');
                    waInput.classList.add('border-red-500');
                    waInput.focus();
                    return;
                }

                if (!isEmailValid && emailInput && emailInput.value.trim() !== '') {
                    setEmailStatus(isValidEmailFormat(emailInput.value.trim().toLowerCase()) ? 'domain_invalid' : 'invalid_format');
                    emailInput.focus();
                    return;
                }
                
                const btn = document.getElementById('submit-btn');
                const originalText = btn.innerText;
                
                btn.disabled = true;
                btn.innerText = 'Memproses...';
                btn.style.opacity = '0.7';
                
                // Fire Checkout Event
                <?php if ($meta_id): ?> 
                <?php if ($is_test): ?>console.log('Testing Meta Pixel: Track <?php echo esc_js($event_checkout); ?>');<?php endif; ?>
                fbq('track', '<?php echo esc_js($event_checkout); ?>', {value: <?php echo floatval($page->product_price); ?>, currency: 'IDR'}); 
                <?php endif; ?>
                
                <?php if ($tiktok_id): ?> 
                <?php if ($is_test): ?>console.log('Testing TikTok Pixel: Track <?php echo esc_js($tiktok_event_checkout); ?>');<?php endif; ?>
                ttq.track('<?php echo esc_js($tiktok_event_checkout); ?>', {value: <?php echo floatval($page->product_price); ?>, currency: 'IDR'}); 
                <?php endif; ?>
                
                try {
                    const fd = new FormData(form);
                    const res = await fetch('<?php echo rest_url("adv/v1/checkout"); ?>', {
                        method: 'POST',
                        body: fd
                    });
                    
                    const data = await res.json();
                    if (data.success) {
                        if (data.redirect_url) {
                            window.location.href = data.redirect_url;
                        } else {
                            window.location.href = window.location.pathname + '?success=1';
                        }
                    } else {
                        alert(data.message || 'Terjadi kesalahan.');
                        btn.disabled = false;
                        btn.innerText = originalText;
                        btn.style.opacity = '1';
                    }
                } catch (err) {
                    alert('Koneksi gagal. Coba lagi.');
                    btn.disabled = false;
                    btn.innerText = originalText;
                    btn.style.opacity = '1';
                }
            });
        }
    </script>
</body>
</html>
