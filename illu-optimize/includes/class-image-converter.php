<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Illu_Media_Image_Converter {

    private array $supported_types = [ 'image/jpeg', 'image/png', 'image/gif' ];

    public function __construct() {
        $options = get_option( 'illu_optimize_settings', [] );
        if ( ( $options['convert_webp'] ?? 'yes' ) !== 'yes' ) return;

        add_filter( 'wp_handle_upload', [ $this, 'convert_on_upload' ], 10, 2 );
        add_filter( 'wp_get_attachment_image_src', [ $this, 'serve_webp' ], 10, 4 );

        add_action( 'add_meta_boxes',        [ $this, 'add_convert_meta_box' ] );
        add_action( 'admin_post_illu_bulk_convert', [ $this, 'handle_bulk_convert' ] );
        add_action( 'wp_ajax_illu_convert_image',   [ $this, 'ajax_convert_image' ] );
    }

    // ── Upload hook ────────────────────────────────────────────────────────────

    public function convert_on_upload( array $file, string $context = 'upload' ): array {
        if ( ! in_array( $file['type'], $this->supported_types, true ) ) return $file;
        if ( ! function_exists( 'imagewebp' ) && ! function_exists( 'imageavif' ) ) return $file;

        $options  = get_option( 'illu_optimize_settings', [] );
        $format   = $options['image_format'] ?? 'webp';
        $quality  = intval( $options['image_quality'] ?? 80 );

        $this->convert_image( $file['file'], $format, $quality );
        return $file;
    }

    // ── Serve WebP ─────────────────────────────────────────────────────────────

    public function serve_webp( $image, $attachment_id, $size, $icon ) {
        if ( ! $image ) return $image;

        $src  = $image[0];
        $path = str_replace( wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $src );

        foreach ( [ 'avif', 'webp' ] as $ext ) {
            $modern = preg_replace( '/\.(jpg|jpeg|png|gif)$/i', '.' . $ext, $path );
            if ( file_exists( $modern ) ) {
                $image[0] = preg_replace( '/\.(jpg|jpeg|png|gif)$/i', '.' . $ext, $src );
                break;
            }
        }
        return $image;
    }

    // ── Core converter ─────────────────────────────────────────────────────────

    public function convert_image( string $source_path, string $format = 'webp', int $quality = 80 ): bool {
        if ( ! file_exists( $source_path ) ) return false;

        $type = exif_imagetype( $source_path );
        $im   = match ( $type ) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg( $source_path ),
            IMAGETYPE_PNG  => @imagecreatefrompng( $source_path ),
            IMAGETYPE_GIF  => @imagecreatefromgif( $source_path ),
            default        => false,
        };
        if ( ! $im ) return false;

        $dest_ext = $format === 'avif' ? 'avif' : 'webp';
        $dest     = preg_replace( '/\.(jpg|jpeg|png|gif)$/i', '.' . $dest_ext, $source_path );

        if ( file_exists( $dest ) ) {
            imagedestroy( $im );
            return true;
        }

        // PNG: preserve transparency
        if ( $type === IMAGETYPE_PNG ) {
            imagealphablending( $im, false );
            imagesavealpha( $im, true );
        }

        $result = match ( $format ) {
            'avif'  => function_exists( 'imageavif' ) ? imageavif( $im, $dest, $quality ) : false,
            default => function_exists( 'imagewebp' ) ? imagewebp( $im, $dest, $quality ) : false,
        };

        imagedestroy( $im );
        return (bool) $result;
    }

    // ── Meta box ───────────────────────────────────────────────────────────────

    public function add_convert_meta_box() {
        add_meta_box(
            'illu_convert_meta_box', 'Illu Optimize — Image Conversion',
            [ $this, 'render_convert_meta_box' ], 'attachment', 'side'
        );
    }

    public function render_convert_meta_box( $post ) {
        if ( ! in_array( get_post_mime_type( $post->ID ), $this->supported_types, true ) ) {
            echo '<p>Format tidak didukung untuk konversi.</p>';
            return;
        }
        $src  = get_attached_file( $post->ID );
        $webp = preg_replace( '/\.(jpg|jpeg|png|gif)$/i', '.webp', $src );
        $avif = preg_replace( '/\.(jpg|jpeg|png|gif)$/i', '.avif', $src );
        $size = filesize( $src );
        ?>
        <p>Original: <?php echo esc_html( number_format( $size / 1024, 1 ) ); ?> KB</p>
        <?php if ( file_exists( $webp ) ) : ?>
            <p style="color:green;">✓ WebP: <?php echo number_format( filesize($webp)/1024, 1 ); ?> KB</p>
        <?php endif; ?>
        <?php if ( file_exists( $avif ) ) : ?>
            <p style="color:green;">✓ AVIF: <?php echo number_format( filesize($avif)/1024, 1 ); ?> KB</p>
        <?php endif; ?>
        <?php if ( ! file_exists( $webp ) || ! file_exists( $avif ) ) : ?>
        <button type="button" class="button"
                onclick="jQuery.post(ajaxurl, {action:'illu_convert_image', id:<?php echo $post->ID; ?>, nonce:'<?php echo wp_create_nonce('illu_convert'); ?>'}, function(r){ if(r.success) location.reload(); });">
            Konversi ke WebP/AVIF
        </button>
        <?php endif; ?>
        <?php
    }

    public function ajax_convert_image() {
        check_ajax_referer( 'illu_convert', 'nonce' );
        if ( ! current_user_can( 'upload_files' ) ) wp_send_json_error( 'Unauthorized' );

        $id   = intval( $_POST['id'] );
        $path = get_attached_file( $id );
        if ( ! $path ) wp_send_json_error( 'File tidak ditemukan' );

        $options = get_option( 'illu_optimize_settings', [] );
        $quality = intval( $options['image_quality'] ?? 80 );

        $webp = $this->convert_image( $path, 'webp', $quality );
        $avif = $this->convert_image( $path, 'avif', $quality );

        wp_send_json_success( [ 'webp' => $webp, 'avif' => $avif ] );
    }

    // ── Bulk convert ───────────────────────────────────────────────────────────

    public function handle_bulk_convert() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        check_admin_referer( 'illu_bulk_convert_action', 'illu_bulk_convert_nonce' );

        $attachments = get_posts( [
            'post_type'      => 'attachment',
            'post_mime_type' => $this->supported_types,
            'posts_per_page' => -1,
            'post_status'    => 'any',
        ] );

        $options = get_option( 'illu_optimize_settings', [] );
        $quality = intval( $options['image_quality'] ?? 80 );
        $count   = 0;

        foreach ( $attachments as $att ) {
            $path = get_attached_file( $att->ID );
            if ( $path && file_exists( $path ) ) {
                if ( $this->convert_image( $path, 'webp', $quality ) ) $count++;
                $this->convert_image( $path, 'avif', $quality );
            }
        }

        wp_safe_redirect( add_query_arg( [
            'page'     => 'illu-optimize',
            'tab'      => 'media',
            'converted'=> $count,
        ], admin_url( 'admin.php' ) ) );
        exit;
    }
}
