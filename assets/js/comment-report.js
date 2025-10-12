(function($) {
    'use strict';
    
    $(document).ready(function() {
        // 处理举报按钮点击
        $(document).on('click', '.wpcomments-report-btn', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var commentId = $button.data('comment-id');
            
            if (!commentId) {
                alert(wpcommentsReport.errorMessage);
                return;
            }
            
            // 确认对话框
            if (!confirm('确定要举报这条评论吗？')) {
                return;
            }
            
            // 禁用按钮，防止重复点击
            $button.prop('disabled', true).text('处理中...');
            
            // 发送 AJAX 请求
            $.ajax({
                url: wpcommentsReport.ajaxurl,
                type: 'POST',
                data: {
                    action: 'wpcomments_report_comment',
                    comment_id: commentId,
                    nonce: wpcommentsReport.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // 成功举报
                        $button.replaceWith('<span class="wpcomments-already-reported">已举报</span>');
                        
                        // 显示感谢消息
                        showMessage(wpcommentsReport.thankYouMessage, 'success');
                    } else {
                        // 举报失败
                        var errorMsg = response.data || wpcommentsReport.errorMessage;
                        
                        if (errorMsg.indexOf('already reported') !== -1) {
                            $button.replaceWith('<span class="wpcomments-already-reported">已举报</span>');
                            showMessage(wpcommentsReport.alreadyReportedMessage, 'info');
                        } else {
                            showMessage(errorMsg, 'error');
                            $button.prop('disabled', false).text(wpcommentsReport.reportText);
                        }
                    }
                },
                error: function() {
                    showMessage(wpcommentsReport.errorMessage, 'error');
                    $button.prop('disabled', false).text(wpcommentsReport.reportText);
                }
            });
        });
        
        // 显示消息函数
        function showMessage(message, type) {
            var messageClass = 'wpcomments-message';
            var bgColor = '#dff0d8';
            var borderColor = '#d6e9c6';
            var textColor = '#3c763d';
            
            switch(type) {
                case 'error':
                    bgColor = '#f2dede';
                    borderColor = '#ebccd1';
                    textColor = '#a94442';
                    break;
                case 'info':
                    bgColor = '#d9edf7';
                    borderColor = '#bce8f1';
                    textColor = '#31708f';
                    break;
            }
            
            var $message = $('<div class="' + messageClass + '" style="' +
                'position: fixed; top: 20px; right: 20px; z-index: 9999; ' +
                'padding: 15px; border: 1px solid ' + borderColor + '; ' +
                'background-color: ' + bgColor + '; color: ' + textColor + '; ' +
                'border-radius: 4px; max-width: 300px; box-shadow: 0 2px 5px rgba(0,0,0,0.2);' +
                '">' + message + '</div>');
            
            $('body').append($message);
            
            // 3秒后自动消失
            setTimeout(function() {
                $message.fadeOut(500, function() {
                    $(this).remove();
                });
            }, 3000);
        }
        
        // 添加样式
        if (!$('#wpcomments-report-styles').length) {
            $('<style id="wpcomments-report-styles">' +
                '.wpcomments-report-link { margin-left: 10px; }' +
                '.wpcomments-report-btn { color: #666; text-decoration: none; font-size: 12px; }' +
                '.wpcomments-report-btn:hover { color: #d63638; text-decoration: underline; }' +
                '.wpcomments-already-reported { color: #999; font-size: 12px; font-style: italic; }' +
                '.wpcomments-report-btn:disabled { opacity: 0.6; cursor: not-allowed; }' +
            '</style>').appendTo('head');
        }
    });
    
})(jQuery);