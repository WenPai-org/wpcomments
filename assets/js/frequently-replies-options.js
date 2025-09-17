document.addEventListener('DOMContentLoaded', function() {
    if (typeof postboxes !== 'undefined') {
        postboxes.add_postbox_toggles('wpcomments-frequently-replies');
    }
});

var repliesItemContainer = document.getElementById('replies-item-container');
var addAnotherReplyBtn = document.getElementById('add-another-reply');
var saveOptionsBtn = document.getElementById('save-options');
var form = document.getElementById('wpcomments-frequently-replies-form');

addAnotherReplyBtn.addEventListener('click', function(e) {
    e.preventDefault();
    
    var newIndex = repliesItemContainer.children.length;
    var newReply = wfrNewReply(newIndex);
    repliesItemContainer.insertAdjacentHTML('beforeend', newReply);
    
    if (typeof postboxes !== 'undefined') {
        postboxes.add_postbox_toggles('wpcomments-frequently-replies');
    }
});

repliesItemContainer.addEventListener('click', function(e) {
    if (e.target.classList.contains('wpcomments-remove') || e.target.closest('.wpcomments-remove')) {
        e.preventDefault();
        
        var postbox = e.target.closest('.postbox');
        var titleInput = postbox.querySelector('input[type="text"]');
        var contentTextarea = postbox.querySelector('textarea');
        
        if (titleInput.value.trim() === '' && contentTextarea.value.trim() === '') {
            postbox.remove();
        } else {
            titleInput.value = '';
            contentTextarea.value = '';
        }
    }
});

form.addEventListener('submit', function(e) {
    e.preventDefault();
    
    wfrBlockForm();
    
    var formData = new FormData(form);
    formData.append('action', 'save_wpcomments_frequently_replies');
    formData.append('wpcomments_nonce', wpcommentsFrequentlyRepliesOptions.nonce);
    
    fetch(wpcommentsFrequentlyRepliesOptions.ajaxurl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        wfrUnblockForm();
        
        if (data.success) {
            showSnackBar(data.data.message, 'success');
        } else {
            showSnackBar(data.data.message || wpcommentsFrequentlyRepliesOptions.i18n.error, 'error');
        }
    })
    .catch(error => {
        wfrUnblockForm();
        showSnackBar(wpcommentsFrequentlyRepliesOptions.i18n.error, 'error');
    });
});

function wfrNewReply(index) {
    return '<div class="postbox reply-item">' +
        '<div class="postbox-header">' +
            '<h2 class="hndle">' +
                '<span>' + wpcommentsFrequentlyRepliesOptions.i18n.new_reply + '</span>' +
            '</h2>' +
            '<div class="handle-actions">' +
                '<button type="button" class="handlediv" aria-expanded="true">' +
                    '<span class="screen-reader-text">' + wpcommentsFrequentlyRepliesOptions.i18n.toggle_panel + '</span>' +
                    '<span class="toggle-indicator" aria-hidden="true"></span>' +
                '</button>' +
                '<button type="button" class="wpcomments-remove button-link-delete">' +
                    '<span class="dashicons dashicons-trash"></span>' +
                    '<span class="screen-reader-text">' + wpcommentsFrequentlyRepliesOptions.i18n.remove + '</span>' +
                '</button>' +
            '</div>' +
        '</div>' +
        '<div class="inside">' +
            '<table class="form-table" role="presentation">' +
                '<tbody>' +
                    '<tr>' +
                        '<th scope="row">' +
                            '<label for="replytitle-' + index + '">' + wpcommentsFrequentlyRepliesOptions.i18n.title + '</label>' +
                        '</th>' +
                        '<td>' +
                            '<input type="text" name="replies[' + index + '][title]" class="regular-text" id="replytitle-' + index + '" value="" placeholder="' + wpcommentsFrequentlyRepliesOptions.i18n.title_placeholder + '">' +
                            '<p class="description">' + wpcommentsFrequentlyRepliesOptions.i18n.title_description + '</p>' +
                        '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th scope="row">' +
                            '<label for="replycontent-' + index + '">' + wpcommentsFrequentlyRepliesOptions.i18n.content + '</label>' +
                        '</th>' +
                        '<td>' +
                            '<textarea name="replies[' + index + '][content]" id="replycontent-' + index + '" rows="5" cols="50" placeholder="' + wpcommentsFrequentlyRepliesOptions.i18n.content_placeholder + '"></textarea>' +
                            '<p class="description">' + wpcommentsFrequentlyRepliesOptions.i18n.content_description + '</p>' +
                        '</td>' +
                    '</tr>' +
                '</tbody>' +
            '</table>' +
        '</div>' +
    '</div>';
}

function wfrBlockForm() {
    saveOptionsBtn.disabled = true;
    addAnotherReplyBtn.disabled = true;
    saveOptionsBtn.innerHTML = '<span class="dashicons dashicons-update-alt" style="vertical-align: middle; margin-right: 5px; animation: spin 1s linear infinite;"></span>' + wpcommentsFrequentlyRepliesOptions.i18n.saving;
}

function wfrUnblockForm() {
    saveOptionsBtn.disabled = false;
    addAnotherReplyBtn.disabled = false;
    saveOptionsBtn.innerHTML = '<span class="dashicons dashicons-yes" style="vertical-align: middle; margin-right: 5px;"></span>' + wpcommentsFrequentlyRepliesOptions.i18n.save;
}

function showSnackBar(message, type) {
    var snackbar = document.getElementById('wpcomments-snackbar');
    var messageElement = snackbar.querySelector('p');
    
    messageElement.textContent = message;
    snackbar.className = 'notice notice-' + (type === 'error' ? 'error' : 'success') + ' is-dismissible';
    snackbar.style.display = 'block';
    
    setTimeout(function() {
        snackbar.style.display = 'none';
    }, 5000);
}