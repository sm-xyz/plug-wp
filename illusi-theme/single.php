<?php
// illusi-theme/single.php — Single Post
get_header(); ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <?php illusi_breadcrumbs(); ?>

    <?php while ( have_posts() ) : the_post(); ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class(''); ?>>

        <!-- Header -->
        <header class="mb-8">
            <?php
            $cats = get_the_category();
            if ( $cats ) :
                echo '<div class="flex flex-wrap gap-2 mb-4">';
                foreach ( $cats as $cat ) :
                    echo '<a href="' . esc_url(get_category_link($cat->term_id)) . '"
                            class="text-xs font-semibold uppercase tracking-wide text-brand hover:underline">'
                        . esc_html($cat->name) . '</a>';
                endforeach;
                echo '</div>';
            endif;
            ?>
            <h1 class="text-3xl md:text-4xl font-bold text-slate-900 leading-tight mb-4"><?php the_title(); ?></h1>
            <div class="flex items-center gap-4 text-sm text-slate-500 flex-wrap">
                <span><?php echo get_the_date(); ?></span>
                <span>·</span>
                <span><?php echo illusi_reading_time(); ?></span>
                <span>·</span>
                <span>Oleh <?php the_author(); ?></span>
            </div>
        </header>

        <!-- Featured Image -->
        <?php if ( has_post_thumbnail() ) : ?>
        <div class="mb-8 rounded-2xl overflow-hidden">
            <?php the_post_thumbnail('illusi-wide', ['class' => 'w-full h-auto', 'loading' => 'eager']); ?>
        </div>
        <?php endif; ?>

        <!-- Content -->
        <div class="prose prose-slate max-w-none">
            <?php the_content(); ?>
        </div>

        <!-- Tags -->
        <?php $tags = get_the_tags(); if ( $tags ) : ?>
        <div class="mt-8 flex flex-wrap gap-2">
            <?php foreach ( $tags as $tag ) : ?>
                <a href="<?php echo esc_url(get_tag_link($tag->term_id)); ?>"
                   class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-1 rounded-full transition-colors">
                    #<?php echo esc_html($tag->name); ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Post Navigation -->
        <nav class="mt-10 flex justify-between gap-4 border-t border-slate-200 pt-6">
            <?php
            $prev = get_previous_post();
            $next = get_next_post();
            ?>
            <div class="flex-1">
                <?php if ( $prev ) : ?>
                <a href="<?php echo get_permalink($prev); ?>" class="group flex flex-col text-sm">
                    <span class="text-slate-400 mb-1">&larr; Sebelumnya</span>
                    <span class="font-semibold text-slate-800 group-hover:text-brand transition-colors"><?php echo get_the_title($prev); ?></span>
                </a>
                <?php endif; ?>
            </div>
            <div class="flex-1 text-right">
                <?php if ( $next ) : ?>
                <a href="<?php echo get_permalink($next); ?>" class="group flex flex-col items-end text-sm">
                    <span class="text-slate-400 mb-1">Berikutnya &rarr;</span>
                    <span class="font-semibold text-slate-800 group-hover:text-brand transition-colors"><?php echo get_the_title($next); ?></span>
                </a>
                <?php endif; ?>
            </div>
        </nav>
    </article>

    <!-- Comments -->
    <?php if ( comments_open() || get_comments_number() ) : ?>
    <div class="mt-10 pt-8 border-t border-slate-200">
        <?php comments_template(); ?>
    </div>
    <?php endif; ?>

    <?php endwhile; ?>
</div>

<?php get_footer();
