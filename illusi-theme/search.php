<?php
// illusi-theme/search.php
get_header(); ?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <?php illusi_breadcrumbs(); ?>

    <header class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900">
            Hasil Pencarian: <span class="text-brand">"<?php echo get_search_query(); ?>"</span>
        </h1>
        <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" class="mt-4 flex gap-2 max-w-md">
            <input type="search" name="s" value="<?php echo get_search_query(); ?>"
                   class="flex-1 border border-slate-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand"
                   placeholder="Cari...">
            <button type="submit" class="bg-brand text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-brand/90">Cari</button>
        </form>
    </header>

    <?php if ( have_posts() ) : ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php while ( have_posts() ) : the_post(); ?>
            <?php get_template_part('template-parts/content', 'loop'); ?>
        <?php endwhile; ?>
    </div>
    <div class="mt-10"><?php illusi_pagination(); ?></div>
    <?php else : ?>
    <div class="text-center py-20">
        <p class="text-slate-500 mb-4">Tidak ada hasil untuk "<?php echo get_search_query(); ?>".</p>
        <a href="<?php echo home_url(); ?>" class="text-brand hover:underline">Kembali ke Beranda</a>
    </div>
    <?php endif; ?>
</div>

<?php get_footer();
