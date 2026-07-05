<!DOCTYPE html>
<html <?php language_attributes(); ?> class="scroll-smooth">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class('bg-white text-slate-800 antialiased'); ?>>
<?php wp_body_open(); ?>

<a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-brand text-white px-4 py-2 rounded z-50">Skip to content</a>

<header id="site-header" class="sticky top-0 z-40 bg-white/90 backdrop-blur border-b border-slate-200">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">

            <!-- Logo -->
            <div class="flex-shrink-0">
                <?php if ( has_custom_logo() ) : ?>
                    <?php the_custom_logo(); ?>
                <?php else : ?>
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="text-xl font-bold text-slate-900 hover:text-brand transition-colors">
                        <?php bloginfo('name'); ?>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Desktop Nav -->
            <?php if ( has_nav_menu('primary') ) : ?>
            <nav class="hidden md:flex items-center gap-6" aria-label="Menu Utama">
                <?php wp_nav_menu([
                    'theme_location' => 'primary',
                    'container'      => false,
                    'menu_class'     => 'flex items-center gap-6 list-none m-0 p-0',
                    'fallback_cb'    => false,
                    'depth'          => 2,
                ]); ?>
            </nav>
            <?php endif; ?>

            <!-- Dark mode toggle + Mobile hamburger -->
            <div class="flex items-center gap-2">
                <button id="dark-toggle" aria-label="Toggle Dark Mode"
                        class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m8.66-10H21M3 12H2m15.07-7.07l-.71.71M6.64 17.36l-.71.71M17.66 17.66l-.71-.71M6.36 6.64l-.71-.71M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                </button>
                <button id="mobile-menu-btn" class="md:hidden p-2 rounded-lg hover:bg-slate-100" aria-label="Menu">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
        </div>

        <!-- Mobile Nav -->
        <div id="mobile-menu" class="md:hidden hidden py-4 border-t border-slate-100">
            <?php if ( has_nav_menu('mobile') ) : ?>
            <?php wp_nav_menu([
                'theme_location' => 'mobile',
                'container'      => false,
                'menu_class'     => 'flex flex-col gap-3 list-none m-0 p-0',
                'fallback_cb'    => false,
            ]); ?>
            <?php elseif ( has_nav_menu('primary') ) : ?>
            <?php wp_nav_menu([
                'theme_location' => 'primary',
                'container'      => false,
                'menu_class'     => 'flex flex-col gap-3 list-none m-0 p-0',
                'fallback_cb'    => false,
            ]); ?>
            <?php endif; ?>
        </div>
    </div>
</header>

<main id="main-content">
