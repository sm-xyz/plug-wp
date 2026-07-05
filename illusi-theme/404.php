<?php
// illusi-theme/404.php
get_header(); ?>

<div class="max-w-2xl mx-auto px-4 text-center py-24">
    <div class="text-8xl font-black text-slate-100 select-none mb-4">404</div>
    <h1 class="text-2xl font-bold text-slate-900 mb-3">Halaman Tidak Ditemukan</h1>
    <p class="text-slate-500 mb-8">Halaman yang Anda cari tidak ada atau telah dipindahkan.</p>
    <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <a href="<?php echo home_url(); ?>"
           class="bg-brand text-white font-semibold px-6 py-3 rounded-lg hover:bg-brand/90 transition-colors">
            Kembali ke Beranda
        </a>
        <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" class="flex gap-2">
            <input type="search" name="s" placeholder="Cari halaman..."
                   class="border border-slate-300 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/20 w-48">
            <button type="submit" class="border border-slate-300 px-4 py-3 rounded-lg text-sm hover:bg-slate-50">Cari</button>
        </form>
    </div>
</div>

<?php get_footer();
