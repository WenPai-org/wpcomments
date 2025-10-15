jQuery(document).ready(function($) {
    // 检查对象是否存在，如果不存在则创建默认值
    if (typeof wpcomments === 'undefined') {
        window.wpcomments = {
            nonce: '',
            ajaxurl: ajaxurl || '',
            plugin_url: '',
            assets_url: '',
            is_network_admin: '0',
            is_multisite: '0'
        };
    }

    if (typeof wpcomments_l10n === 'undefined') {
        window.wpcomments_l10n = {
            settings_saved: '设置已成功保存。'
        };
    }

    // Tab navigation - 统一 wpavatar 样式
    $('.wpcomments-tab').on('click', function() {
        var tab = $(this).data('tab');
        if (!tab) return;

        $('.wpcomments-tab').removeClass('active');
        $(this).addClass('active');

        $('.wpcomments-section').hide();
        $('#wpcomments-section-' + tab).show();

        if (window.history && window.history.pushState) {
            var url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            window.history.pushState({}, '', url);
        }
    });

    // Form validation and submission（含网络设置表单）
    $('#wpcomments-basic-form, #wpcomments-features-form, #wpcomments-management-form, #wpcomments-advanced-form, #wpcomments-network-form, #wpcomments-email-settings-form').on('submit', function(e) {
        var $form = $(this);
        var $status = $('#wpcomments-status');
        var $submitButton = $form.find('button[type="submit"]');
        
        // 显示保存状态
        $submitButton.prop('disabled', true).text('保存中...');
        $status.removeClass('notice-error notice-success').addClass('notice notice-info').html('<p>正在保存设置...</p>').show();
        
        // 让表单正常提交到 options.php
        setTimeout(function() {
            $status.removeClass('notice-info').addClass('notice-success').html('<p>' + wpcomments_l10n.settings_saved + '</p>');
            $submitButton.prop('disabled', false).text('保存设置');
            
            setTimeout(function() {
                $status.fadeOut();
            }, 3000);
        }, 1000);
        
        return true;
    });

    // 网络管理工具
    $('#sync-all-sites').on('click', function() {
        if (!confirm('确定要将当前网络设置同步到所有站点吗？此操作将覆盖各站点的现有设置。')) {
            return;
        }
        
        var $button = $(this);
        $button.prop('disabled', true).text('同步中...');
        
        // 这里可以添加 AJAX 请求来执行同步操作
        setTimeout(function() {
            $button.prop('disabled', false).text('同步设置到所有站点');
            alert('设置同步完成！');
        }, 2000);
    });

    // 网络控制选项管理
    $('#select-all-options').on('click', function() {
        $('input[name="wpcomments_network_controlled_options[]"]').prop('checked', true);
    });

    $('#deselect-all-options').on('click', function() {
        $('input[name="wpcomments_network_controlled_options[]"]').prop('checked', false);
    });

    $('#reset-default-options').on('click', function() {
        if (confirm('确定要重置所有控制选项吗？该操作会将所有站点恢复为各自的设置。')) {
            var defaultOptions = ['wpcomments_disable_comments', 'wpcomments_enable_validation', 'wpcomments_enable_role_badge'];

            $('input[name="wpcomments_network_controlled_options[]"]').each(function() {
                $(this).prop('checked', defaultOptions.indexOf($(this).val()) !== -1);
            });
        }
    });

    // 导出/导入网络设置
    $('#export-network-settings').on('click', function() {
        // 这里可以添加导出功能
        alert('导出功能开发中...');
    });

    $('#import-network-settings').on('click', function() {
        // 这里可以添加导入功能
        alert('导入功能开发中...');
    });

    // 处理禁用状态的视觉效果
    function updateDisabledStates() {
        $('input:disabled').each(function() {
            var $input = $(this);
            var $row = $input.closest('tr');
            
            // 添加禁用状态的视觉效果
            $row.addClass('wpcomments-disabled-row');
            
            // 如果是开关按钮，添加特殊样式
            if ($input.closest('.wpcomments-switch').length) {
                $input.closest('.wpcomments-switch').addClass('wpcomments-switch-disabled');
            }
        });
    }

    // 处理"完全禁用评论功能"选项的联动逻辑
    function handleDisableCommentsLinkage() {
        var $disableCommentsCheckbox = $('input[name="wpcomments_disable_comments"]');
        var $relatedOptions = $('input[name="wpcomments_enable_validation"], input[name="wpcomments_enable_remove_website_field"], input[name="wpcomments_enable_private_comments"], input[name="wpcomments_enable_comment_limiter"]');
        
        function toggleRelatedOptions() {
            var isDisabled = $disableCommentsCheckbox.is(':checked');
            
            $relatedOptions.each(function() {
                var $input = $(this);
                var $row = $input.closest('tr');
                var $switch = $input.closest('.wpcomments-switch');
                
                if (isDisabled) {
                    // 禁用相关选项
                    $input.prop('disabled', true);
                    $row.addClass('wpcomments-disabled-row');
                    $switch.addClass('wpcomments-switch-disabled');
                    
                    // 取消勾选
                    $input.prop('checked', false);
                } else {
                    // 启用相关选项（如果不是被网络设置禁用的话）
                    if (!$input.data('network-disabled')) {
                        $input.prop('disabled', false);
                        $row.removeClass('wpcomments-disabled-row');
                        $switch.removeClass('wpcomments-switch-disabled');
                    }
                }
            });
            
            // 处理子设置区域
            var $privateCommentsCard = $('.wpcomments-stats-card').filter(function() {
                return $(this).find('h3').text().indexOf('私密评论设置') > -1;
            });
            var $limiterCard = $('.wpcomments-stats-card').filter(function() {
                return $(this).find('h3').text().indexOf('评论字符限制设置') > -1;
            });
            
            if (isDisabled) {
                $privateCommentsCard.hide();
                $limiterCard.hide();
            } else {
                // 根据对应的主选项状态显示/隐藏子设置
                if ($('input[name="wpcomments_enable_private_comments"]').is(':checked')) {
                    $privateCommentsCard.show();
                }
                if ($('input[name="wpcomments_enable_comment_limiter"]').is(':checked')) {
                    $limiterCard.show();
                }
            }
        }
        
        // 页面加载时执行一次
        toggleRelatedOptions();
        
        // 监听复选框变化
        $disableCommentsCheckbox.on('change', toggleRelatedOptions);
    }

    // 处理子功能选项的联动逻辑
    function handleSubOptionsLinkage() {
        // 私密评论设置联动
        $('input[name="wpcomments_enable_private_comments"]').on('change', function() {
            var $privateCommentsCard = $('.wpcomments-stats-card').filter(function() {
                return $(this).find('h3').text().indexOf('私密评论设置') > -1;
            });
            
            if ($(this).is(':checked') && !$('input[name="wpcomments_disable_comments"]').is(':checked')) {
                $privateCommentsCard.show();
            } else {
                $privateCommentsCard.hide();
            }
        });
        
        // 评论字符限制设置联动
        $('input[name="wpcomments_enable_comment_limiter"]').on('change', function() {
            var $limiterCard = $('.wpcomments-stats-card').filter(function() {
                return $(this).find('h3').text().indexOf('评论字符限制设置') > -1;
            });
            
            if ($(this).is(':checked') && !$('input[name="wpcomments_disable_comments"]').is(':checked')) {
                $limiterCard.show();
            } else {
                $limiterCard.hide();
            }
        });
        
        // 举报系统设置联动
        $('input[name="wpcomments_enable_report_comments"]').on('change', function() {
            var $reportCard = $('.wpcomments-stats-card').filter(function() {
                return $(this).find('h3').text().indexOf('举报系统设置') > -1;
            });
            
            if ($(this).is(':checked')) {
                $reportCard.show();
            } else {
                $reportCard.hide();
            }
        });
    }

    // 处理功能增强标签页的联动逻辑
    function handleFeaturesLinkage() {
        // 初始化举报系统设置的显示状态
        var $reportCheckbox = $('input[name="wpcomments_enable_report_comments"]');
        var $reportCard = $('.wpcomments-stats-card').filter(function() {
            return $(this).find('h3').text().indexOf('举报系统设置') > -1;
        });
        
        if ($reportCheckbox.is(':checked')) {
            $reportCard.show();
        } else {
            $reportCard.hide();
        }
    }

    // 管理工具标签页的联动逻辑（常用回复、删除待审核）
    function handleManagementLinkage() {
        var $form = $('#wpcomments-management-form');
        if (!$form.length) return;

        var $freqCheckbox = $form.find('input[name="wpcomments_enable_frequently_replies"]');
        var $deleteCheckbox = $form.find('input[name="wpcomments_enable_delete_pending"]');

        // 查找现有卡片，如果不存在则创建占位卡片
        function getOrCreateCard(type) {
            var selectorMap = {
                frequently: 'a[href*="wpcomments-frequently-replies"]',
                deletePending: 'a[href*="wpcomments-delete-pending"]'
            };

            var $existing = $form.find('.wpcomments-stats-card').filter(function() {
                return $(this).find(selectorMap[type]).length > 0;
            });

            if ($existing.length) {
                return $existing.first();
            }

            var html = '';
            if (type === 'frequently') {
                html = [
                    '<div class="wpcomments-stats-card" id="wpcomments-frequently-replies-card">',
                    '  <h3>常用回复管理</h3>',
                    '  <p>管理您的常用回复模板，提高评论回复效率。</p>',
                    '  <div class="wpcomments-action-buttons">',
                    '    <a href="edit-comments.php?page=wpcomments-frequently-replies" class="button button-secondary">管理常用回复</a>',
                    '  </div>',
                    '</div>'
                ].join('');
            } else if (type === 'deletePending') {
                html = [
                    '<div class="wpcomments-stats-card" id="wpcomments-delete-pending-card">',
                    '  <h3>删除待审核评论</h3>',
                    '  <p>批量删除站点内所有待审核状态的评论。</p>',
                    '  <div class="wpcomments-action-buttons">',
                    '    <a href="edit-comments.php?page=wpcomments-delete-pending" class="button button-secondary">打开删除工具</a>',
                    '  </div>',
                    '</div>'
                ].join('');
            }

            if (!html) return $();

            var $card = $(html);
            // 插入到提交按钮之前，与后端渲染位置保持一致
            var $submitWrapper = $form.find('.wpcomments-submit-wrapper');
            if ($submitWrapper.length) {
                $submitWrapper.before($card);
            } else {
                $form.append($card);
            }
            return $card;
        }

        var $freqCard = getOrCreateCard('frequently');
        var $deleteCard = getOrCreateCard('deletePending');

        function toggleCards() {
            // 仅当复选框启用且已选中时显示卡片
            if ($freqCheckbox.length) {
                if ($freqCheckbox.is(':checked') && !$freqCheckbox.is(':disabled')) {
                    $freqCard.show();
                } else {
                    $freqCard.hide();
                }
            }

            if ($deleteCheckbox.length) {
                if ($deleteCheckbox.is(':checked') && !$deleteCheckbox.is(':disabled')) {
                    $deleteCard.show();
                } else {
                    $deleteCard.hide();
                }
            }
        }

        // 初始化一次
        toggleCards();

        // 监听开关变化
        $freqCheckbox.on('change', toggleCards);
        $deleteCheckbox.on('change', toggleCards);
    }

    // 页面加载时更新禁用状态
    updateDisabledStates();
    
    // 初始化联动逻辑
    handleDisableCommentsLinkage();
    handleSubOptionsLinkage();
    handleFeaturesLinkage();
    handleManagementLinkage();

    // 标签页切换时重新检查禁用状态和联动逻辑
    $('.wpcomments-tab').on('click', function() {
        setTimeout(function() {
            updateDisabledStates();
            handleDisableCommentsLinkage();
            handleSubOptionsLinkage();
            handleFeaturesLinkage();
            handleManagementLinkage();
        }, 100);
    });

    // Settings saved notification
    if (window.location.search.indexOf('settings-updated=true') > -1) {
        $('#wpcomments-status')
            .removeClass('notice-error')
            .addClass('notice-success')
            .text(wpcomments_l10n.settings_saved)
            .show()
            .delay(3000)
            .fadeOut();
    }

    // Set active tab
    var currentTab = '';

    if (window.location.search.indexOf('tab=') > -1) {
        var urlParams = new URLSearchParams(window.location.search);
        currentTab = urlParams.get('tab');
    }

    if (!currentTab) {
        var $activeTab = $('.wpcomments-tab.active');
        if ($activeTab.length) {
            currentTab = $activeTab.data('tab');
        }
    }

    if (currentTab) {
        $('.wpcomments-tab[data-tab="' + currentTab + '"]').trigger('click');
    } else {
        $('.wpcomments-tab:first').trigger('click');
    }

    // 兼容旧的 nav-tab 样式
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        var targetTab = $(this).attr('href').split('tab=')[1];
        
        // Update active tab
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Show/hide tab content
        $('.wpcomments-tab-content > div').hide();
        $('#wpcomments-tab-' + targetTab).show();
        
        // Update URL without page reload
        if (history.pushState) {
            var newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?page=wpcomments-settings&tab=' + targetTab;
            window.history.pushState({path: newUrl}, '', newUrl);
        }
    });
    
    // Show active tab on page load (兼容旧样式)
    var urlParams = new URLSearchParams(window.location.search);
    var activeTab = urlParams.get('tab') || 'general';
    $('.nav-tab[href*="tab=' + activeTab + '"]').addClass('nav-tab-active');
    $('.wpcomments-tab-content > div').hide();
    $('#wpcomments-tab-' + activeTab).show();
});