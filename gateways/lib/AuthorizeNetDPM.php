<?php
/**
 * Demonstrates the Direct Post Method.
 *
 * To implement the Direct Post Method you need to implement 3 steps:
 *
 * Step 1: Add necessary hidden fields to your checkout form and make your form is set to post to AuthorizeNet.
 *
 * Step 2: Receive a response from AuthorizeNet, do your business logic, and return
 *         a relay response snippet with a url to redirect the customer to.
 *
 * Step 3: Show a receipt page to your customer.
 *
 * This class is more for demonstration purposes than actual production use.
 *
 *
 * @package    AuthorizeNet
 * @subpackage AuthorizeNetDPM
 */

/**
 * A class that demonstrates the DPM method.
 *
 * @package    AuthorizeNet
 * @subpackage AuthorizeNetDPM
 */
class AuthorizeNetDPM extends AuthorizeNetSIM_Form
{

    const LIVE_URL = 'https://secure2.authorize.net/gateway/transact.dll';
    const SANDBOX_URL = 'https://test.authorize.net/gateway/transact.dll';

    /**
     * Implements all 3 steps of the Direct Post Method for demonstration
     * purposes.
     */
    public static function directPost($url, $price = "0.00", $rand = '', $nonce) {
		$api = get_option( 'mcs_authnet_api' );
		$key = get_option( 'mcs_authnet_key' );
		$hash = get_option( 'mcs_authnet_hash' );
        // Step 1: Show checkout form to customer.
        if ( isset( $_POST['mcs_quantity'] ) || get_option( 'mcs_quantity' ) != 'true' ) {
            $fp_sequence = $rand; // Any sequential number like an invoice number.
            return AuthorizeNetDPM::getCreditCardForm( $price, $fp_sequence, $url, $api, $key, $nonce );
        }
        // Step 3: Show receipt page to customer.
        else if ( !count($_POST) && count($_GET) ) {
            if ($_GET['response_code'] == 1) {
				$transaction_id = sanitize_text_field( $_GET['transaction_id'] );
				$receipt = add_query_arg( array( 'mcs_receipt'=>$transaction_id ), home_url() );
                return sprintf( __( 'Thank you for your purchase! Your transaction id is: #%1$s. <a href="%2$s">View your receipt</a>', 'my-calendar-submissions' ), $transaction_id, $receipt );
            } else {
				return sprintf( __( "Sorry, an error occurred: %s.",'my-calendar-submissions'), "<strong>".sanitize_text_field( $_GET['response_reason_text'] )."</strong>" );
            }
        }
    }
	
	public static function receivePost( $url, $api, $hash ) {
        // Step 2: Handle AuthorizeNet Transaction Result & return snippet.
		if ( count( $_POST ) ) {
			$url = remove_query_arg( 'mcsipn','true' );
            $response = new AuthorizeNetSIM($api, $hash);
            if ($response->isAuthorizeNet()) {
                if ($response->approved) {
                    // Do your processing here.
                    $redirect_url = add_query_arg( array( 'response_code'=>1, 'transaction_id'=>$response->transaction_id ), $url );
                } else {
                    // Redirect to error page.
                    $redirect_url = add_query_arg( array( 'response_code'=>$response->response_code,'response_reason_text'=>$response->response_reason_text, $url ) );
                }
                // Send the Javascript back to AuthorizeNet, which will redirect user back to your site.
                echo AuthorizeNetDPM::getRelayResponseSnippet($redirect_url);
            } else {
                echo "Error -- not AuthorizeNet. Check your MD5 Setting.";
            }
		}	
	}
    
    /**
     * A snippet to send to AuthorizeNet to redirect the user back to the
     * merchant's server. Use this on your relay response page.
     *
     * @param string $redirect_url Where to redirect the user.
     *
     * @return string
     */
    public static function getRelayResponseSnippet($redirect_url) {
        return "<html><head><script language=\"javascript\">
                <!--
                window.location=\"{$redirect_url}\";
                //-->
                </script>
                </head><body><noscript><meta http-equiv=\"refresh\" content=\"1;url={$redirect_url}\"></noscript></body></html>";
    }
    
    /**
     * Generate a sample form for use in a demo Direct Post implementation.
     *
     * @param string $amount                   Amount of the transaction.
     * @param string $fp_sequence              Sequential number(ie. Invoice #)
     * @param string $relay_response_url       The Relay Response URL
     * @param string $api_login_id             Your API Login ID
     * @param string $transaction_key          Your API Tran Key.
     *
     * @return string
     */
    public static function getCreditCardForm($price, $fp_sequence, $relay_response_url, $api, $key, $nonce ) {
		$test_mode = ( get_option( 'mcs_use_sandbox' ) == 'true' ) ? true : false ;
		$prefill = ( $test_mode ) ? true : false ;
        $time = time();
        $fp = self::getFingerprint($api, $key, $price, $fp_sequence, $time);
        $sim = new AuthorizeNetSIM_Form(
            array(
            'x_amount'        => $price,
            'x_fp_sequence'   => $fp_sequence,
            'x_fp_hash'       => $fp,
            'x_fp_timestamp'  => $time,
            'x_relay_response'=> "TRUE",
            'x_relay_url'     => $relay_response_url,
            'x_login'         => $api,
            )
        );
        $hidden_fields = $sim->getHiddenFieldString();
		global $wp;
		$hidden_fields .= "<input type='hidden' name='x_referer_url' value='".mcs_replace_http( home_url(add_query_arg(array(),$wp->request) ) )."' />";		
        $post_url = ($test_mode ? self::SANDBOX_URL : self::LIVE_URL);
        $button = get_option('mcs_button');	
		$quantity = isset( $_POST['mcs_quantity'] ) ? (int) $_POST['mcs_quantity'] : 1;
		if ( $quantity == 1 ) {
			$purchasing = '<p>'.sprintf( __('You\'re purchasing a payment key to submit %1$s event for $%2$s.','my-calendar-submissions' ), "<strong>$quantity</strong>", "<strong>$price</strong>" ).'</p>';
		} else {
			$purchasing = '	<p>'.sprintf( __('You\'re purchasing a payment key for %1$s events. Total: $%2$s.','my-calendar-submissions' ), "<strong>$quantity</strong>", "<strong>$price</strong>" ).'</p>';
		}
        $form = $purchasing.'
        <form method="post" action="'.$post_url.'">
				<div>
                '.$hidden_fields.'
				<input type="hidden" name="x_amount_base" value="'.$price.'" />
				</div>
                <div>
                    <label for="x_card_num">'.__('Credit Card Number', 'my-calendar-submissions' ).'</label>
                    <input type="text" required aria-required="true" size="17" id="x_card_num" name="x_card_num" value="'.($prefill ? '6011000000000012' : '').'" />
                </div>
                <div>
                    <label for="x_exp_date">'.__('Expiration', 'my-calendar-submissions' ).'</label>
                    <input type="text" required aria-required="true" size="4" id="x_exp_date" name="x_exp_date" placeholder="05/'.date('y',strtotime('+ 2 years') ).'" value="'.($prefill ? '04/17' : '').'" />
                </div>
                <div>
                    <label for="x_card_code">'.__('Security Code', 'my-calendar-submissions' ).'</label>
                    <input type="text" required aria-required="true" size="4" id="x_card_code" name="x_card_code" placeholder="123" value="'.($prefill ? '782' : '').'" />
                </div>
                <div>
                    <label for="x_first_name">'.__('First Name', 'my-calendar-submissions' ).'</label>
                    <input type="text" required aria-required="true" size="17" id="x_first_name" name="x_first_name" value="'.($prefill ? 'John' : '').'" />
                </div>
                <div>
                    <label for="x_last_name">'.__('Last Name', 'my-calendar-submissions' ).'</label>
                    <input type="text" required aria-required="true" size="17" id="x_last_name" name="x_last_name" value="'.($prefill ? 'Doe' : '').'" />
                </div>
                <div>
                    <label for="x_payer_email">'.__('Email', 'my-calendar-submissions' ).'</label>
                    <input type="email" required aria-required="true" size="17" id="x_payer_email" name="x_payer_email" value="'.($prefill ? 'john@doe.com' : '').'" />
                </div>				
                <div>
                    <label for="x_address">'.__('Address', 'my-calendar-submissions' ).'</label>
                    <input type="text" size="26" id="x_address" name="x_address" value="'.($prefill ? '123 Main Street' : '').'" />
                </div>
                <div>
                    <label for="x_city">'.__('City', 'my-calendar-submissions' ).'</label>
                    <input type="text" size="17" id="x_city" name="x_city" value="'.($prefill ? 'Boston' : '').'" />
                </div>
                <div>
                    <label for="x_state">'.__('State', 'my-calendar-submissions' ).'</label>
                    <input type="text" size="4" id="x_state" name="x_state" value="'.($prefill ? 'MA' : '').'" />
                </div>
                <div>
                    <label for="x_zip">'.__('Zip Code', 'my-calendar-submissions' ).'</label>
                    <input type="text" size="9" id="x_zip" name="x_zip" value="'.($prefill ? '02142' : '').'" />
                </div>
                <div>
                    <label for="x_country">'.__('Country', 'my-calendar-submissions' ).'</label>
                    <input type="text" size="22" id="x_country" name="x_country" value="'.($prefill ? 'US' : '').'" />
                </div>';		
			if ( $button != '' && mc_is_url($button) ) {
				$form .= "<input type='image' src='$button' name='submit' class='button' alt='".__('Buy a payment key','my-calendar-submissions')."' />";
			} else {
				$form .= "<input type='submit' name='submit' class='button' value='".__('Buy a payment key','my-calendar-submissions')."' />";
			}
			
		$form  .= apply_filters( 'mcs_authorizenet_form', '', $price );
		$form .= '</form>';
        return $form;
    }

}