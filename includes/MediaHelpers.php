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
}
