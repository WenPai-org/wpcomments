<?php
/**
 * WPComments Settings Class
 *
 * @package WPComments
 * @since   2.0.0
 */

namespace WPComments;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPComments Settings Class
 */
class WPComments_Settings {

	/**
	 * Settings tabs.
	 *
	 * @var array
	 */
	private $tabs = array();

	/**
	 * Current tab.
	 *
	 * @var string
	 */
	private $current_tab = 'general';

	/**
	 * Option group.
	 *
	 * @var string
	 */
	private $option_group = 'wpcomments_settings';

	/**
	 * Page slug.
	 *
	 * @var string
	 */
	private $page_slug = 'wpcomments-settings';

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( is_multisite() ) {
			$network_disable_comments = get_site_option( 'wpcomments_network_disable_comments', 0 );
			if ( $network_disable_comments ) {
				return;
			}
		}

		$site_disable_comments = get_option( 'wpcomments_disable_comments', 0 );
		if ( $site_disable_comments ) {
			return;
		}

		$this->init_tabs();
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}
    
    private function init_tabs() {
        $this->tabs = array(
            'general' => array(
                'title' => '基本设置',
                'icon' => 'dashicons-admin-generic'
            ),
            'features' => array(
                'title' => '功能设置', 
                'icon' => 'dashicons-admin-tools'
            ),
            'email_notifications' => array(
                'title' => '邮件通知',
                'icon' => 'dashicons-email-alt'
            ),
            'advanced' => array(
                'title' => '高级设置',
                'icon' => 'dashicons-admin-settings'
            )
        );
        
        if (isset($_GET['tab']) && array_key_exists($_GET['tab'], $this->tabs)) {
            $this->current_tab = sanitize_text_field($_GET['tab']);
        }
    }
    
    public function add_admin_menu() {
        if (current_user_can('manage_options')) {
            add_options_page(
                'WPComments Settings',
                'WPComments',
                'manage_options',
                $this->page_slug,
                array($this, 'render_settings_page')
            );
        }
    }
    
    public function register_settings() {
        add_settings_section(
            'wpcomments_general_section',
            '',
            null,
            $this->page_slug
        );
        

        
        register_setting($this->option_group, 'wpcomments_enable_herpderp', array(
            'type' => 'boolean',
            'sanitize_callback' => 'boolval',
            'default' => false
        ));
        
        add_settings_field(
            'wpcomments_enable_herpderp',
            '阿巴阿巴功能',
            array($this, 'render_checkbox_field'),
            $this->page_slug,
            'wpcomments_general_section',
            array('option_name' => 'wpcomments_enable_herpderp')
        );
        
        register_setting($this->option_group, 'wpcomments_enable_role_badge', array(
            'type' => 'boolean',
            'sanitize_callback' => 'boolval',
            'default' => false
        ));
        
        add_settings_field(
            'wpcomments_enable_role_badge',
            '用户角色徽章',
            array($this, 'render_checkbox_field'),
            $this->page_slug,
            'wpcomments_general_section',
            array('option_name' => 'wpcomments_enable_role_badge')
        );
        
        register_setting($this->option_group, 'wpcomments_enable_delete_pending', array(
            'type' => 'boolean',
            'sanitize_callback' => 'boolval',
            'default' => false
        ));
        
        add_settings_field(
            'wpcomments_enable_delete_pending',
            '删除待审核评论',
            array($this, 'render_checkbox_field'),
            $this->page_slug,
            'wpcomments_general_section',
            array('option_name' => 'wpcomments_enable_delete_pending')
        );
        
        register_setting($this->option_group, 'wpcomments_enable_sticky_moderate', array(
            'type' => 'boolean',
            'sanitize_callback' => 'boolval',
            'default' => false
        ));
        
        add_settings_field(
            'wpcomments_enable_sticky_moderate',
            '置顶审核评论',
            array($this, 'render_checkbox_field'),
            $this->page_slug,
            'wpcomments_general_section',
            array('option_name' => 'wpcomments_enable_sticky_moderate')
        );
        
        register_setting($this->option_group, 'wpcomments_enable_remove_feed_link', array(
            'type' => 'boolean',
            'sanitize_callback' => 'boolval',
            'default' => false
        ));
        
        add_settings_field(
            'wpcomments_enable_remove_feed_link',
            '完全禁用评论RSS订阅功能',
            array($this, 'render_checkbox_field'),
            $this->page_slug,
            'wpcomments_general_section',
            array('option_name' => 'wpcomments_enable_remove_feed_link')
        );
        
        register_setting($this->option_group, 'wpcomments_enable_remove_website_field', array(
            'type' => 'boolean',
            'sanitize_callback' => 'boolval',
            'default' => false
        ));
        
        add_settings_field(
            'wpcomments_enable_remove_website_field',
            '移除网站链接字段',
            array($this, 'render_checkbox_field'),
            $this->page_slug,
            'wpcomments_general_section',
            array('option_name' => 'wpcomments_enable_remove_website_field')
        );
        
        register_setting($this->option_group, 'wpcomments_enable_frequently_replies', array(
            'type' => 'boolean',
            'sanitize_callback' => 'boolval',
            'default' => false
        ));
        
        add_settings_field(
            'wpcomments_enable_frequently_replies',
            '常用回复功能',
            array($this, 'render_checkbox_field'),
            $this->page_slug,
            'wpcomments_general_section',
            array('option_name' => 'wpcomments_enable_frequently_replies')
        );
        
        register_setting($this->option_group, 'wpcomments_enable_validation', array(
            'type' => 'boolean',
            'sanitize_callback' => 'boolval',
            'default' => true
        ));
        
        add_settings_field(
            'wpcomments_enable_validation',
            '评论表单验证',
            array($this, 'render_checkbox_field'),
            $this->page_slug,
            'wpcomments_general_section',
            array('option_name' => 'wpcomments_enable_validation')
        );
        
        register_setting($this->option_group, 'wpcomments_enable_moderation_info', array(
            'type' => 'boolean',
            'sanitize_callback' => 'boolval',
            'default' => true
        ));
        
        add_settings_field(
            'wpcomments_enable_moderation_info',
            '评论审核信息',
            array($this, 'render_checkbox_field'),
            $this->page_slug,
            'wpcomments_general_section',
            array('option_name' => 'wpcomments_enable_moderation_info')
        );
        
        \WPComments\WPComments_Moderation_Info::register_settings();
    }
    
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        if (isset($_GET['settings-updated'])) {
            add_settings_error('wpcomments_messages', 'wpcomments_message', '设置已保存', 'updated');
        }
        
        settings_errors('wpcomments_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <nav class="nav-tab-wrapper">
                <?php foreach ($this->tabs as $tab_key => $tab): ?>
                    <a href="<?php echo esc_url(add_query_arg('tab', $tab_key, admin_url('options-general.php?page=' . $this->page_slug))); ?>" 
                       class="nav-tab <?php echo $this->current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                        <span class="dashicons <?php echo esc_attr($tab['icon']); ?>"></span>
                        <?php echo esc_html($tab['title']); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            
            <div class="wpcomments-tab-content">
                <?php $this->render_tab_content(); ?>
            </div>
        </div>
        <?php
    }
    
    private function render_tab_content() {
        switch ($this->current_tab) {
            case 'general':
                $this->render_general_tab();
                break;
            case 'features':
                $this->render_features_tab();
                break;
            case 'email_notifications':
                $this->render_email_notifications_tab();
                break;
            case 'advanced':
                $this->render_advanced_tab();
                break;
        }
    }
    
    private function render_general_tab() {
        ?>
        <form action="options.php" method="post">
            <?php
            settings_fields($this->option_group);
            do_settings_sections($this->page_slug);
            submit_button('保存设置');
            ?>
        </form>
        <?php
    }
    
    public function render_checkbox_field($args) {
        $option_name = $args['option_name'];
        $value = get_option($option_name, false);
        ?>
        <fieldset>
            <input type="hidden" name="<?php echo esc_attr($option_name); ?>" value="0" />
            <label for="<?php echo esc_attr($option_name); ?>">
                <input type="checkbox" id="<?php echo esc_attr($option_name); ?>" name="<?php echo esc_attr($option_name); ?>" value="1" <?php checked(1, $value); ?> />
                <?php
                switch($option_name) {
                    case 'wpcomments_enable_herpderp':
                        echo '将评论内容替换为"阿巴阿巴"';
                        break;
                    case 'wpcomments_enable_role_badge':
                        echo '在评论中显示用户角色徽章';
                        break;
                    case 'wpcomments_enable_delete_pending':
                        echo '自动删除待审核评论';
                        break;
                    case 'wpcomments_enable_sticky_moderate':
                        echo '置顶待审核评论';
                        break;
                    case 'wpcomments_enable_remove_feed_link':
                        echo '彻底移除评论RSS订阅功能';
                        break;
                    case 'wpcomments_enable_remove_website_field':
                        echo '移除评论表单中的网站链接字段';
                        break;
                    case 'wpcomments_enable_frequently_replies':
                        echo '启用常用回复功能';
                        break;
                    case 'wpcomments_enable_validation':
                        echo '启用评论表单验证';
                        break;
                    case 'wpcomments_enable_moderation_info':
                        echo '启用评论审核信息';
                        break;
                }
                ?>
            </label>
            <?php
            switch($option_name) {
                case 'wpcomments_enable_herpderp':
                    echo '<p class="description">启用后，所有评论内容都会显示为"阿巴阿巴"。</p>';
                    break;
                case 'wpcomments_enable_role_badge':
                    echo '<p class="description">在评论作者名称旁显示其用户角色（如管理员、编辑等）。</p>';
                    break;
                case 'wpcomments_enable_delete_pending':
                    echo '<p class="description">自动删除超过指定时间的待审核评论。</p>';
                    break;
                case 'wpcomments_enable_sticky_moderate':
                    echo '<p class="description">将待审核的评论置顶显示，方便管理员处理。</p>';
                    break;
                case 'wpcomments_enable_remove_feed_link':
                    echo '<p class="description">彻底移除评论RSS订阅功能，包括头部链接和订阅URL访问，访问时返回404错误。</p>';
                    break;
                case 'wpcomments_enable_remove_website_field':
                    echo '<p class="description">隐藏评论表单中的网站URL输入框。</p>';
                    break;
                case 'wpcomments_enable_frequently_replies':
                    echo '<p class="description">启用后，您可以创建和管理常用回复模板，在回复评论时快速插入预设内容。</p>';
                    break;
                case 'wpcomments_enable_validation':
                    echo '<p class="description">启用前端评论表单验证，包括必填字段检查、邮箱格式验证等，并阻止包含活跃链接的评论。</p>';
                    break;
                case 'wpcomments_enable_moderation_info':
                    echo '<p class="description">在评论管理页面显示评论的修订历史信息，包括最后修改时间和编辑者信息。</p>';
                    break;
            }
            ?>
        </fieldset>
        <?php
    }
    
    private function render_features_tab() {
        ?>
        <p>功能设置选项已移至基本设置标签页，请在基本设置中配置所有功能选项。</p>
        <?php
    }
    

    
    private function render_advanced_tab() {
        ?>
        <p>高级设置选项已移至基本设置标签页，请在基本设置中配置所有功能选项。</p>
        <?php
    }
    

    

    
    public function enqueue_admin_assets($hook) {
        if ('settings_page_' . $this->page_slug !== $hook) {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('wpcomments-admin', WPCOMMENTS_URL . 'assets/js/admin.js', array('jquery'), WPCOMMENTS_VERSION, true);
        
        wp_add_inline_style('wp-admin', $this->get_admin_styles());
    }
    
    private function get_admin_styles() {
        return '
            .nav-tab-wrapper {
                margin-bottom: 20px;
            }
            
            .nav-tab .dashicons {
                margin-right: 5px;
                vertical-align: middle;
            }
            
            .wpcomments-tab-content {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            
            .wpfr-container {
                max-width: 800px;
            }
            
            .reply-item {
                background: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 15px;
                margin-bottom: 15px;
                position: relative;
            }
            
            .reply-header {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 10px;
            }
            
            .reply-title {
                flex: 1;
            }
            
            .wpfr-remove {
                color: #a00;
                border-color: #a00;
            }
            
            .wpfr-remove:hover {
                background: #a00;
                color: #fff;
            }
            
            .reply-textarea {
                width: 100%;
                resize: vertical;
            }
            
            .wpfr-actions {
                margin-top: 20px;
                padding-top: 15px;
                border-top: 1px solid #ddd;
                display: flex;
                gap: 10px;
            }
            
            #wpfr-message {
                margin-top: 15px;
                padding: 10px;
                border-radius: 4px;
                display: none;
            }
            
            #wpfr-message.success {
                background: #d4edda;
                border: 1px solid #c3e6cb;
                color: #155724;
            }
            
            #wpfr-message.error {
                background: #f8d7da;
                border: 1px solid #f5c6cb;
                color: #721c24;
            }
            
            .form-table th {
                width: 200px;
                vertical-align: top;
                padding-top: 15px;
                font-weight: 600;
            }
            
            .form-table td {
                padding-top: 10px;
                padding-bottom: 15px;
            }
            
            .form-table td label {
                font-weight: 500;
                margin-left: 8px;
            }
            
            .form-table td p.description {
                margin-top: 8px;
                font-style: italic;
                color: #666;
            }
        ';
    }
    
    private function render_email_notifications_tab() {
        if (!class_exists('WPComments\\WPComments_Email_Notification')) {
            echo '<div class="notice notice-error"><p>邮件通知功能类未加载。请确保插件正确安装。</p></div>';
            return;
        }
        
        $notification = new \WPComments\WPComments_Email_Notification();
        $notification->output_admin_settings_page();
    }
}