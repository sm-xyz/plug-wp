<?php
// illusi-theme/archive.php
get_header(); ?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <?php illusi_breadcrumbs(); ?>

    <header class="mb-8">
        <h1 class="text-3xl font-bold text-slate-900"><?php the_archive_title(); ?></h1>
        <?php the_archive_description('<p class="mt-2 text-slate-500">', '</p>'); ?>
    </header>

    <?php if ( have_posts() ) : ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php while ( have_posts() ) : the_post(); ?>
            <?php get_template_part('template-parts/content', 'loop'); ?>
        <?php endwhile; ?>
    </div>
    <div class="mt-10"><?php illusi_pagination(); ?></div>
    <?php else : ?>
    <p class="text-slate-500">Tidak ada post dalam arsip ini.</p>
    <?php endif; ?>
</div>

<?php get_footer();
