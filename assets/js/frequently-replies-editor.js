QTags.addButton('wpcomments_frequently_replies', wpcommentsReplies.i18n.btn, wfrOpenThickbox, '', '', wpcommentsReplies.i18n.tip);

function wfrOpenThickbox() {
    var replies = wpcommentsReplies.replies;
    var content = '';
    
    if (replies.length > 0) {
        content += '<select id="wpcomments-replies-select" style="width: 100%; margin-bottom: 10px;">';
        content += '<option value="">' + wpcommentsReplies.i18n.pleaseSelect + '</option>';
        
        for (var i = 0; i < replies.length; i++) {
            content += '<option value="' + replies[i].content + '">' + replies[i].title + '</option>';
        }
        
        content += '</select>';
    } else {
        content += '<p>' + wpcommentsReplies.i18n.noItem + '</p>';
        content += '<p><a href="' + wpcommentsReplies.i18n.optionsUrl + '">' + wpcommentsReplies.i18n.pleaseAdd + ' ' + wpcommentsReplies.i18n.here + '</a></p>';
    }
    
    content += '<div style="text-align: right; margin-top: 15px;">';
    content += '<button id="wpcomments-insert-reply" class="button-primary" style="margin-right: 5px;">' + wpcommentsReplies.i18n.insert + '</button>';
    content += '<button id="wpcomments-cancel-reply" class="button">' + wpcommentsReplies.i18n.cancel + '</button>';
    content += '</div>';
    
    tb_show(wpcommentsReplies.i18n.btn, '#TB_inline?width=400&height=200&inlineId=wpcomments-replies-content');
    
    jQuery('#TB_ajaxContent').html(content);
}

jQuery(document).on('click', '#wpcomments-insert-reply', function(e) {
    e.preventDefault();
    
    var selectedReply = jQuery('#wpcomments-replies-select').val();
    
    if (selectedReply) {
        QTags.insertContent(selectedReply);
        tb_remove();
    } else {
        alert(wpcommentsReplies.i18n.pleaseSelect);
    }
});

jQuery(document).on('click', '#wpcomments-cancel-reply', function(e) {
    e.preventDefault();
    tb_remove();
});