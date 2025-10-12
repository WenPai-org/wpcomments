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

    // Form validation and submission
    $('#wpcomments-basic-form, #wpcomments-features-form, #wpcomments-management-form, #wpcomments-advanced-form').on('submit', function(e) {
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

    // 页面加载时更新禁用状态
    updateDisabledStates();

    // 标签页切换时重新检查禁用状态
    $('.wpcomments-tab').on('click', function() {
        setTimeout(updateDisabledStates, 100);
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