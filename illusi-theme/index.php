<?php
// illusi-theme/index.php — Homepage / Blog loop fallback
get_header(); ?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <?php illusi_breadcrumbs(); ?>

    <?php if ( is_home() && ! is_front_page() ) : ?>
        <h1 class="text-3xl font-bold mb-8">Blog</h1>
    <?php endif; ?>

    <?php if ( have_posts() ) : ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php while ( have_posts() ) : the_post(); ?>
                <?php get_template_part('template-parts/content', 'loop'); ?>
            <?php endwhile; ?>
        </div>
        <div class="mt-10">
            <?php illusi_pagination(); ?>
        </div>
    <?php else : ?>
        <div class="text-center py-20">
            <p class="text-slate-500">Tidak ada konten ditemukan.</p>
            <a href="<?php echo home_url(); ?>" class="mt-4 inline-block text-brand hover:underline">Kembali ke Beranda</a>
        </div>
    <?php endif; ?>
</div>

<?php get_footer();
