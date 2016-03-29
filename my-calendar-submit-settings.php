<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_filter( 'mcs_submission_fee', 'mcs_format_submission_fee' );
function mcs_format_submission_fee( $fee ) {
	return sprintf("%01.2f", $fee );
}

function mcs_update_settings( $post ) {
	if ( isset($post['mc-submit-settings']) ) {
		$nonce = $_POST['_wpnonce'];
		if ( !wp_verify_nonce( $nonce,'my-calendar-submissions' ) ) return;	
		$mcs_date_format = $post['mcs_date_format'];
		$mcs_submit_id = (int) $post['mcs_submit_id'];
		$mcs_time_format = $post['mcs_time_format'];
		$mcs_response = $post['mcs_response'];	// admin email after submission
		$mcs_confirmation = $post['mcs_confirmation'];	// submitter email after submission
		$mcs_to = is_email( $post['mcs_to'] ); // send to
		$mcs_from = is_email( $post['mcs_from'] ); // send from
		$mcs_subject = $post['mcs_subject']; // subject line
		$mcs_edit_subject = $post['mcs_edit_subject']; // subject line
		$mcs_confirmation_subject = $post['mcs_confirmation_subject'];
		$mcs_edit_confirmation_subject = $post['mcs_edit_confirmation_subject'];
		$mcs_criteria = $post['mcs_criteria']; // who can submit events
		$mcs_html_email = ( isset( $post['mcs_html_email'] ) )?'true':'false'; // send as HTML
		$mcs_check_conflicts = ( isset($post['mcs_check_conflicts']) )?'true':'false'; // are conflicts a problem?
		$mcs_upload_images = ( isset($post['mcs_upload_images']) )?'true':'false';
		$mcs_automatic_approval = ( isset($post['mcs_automatic_approval']) )?'true':'false'; // is approval required?
		$mcs_dont_send_submitter_email = ( isset( $post['mcs_dont_send_submitter_email'] ) ) ? 'true' : 'false'; // disable emails when approval is automatic
		$mcs_dont_send_admin_email = ( isset( $post['mcs_dont_send_admin_email'] ) ) ? 'true' : 'false'; // disable emails when approval is automatic

		update_option( 'mcs_to',$mcs_to );
		update_option( 'mcs_check_conflicts',$mcs_check_conflicts );
		update_option( 'mcs_upload_images',$mcs_upload_images );
		update_option( 'mcs_automatic_approval', $mcs_automatic_approval );
		update_option( 'mcs_date_format', $mcs_date_format );
		update_option( 'mcs_submit_id', $mcs_submit_id );
		update_option( 'mcs_time_format', $mcs_time_format );
		update_option( 'mcs_from',$mcs_from );
		update_option( 'mcs_response',$mcs_response );
		update_option( 'mcs_confirmation',$mcs_confirmation );	
		update_option( 'mcs_subject',$mcs_subject );
		update_option( 'mcs_edit_subject',$mcs_edit_subject );
		update_option( 'mcs_confirmation_subject',$mcs_confirmation_subject );
		update_option( 'mcs_edit_confirmation_subject',$mcs_edit_confirmation_subject );
		update_option( 'mcs_criteria',$mcs_criteria );
		update_option( 'mcs_html_email', $mcs_html_email );
		update_option( 'mcs_dont_send_submitter_email', $mcs_dont_send_submitter_email );
		update_option( 'mcs_dont_send_admin_email', $mcs_dont_send_admin_email );
		do_action( 'mcs_settings_update', $_POST );		
		return "<div class=\"updated\"><p><strong>".__('My Calendar Event Submission Settings saved','my-calendar-submissions').".$verify</strong></p></div>";
	}
	if ( isset($post['mc-payment-settings']) ) {
		$nonce = $_POST['_wpnonce'];
		if ( !wp_verify_nonce( $nonce,'my-calendar-submissions' ) ) return;	
		$mcs_payments = ( isset($post['mcs_payments']) )?'true':'false'; // are payments required?
		$mcs_gateway = ( isset($post['mcs_gateway']) ) ? $post['mcs_gateway'] : 'paypal' ;
		$mcs_payment_response = $post['mcs_payment_response'];	// admin email after submission
		$mcs_payment_confirmation = $post['mcs_payment_confirmation'];	// submitter email after submission
		$mcs_payment_subject = $post['mcs_payment_subject']; // subject line
		$mcs_payment_message = $post['mcs_payment_message'];
		$mcs_payment_confirmation_subject = $post['mcs_payment_confirmation_subject'];	
		$mcs_use_sandbox = ( isset($post['mcs_use_sandbox']) )?'true':'false'; // Using sandbox?
		$mcs_submission_fee = apply_filters( 'mcs_submission_fee', $post['mcs_submission_fee'] ); // posting cost for public
		$mcs_members_discount = (int) preg_replace( '/\D/', '',$post['mcs_members_discount'] ); // discount for members (percentage)
		$mcs_paypal_email  = is_email( $post['mcs_paypal_email'] ); // paypal email
		$mcs_purchase_page = ( isset( $post['mcs_purchase_page'] ) ) ? (int) $post['mcs_purchase_page'] : '';
		$mcs_ssl = ( isset($post['mcs_ssl']) )?'true':'false'; 
		$mcs_paypal_merchant_id = $post['mcs_paypal_merchant_id']; // paypal merchant ID
		$mcs_authnet_api = ( isset( $post['mcs_authnet_api'] ) ) ? $post['mcs_authnet_api'] : '';
		$mcs_authnet_key = ( isset( $post['mcs_authnet_key'] ) ) ? $post['mcs_authnet_key'] : '';
		$mcs_authnet_hash = ( isset( $post['mcs_authnet_hash'] ) ) ? $post['mcs_authnet_hash'] : '';
		$mcs_button = $post['mcs_button'];
		$mcs_currency = $post['mcs_currency'];
		$mcs_quantity = ( isset($post['mcs_quantity']) )?'true':'false'; // are payments required?
		$mcs_discount = $post['mcs_discount']; // sets up default sales
		
		update_option( 'mcs_payments',$mcs_payments );
		update_option( 'mcs_gateway',$mcs_gateway );
		update_option( 'mcs_authnet_api', $mcs_authnet_api );
		update_option( 'mcs_authnet_key', $mcs_authnet_key );
		update_option( 'mcs_authnet_hash', $mcs_authnet_hash );
		update_option( 'mcs_discount',$mcs_discount );
		update_option( 'mcs_quantity',$mcs_quantity );
		update_option( 'mcs_payment_response',$mcs_payment_response );
		update_option( 'mcs_payment_message',$mcs_payment_message );
		update_option( 'mcs_payment_confirmation',$mcs_payment_confirmation );	
		update_option( 'mcs_payment_subject',$mcs_payment_subject );
		update_option( 'mcs_payment_confirmation_subject',$mcs_payment_confirmation_subject );	
		update_option( 'mcs_use_sandbox',$mcs_use_sandbox );
		update_option( 'mcs_submission_fee',$mcs_submission_fee );
		update_option( 'mcs_members_discount',$mcs_members_discount );
		update_option( 'mcs_paypal_email',$mcs_paypal_email );
		update_option( 'mcs_purchase_page', $mcs_purchase_page );
		update_option( 'mcs_paypal_merchant_id',$mcs_paypal_merchant_id );
		update_option( 'mcs_currency',$mcs_currency );
		update_option( 'mcs_ssl', $mcs_ssl );
		update_option( 'mcs_button',$mcs_button );
		do_action( 'mcs_payment_settings_update', $_POST );
		if ( $mcs_ssl == 'true' && !$mcs_purchase_page ) {
			$append = "<p>".__( 'You must indicate the page ID of your purchase form in order to use SSL.', 'my-calendar-submissions' )."</p>";
		} else {
			$append = '';
		}
		return "<div class=\"updated\"><p><strong>".__('My Calendar Payment Settings saved','my-calendar-submissions')."</strong>$append</p></div>";
	}
	$custom_return = apply_filters( 'mcs_custom_settings_update', false, $_POST );
	if ( $custom_return ) {
		return $custom_return;
	}
	
	return false;
}

function mcs_settings() {
	mcs_check();
	$response = mcs_update_settings($_POST);
	echo $response;		
	$options = get_option('mcs_options');
	$defaults = $options['defaults'];
	$mcs_to = get_option('mcs_to'); // send to
	$mcs_from = get_option('mcs_from'); // send from
	$mcs_subject = ( get_option('mcs_subject') )?get_option('mcs_subject'):$defaults['mcs_subject']; // subject line
	$mcs_edit_subject = ( get_option('mcs_edit_subject') )?get_option('mcs_edit_subject'):$defaults['mcs_subject']; // subject line
	$mcs_response = ( get_option('mcs_response') )?get_option('mcs_response'):$defaults['mcs_response'];	// admin email after submission
	$mcs_confirmation = ( get_option('mcs_confirmation') )?get_option('mcs_confirmation'):$defaults['mcs_confirmation'];	// submitter email after submission
	$mcs_confirmation_subject = ( get_option('mcs_confirmation_subject') )?get_option('mcs_confirmation_subject'):$defaults['mcs_confirmation_subject']; // subject line
	$mcs_edit_confirmation_subject = ( get_option('mcs_edit_confirmation_subject') )?get_option('mcs_edit_confirmation_subject'):$defaults['mcs_confirmation_subject']; // subject line
	$mcs_payments = ( get_option('mcs_payments') )?get_option('mcs_payments'):$defaults['mcs_payments']; // are payments required?
	//$mcs_payments_approved = ( get_option('mcs_payments_approved') )?get_option('mcs_payments_approved'):$defaults['mcs_payments_approved']; // paid submissions auto approved
	$mcs_payment_subject = ( get_option('mcs_payment_subject') )?get_option('mcs_payment_subject'):$defaults['mcs_payment_subject']; // subject line
	$mcs_payment_response = ( get_option('mcs_payment_response') )?get_option('mcs_payment_response'):$defaults['mcs_payment_response'];	// admin email after submission
	$mcs_payment_confirmation = ( get_option('mcs_payment_confirmation') )?get_option('mcs_payment_confirmation'):$defaults['mcs_payment_confirmation'];	// submitter email after submission
	$mcs_payment_confirmation_subject = ( get_option('mcs_payment_confirmation_subject') )?get_option('mcs_payment_confirmation_subject'):$defaults['mcs_payment_confirmation_subject']; // subject line
	$mcs_payment_message = ( get_option('mcs_payment_message') )?get_option('mcs_payment_message'):$defaults['mcs_payment_message']; // subject line
	$mcs_submission_fee = ( get_option('mcs_submission_fee') )?get_option('mcs_submission_fee'):$defaults['mcs_submission_fee']; // posting cost for public
	$mcs_members_discount = get_option('mcs_members_discount'); // discount for members (percentage)
	$mcs_criteria = ( get_option('mcs_criteria') )?get_option('mcs_criteria'):$defaults['mcs_criteria']; // who can submit events
	$mcs_paypal_email  = get_option('mcs_paypal_email'); // paypal email
	$mcs_purchase_page	= get_option( 'mcs_purchase_page' ); 
	$mcs_use_sandbox = ( get_option('mcs_use_sandbox') )?get_option('mcs_use_sandbox'):$defaults['mcs_use_sandbox']; // use sandbox
	$mcs_paypal_merchant_id = get_option('mcs_paypal_merchant_id'); // paypal merchant ID
	$mcs_button = get_option('mcs_button');
	$mcs_submit_id = get_option('mcs_submit_id');
	$mcs_date_format = get_option('mcs_date_format');
	$mcs_time_format = get_option('mcs_time_format');
	$mcs_currency = get_option('mcs_currency');
	$mcs_quantity = get_option('mcs_quantity');
	$mcs_discount = get_option('mcs_discount');
	$mcs_gateway = get_option( 'mcs_gateway' );
	$mcs_authnet_api = get_option( 'mcs_authnet_api' );
	$mcs_authnet_key = get_option( 'mcs_authnet_key' );
	$mcs_authnet_hash = get_option( 'mcs_authnet_hash' );
	$mcs_check_conflicts = get_option('mcs_check_conflicts');
	$mcs_upload_images = get_option( 'mcs_upload_images' );
	$mcs_automatic_approval = get_option('mcs_automatic_approval');
	$mcs_dont_send_submitter_email = get_option( 'mcs_dont_send_submitter_email' );
	$mcs_dont_send_admin_email = get_option( 'mcs_dont_send_admin_email' );
?>
    <div class="wrap jd-my-calendar" id="mc_settings">
	<?php my_calendar_check_db();?>
	<h2><?php _e('My Calendar Pro','my-calendar-submissions'); ?></h2>
	<div class="mc-tabs mcs-settings settings postbox-container jcd-wide">
		<ul class="tabs" role="tablist">
			<li role="tab" id="tab_mcs" aria-controls="mcs_tab"><a href="#mcs_tab"><?php _e( 'Submissions', 'my-calendar' ); ?></a></li>
			<li role="tab" id="tab_mcs_payments" aria-controls="mcs_payments_tab"><a href="#mcs_payments_tab"><?php _e( 'Payments', 'my-calendar' ); ?></a></li>
			<?php 
				$tabs = apply_filters( 'mcs_settings_tabs', array() ); 
				foreach ( $tabs as $key => $value ) {
					$key = sanitize_title( $key );
					$value = esc_html( $value );
					echo '<li role="tab" id="tab_mcs_' . $key . '" aria-controls="mcs_' . $key . '_tab"><a href="#mcs_' . $key . '_tab">' . $value . '</a></li>' . "\n";
				}
			?>
		</ul>
		
	<div class="metabox-holder wptab" aria-labelledby="tab_mcs" role="tabpanel" aria-live="assertive" id="mcs_tab">
		
		<div class="ui-sortable meta-box-sortables">   
		<div class="postbox">
			<h3><?php _e('Event Submissions Settings','my-calendar-submissions'); ?></h3>
			<div class="inside">
			<p>
			<a href="<?php echo admin_url('admin.php?page=my-calendar-manage&amp;limit=reserved#my-calendar-admin-table'); ?>"><?php _e('View pending event submissions','my-calendar-submissions'); ?></a>
			</p>
			<form method="post" action="<?php echo admin_url("admin.php?page=my-calendar-submissions"); ?>">
			<div><input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('my-calendar-submissions'); ?>" /></div>
			<p>
			<label for="mcs_submit_id"><?php _e('Event Submission Page ID','my-calendar-submissions'); ?></label> <input type="text" name="mcs_submit_id" id="mcs_submit_id" size="6" value="<?php echo esc_attr(trim($mcs_submit_id)); ?>" />
			</p>			
			<p class='format'>
			<label for="mcs_date_format"><?php _e('Date format hint','my-calendar-submissions'); ?></label> <select name="mcs_date_format" id="mcs_date_format">
			<option value="m/d/Y" <?php echo mc_is_selected( 'mcs_date_format', 'm/d/y'); ?>><?php echo date('m/d/Y'); ?></option>
			<option value="d-m-Y" <?php echo mc_is_selected( 'mcs_date_format', 'd-m-Y'); ?>><?php echo date('d-m-Y'); ?></option>
			<option value="Y-m-d" <?php echo mc_is_selected( 'mcs_date_format', 'Y-m-d'); ?>><?php echo date('Y-m-d'); ?></option>
			<option value="j F Y" <?php echo mc_is_selected( 'mcs_date_format', 'j F Y'); ?>><?php echo date_i18n('j F Y'); ?></option>
			<option value="M j, Y" <?php echo mc_is_selected( 'mcs_date_format', 'M j, Y'); ?>><?php echo date('M j, Y'); ?></option>
			</select>
			</p>
			<p class='format'>
			<label for="mcs_time_format"><?php _e('Time format hint','my-calendar-submissions'); ?></label> <select name="mcs_time_format" id="mcs_time_format">
			<option value="h:i a" <?php echo mc_is_selected( 'mcs_time_format', 'h:i a'); ?>><?php echo date('h:i a'); ?></option>
			<option value="H:i" <?php echo mc_is_selected( 'mcs_time_format', 'H:i'); ?>><?php echo date('H:i'); ?></option>
			</select>
			</p>			
			<p>
			<input type="checkbox" id="mcs_check_conflicts" name="mcs_check_conflicts" <?php mc_is_checked('mcs_check_conflicts','true'); ?> /> <label for="mcs_check_conflicts"><?php _e('Prevent conflicting events. (if locations are used, checks only for conflicts at that location.)','my-calendar-submissions'); ?></label>
			</p>
			<p>
			<input type="checkbox" id="mcs_upload_images" name="mcs_upload_images" <?php mc_is_checked('mcs_upload_images','true'); ?> /> <label for="mcs_upload_images"><?php _e('Allow public event submitters to upload their own images','my-calendar-submissions'); ?></label>
			</p>			
			<p>
			<input type="checkbox" id="mcs_automatic_approval" name="mcs_automatic_approval" <?php mc_is_checked('mcs_automatic_approval','true'); ?> /> <label for="mcs_automatic_approval"><?php _e('Submitted events do not require approval.','my-calendar-submissions'); ?></label>
			</p>
			<p>
			<input type="checkbox" id="mcs_dont_send_submitter_email" name="mcs_dont_send_submitter_email" <?php mc_is_checked('mcs_dont_send_submitter_email','true'); ?> /> <label for="mcs_dont_send_submitter_email"><?php _e('Disable submitter email notification on automatically approved events.','my-calendar-submissions'); ?></label>
			</p>
			<p>
			<input type="checkbox" id="mcs_dont_send_admin_email" name="mcs_dont_send_admin_email" <?php mc_is_checked('mcs_dont_send_admin_email','true'); ?> /> <label for="mcs_dont_send_admin_email"><?php _e('Disable admin email notification on automatically approved events.','my-calendar-submissions'); ?></label>
			</p>			
			<h4><?php _e('New event messages','my-calendar-submissions'); ?></h4>
			<p>
			<input type="checkbox" id="mcs_html_email" name="mcs_html_email" <?php mc_is_checked('mcs_html_email','true'); ?> /> <label for="mcs_html_email"><?php _e('Send email notifications as HTML.','my-calendar-submissions'); ?></label>
			</p>
			<fieldset>
			<legend><?php _e('Sent to administrators','my-calendar-submissions'); ?></legend>
			<ul>
			<li>
			<label for="mcs_to"><?php _e('Send notifications to:','my-calendar-submissions'); ?></label> <input type="text" name="mcs_to" id="mcs_to" size="60" value="<?php echo ( $mcs_to == '' )?get_bloginfo('admin_email'):esc_attr($mcs_to); ?>" />
			</li>
			<li>
			<label for="mcs_from"><?php _e('Send notifications from:','my-calendar-submissions'); ?></label> <input type="text" name="mcs_from" id="mcs_from" size="60" value="<?php echo ( $mcs_from == '' )?get_bloginfo('admin_email'):esc_attr($mcs_from); ?>" />
			</li>
			<li>
			<label for="mcs_subject"><?php _e('Notification Subject','my-calendar-submissions'); ?></label> <input type="text" name="mcs_subject" id="mcs_subject" size="60" value="<?php echo stripslashes(esc_attr($mcs_subject)); ?>" />
			</li>
			<li>
			<label for="mcs_edit_subject"><?php _e('Notification Subject (Edits)','my-calendar-submissions'); ?></label> <input type="text" name="mcs_edit_subject" id="mcs_edit_subject" size="60" value="<?php echo stripslashes(esc_attr($mcs_edit_subject)); ?>" />
			</li>		
			<li>
			<label for="mcs_response"><?php _e('Notification message','my-calendar-submissions'); ?></label><br /><textarea name="mcs_response" id="mcs_response" rows="4" cols="60"><?php echo stripslashes(esc_attr($mcs_response)); ?></textarea>
			<?php $edit_link = ( mcs_submit_url() ) ? ', <code>edit_link</code>' : ''; ?>
			<em><?php echo __( 'Available template tags: <code>first_name</code>, <code>last_name</code>, <code>email</code>, <code>title</code>, <code>date</code>, <code>time</code>, <code>description</code>, <code>short</code>, <code>image</code>, <code>url</code>, <code>location</code>, <code>street</code>, <code>city</code>, <code>phone</code>, <code>blogname</code>','my-calendar-submissions') . $edit_link; ?></em>
			</li>
			</ul>
			</fieldset>
			<fieldset>
			<legend><?php _e('Sent to event submitter','my-calendar-submissions'); ?></legend>
			<ul>
			<li>
			<label for="mcs_confirmation_subject"><?php _e('Confirmation Subject','my-calendar-submissions'); ?></label> <input type="text" name="mcs_confirmation_subject" id="mcs_confirmation_subject" size="60" value="<?php echo stripslashes(esc_attr($mcs_confirmation_subject)); ?>" />
			</li>
			<li>
			<label for="mcs_edit_confirmation_subject"><?php _e('Confirmation Subject','my-calendar-submissions'); ?></label> <input type="text" name="mcs_edit_confirmation_subject" id="mcs_edit_confirmation_subject" size="60" value="<?php echo stripslashes(esc_attr($mcs_edit_confirmation_subject)); ?>" />
			</li>			
			<li>
			<label for="mcs_confirmation"><?php _e('Submitter confirmation message','my-calendar-submissions'); ?></label><br /><textarea name="mcs_confirmation" id="mcs_confirmation" rows="4" cols="60"><?php echo stripslashes(esc_attr($mcs_confirmation)); ?></textarea>
			<em><?php _e('Available template tags: <code>first_name</code>, <code>last_name</code>, <code>email</code>, <code>title</code>, <code>date</code>, <code>time</code>, <code>description</code>, <code>short</code>, <code>image</code>, <code>url</code>, <code>location</code>, <code>street</code>, <code>city</code>, <code>phone</code> <code>blogname</code>, <code>edit_link</code>','my-calendar-submissions'); ?></em>
			</li>	
			</ul>
			</fieldset>
			<h4><?php _e('Event Submission Criteria','my-calendar-submissions'); ?></h4>
			<fieldset>
			<legend><?php _e('Who may use the front-end event submission widget?','my-calendar-submissions'); ?></legend>
			<ul>
			<li>
			<input type="radio" id="mcs_public" name="mcs_criteria" value="1" <?php mc_is_checked('mcs_criteria','1'); ?> /> <label for="mcs_public"><?php _e('General public.','my-calendar-submissions'); ?></label>
			</li>			
			<li>
			<input type="radio" id="mcs_members_only" name="mcs_criteria" value="2" <?php mc_is_checked('mcs_criteria','2'); ?> /> <label for="mcs_members_only"><?php _e('Members.','my-calendar-submissions'); ?></label>
			</li>
			<li>
			<input type="radio" id="mcs_member_status" name="mcs_criteria" value="3" <?php mc_is_checked('mcs_criteria','3'); ?> /> <label for="mcs_member_status"><?php _e('Members with the "mc_add_events" capability.','my-calendar-submissions'); ?></label>
			</li>
			</ul>
			</fieldset>
			<p><input type="submit" name="mc-submit-settings" class="button-primary" value="<?php _e('Save Submissions Settings','my-calendar-submissions'); ?>" /></p>			
			</form>			
			</div>
		</div>
		</div>
	</div>
	<div class="metabox-holder wptab" aria-labelledby="tab_payments_mcs" role="tabpanel" aria-live="assertive" id="mcs_payments_tab">
	<form method="post" action="<?php echo admin_url("admin.php?page=my-calendar-submissions#mcs_payments_tab"); ?>">
	<div class="ui-sortable meta-box-sortables">   
		<div class="postbox">
			<h3><?php _e('Payment Settings','my-calendar-submissions'); ?></h3>
			<div class="inside">
			<div><input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('my-calendar-submissions'); ?>" /></div>
			<ul>
			<li>
			<input type="checkbox" id="mcs_payments" name="mcs_payments" <?php mc_is_checked('mcs_payments','true'); ?> /> <label for="mcs_payments"><?php _e('Require payment to submit an event','my-calendar-submissions'); ?></label>
			</li>
			<?php if ( get_option( 'mcs_payments' ) == 'true' ) { ?>
			<li>
			<input type="checkbox" id="mcs_use_sandbox" name="mcs_use_sandbox" <?php mc_is_checked('mcs_use_sandbox','true'); ?> /> <label for="mcs_use_sandbox"><?php _e('Place gateway in Testing mode','my-calendar-submissions'); ?></label>
			</li>
			<li>
			<input type="checkbox" id="mcs_quantity" name="mcs_quantity" <?php mc_is_checked('mcs_quantity','true'); ?> /> <label for="mcs_quantity"><?php _e('Visitors may purchase multiple-use payment keys','my-calendar-submissions'); ?></label>
			</li>
			<li>
			<label for="mcs_payment_message"><?php _e('Payment Form Message (shows above payments form)','my-calendar-submissions'); ?></label> <textarea type="text" name="mcs_payment_message" id="mcs_payment_message" rows="2" cols="60"><?php echo stripslashes(esc_attr($mcs_payment_message)); ?></textarea>
			<em><?php _e('Available template tags: <code>blogname</code>, <code>begins</code>, <code>ends</code>, <code>price</code>, <code>discount</code>, <code>currency</code>','my-calendar-submissions'); ?></em>
			</li>			
			</ul>
			<h4><?php _e('New purchase messages','my-calendar-submissions'); ?></h4>
			<fieldset>
			<legend><?php _e('Sent to administrators','my-calendar-submissions'); ?></legend>
			<ul>
			<li>
			<label for="mcs_payment_subject"><?php _e('Payment Notification Subject','my-calendar-submissions'); ?></label> <input type="text" name="mcs_payment_subject" id="mcs_payment_subject" size="60" value="<?php echo stripslashes(esc_attr($mcs_payment_subject)); ?>" />
			</li>
			<li>
			<label for="mcs_payment_response"><?php _e('Payment Notification message','my-calendar-submissions'); ?></label><br /><textarea name="mcs_payment_response" id="mcs_payment_response" rows="4" cols="60"><?php echo stripslashes(esc_attr($mcs_payment_response)); ?></textarea>
			<em><?php _e('Available template tags: <code>blogname</code>, <code>first_name</code>, <code>last_name</code>, <code>price</code>, <code>key</code>, <code>quantity</code>, <code>receipt</code>','my-calendar-submissions'); ?></em>
			</li>
			</ul>
			</fieldset>
			<fieldset>
			<legend><?php _e('Sent to purchaser','my-calendar-submissions'); ?></legend>
			<ul>
			<li>
			<label for="mcs_payment_confirmation_subject"><?php _e('Payment Confirmation Subject','my-calendar-submissions'); ?></label> <input type="text" name="mcs_payment_confirmation_subject" id="mcs_payment_confirmation_subject" size="60" value="<?php echo stripslashes(esc_attr($mcs_payment_confirmation_subject)); ?>" />
			</li>
			<li>
			<label for="mcs_payment_confirmation"><?php _e('Payment Submitter confirmation message','my-calendar-submissions'); ?></label><br /><textarea name="mcs_payment_confirmation" id="mcs_payment_confirmation" rows="4" cols="60"><?php echo stripslashes(esc_attr($mcs_payment_confirmation)); ?></textarea>
			<em><?php _e('Available template tags: <code>first_name</code>, <code>last_name</code>, <code>price</code>, <code>key</code>, <code>quantity</code>, <code>receipt</code>','my-calendar-submissions'); ?></em>
			</li>	
			</ul>
			</fieldset>
			<ul>
			<li>
			<?php		
			$pricing = apply_filters( 'mcs_submission_fee_settings', false );
			if ( !$pricing ) {
			?>
			<label for="mcs_submission_fee"><?php _e('Base price:','my-calendar-submissions'); ?></label> <input type="text" name="mcs_submission_fee" id="mcs_submission_fee" size="60" value="<?php echo esc_attr($mcs_submission_fee); ?>" />
			<?php
			} else {
				echo $pricing;
			}
			?>
			</li>
			<li><label for="mcs_currency"><?php _e('Currency:','my-calendar-submissions'); ?></label> 
			<?php $mcs_currency_codes = array( "USD" => __('U.S. Dollars ($)','my-calendar-submissions'),
							"EUR" => __('Euros (€)','my-calendar-submissions'), 
							"AUD" => __('Australian Dollars (A $)','my-calendar-submissions'),
							"CAD" => __('Canadian Dollars (C $)','my-calendar-submissions'), 
							"GBP" => __('Pounds Sterling (£)','my-calendar-submissions'), 
							"JPY" => __('Yen (¥)','my-calendar-submissions'),
							"NZD" => __('New Zealand Dollar ($)','my-calendar-submissions'), 
							"CHF" => __('Swiss Franc','my-calendar-submissions'),
							"HKD" => __('Hong Kong Dollar ($)','my-calendar-submissions'),
							"SGD" => __('Singapore Dollar ($)','my-calendar-submissions'),
							"SEK" => __('Swedish Krona','my-calendar-submissions'),
							"DKK" => __('Danish Krone','my-calendar-submissions'),
							"PLN" => __('Polish Zloty','my-calendar-submissions'),
							"NOK" => __('Norwegian Krone','my-calendar-submissions'),
							"HUF" => __('Hungarian Forint','my-calendar-submissions'),
							"ILS" => __('Israeli Shekel','my-calendar-submissions'),
							"MXN" => __('Mexican Peso','my-calendar-submissions'),
							"BRL" => __('Brazilian Real (only for Brazilian users)','my-calendar-submissions'),
							"MYR" => __('Malaysian Ringgits (only for Malaysian users)','my-calendar-submissions'),
							"PHP" => __('Philippine Pesos','my-calendar-submissions'),
							"TWD" => __('Taiwan New Dollars','my-calendar-submissions'),
							"THB" => __('Thai Baht','my-calendar-submissions'),
							"TRY" => __('Turkish Lira (only for Turkish users)','my-calendar-submissions') );
				echo "<select name='mcs_currency' id='mcs_currency'>";
				foreach ( $mcs_currency_codes as $code => $currency ) {
					$selected = ( get_option('mcs_currency' ) == $code) ? "selected='selected'" : "";
					echo "<option value='$code' $selected>$currency</option>";	
				}
        	echo "</select>";
			?>
			</li>
			<li>
			<label for="mcs_members_discount"><?php _e('Member discount (%)','my-calendar-submissions'); ?></label> <input type="text" name="mcs_members_discount" id="mcs_members_discount" size="3" value="<?php echo esc_attr($mcs_members_discount); ?>" /> <?php _e('Member\'s discounted cost:','my-calendar-submissions'); ?> <?php echo mcs_get_price( true ); ?>
			</li>
			<?php echo apply_filters( 'mcs_custom_fields', '' ); ?>
			</ul>
			</div>
		</div>
	</div>
	<div class="ui-sortable meta-box-sortables">   
		<div class="postbox">
			<h3><?php _e('Payment Gateways', 'my-calendar-submissions' ); ?></h3>
			<div class="inside">
			<ul>
			<li>
			<input type="radio" id="mcs_gateway_paypal" name="mcs_gateway" value="paypal" <?php mc_is_checked( 'mcs_gateway','paypal' ); ?> /> <label for="mcs_gateway_paypal"><?php _e('Use Paypal','my-calendar-submissions'); ?></label>
			</li>
			<li>
			<input type="radio" id="mcs_gateway_authorizenet" name="mcs_gateway" value="authorizenet" <?php mc_is_checked('mcs_gateway','authorizenet'); ?> /> <label for="mcs_gateway_authorizenet"><?php _e('Use Authorize.net','my-calendar-submissions'); ?></label>
			</li>
			<li>
			<input type="checkbox" id="mcs_ssl" name="mcs_ssl" value="true" aria-describedby="mcs_ssl_desc" <?php mc_is_checked('mcs_ssl','true'); ?> /> <label for="mcs_ssl"><?php _e('Use SSL for Payment pages.','my-calendar-submissions'); ?></label><br />
			<span id="mcs_ssl_desc"><?php _e('SSL is not required for My Calendar Pro to be secure, but can improve the comfort level of your users on your site.','my-calendar-submissions' ); ?></span>
			</li>
			<li>
			<?php if ( get_option( 'mcs_ssl' ) == 'true' ) { $required = 'required aria-required="true"'; } else { $required = ''; } ?>
			<input type="text" size='4' id="mcs_purchase_page" name="mcs_purchase_page" value="<?php echo esc_attr($mcs_purchase_page); ?>"<?php echo $required; ?> /> <label for="mcs_purchase_page"><?php _e('Post ID for My Calendar Submissions form.','my-calendar-submissions'); ?></label><br />
			</li>			
			</ul>
			<?php if ( get_option( 'mcs_gateway' ) == 'authorizenet' ) { ?>
			<h4><?php _e('Authorize.net Settings', 'my-calendar-submissions' ); ?></h4>
			<fieldset>
			<div>
				<input type="hidden" name="mcs_paypal_email" value="<?php echo esc_attr($mcs_paypal_email); ?>" />
				<input type="hidden" name="mcs_paypal_merchant_id" value="<?php echo esc_attr($mcs_paypal_merchant_id); ?>" />
			</div>
			<legend><?php _e('Authorize.net Settings', 'my-calendar-submissions' ); ?></legend>
			<ul>
			<li>
			<label for="mcs_authnet_api"><?php _e('API Login ID','my-calendar-submissions'); ?></label> <input type="text" name="mcs_authnet_api" id="mcs_authnet_api" size="60" value="<?php echo esc_attr($mcs_authnet_api); ?>" />
			</li>
			<li>
			<label for="mcs_authnet_key"><?php _e('Transaction Key','my-calendar-submissions'); ?></label> <input type="text" name="mcs_authnet_key" id="mcs_authnet_key" size="60" value="<?php echo esc_attr($mcs_authnet_key); ?>" />
			</li>
			<li>
			<label for="mcs_authnet_hash"><?php _e('MD5 Hash Value','my-calendar-submissions'); ?></label> <input type="text" name="mcs_authnet_hash" id="mcs_authnet_hash" size="60" value="<?php echo esc_attr($mcs_authnet_hash); ?>" />
			</li>			
			</ul>
			</fieldset>	
			
			<?php } else { ?>
			<h4><?php _e('PayPal Settings', 'my-calendar-submissions' ); ?></h4>
			<fieldset>
			<div>
				<input type="hidden" name="mcs_authnet_api" value="<?php echo esc_attr($mcs_authnet_api); ?>" />
				<input type="hidden" name="mcs_authnet_key" value="<?php echo esc_attr($mcs_authnet_key); ?>" />
				<input type="hidden" name="mcs_authnet_hash" value="<?php echo esc_attr($mcs_authnet_hash); ?>" />
			</div>			
			<legend><?php _e('PayPal Settings', 'my-calendar-submissions' ); ?></legend>
			<ul>
			<li>
			<label for="mcs_paypal_email"><?php _e('Paypal email (primary)','my-calendar-submissions'); ?></label> <input type="text" name="mcs_paypal_email" id="mcs_paypal_email" size="60" value="<?php echo esc_attr($mcs_paypal_email); ?>" />
			</li>
			<li>
			<label for="mcs_paypal_merchant_id"><?php _e('Paypal merchant ID','my-calendar-submissions'); ?></label> <input type="text" name="mcs_paypal_merchant_id" id="mcs_paypal_merchant_id" size="60" value="<?php echo esc_attr($mcs_paypal_merchant_id); ?>" />
			</li>
			</ul>
			</fieldset>
			
			<?php } ?>
			<ul>
			<li>
			<label for="mcs_button"><?php _e('Purchase Button image','my-calendar-submissions'); ?></label> <input type="text" name="mcs_button" id="mcs_button" size="60" value="<?php echo esc_url($mcs_button); ?>" />
			</li>
			</ul>
			</div>
		</div>
	</div>
	<div class="ui-sortable meta-box-sortables">   
		<div class="postbox">
			<h3><?php _e('Configure a Sale','my-calendar-submissions'); ?></h3>
			<div class="inside">
			<fieldset>
			<legend><?php _e('Sale settings','my-calendar-submissions'); ?></legend>
			<?php 
			$sales = apply_filters( 'mcs_custom_sale', false ); 
			if ( !$sales ) {
			?>
				<ul>
				<li>
				<label for="mcs_sale_begins"><?php _e('Date sale begins (e.g. November 24th, 2013)','my-calendar-submissions'); ?></label> <input type="text" name="mcs_discount[begins]" id="mcs_sale_begins" size="20" value="<?php echo ( isset($mcs_discount['begins']) )?esc_attr($mcs_discount['begins']):''; ?>" /> <?php _e('Discounted cost per event:','my-calendar-submissions'); ?> <?php echo sprintf( '$%01.2f', mcs_get_price( true ) ); ?>
				</li>
				<li>
				<label for="mcs_sale_ends"><?php _e('Date sale ends (e.g. December 25th, 2013)','my-calendar-submissions'); ?></label> <input type="text" name="mcs_discount[ends]" id="mcs_sale_ends" size="20" value="<?php echo ( isset($mcs_discount['ends']) )?esc_attr($mcs_discount['ends']):''; ?>" />
				</li>
				<li>
				<label for="mcs_sale_rate"><?php _e('Percentage discount','my-calendar-submissions'); ?></label> <input type="text" name="mcs_discount[rate]" id="mcs_sale_rate" size="3" value="<?php echo ( isset($mcs_discount['rate']) )?esc_attr($mcs_discount['rate']):''; ?>" />
				</li>
				</ul>
			<?php
			} else { 
				echo $sales;
			}
			?>
				<p><?php _e('<strong>Note:</strong> if members have a discount, the additional sale rate will not be compounded with their normal rate.','my-calendar-submissions'); ?></p>
			</fieldset>
			<?php } else { ?>
				<div>
				<input type="hidden" name="mcs_payment_response" value="<?php echo $mcs_payment_response; ?>" />
				<input type="hidden" name="mcs_payment_confirmation" value="<?php echo $mcs_payment_confirmation; ?>" />
				<input type="hidden" name="mcs_payment_subject" value="<?php echo $mcs_payment_subject; ?>" />
				<input type="hidden" name="mcs_payment_message" value="<?php echo $mcs_payment_message; ?>" />
				<input type="hidden" name="mcs_payment_confirmation_subject" value="<?php echo $mcs_payment_confirmation_subject; ?>" />
				<input type="hidden" name="mcs_submission_fee" value="<?php echo $mcs_submission_fee; ?>" />
				<input type="hidden" name="mcs_members_discount" value="<?php echo $mcs_members_discount; ?>" />
				<input type="hidden" name="mcs_paypal_email" value="<?php echo $mcs_paypal_email; ?>" />
				<input type="hidden" name="mcs_paypal_merchant_id" value="<?php echo $mcs_paypal_merchant_id; ?>" />
				<input type="hidden" name="mcs_button" value="<?php echo $mcs_button; ?>" />
				<input type="hidden" name="mcs_currency" value="<?php echo $mcs_currency; ?>" />
				<input type="hidden" name="mcs_discount[begins]" value="<?php echo $mcs_discount['begins']; ?>" />
				<input type="hidden" name="mcs_discount[ends]" value="<?php echo $mcs_discount['ends']; ?>" />
				<input type="hidden" name="mcs_discount[rate]" value="<?php echo $mcs_discount['rate']; ?>" />
				</div>
			<?php } ?>
				<p><input type="submit" name="mc-payment-settings" class="button-primary" value="<?php _e('Save Payment Settings','my-calendar-submissions'); ?>" /></p>			
			</div>
		</div>		
	</div>
	</form>	
	</div>		
	<?php 
		$panels = apply_filters( 'mcs_settings_panels', array() );
		foreach ( $panels as $key => $value ) {
			$content = ( is_array( $value ) && isset( $value['content'] ) ) ? $value['content'] : $value;
			$label = ( is_array( $value ) && isset( $value['label'] ) ) ? $value['label'] : __( 'Save Settings' );
			$wp_nonce = wp_nonce_field( $key, '_wpnonce', true, false );
			$top = '
			<div class="metabox-holder wptab" aria-labelledby="tab_' . $key . '_mcs" role="tabpanel" aria-live="assertive" id="mcs_' . $key . '_tab">
				<form method="post" action="' . admin_url( "admin.php?page=my-calendar-submissions#mcs_$key" . '_tab' ) . '" enctype="multipart/form-data">'.
					$wp_nonce
					.'<div class="ui-sortable meta-box-sortables">   
						<div class="postbox">';
			
			$bottom = '
						</div>
					</div>
				</form>
			</div>';
			
			$middle = str_replace( '{submit}', '<p><input type="submit" name="'.$key.'_settings" class="button-primary" value="' . $label . '" /></p>', $content );
			
			echo $top . $middle . $bottom;
		}

	?>
	</div>
	<?php
	if ( function_exists('mcs_submit_exists') ) { $remove = true; } else { $remove = false; }
	$guide_url = plugins_url( '/My-Calendar-Pro.pdf',__FILE__);
	$add = array( 
		'Event Submissions Shortcode'=>
			'<p>'.__( 'The event submissions form can be configured via shortcode or via widget.','my-calendar-submissions' ).'</p>
			<p>'.__('New events are always submitted with approval pending and must be approved by a user with appropriate permissions in order to appear on the site.','my-calendar-submissions').'</p>
			<p>'.sprintf( __( 'Use the <a href="%s">shortcode generator</a> to create your My Calendar submissions form!', 'my-calendar-submissions' ), admin_url( 'admin.php?page=my-calendar-help#mc-generator' ) ).'</p>',
		'Responsive Mode' =>
			'<p>'.__( 'The file <code>mc-responsive.css</code> in your theme directory will replace the My Calendar PRO responsive stylesheet.', 'my-calendar-submissions' ).'</p>' );
	?>
	<?php mc_show_sidebar('',$add,$remove); ?>
</div>
<?php
	// creates settings page for my calendar appointments
}