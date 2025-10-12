(function($) {
    'use strict';
    
    $(document).ready(function() {
        var commentField = $('#comment');
        var counterContainer = $('#wpcomments-counter-container');
        var progressBar = $('#wpcomments-progress-fill');
        var charCounter = $('#wpcomments-char-counter');
        var submitButton = $('#submit');
        
        if (commentField.length && counterContainer.length) {
            var minChars = parseInt(wpcommentsLimiter.minChars) || 20;
            var maxChars = parseInt(wpcommentsLimiter.maxChars) || 1000;
            var isAdmin = wpcommentsLimiter.isAdmin === 'yes';
            var enableAdminFeature = wpcommentsLimiter.enableAdminFeature === 'yes';
            
            // 如果是管理员且启用了管理员豁免功能，则不显示限制
            if (isAdmin && !enableAdminFeature) {
                counterContainer.hide();
                return;
            }
            
            function updateCounter() {
                var currentLength = commentField.val().length;
                var percentage = 0;
                var color = '#4caf50'; // 绿色
                var status = 'good';
                
                // 计算进度条百分比
                if (currentLength <= minChars) {
                    percentage = (currentLength / minChars) * 50; // 前50%用于最小值
                    color = '#f44336'; // 红色
                    status = 'too-short';
                } else if (currentLength <= maxChars) {
                    percentage = 50 + ((currentLength - minChars) / (maxChars - minChars)) * 50;
                    color = '#4caf50'; // 绿色
                    status = 'good';
                } else {
                    percentage = 100;
                    color = '#f44336'; // 红色
                    status = 'too-long';
                }
                
                // 更新进度条
                progressBar.css({
                    'width': percentage + '%',
                    'background-color': color
                });
                
                // 更新计数器文字
                var counterText = 'Characters: ' + currentLength;
                if (currentLength < minChars) {
                    counterText += ' (需要至少 ' + minChars + ' 个字符)';
                } else if (currentLength > maxChars) {
                    counterText += ' (超出限制 ' + (currentLength - maxChars) + ' 个字符)';
                } else {
                    counterText += ' / ' + maxChars;
                }
                
                charCounter.text(counterText).css('color', color);
                
                // 控制提交按钮状态
                if (status === 'good') {
                    submitButton.prop('disabled', false).removeClass('wpcomments-disabled');
                } else {
                    submitButton.prop('disabled', true).addClass('wpcomments-disabled');
                }
            }
            
            // 绑定事件
            commentField.on('input keyup paste', function() {
                setTimeout(updateCounter, 10);
            });
            
            // 初始化
            updateCounter();
            
            // 表单提交验证
            commentField.closest('form').on('submit', function(e) {
                var currentLength = commentField.val().length;
                
                if (currentLength < minChars) {
                    e.preventDefault();
                    alert(wpcommentsLimiter.minMessage);
                    commentField.focus();
                    return false;
                }
                
                if (currentLength > maxChars) {
                    e.preventDefault();
                    alert(wpcommentsLimiter.maxMessage);
                    commentField.focus();
                    return false;
                }
            });
        }
    });
    
})(jQuery);