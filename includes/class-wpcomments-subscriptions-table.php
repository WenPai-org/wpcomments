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

        $data = $this->load_data();
        usort($data, array(&$this, 'sort_data'));

        $per_page = 20;
        $current_page = $this->get_pagenum();
        $total_items = count($data);

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page
        ));

        $data = array_slice($data, (($current_page - 1) * $per_page), $per_page);

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
    
    private function load_data() {
        $data = array();

        $comments = get_comments(array(
            'meta_key' => 'wpcomments_subscribe_to_comment',
            'meta_value' => 'on'
        ));
        
        foreach ($comments as $comment) {
            $post = get_post($comment->comment_post_ID);
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
    
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'id':
            case 'date':
            case 'name':
            case 'email':
            case 'post':
            case 'status':
                return $item[$column_name];
            default:
                return print_r($item, true);
        }
    }
    
    public function column_date($item) {
        return date('Y-m-d H:i:s', strtotime($item['date']));
    }
    
    public function column_post($item) {
        $comment = get_comment($item['id']);
        if ($comment) {
            $post_url = get_permalink($comment->comment_post_ID);
            return '<a href="' . $post_url . '#comment-' . $comment->comment_ID . '" target="_blank">' . $item['post'] . '</a>';
        }
        return $item['post'];
    }
    
    public function sort_data($a, $b) {
        $orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'date';
        $order = (!empty($_GET['order'])) ? $_GET['order'] : 'desc';
        
        $result = strcmp($a[$orderby], $b[$orderby]);
        
        if ($order === 'asc') {
            return $result;
        }
        
        return -$result;
    }
}