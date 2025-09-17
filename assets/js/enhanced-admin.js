jQuery(document).ready(function($) {
    'use strict';
    
    const WPCommentsEnhancedAdmin = {
        init: function() {
            console.log('WPCommentsEnhancedAdmin initializing...');
            this.initPostboxes();
            
            setTimeout(() => {
                this.initSortable();
                this.initPreview();
                this.initAutoSave();
                this.bindEvents();
                console.log('WPCommentsEnhancedAdmin initialization complete');
            }, 100);
        },

        initPostboxes: function() {
            if (typeof postboxes !== 'undefined') {
                postboxes.add_postbox_toggles('wpcomments-frequently-replies');
            }
            
            $('.handlediv').off('click.postboxes').on('click', function(e) {
                e.preventDefault();
                const $postbox = $(this).closest('.postbox');
                const $inside = $postbox.find('.inside');
                
                if ($inside.is(':visible')) {
                    $inside.slideUp(200);
                    $(this).attr('aria-expanded', 'false');
                } else {
                    $inside.slideDown(200);
                    $(this).attr('aria-expanded', 'true');
                }
            });
        },
        
        initSortable: function() {
            const $container = $('#replies-item-container');
            
            if ($container.length === 0) {
                console.error('Sortable container not found: #replies-item-container');
                return;
            }
            
            if (typeof $.fn.sortable === 'undefined') {
                console.error('jQuery UI sortable not available');
                this.loadSortableLibrary();
                return;
            }
            
            $container.sortable({
                handle: '.reply-handle',
                placeholder: 'reply-placeholder',
                tolerance: 'pointer',
                cursor: 'move',
                opacity: 0.8,
                start: function(e, ui) {
                    ui.placeholder.height(ui.item.height());
                    ui.placeholder.addClass('reply-item');
                },
                update: function(e, ui) {
                    WPCommentsEnhancedAdmin.updateOrder();
                    WPCommentsEnhancedAdmin.showNotification('排序已更新', 'success');
                }
            });
            
            this.addSortHandles();
            console.log('Sortable initialized successfully');
        },
        
        loadSortableLibrary: function() {
            console.log('jQuery UI sortable not found, attempting to load...');
            
            if (typeof wp !== 'undefined' && wp.hooks) {
                wp.hooks.addAction('wp_enqueue_scripts', 'wpcomments', function() {
                    wp.enqueue.script('jquery-ui-sortable');
                });
            }
            
            const script = document.createElement('script');
            script.src = 'https://code.jquery.com/ui/1.13.2/jquery-ui.min.js';
            script.onload = () => {
                console.log('jQuery UI loaded successfully');
                this.initSortable();
            };
            script.onerror = () => {
                console.error('Failed to load jQuery UI');
            };
            document.head.appendChild(script);
            
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://code.jquery.com/ui/1.13.2/themes/ui-lightness/jquery-ui.css';
            document.head.appendChild(link);
        },
        
        addSortHandles: function() {
            $('.reply-item').each(function() {
                if (!$(this).find('.reply-handle').length) {
                    $(this).find('.postbox-header h2').prepend(
                        '<span class="reply-handle" title="拖拽排序">' +
                        '<span class="dashicons dashicons-menu"></span>' +
                        '</span>'
                    );
                }
            });
        },
        
        initPreview: function() {
            $('.reply-item').each(function() {
                const $item = $(this);
                const $content = $item.find('textarea[name*="[content]"]');
                
                if (!$item.find('.preview-container').length) {
                    $content.after('<div class="preview-container" style="display:none;"></div>');
                }
                
                if (!$item.find('.preview-toggle').length) {
                    $item.find('.postbox-header .handle-actions').prepend(
                        '<button type="button" class="preview-toggle button-link" title="预览">' +
                        '<span class="dashicons dashicons-visibility"></span>' +
                        '</button>'
                    );
                }
            });
        },
        
        initAutoSave: function() {
            let autoSaveTimer;
            
            $(document).on('input', 'input[name*="[title]"], textarea[name*="[content]"]', function() {
                clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(() => {
                    WPCommentsEnhancedAdmin.autoSave();
                }, 2000);
            });
        },
        
        bindEvents: function() {
            $(document).on('click', '.preview-toggle', this.togglePreview.bind(this));
            $(document).on('click', '.add-new-reply', this.addNewReply.bind(this));
            $(document).on('click', '.delete-reply', this.deleteReply.bind(this));
            $(document).on('input', 'textarea[name*="[content]"]', this.updatePreview.bind(this));
        },
        
        togglePreview: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const $item = $button.closest('.reply-item');
            const $preview = $item.find('.preview-container');
            const $content = $item.find('textarea[name*="[content]"]');
            
            if ($preview.is(':visible')) {
                $preview.hide();
                $button.find('.dashicons').removeClass('dashicons-hidden').addClass('dashicons-visibility');
                $button.attr('title', '预览');
            } else {
                this.renderPreview($content.val(), $preview);
                $preview.show();
                $button.find('.dashicons').removeClass('dashicons-visibility').addClass('dashicons-hidden');
                $button.attr('title', '隐藏预览');
            }
        },
        
        renderPreview: function(content, $container) {
            if (!content.trim()) {
                $container.html('<p class="preview-empty">内容为空</p>');
                return;
            }
            
            const processedContent = content
                .replace(/\n/g, '<br>')
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/`(.*?)`/g, '<code>$1</code>');
            
            $container.html('<div class="preview-content">' + processedContent + '</div>');
        },
        
        updatePreview: function(e) {
            const $textarea = $(e.currentTarget);
            const $item = $textarea.closest('.reply-item');
            const $preview = $item.find('.preview-container');
            
            if ($preview.is(':visible')) {
                this.renderPreview($textarea.val(), $preview);
            }
        },
        
        addNewReply: function(e) {
            e.preventDefault();
            const newIndex = $('.reply-item').length;
            const newReplyHtml = this.getNewReplyTemplate(newIndex);
            
            $('#replies-item-container').append(newReplyHtml);
            
            const $newItem = $('.reply-item').last();
            this.addSortHandles();
            this.initPreview();
            
            $newItem.find('input[name*="[title]"]').focus();
            
            this.showNotification('新回复模板已添加', 'success');
        },
        
        getNewReplyTemplate: function(index) {
            return `
                <div class="postbox reply-item" data-index="${index}">
                    <div class="postbox-header">
                        <h2>
                            <input type="text" name="replies[${index}][title]" 
                                   placeholder="回复标题" class="reply-title-input" />
                        </h2>
                        <div class="handle-actions">
                            <button type="button" class="preview-toggle button-link" title="预览">
                                <span class="dashicons dashicons-visibility"></span>
                            </button>
                            <button type="button" class="delete-reply button-link" title="删除">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    </div>
                    <div class="inside">
                        <textarea name="replies[${index}][content]" 
                                  placeholder="回复内容" 
                                  class="reply-content-textarea" 
                                  rows="4"></textarea>
                        <div class="preview-container" style="display:none;"></div>
                    </div>
                </div>
            `;
        },
        
        deleteReply: function(e) {
            e.preventDefault();
            const $item = $(e.currentTarget).closest('.reply-item');
            
            if (confirm('确定要删除这个回复模板吗？')) {
                $item.fadeOut(300, function() {
                    $(this).remove();
                    WPCommentsEnhancedAdmin.updateOrder();
                    WPCommentsEnhancedAdmin.showNotification('回复模板已删除', 'success');
                });
            }
        },
        
        updateOrder: function() {
            $('.reply-item').each(function(index) {
                const $item = $(this);
                $item.attr('data-index', index);
                $item.find('input[name*="[title]"]').attr('name', `replies[${index}][title]`);
                $item.find('textarea[name*="[content]"]').attr('name', `replies[${index}][content]`);
            });
        },
        
        autoSave: function() {
            const formData = this.getFormData();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wpcomments_auto_save_replies',
                    nonce: wpcommentsRepliesOptions.nonce,
                    replies: formData
                },
                success: function(response) {
                    if (response.success) {
                        WPCommentsEnhancedAdmin.showNotification('自动保存成功', 'info', 2000);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', xhr.responseText);
                    WPCommentsEnhancedAdmin.showNotification('保存失败: ' + error, 'error');
                }
            });
        },
        
        getFormData: function() {
            const replies = [];
            $('.reply-item').each(function() {
                const $item = $(this);
                const title = $item.find('input[name*="[title]"]').val();
                const content = $item.find('textarea[name*="[content]"]').val();
                
                if (title || content) {
                    replies.push({
                        title: title,
                        content: content
                    });
                }
            });
            return replies;
        },
        
        showNotification: function(message, type = 'info', duration = 3000) {
            const $notification = $(`
                <div class="notice notice-${type} is-dismissible enhanced-notification">
                    <p>${message}</p>
                </div>
            `);
            
            $('.wrap h1').after($notification);
            
            setTimeout(() => {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, duration);
        },
        
        testSortable: function() {
            const $container = $('#replies-item-container');
            console.log('Container found:', $container.length > 0);
            console.log('jQuery UI sortable available:', typeof $.fn.sortable !== 'undefined');
            console.log('Reply items count:', $('.reply-item').length);
            
            if ($container.length > 0 && typeof $.fn.sortable !== 'undefined') {
                console.log('Sortable should be working');
                return true;
            } else {
                console.log('Sortable setup failed');
                return false;
            }
        }
    };
    
    WPCommentsEnhancedAdmin.init();
    
    window.WPCommentsEnhancedAdmin = WPCommentsEnhancedAdmin;
});