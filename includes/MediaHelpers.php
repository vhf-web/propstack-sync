<?php 
namespace Propstack\Includes;

class MediaHelpers {
    public static function set_featured_image_from_url(int $post_id, string $image_url, bool $delete_old = true, string $alt_text = '') {
        if (empty($image_url)) {
            error_log("[propstack] skip: empty image_url for post {$post_id}");
            return;
        }

        $prev_url  = get_post_meta($post_id, '_featured_source_url', true);
        $prev_hash = get_post_meta($post_id, '_featured_source_hash', true);
        $has_thumb = has_post_thumbnail($post_id) ? 'yes' : 'no';
        error_log("[propstack] start set_featured for post {$post_id}; url={$image_url}; prev_url={$prev_url}; prev_hash={$prev_hash}; has_thumb={$has_thumb}; delete_old=" . ($delete_old?'1':'0'));

        if ( ! function_exists('download_url') )   require_once ABSPATH . 'wp-admin/includes/file.php';
        if ( ! function_exists('media_handle_sideload') ) require_once ABSPATH . 'wp-admin/includes/media.php';
        if ( ! function_exists('wp_read_image_metadata') ) require_once ABSPATH . 'wp-admin/includes/image.php';

        $tmp = download_url($image_url);
        if (is_wp_error($tmp)) {
            error_log("[propstack] download_url error: " . $tmp->get_error_message());
            return;
        }
        $size = @filesize($tmp);
        error_log("[propstack] downloaded tmp={$tmp}; size=" . ($size !== false ? $size : 'n/a'));

        $contents = @file_get_contents($tmp);
        $new_hash = $contents !== false ? sha1($contents) : '';
        error_log("[propstack] new_hash={$new_hash}");

        if ($prev_url === $image_url && $prev_hash && $prev_hash === $new_hash && has_post_thumbnail($post_id)) {
            @unlink($tmp);
            error_log("[propstack] identical image detected, keep current thumbnail");
            return;
        }

        $file_array = [
            'name'     => wp_basename(parse_url($image_url, PHP_URL_PATH)) ?: 'image.jpg',
            'tmp_name' => $tmp,
        ];

        $attachment_id = media_handle_sideload($file_array, $post_id);
        if (is_wp_error($attachment_id)) {
            @unlink($tmp);
            error_log("[propstack] media_handle_sideload error: ".$attachment_id->get_error_message());
            return;
        }
        if (!empty($alt_text)) {
            update_post_meta($attachment_id, '_wp_attachment_image_alt', sanitize_text_field($alt_text));
        }

        error_log("[propstack] new attachment_id={$attachment_id}");

        if ($delete_old && has_post_thumbnail($post_id)) {
            $old_id = (int) get_post_thumbnail_id($post_id);
            if ($old_id && $old_id !== $attachment_id) {
                wp_delete_attachment($old_id, true);
                error_log("[propstack] deleted old attachment_id={$old_id}");
            }
        }

        set_post_thumbnail($post_id, $attachment_id);
        update_post_meta($post_id, '_featured_source_url', esc_url_raw($image_url));
        if ($new_hash) {
            update_post_meta($post_id, '_featured_source_hash', $new_hash);
        }

        $now_has = has_post_thumbnail($post_id) ? 'yes' : 'no';
        error_log("[propstack] done; has_post_thumbnail={$now_has}");
    }
     
    public static function import_or_update_pdf_for_post(int $post_id, string $pdf_url, string $filename = 'expose.pdf', string $checksum = '') {
        if (empty($pdf_url)) {
            return new WP_Error('no-url', 'Empty PDF url');
        }

       
        $meta_checksum_key = '_expose_checksum';
        $meta_attach_key   = '_expose_attachment_id';

        $prev_checksum = (string) get_post_meta($post_id, $meta_checksum_key, true);
        $prev_attach   = (int) get_post_meta($post_id, $meta_attach_key, true);

        if ($prev_checksum && $checksum && $prev_checksum === $checksum && $prev_attach && get_post_status($prev_attach)) {
            return $prev_attach;
        }

        if ( ! function_exists('download_url') )              require_once ABSPATH . 'wp-admin/includes/file.php';
        if ( ! function_exists('wp_handle_sideload') )        require_once ABSPATH . 'wp-admin/includes/file.php';
        if ( ! function_exists('wp_generate_attachment_metadata') ) require_once ABSPATH . 'wp-admin/includes/image.php';
        if ( ! function_exists('wp_insert_attachment') )      require_once ABSPATH . 'wp-admin/includes/post.php';

        $tmp = download_url($pdf_url, 60);
        if (is_wp_error($tmp)) {
            return $tmp;
        }

        if (empty($checksum)) {
            $contents = @file_get_contents($tmp);
                if ($contents !== false) {
                    $checksum = sha1($contents);
                }
        }

        $safe_name = sanitize_file_name($filename);
        if (strpos($safe_name, '.') === false) $safe_name .= '.pdf';

        $file_array = [
            'name'     => $safe_name,
            'type'     => 'application/pdf',
            'tmp_name' => $tmp,
            'error'    => 0,
            'size'     => @filesize($tmp) ?: null,
        ];

        $overrides = [
            'test_form' => false,
            'mimes'     => [ 'pdf' => 'application/pdf' ],
        ];

        $sideload = wp_handle_sideload($file_array, $overrides);
        if (!empty($sideload['error'])) {
            @unlink($tmp);
            return new WP_Error('sideload-failed', $sideload['error']);
        }

        $file_path = $sideload['file'];
        $file_url  = $sideload['url'];
        $file_type = wp_check_filetype(basename($file_path), null);

        $attachment = [
            'post_mime_type' => $file_type['type'] ?: 'application/pdf',
            'post_title'     => preg_replace('/\.[^.]+$/', '', basename($safe_name)),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ];

        $attach_id = wp_insert_attachment($attachment, $file_path, $post_id);
        if (is_wp_error($attach_id) || !$attach_id) {
            @unlink($file_path);
            return new WP_Error('attach-failed', 'Cannot create attachment');
        }

        $attach_meta = wp_generate_attachment_metadata($attach_id, $file_path);
        wp_update_attachment_metadata($attach_id, $attach_meta);

        if ($prev_attach && $prev_attach !== $attach_id) {
            wp_delete_attachment($prev_attach, true);
        }

        update_post_meta($post_id, $meta_attach_key, $attach_id);
        if (!empty($checksum)) {
            update_post_meta($post_id, $meta_checksum_key, $checksum);
        }

        update_post_meta($post_id, 'expose_attachment_id', $attach_id);
        update_post_meta($post_id, 'expose_url', $file_url);

        return $attach_id;
    }
}
