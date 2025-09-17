<?php
/**
 * Email comment reply notification template.
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
            border-bottom: 2px solid #28a745;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #28a745;
            margin: 0;
            font-size: 24px;
        }
        .original-comment {
            background-color: #f8f9fa;
            border-left: 4px solid #6c757d;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 4px 4px 0;
        }
        .new-reply {
            background-color: #e8f5e8;
            border-left: 4px solid #28a745;
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
            padding: 15px;
            border-radius: 4px;
            border: 1px solid #ddd;
            margin: 10px 0;
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
            background-color: #28a745;
            color: #ffffff;
        }
        .btn-secondary {
            background-color: #6c757d;
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
            <p>您的评论收到了新回复</p>
        </div>

        <div class="post-info">
            <strong>文章：</strong> <a href="<?php echo esc_url($post_url); ?>"><?php echo esc_html($post_title); ?></a>
        </div>

        <div class="original-comment">
            <h3>您的原评论：</h3>
            <div class="comment-meta">
                <strong>发表时间：</strong> <?php echo esc_html($original_comment_date); ?>
            </div>
            <div class="comment-content">
                <?php echo wpautop(esc_html($original_comment_content)); ?>
            </div>
        </div>

        <div class="new-reply">
            <h3>新回复：</h3>
            <div class="comment-meta">
                <strong>回复者：</strong> <?php echo esc_html($reply_author); ?><br>
                <strong>回复时间：</strong> <?php echo esc_html($reply_date); ?><br>
                <?php if (!empty($reply_author_url)): ?>
                <strong>网站：</strong> <a href="<?php echo esc_url($reply_author_url); ?>"><?php echo esc_html($reply_author_url); ?></a><br>
                <?php endif; ?>
            </div>
            <div class="comment-content">
                <?php echo wpautop(esc_html($reply_content)); ?>
            </div>
        </div>

        <div class="actions">
            <a href="<?php echo esc_url($post_url); ?>#comment-<?php echo esc_attr($reply_id); ?>" class="btn btn-primary">查看完整对话</a>
            <a href="<?php echo esc_url($reply_url); ?>" class="btn btn-secondary">回复此评论</a>
        </div>

        <div class="footer">
            <p>此邮件由 <?php echo esc_html(get_bloginfo('name')); ?> 自动发送</p>
            <p>网站地址：<a href="<?php echo esc_url(home_url()); ?>"><?php echo esc_url(home_url()); ?></a></p>
            
            <div class="unsubscribe">
                <p>如果您不想再收到此类邮件，请 <a href="<?php echo esc_url($unsubscribe_url); ?>">取消订阅</a></p>
            </div>
        </div>
    </div>
</body>
</html>