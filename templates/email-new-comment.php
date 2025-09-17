<?php
/**
 * Email new comment notification template.
 *
 * @package WPComments
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($subject); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            border-bottom: 2px solid #0073aa;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #0073aa;
            margin: 0;
            font-size: 24px;
        }
        .comment-info {
            background-color: #f8f9fa;
            border-left: 4px solid #0073aa;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 4px 4px 0;
        }
        .comment-meta {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }
        .comment-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 4px;
            border: 1px solid #ddd;
            margin: 15px 0;
        }
        .post-info {
            background-color: #e8f4f8;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .actions {
            margin-top: 30px;
            text-align: center;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 0 10px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            text-align: center;
        }
        .btn-primary {
            background-color: #0073aa;
            color: #ffffff;
        }
        .btn-secondary {
            background-color: #666;
            color: #ffffff;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        .unsubscribe {
            margin-top: 20px;
            font-size: 11px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1><?php echo esc_html(get_bloginfo('name')); ?></h1>
            <p>您有一条新的评论通知</p>
        </div>

        <div class="comment-info">
            <div class="comment-meta">
                <strong>评论者：</strong> <?php echo esc_html($comment_author); ?><br>
                <strong>邮箱：</strong> <?php echo esc_html($comment_author_email); ?><br>
                <strong>时间：</strong> <?php echo esc_html($comment_date); ?><br>
                <?php if (!empty($comment_author_url)): ?>
                <strong>网站：</strong> <a href="<?php echo esc_url($comment_author_url); ?>"><?php echo esc_html($comment_author_url); ?></a><br>
                <?php endif; ?>
            </div>
        </div>

        <div class="post-info">
            <strong>文章：</strong> <a href="<?php echo esc_url($post_url); ?>"><?php echo esc_html($post_title); ?></a>
        </div>

        <div class="comment-content">
            <h3>评论内容：</h3>
            <?php echo wpautop(esc_html($comment_content)); ?>
        </div>

        <div class="actions">
            <a href="<?php echo esc_url($approve_url); ?>" class="btn btn-primary">批准评论</a>
            <a href="<?php echo esc_url($spam_url); ?>" class="btn btn-secondary">标记为垃圾</a>
            <a href="<?php echo esc_url($delete_url); ?>" class="btn btn-secondary">删除评论</a>
        </div>

        <div class="footer">
            <p>此邮件由 <?php echo esc_html(get_bloginfo('name')); ?> 自动发送</p>
            <p>管理后台：<a href="<?php echo esc_url(admin_url()); ?>"><?php echo esc_url(admin_url()); ?></a></p>
            
            <div class="unsubscribe">
                <p>如果您不想再收到此类邮件，请 <a href="<?php echo esc_url($unsubscribe_url); ?>">取消订阅</a></p>
            </div>
        </div>
    </div>
</body>
</html>