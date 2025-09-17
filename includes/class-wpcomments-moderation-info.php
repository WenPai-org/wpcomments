<?php
/**
 * Comment moderation info functionality.
 *
 * @package WPComments
 * @since   1.0.0
 */

namespace WpComments;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPComments_Moderation_Info
 *
 * Handles comment moderation information display.
 */
class WPComments_Moderation_Info {
    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->init_hooks();
    }

    private function init_hooks()
    {
        add_filter('manage_edit-comments_columns', [$this, 'manage_edit_comments_columns'], 10, 1);
        add_action('manage_comments_custom_column', [$this, 'manage_comments_custom_column'], 10, 2);
        add_action('edit_comment', [$this, 'after_update_comment_metadata'], 10, 2);
        add_filter('comment_text', [$this, 'add_last_modified_date'], 10, 2);
    }

    public function manage_edit_comments_columns($columns)
    {
        unset($columns['date']);
        $columns['comodinfo_date_columns'] = esc_html__('Date', 'wpcomments');
        return $columns;
    }

    public function get_last_update_from_comment_revisions($comment_revisions, $show_author = 1)
    {
        $last_comment_data = end($comment_revisions);
        $modified_by_user = get_userdata($last_comment_data['author_id']);
        $modified_by = $modified_by_user->display_name;
        
        if (1 === $show_author) {
            $modified = sprintf(
                esc_html__('Edited on %1$s at %2$s, by %3$s', 'wpcomments'),
                mysql2date(get_option('date_format'), $last_comment_data['modified_date']),
                mysql2date(get_option('time_format'), $last_comment_data['modified_date']),
                $modified_by
            );
        } else {
            $modified = sprintf(
                esc_html__('Edited on %1$s at %2$s', 'wpcomments'),
                mysql2date(get_option('date_format'), $last_comment_data['modified_date']),
                mysql2date(get_option('time_format'), $last_comment_data['modified_date'])
            );
        }
        return esc_html($modified);
    }

    public function manage_comments_custom_column($column, $comment_ID)
    {
        if ('comodinfo_date_columns' !== $column) {
            return;
        }

        $submitted = sprintf(
            esc_html__('%1$s at %2$s', 'wpcomments'),
            get_comment_date(get_option('date_format'), $comment_ID),
            get_comment_date(get_option('time_format'), $comment_ID)
        );
        
        echo '<div class="submitted-on">';
        if ('approved' === wp_get_comment_status($comment_ID)) {
            printf(
                '<a href="%s">%s</a>',
                esc_url(get_comment_link($comment_ID)),
                esc_html($submitted)
            );
        } else {
            echo esc_html($submitted);
        }
        echo '</div>';

        $comment_revisions = get_comment_meta($comment_ID, 'comodinfo_comment_revisions', true);

        if (!empty($comment_revisions)) {
            $modified = $this->get_last_update_from_comment_revisions($comment_revisions);
            echo esc_html($modified);
        }
    }

    public function after_update_comment_metadata($comment_ID, $data)
    {
        $date = current_time('mysql');
        $date_gmt = get_gmt_from_date($date);
        $author_id = get_current_user_id();
        $content = $data['comment_content'];

        $new_data = array(
            'modified_date' => $date,
            'modified_date_gmt' => $date_gmt,
            'author_id' => $author_id,
            'content' => $content,
        );

        $comment_revisions = get_comment_meta($comment_ID, 'comodinfo_comment_revisions');
        $comment_revisions[] = $new_data;
        update_comment_meta($comment_ID, 'comodinfo_comment_revisions', $comment_revisions);
    }

    public function add_last_modified_date($comment_text, $comment)
    {
        $comment_ID = $comment->comment_ID;
        $comment_revisions = get_comment_meta($comment_ID, 'comodinfo_comment_revisions', true);

        if (!empty($comment_revisions)) {
            $option_position = get_option('wpcomments_moderation_info_position', 'after_comment');
            $option_author = get_option('wpcomments_moderation_info_show_author', '1');

            if (!empty($option_position) && 'none' !== $option_position) {
                $modified = $this->get_last_update_from_comment_revisions($comment_revisions, $option_author);
                if ('before_comment' === $option_position) {
                    $comment_text = '<p class="cmda-last-modified">' . $modified . '</p>' . $comment_text;
                } elseif ('after_comment' === $option_position) {
                    $comment_text = $comment_text . '<p class="cmda-last-modified">' . $modified . '</p>';
                }
            }
        }
        return $comment_text;
    }

    public static function register_settings() {
        register_setting('wpcomments_settings', 'wpcomments_moderation_show_last_modified', array(
            'type' => 'boolean',
            'sanitize_callback' => 'boolval',
            'default' => true
        ));
        
        register_setting('wpcomments_settings', 'wpcomments_moderation_show_edit_author', array(
            'type' => 'boolean',
            'sanitize_callback' => 'boolval',
            'default' => false
        ));
        
        add_settings_field(
            'wpcomments_moderation_show_last_modified',
            '前端显示最后修改日期',
            array(__CLASS__, 'render_show_last_modified_field'),
            'wpcomments',
            'wpcomments_general_section'
        );
        
        add_settings_field(
            'wpcomments_moderation_show_edit_author',
            '显示编辑作者用户名',
            array(__CLASS__, 'render_show_edit_author_field'),
            'wpcomments',
            'wpcomments_general_section'
        );
    }

    public static function render_position_setting()
    {
        $option_position = get_option('wpcomments_moderation_info_position', 'after_comment');
        ?>
        <fieldset>
            <label for="wpcomments_moderation_info_position">
                <?php esc_html_e('Show the last modified date…', 'wpcomments'); ?>
            </label><br />
            <select name="wpcomments_moderation_info_position" id="wpcomments_moderation_info_position">
                <option value="after_comment" <?php selected($option_position, 'after_comment', true); ?>>
                    <?php esc_html_e('After the comment (default)', 'wpcomments'); ?>
                </option>
                <option value="before_comment" <?php selected($option_position, 'before_comment', true); ?>>
                    <?php esc_html_e('Before the comment', 'wpcomments'); ?>
                </option>
                <option value="none" <?php selected($option_position, 'none', true); ?>>
                    <?php esc_html_e('Do not show the last modified date on front-end', 'wpcomments'); ?>
                </option>
            </select>
        </fieldset>
        <?php
    }

    public static function render_author_setting()
    {
        $option_author = get_option('wpcomments_moderation_info_show_author', '1');
        ?>
        <fieldset>
            <label for="wpcomments_moderation_info_show_author">
                <?php esc_html_e('Whether to display the author of the edition', 'wpcomments'); ?>
            </label><br />
            <select name="wpcomments_moderation_info_show_author" id="wpcomments_moderation_info_show_author">
                <option value="1" <?php selected($option_author, '1', true); ?>>
                    <?php esc_html_e('Yes, display the author username', 'wpcomments'); ?>
                </option>
                <option value="0" <?php selected($option_author, '0', true); ?>>
                    <?php esc_html_e('No, do not display the author username', 'wpcomments'); ?>
                </option>
            </select>
        </fieldset>
        <?php
    }
    
    public static function render_show_last_modified_field() {
        $value = get_option('wpcomments_moderation_show_last_modified', true);
        ?>
        <input type="hidden" name="wpcomments_moderation_show_last_modified" value="0" />
        <input type="checkbox" id="wpcomments_moderation_show_last_modified" name="wpcomments_moderation_show_last_modified" value="1" <?php checked(1, $value); ?> />
        <label for="wpcomments_moderation_show_last_modified">在前端显示评论的最后修改日期</label>
        <p class="description">启用后，在评论内容中显示评论的最后修改时间。</p>
        <?php
    }
    
    public static function render_show_edit_author_field() {
        $value = get_option('wpcomments_moderation_show_edit_author', false);
        ?>
        <input type="hidden" name="wpcomments_moderation_show_edit_author" value="0" />
        <input type="checkbox" id="wpcomments_moderation_show_edit_author" name="wpcomments_moderation_show_edit_author" value="1" <?php checked(1, $value); ?> />
        <label for="wpcomments_moderation_show_edit_author">显示编辑评论的作者用户名</label>
        <p class="description">启用后，在修改日期旁显示编辑评论的用户名。</p>
        <?php
    }
}