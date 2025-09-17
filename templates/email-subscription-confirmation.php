<?php
/**
 * Email subscription confirmation template.
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
            border-bottom: 2px solid #17a2b8;
            padding-bottom: 20px;
            margin-bottom: 30px;
            text-align: center;
        }
        .header h1 {
            color: #17a2b8;
            margin: 0;
            font-size: 24px;
        }
        .confirmation-box {
            background-color: #e8f6f8;
            border: 2px solid #17a2b8;
            border-radius: 8px;
            padding: 25px;
            margin: 25px 0;
            text-align: center;
        }
        .confirmation-icon {
            font-size: 48px;
            color: #17a2b8;
            margin-bottom: 15px;
        }
        .post-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            border-left: 4px solid #17a2b8;
        }
        .actions {
            margin-top: 30px;
            text-align: center;
        }
        .btn {
            display: inline-block;
            padding: 15px 30px;
            margin: 0 10px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            text-align: center;
            font-size: 16px;
        }
        .btn-primary {
            background-color: #17a2b8;
            color: #ffffff;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: #ffffff;
        }
        .info-section {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 20px;
            margin: 25px 0;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1><?php echo esc_html(get_bloginfo('name')); ?></h1>
            <p>评论订阅确认</p>
        </div>

        <div class="confirmation-box">
            <div class="confirmation-icon">✉️</div>
            <h2>请确认您的订阅</h2>
            <p>感谢您订阅评论通知！为了确保邮件能够正确送达，请点击下方按钮确认您的订阅。</p>
        </div>

        <div class="post-info">
            <strong>订阅文章：</strong> <a href="<?php echo esc_url($post_url); ?>"><?php echo esc_html($post_title); ?></a><br>
            <strong>订阅邮箱：</strong> <?php echo esc_html($subscriber_email); ?><br>
            <strong>订阅时间：</strong> <?php echo esc_html($subscription_date); ?>
        </div>

        <div class="actions">
            <a href="<?php echo esc_url($confirm_url); ?>" class="btn btn-primary">确认订阅</a>
        </div>

        <div class="info-section">
            <h3>关于评论订阅：</h3>
            <ul>
                <li>确认订阅后，当有新评论或回复时您将收到邮件通知</li>
                <li>您可以随时取消订阅</li>
                <li>我们不会将您的邮箱地址分享给第三方</li>
                <li>如果您没有订阅，请忽略此邮件</li>
            </ul>
        </div>

        <div class="footer">
            <p>此邮件由 <?php echo esc_html(get_bloginfo('name')); ?> 自动发送</p>
            <p>网站地址：<a href="<?php echo esc_url(home_url()); ?>"><?php echo esc_url(home_url()); ?></a></p>
            <p style="margin-top: 15px; font-size: 11px; color: #999;">
                如果确认按钮无法点击，请复制以下链接到浏览器地址栏：<br>
                <?php echo esc_url($confirm_url); ?>
            </p>
        </div>
    </div>
</body>
</html>