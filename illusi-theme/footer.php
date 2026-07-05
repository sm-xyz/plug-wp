</main><!-- #main-content -->

<footer class="bg-slate-900 text-slate-300 mt-16">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">

            <!-- Brand -->
            <div>
                <h3 class="text-white font-bold text-lg mb-3"><?php bloginfo('name'); ?></h3>
                <p class="text-sm text-slate-400 leading-relaxed"><?php bloginfo('description'); ?></p>
            </div>

            <!-- Widget Area 1 -->
            <?php if ( is_active_sidebar('footer-1') ) : ?>
            <div><?php dynamic_sidebar('footer-1'); ?></div>
            <?php endif; ?>

            <!-- Widget Area 2 -->
            <?php if ( is_active_sidebar('footer-2') ) : ?>
            <div><?php dynamic_sidebar('footer-2'); ?></div>
            <?php endif; ?>
        </div>

        <?php if ( has_nav_menu('footer') ) : ?>
        <nav class="border-t border-slate-700 pt-6 mb-4" aria-label="Footer Menu">
            <?php wp_nav_menu([
                'theme_location' => 'footer',
                'container'      => false,
                'menu_class'     => 'flex flex-wrap gap-x-6 gap-y-2 list-none m-0 p-0',
                'fallback_cb'    => false,
                'depth'          => 1,
            ]); ?>
        </nav>
        <?php endif; ?>

        <div class="border-t border-slate-700 pt-6 flex flex-col sm:flex-row justify-between items-center gap-3 text-sm text-slate-500">
            <p>&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. All rights reserved.</p>
            <p>Powered by <a href="https://wordpress.org" class="hover:text-white transition-colors">WordPress</a></p>
        </div>
    </div>
</footer>

<script>
// Dark mode toggle
(function() {
    var btn = document.getElementById('dark-toggle');
    if (btn) {
        btn.addEventListener('click', function() {
            var isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('illusi_dark', isDark ? '1' : '0');
            document.cookie = 'illusi_dark=' + (isDark ? '1' : '0') + ';path=/;max-age=31536000;SameSite=Strict';
        });
    }
    // Mobile menu
    var mBtn = document.getElementById('mobile-menu-btn');
    var mMenu = document.getElementById('mobile-menu');
    if (mBtn && mMenu) {
        mBtn.addEventListener('click', function() {
            mMenu.classList.toggle('hidden');
            mBtn.setAttribute('aria-expanded', !mMenu.classList.contains('hidden'));
        });
    }
})();
</script>

<?php wp_footer(); ?>
</body>
</html>
