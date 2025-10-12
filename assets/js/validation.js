/**
 * WPComments 前端验证脚本
 * 
 * 合并评论表单验证功能
 */

jQuery(document).ready(function($) {
    // 评论表单验证
    if ($('#commentform').length) {
        $('#commentform').on('submit', function(e) {
            var isValid = true;
            var $form = $(this);
            
            // 清除之前的错误提示
            $form.find('.error').removeClass('error');
            $form.find('label.error').remove();
            
            // 验证姓名
            var $author = $form.find('#author');
            if ($author.length && $author.val().trim() === '') {
                showError($author, wpcomments_validation.required_name);
                isValid = false;
            }
            
            // 验证邮箱
            var $email = $form.find('#email');
            if ($email.length) {
                var emailVal = $email.val().trim();
                if (emailVal === '') {
                    showError($email, wpcomments_validation.required_email);
                    isValid = false;
                } else if (!isValidEmail(emailVal)) {
                    showError($email, wpcomments_validation.invalid_email);
                    isValid = false;
                }
            }
            
            // 验证评论内容
            var $comment = $form.find('#comment');
            if ($comment.length && $comment.val().trim() === '') {
                showError($comment, wpcomments_validation.required_comment);
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                // 滚动到第一个错误字段
                var $firstError = $form.find('.error').first();
                if ($firstError.length) {
                    $('html, body').animate({
                        scrollTop: $firstError.offset().top - 100
                    }, 300);
                    $firstError.focus();
                }
            }
        });
    }
    
    // 显示错误信息
    function showError($field, message) {
        $field.addClass('error');
        $field.after('<label class="error">' + message + '</label>');
    }
    
    // 验证邮箱格式
    function isValidEmail(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // 实时验证
    $('#commentform input, #commentform textarea').on('blur', function() {
        var $field = $(this);
        var fieldId = $field.attr('id');
        
        // 移除之前的错误状态
        $field.removeClass('error');
        $field.next('label.error').remove();
        
        // 根据字段类型进行验证
        switch(fieldId) {
            case 'author':
                if ($field.val().trim() === '') {
                    showError($field, wpcomments_validation.required_name);
                }
                break;
            case 'email':
                var emailVal = $field.val().trim();
                if (emailVal === '') {
                    showError($field, wpcomments_validation.required_email);
                } else if (!isValidEmail(emailVal)) {
                    showError($field, wpcomments_validation.invalid_email);
                }
                break;
            case 'comment':
                if ($field.val().trim() === '') {
                    showError($field, wpcomments_validation.required_comment);
                }
                break;
        }
    });
    
    // 输入时清除错误状态
    $('#commentform input, #commentform textarea').on('input', function() {
        var $field = $(this);
        if ($field.hasClass('error') && $field.val().trim() !== '') {
            $field.removeClass('error');
            $field.next('label.error').remove();
        }
    });
});