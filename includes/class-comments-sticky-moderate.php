<?php

namespace WpComments;

defined( 'ABSPATH' ) || exit;

class Comments_Sticky_Moderate {

    public function __construct() {
        add_action( 'admin_footer-edit-comments.php', array( $this, 'print_script' ) );
    }

    public function print_script(): void {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function() {
                jQuery('.wp-list-table .comment').each(function() {
                    var row                 = jQuery(this),
                        columnCommentRow  = row.find('.column-comment'),
                        commentRowActions = columnCommentRow.find('.row-actions').detach();

                    commentRowActions.prependTo(columnCommentRow);
                });
            });
        </script>
        <?php
    }
}