<?php
// illusi-theme/page.php — Static Page
get_header(); ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <?php illusi_breadcrumbs(); ?>
    <?php while ( have_posts() ) : the_post(); ?>
    <article id="post-<?php the_ID(); ?>" <?php post_class(''); ?>>
        <header class="mb-8">
            <h1 class="text-3xl md:text-4xl font-bold text-slate-900"><?php the_title(); ?></h1>
        </header>
        <?php if ( has_post_thumbnail() ) : ?>
        <div class="mb-8 rounded-2xl overflow-hidden">
            <?php the_post_thumbnail('illusi-wide', ['class' => 'w-full h-auto']); ?>
        </div>
        <?php endif; ?>
        <div class="prose prose-slate max-w-none"><?php the_content(); ?></div>
        <?php
        wp_link_pages(['before' => '<nav class="mt-6 flex gap-2"><span class="font-semibold">Halaman:</span>', 'after' => '</nav>']);
        ?>
    </article>
    <?php endwhile; ?>
</div>

<?php get_footer();
