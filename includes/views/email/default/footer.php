<?php
/**
 * Email Footer
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$footerText = apply_filters('wppayform/email_template_footer_text','&copy; '.get_bloginfo( 'name', 'display' ).'.', $submission, $notification);;
?>
</div></td></tr></table></td></tr></table></td></tr></table>
<table border="0" cellpadding="10" cellspacing="0" width="600" id="template_footer"><tr><td valign="top"><table border="0" cellpadding="10" cellspacing="0" width="100%"><tr><td class="fluent_credit" colspan="2" valign="middle" id="credit">
<span><?php echo $footerText; ?></span>
<?php do_action( 'wppayform/email_template_after_footer', $submission, $notification );?>
</td></tr></table></td></tr></table></td></tr></table></div></body></html>