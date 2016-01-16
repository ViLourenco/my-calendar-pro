<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function mcs_receive_ipn() {
	if ( isset($_GET['mcsipn']) && $_GET['mcsipn'] == 'true' ) {
		global $wpdb; 
		mcs_check();
		if ( get_option( 'mcs_gateway' ) == 'authorizenet' ) {
			require_once 'gateways/AuthorizeNet.php'; // The SDK
			$url = add_query_arg( 'mcsipn','true', home_url() );
			$api = get_option( 'mcs_authnet_api' );
			$hash = get_option( 'mcs_authnet_hash' );
			// these all need to be set from Authorize.Net data
			$payment_status = mcs_map_status( $_POST['x_response_code'] ); // map response to equivalent from PayPal
			$item_number = 1;  // mandatory for Paypal, but only represents a submissions purchase here.
			$price = $_POST['x_amount'];
			$quantity = ( isset($_POST['quantity']) )?$_POST['quantity']:1; // need to add to form
			$quantity = ( is_int($quantity) )?$quantity:1;
			$payer_email = $_POST['x_payer_email']; // must add to form
			$payer_first_name = $_POST['x_first_name'];
			$payer_last_name = $_POST['x_last_name'];
			$mc_fee = '0.00'; // not included in data
			$item_name = sprintf(__('%s Event Submission','my-calendar-submissions'),get_option('blogname')); // required by Paypal
			$parent = '';	
			$redirect_url = $_POST['x_referer_url'];			
			// paypal IPN data
            $ipn = new AuthorizeNetSIM($api, $hash);
			if ( $ipn->isAuthorizeNet()	) {	
				if ( $ipn->approved ) { 
					$response = 'VERIFIED'; 
					$redirect_url = add_query_arg( array( 'response_code'=>'1', 'transaction_id'=>$ipn->transaction_id ), $redirect_url ); 
					$txn_id = $ipn->transaction_id;
				} else { 
					$response = 'ERROR'; 
					$redirect_url = add_query_arg( array( 'response_code'=>$ipn->response_code, 'response_reason_text'=>$ipn->response_reason_text ), $redirect_url );
					$txn_id = false;
				}
				$response_code = '200';
			} else {
				wp_die( __('That transaction was not handled by Authorize.net. Please verify your MD5 setting.','my-calendar-submissions' ) );
			}
		} else {
		
			if ( isset( $_POST['payment_status'] ) ) {
				$sandbox = get_option("mcs_use_sandbox");
				$receiver = strtolower( get_option('mcs_paypal_email') );
				$url = ( $sandbox == 'true' )?'https://www.sandbox.paypal.com/webscr':'https://www.paypal.com/webscr';

				$req = 'cmd=_notify-validate';
				foreach ($_POST as $key => $value) {
					$value = urlencode(stripslashes($value));
					$req .= "&$key=$value";
				}		
				$args = wp_parse_args( $req, array() );
				$params = array(
					'body'		=> $args,
					'sslverify' => false,
					'timeout' 	=> 30,
				);
				// transaction variables to store
				$payment_status = $_POST['payment_status'];
				$item_number = $_POST['item_number'];
				$price = $_POST['mc_gross'];
				$payment_currency = $_POST['mc_currency'];
				$receiver_email = $_POST['receiver_email'];
				$quantity = ( isset($_POST['quantity']) )?$_POST['quantity']:1;
				$quantity = ( is_int($quantity) )?$quantity:1;
				$payer_email = $_POST['payer_email'];
				$payer_first_name = $_POST['first_name'];
				$payer_last_name = $_POST['last_name'];
				$mc_fee = $_POST['mc_fee'];
				$item_name = $_POST['item_name'];
				$txn_id = $_POST['txn_id'];
				$parent = isset( $_POST['parent_txn_id'] )?$_POST['parent_txn_id']:'';	
				// paypal IPN data
				$ipn = wp_remote_post( $url, $params );
				$response = $ipn['body'];
				$response_code = $ipn['response']['code'];
				// die conditions for PayPal
				// if receiver email or currency are wrong, this is probably a fraudulent transaction.
				if ( strtolower($receiver_email) != $receiver || $payment_currency != get_option('mcs_currency') ) { 
					wp_mail( get_option('mcs_to'), 'Payment Conditions Error', 'PayPal receiver email did not match account or payment currency did not match payment' ); 
					wp_die(); 
				} 
				$redirect_url = false;
			} else {
				wp_die( "No valid IPN request made" );
			}
		}

		if ( $response_code == '200' ) {
			if ( $response == "VERIFIED" ) {
				$status = "";
				if ( get_option( 'mcs_gateway' ) != 'authorizenet' ) {
					// See whether the transaction already exists. (For refunds, reversals, or canceled reversals)
					$sql = "SELECT id, hash, status FROM ".my_calendar_payments_table()." WHERE txn_id = %s";
					$txn = ( $parent != '' )?$wpdb->get_row( $wpdb->prepare( $sql, array($parent) ) ):$wpdb->get_row( $wpdb->prepare( $sql, array($txn_id) ) );
				} else {
					$txn = false;
				}
				switch ( $payment_status ) {
					case 'Completed':
					case 'Created':
					case 'Denied':
					case 'Expired':
					case 'Failed':
					case 'Processed':
					case 'Voided':$status = $payment_status;break;
					case 'Pending': $status = $payment_status.': '.$post['pending_reason'];  break;
					case 'Refunded':
					case 'Reversed':
					case 'Canceled_Reversal': $status = $payment_status.': '.$post['ReasonCode']; break;
				}
				
				if ( empty( $txn ) ) {
					//error_log("INSERT: ".$txn_id." ".$status);
					$uniqid = uniqid('E');
					$hash = mcs_uniqid( $uniqid );
					$sql = "INSERT INTO ".my_calendar_payments_table()."
							(item_number,quantity,total,hash,txn_id,price,fee,status,transaction_date,first_name,last_name,payer_email)
							VALUES(%d, %d, %d, %s, %s, %f, %f, %s, NOW(), %s, %s, %s )";
					$wpdb->query( $wpdb->prepare( $sql,array( $item_number,$quantity,$quantity,$hash,$txn_id,$price,$mc_fee,$status,$payer_first_name,$payer_last_name,$payer_email) ) );
				} else {
					$hash = $txn->hash;
					//error_log("UPDATE: ".$txn_id." ".$status." ".$hash." ->".$item_number);
					$sql = "UPDATE ".my_calendar_payments_table()."
							SET status = %s,price=%f,fee=%f,transaction_date = NOW() WHERE id = %d";
					$r = $wpdb->query($wpdb->prepare($sql,array( $status,$price,$mc_fee,$txn->id)));
					//error_log(var_dump($r, true));
				}			
				if ( $status == "Completed" ) {
					mcs_send_notifications( $payer_first_name, $payer_last_name, $payer_email, $price, $hash, $quantity );
					setcookie("mcs_receipt", 'true', time()+60*60, SITECOOKIEPATH, COOKIE_DOMAIN, false, true );
				}
			} else {
				// log for manual investigation
				$blogname = get_option('blogname');
				$mail_From = "From: $blogname Events <".get_option('mcs_from').">";				
				$mail_Subject = __("INVALID IPN on My Calendar Submission Payment",'my-calendar-submissions');
				$mail_Body = __("Something went wrong. Hopefully this information will help:",'my-calendar-submissions')."\n\n";
				foreach ( $_POST as $key => $value ) {
					$mail_Body .= $key . " = " .$value ."\n";
				}
				wp_mail( get_option('mcs_to'), $mail_Subject, $mail_Body, $mail_From);
			}
		} else {
			$blogname = get_option('blogname');
			$mail_From = "From: $blogname Events <".get_option('mcs_from').">";
			$mail_Subject = __("WP HTTP Failed to contact Paypal",'my-calendar-submissions');
			$mail_Body = __("Something went wrong. Hopefully this information will help:",'my-calendar-submissions')."\n\n";
			$mail_Body .= print_r($ipn,1);
			wp_mail( get_option('mcs_to'), $mail_Subject, $mail_Body, $mail_From);
		}
		if ( $redirect_url ) {
			echo AuthorizeNetDPM::getRelayResponseSnippet($redirect_url); 						
			//wp_safe_redirect( $redirect_url );
			exit;
		} else {
			status_header(200);
		}		
	} else {
		return;
	}
}

function mcs_map_status( $status ) {
	switch( $status ) {
		case 1: $response = 'Completed'; break;
		case 2: $response = 'Declined'; break;
		case 3: $response = 'Error'; break;
		case 4: $response = 'Held for Review'; break;
		default: $response = 'Completed';
	}
	return $response;
}

function mcs_send_notifications( $first, $last, $email, $price, $hash, $quantity ) {
	if ( function_exists( 'jd_draw_template' ) ) {
		$blogname = get_option('blogname');
		$mail_From = "From: $blogname Events <".get_option('mcs_from').">";
		$subject = get_option('mcs_payment_subject');
		$subject2 = get_option('mcs_payment_confirmation_subject');
		$receipt = add_query_arg( 'mcs_receipt', $hash, home_url() );
		$search = array('first_name'=>$first,
						'last_name'=>$last,
						'blogname'=>$blogname,
						'price'=>$price,
						'key'=>$hash,
						'quantity'=>$quantity,
						'receipt'=>$receipt );
		$subject = jd_draw_template( $search, $subject );
		$subject2 = jd_draw_template( $search, $subject2 );
		$body = jd_draw_template( $search, get_option('mcs_payment_confirmation') );
		$body2 = jd_draw_template( $search, get_option('mcs_payment_response') );
		wp_mail($email, $subject, $body, $mail_From);
		wp_mail(get_option('mcs_to'), $subject, $body2, $mail_From);
	}
}

function mcs_uniqid( $hash, $i=1 ) {
global $wpdb;
$sql = "SELECT hash FROM ".my_calendar_payments_table()." WHERE hash='$hash'";
	if ( $wpdb->get_var($sql) ) { 
		$hash = uniqid('E'.$i);
		mcs_uniqid( $hash, $i++ );
	} else {
		return $hash;
	}
}


function mcs_paypal_form( $price, $currency, $discount_rate, $discounts, $discount, $quantity ) {
	if ( !get_option('mcs_paypal_merchant_id') ) {
		$form = "<p class='warning'>".__('PayPal account is not configured.','my-calendar-submissions' )."</p>";
	} else {
		$use_sandbox = get_option('mcs_use_sandbox');
		$button = get_option('mcs_button');	
		$form = "
		<form action='".( $use_sandbox != 'true' ? "https://www.paypal.com/cgi-bin/webscr" : "https://www.sandbox.paypal.com/cgi-bin/webscr")."' method='POST'>
		<input type='hidden' name='cmd' value='_xclick' />
		<input type='hidden' name='business' value='".get_option('mcs_paypal_merchant_id')."' />
		<input type='hidden' name='item_name' value='".sprintf(__('%s Event Submission','my-calendar-submissions'),get_option('blogname'))."' />
		<input type='hidden' name='item_number' value='1' />
		<input type='hidden' name='amount' value='$price' />
		<input type='hidden' name='no_shipping' value='1' />
		<input type='hidden' name='no_note' value='1' />
		<input type='hidden' name='currency_code' value='$currency' />";
		$search = "http";
		$replace = "https";
		$form .= "
		<input type='hidden' name='notify_url' value='".mcs_replace_http( add_query_arg( 'mcsipn', 'true', home_url() ) )."' />
		<input type='hidden' name='return' value='".mcs_replace_http( add_query_arg( 'response_code','thanks', mc_get_current_url() ) )."' />
		<input type='hidden' name='cancel_return' value='".mcs_replace_http( add_query_arg( 'response_code','cancel', mc_get_current_url() ) )."' />";
		if ( $discount == true && $discount_rate > 0 ) {
			$form .= "
			<input type='hidden' name='discount_rate' value='$discount_rate' />";
			if ( $quantity == 'true' ) {
				$form .= "
				<input type='hidden' name='discount_rate2' value='$discount_rate' />";	
			}
		}
		if ( $button != '' && mc_is_url($button) ) {
			$form .= "<input type='image' src='$button' name='submit' class='button' alt='".__('Buy a payment key','my-calendar-submissions')."' />";
		} else {
			$form .= "<input type='submit' name='submit' class='button' value='".__('Buy a payment key','my-calendar-submissions')."' />";
		}
		if ( $quantity == 'true' ) {
			$form .= "
			<p><label for='quantity'>".__('Quantity','my-calendar-submissions')."</label> <input type='number' name='quantity' value='1' id='quantity' size='3' /></p>";
		}
		$form  .= apply_filters( 'mcs_paypal_form', '', $price, $currency, $discount_rate, $discounts, $discount );
		$form .= "</form>";
	}
	return $form;
}


function mcs_payment_form() {
	$ret = $form = '';
	if ( isset( $_GET['response_code'] ) ) {
		$mcs = $_GET['response_code'];
		$provider = ( get_option( 'mcs_gateway' ) == 2 ) ? 'Authorize.net' : 'PayPal'; 
		switch ( $mcs ) {
			case 'thanks':
				$ret = "<p class='notice'>".sprintf( __("Thank you for your purchase! You can view your purchase information at %s. You will receive an email with your payment key once your payment is finalized.",'my-calendar-submissions' ), $provider )."</p>";
			break;
			case 'cancel':
				$ret = __("Sorry that you decided to cancel your purchase! Contact us if you have any questions!",'my-calendar-submissions');
			break;
		}
	}
	if ( mcs_payment_required() ) {
		$price = mcs_get_price( is_user_logged_in() );
		$currency = get_option( 'mcs_currency' );
		$quantity = get_option( 'mcs_quantity' );

		$discounts = mcs_check_discount();
		$discount_rate = (int) $discounts['rate'];
		$discount = ( $discount_rate != 0 ) ? true : false ;
	
	if ( isset( $_GET['response_code'] ) ) {
		$message = '';
	} else {
		$message = wpautop( jd_draw_template( array( 'price'=>$price, 'currency'=>$currency,'discount'=>$discount_rate,'begins'=>$discounts['begins'],'ends'=>$discounts['ends'] ), get_option('mcs_payment_message') ) );
	}
	$form = "<div class='mc-payments-form ".get_option('mcs_gateway')."'>
		 $ret
		 $message";
		$nonce = wp_create_nonce( 'mcs-payments-nonce' );		 
		if ( get_option( 'mcs_gateway' ) == 'authorizenet' ) {
			if ( get_option( 'mcs_quantity' ) != 'true' || (get_option( 'mcs_quantity' ) == 'true' && isset( $_POST['mcs_quantity'] ) || isset( $_GET['response_code'] ) ) ) {
				require_once 'gateways/AuthorizeNet.php'; // The SDK
				$url = mcs_replace_http( add_query_arg( 'mcsipn','true', get_permalink() ) );
				$rand = time().rand( 100000,999999 );
					$mcs_quantity = isset( $_POST['mcs_quantity'] ) ? (int) $_POST['mcs_quantity'] : 1;
					$price = mcs_calculate_price( $mcs_quantity, $price, $discount, $discount_rate );
				$form .= AuthorizeNetDPM::directPost($url, $price, $rand, $nonce );
			} else {
				$form .= mcs_set_quantity_form( $price );
			}
		} else {
			$form .= mcs_paypal_form( $price, $currency, $discount_rate, $discounts, $discount, $quantity );
		}
		$form .= "</div>";		
	}
	return $form;
}