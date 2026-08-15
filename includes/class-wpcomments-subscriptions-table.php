<?php

namespace WPComments;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class WPComments_Subscriptions_Table extends \WP_List_Table {
    
    public function __construct() {
        parent::__construct(array(
            'singular' => '',
            'plural' => '',
            'ajax' => false,
        ));
    }
    
    public function prepare_items() {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $per_page = 20;
        $current_page = $this->get_pagenum();
        $total_items = $this->count_data();
        $data = $this->load_data($per_page, ($current_page - 1) * $per_page);

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page
        ));

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }
    
    public function get_columns() {
        return array(
            'id' => 'ID',
            'date' => '日期',
            'name' => '姓名',
            'email' => '邮箱',
            'post' => '评论文章',
            'status' => '状态'
        );
    }
    
    public function get_hidden_columns() {
        return array('id');
    }
    
    public function get_sortable_columns() {
        return array(
            'date' => array('date', 'desc'),
            'name' => array('name', 'asc'),
            'email' => array('email', 'asc'),
            'post' => array('post', 'asc'),
        );
    }
    
    private function load_data($number, $offset) {
        $data = array();

        $orderby = sanitize_key((string) filter_input(INPUT_GET, 'orderby', FILTER_UNSAFE_RAW));
        $orderby = '' !== $orderby ? $orderby : 'date';
        $requested_order = sanitize_key((string) filter_input(INPUT_GET, 'order', FILTER_UNSAFE_RAW));
        $order = 'asc' === strtolower($requested_order) ? 'ASC' : 'DESC';
        $orderby_map = array('date' => 'comment_date', 'name' => 'comment_author', 'email' => 'comment_author_email', 'post' => 'comment_post_ID');
        $comments = get_comments(array(
            'meta_key' => 'wpcomments_subscribe_to_comment', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- indexed key contains positive subscriptions only since DB v3.
            'number' => absint($number),
            'offset' => absint($offset),
            'orderby' => $orderby_map[$orderby] ?? 'comment_date',
            'order' => $order,
        ));
        
        foreach ($comments as $comment) {
            $post = get_post((int) $comment->comment_post_ID);
            if (!$post) {
                continue;
            }
            
            $data[] = array(
                'id' => $comment->comment_ID,
                'date' => $comment->comment_date,
                'name' => $comment->comment_author,
                'email' => $comment->comment_author_email,
                'post' => $post->post_title,
                'status' => '已订阅'
            );
        }

        return $data;
    }

    private function count_data() {
        return (int) get_comments(array(
            'meta_key' => 'wpcomments_subscribe_to_comment', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- indexed key contains positive subscriptions only since DB v3.
            'count' => true,
        ));
    }
    
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'id':
            case 'date':
            case 'name':
            case 'email':
            case 'post':
            case 'status':
                return esc_html((string) $item[$column_name]);
            default:
                return esc_html(wp_json_encode($item));
        }
    }
    
    public function column_date($item) {
        return esc_html(mysql2date('Y-m-d H:i:s', $item['date']));
    }
    
    public function column_post($item) {
        $comment = get_comment($item['id']);
        if ($comment) {
            $post_url = get_permalink((int) $comment->comment_post_ID);
            return '<a href="' . esc_url($post_url . '#comment-' . $comment->comment_ID) . '" target="_blank" rel="noopener noreferrer">' . esc_html($item['post']) . '</a>';
        }
        return esc_html($item['post']);
    }
    
    public function sort_data($a, $b) {
        $orderby = sanitize_key((string) filter_input(INPUT_GET, 'orderby', FILTER_UNSAFE_RAW));
        $orderby = '' !== $orderby ? $orderby : 'date';
        $order = sanitize_key((string) filter_input(INPUT_GET, 'order', FILTER_UNSAFE_RAW));
        $order = '' !== $order ? $order : 'desc';
        if (!in_array($orderby, array('date', 'name', 'email', 'post'), true)) {
            $orderby = 'date';
        }
        
        $result = strcmp($a[$orderby], $b[$orderby]);
        
        if ($order === 'asc') {
            return $result;
        }
        
        return -$result;
    }
}
