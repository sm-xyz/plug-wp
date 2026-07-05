<?php
// illusi-theme/template-parts/content-loop.php — Post Card
?>
<article id="post-<?php the_ID(); ?>" <?php post_class('bg-white rounded-2xl border border-slate-200 overflow-hidden hover:shadow-md transition-shadow flex flex-col'); ?>>

    <?php if ( has_post_thumbnail() ) : ?>
    <a href="<?php the_permalink(); ?>" class="block aspect-video overflow-hidden" tabindex="-1" aria-hidden="true">
        <?php the_post_thumbnail('illusi-card', [
            'class'   => 'w-full h-full object-cover transition-transform duration-300 hover:scale-105',
            'loading' => 'lazy',
            'decoding'=> 'async',
        ]); ?>
    </a>
    <?php endif; ?>

    <div class="p-5 flex flex-col flex-1">
        <!-- Category -->
        <?php
        $cats = get_the_category();
        if ( $cats ) :
            $cat = $cats[0];
        ?>
        <a href="<?php echo esc_url(get_category_link($cat->term_id)); ?>"
           class="text-xs font-semibold uppercase tracking-wide text-brand hover:underline mb-2 inline-block">
            <?php echo esc_html($cat->name); ?>
        </a>
        <?php endif; ?>

        <!-- Title -->
        <h2 class="text-lg font-bold text-slate-900 leading-tight mb-2">
            <a href="<?php the_permalink(); ?>" class="hover:text-brand transition-colors">
                <?php the_title(); ?>
            </a>
        </h2>

        <!-- Excerpt -->
        <p class="text-sm text-slate-500 leading-relaxed flex-1 mb-4">
            <?php echo wp_trim_words(get_the_excerpt(), 18, '...'); ?>
        </p>

        <!-- Meta -->
        <div class="flex items-center justify-between text-xs text-slate-400 pt-3 border-t border-slate-100">
            <span><?php echo get_the_date('d M Y'); ?></span>
            <span><?php echo illusi_reading_time(); ?></span>
        </div>
    </div>
</article>
