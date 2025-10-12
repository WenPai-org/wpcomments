(function($) {
    'use strict';
    
    $(document).ready(function() {
        // 初始化图表
        initTrendChart();
        initStatusChart();
    });
    
    function initTrendChart() {
        const ctx = document.getElementById('comments-trend-chart');
        if (!ctx) return;
        
        // 获取趋势数据
        $.ajax({
            url: wpcommentsAnalytics.ajaxurl,
            type: 'POST',
            data: {
                action: 'wpcomments_analytics_data',
                type: 'trend',
                nonce: wpcommentsAnalytics.nonce
            },
            success: function(response) {
                if (response.success) {
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: response.data.labels,
                            datasets: [{
                                label: '每日评论数',
                                data: response.data.data,
                                borderColor: '#0073aa',
                                backgroundColor: 'rgba(0, 115, 170, 0.1)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    }
                                }
                            }
                        }
                    });
                } else {
                    showError(ctx, wpcommentsAnalytics.i18n.error);
                }
            },
            error: function() {
                showError(ctx, wpcommentsAnalytics.i18n.error);
            }
        });
    }
    
    function initStatusChart() {
        const ctx = document.getElementById('comments-status-chart');
        if (!ctx) return;
        
        // 获取状态数据
        $.ajax({
            url: wpcommentsAnalytics.ajaxurl,
            type: 'POST',
            data: {
                action: 'wpcomments_analytics_data',
                type: 'status',
                nonce: wpcommentsAnalytics.nonce
            },
            success: function(response) {
                if (response.success) {
                    new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: response.data.labels,
                            datasets: [{
                                data: response.data.data,
                                backgroundColor: [
                                    '#4CAF50',  // 已批准 - 绿色
                                    '#FF9800',  // 待审核 - 橙色
                                    '#F44336',  // 垃圾评论 - 红色
                                    '#9E9E9E'   // 回收站 - 灰色
                                ],
                                borderWidth: 2,
                                borderColor: '#fff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 20,
                                        usePointStyle: true
                                    }
                                }
                            }
                        }
                    });
                } else {
                    showError(ctx, wpcommentsAnalytics.i18n.error);
                }
            },
            error: function() {
                showError(ctx, wpcommentsAnalytics.i18n.error);
            }
        });
    }
    
    function showError(ctx, message) {
        const container = ctx.closest('.wpcomments-chart-container');
        const errorDiv = $('<div class="wpcomments-chart-error" style="text-align: center; padding: 40px; color: #666;">' + message + '</div>');
        $(ctx).hide();
        container.append(errorDiv);
    }
    
})(jQuery);