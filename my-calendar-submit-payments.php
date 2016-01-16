<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function mcs_sales_page() {
	global $wpdb;
	mcs_check();
	echo "<div class='wrap jd-my-calendar'>";
    echo "<h2>My Calendar Submissions Sales</h2>";
	?>
	<div class="postbox-container" style="width: 70%">
	
		<div class="metabox-holder">
		<div class="ui-sortable meta-box-sortables">   
		<div class="postbox">
			<h3><?php _e('Register a payment','my-calendar-submissions'); ?></h3>
			<div class="inside"> 
			<?php
				$response = mcs_add_payment($_POST);
				echo $response;		
				$quantity = 1; // send to
				$price = get_option('mcs_submission_fee'); // send from
				$first_name = ''; // subject line
				$last_name = '';	// admin email after submission
				$email = '';	// submitter email after submission
				$transaction_date = date_i18n( 'j F, Y' ); // subject line
			?>
			<p><?php _e('Use this form to manually register a payment and send payment notification messages.','my-calendar-submissions'); ?></p>
			<form method="post" action="<?php echo admin_url("admin.php?page=my-calendar-payments"); ?>">
			<div>
			<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('my-calendar-payments'); ?>" />
			</div>
			<p><input type="submit" name="mc-submit-payments" class="button-primary" value="<?php _e('Enter Payment','my-calendar-submissions'); ?>" /></p>			
			<ul>
			<li>
			<label for="quantity"><?php _e('Quantity (event submissions purchased)','my-calendar-submissions'); ?></label> <input type="text" name="quantity" id="quantity" size="6" value="<?php echo esc_attr($quantity); ?>" /> 
			<label for="price"><?php _e('Price Paid (total)','my-calendar-submissions'); ?></label> <input type="text" name="price" id="price" size="6" value="<?php echo esc_attr($price); ?>" />
			</li>
			<li>
			<label for="first_name"><?php _e('First Name','my-calendar-submissions'); ?></label> <input type="text" name="first_name" id="first_name" size="60" value="<?php echo esc_attr($first_name); ?>" />
			</li>
			<li>
			<label for="last_name"><?php _e('Last Name','my-calendar-submissions'); ?></label> <input type="text" name="last_name" id="last_name" size="60" value="<?php esc_attr($last_name); ?>" />
			</li>
			<li>
			<label for="email"><?php _e('Email','my-calendar-submissions'); ?></label> <input type="text" name="email" id="email" size="60" value="<?php echo esc_attr($email); ?>" />
			</li>			
			<li>
			<label for="transaction_date"><?php _e('Transaction Date','my-calendar-submissions'); ?></label> <input type="text" name="transaction_date" id="transaction_date" size="20" value="<?php echo esc_attr($transaction_date); ?>" />
			</li>	
			</ul>
			<p><input type="submit" name="mc-submit-payments" class="button-secondary" value="<?php _e('Enter Payment','my-calendar-submissions'); ?>" /></p>		
			</form>				
			</div>
		</div>
		</div>
	</div>
	
	<div class="metabox-holder">

		<div class="ui-sortable meta-box-sortables">   
		<div class="postbox">
			<h3>My Calendar Event Payments</h3>
			<div class="inside">   
		<?php
	foreach($_GET as $key => $value) {
		$_GET[$key] = trim($value);
	}
	
	$name_query = $email_query = $txn_id_query = $hash_query = 	$status_query = "";
	if( !empty( $_GET['name'] ) ) {
		$name_query = " AND (first_name LIKE '%".$wpdb->escape($_GET['name'])."%' OR last_name LIKE '%".$wpdb->escape($_GET['name'])."%') ";
	}
	if(!empty($_GET['email'])) {
		$email_query = " AND payer_email LIKE '%".$wpdb->escape($_GET['email'])."%' ";
	}
	if(!empty($_GET['txn_id'])) {
		$txn_id_query = " AND txn_id = '".$wpdb->escape($_GET['txn_id'])."' ";
	}	
	if(!empty($_GET['hash'])) {
		$hash_query = " AND hash = '".$wpdb->escape($_GET['hash'])."' ";
	}		
	if(!empty($_GET['status'])) {
		$operator = $_GET['status'] == 1 ? "=" : "!=";
		$status_query = " AND status $operator 'Completed' ";
	}
		
	$current = empty($_GET['paged']) ? 1 : intval($_GET['paged']);
	$items_per_page = 50;
	
	$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM ".my_calendar_payments_table()."
			WHERE TRUE AND item_number = 1
			$name_query
			$email_query
			$txn_id_query
			$hash_query
			$status_query
			ORDER BY id DESC
			LIMIT ".(($current-1)*$items_per_page).", ".$items_per_page;

	$rows = $wpdb->get_results($sql);
	$found_rows = $wpdb->get_col("SELECT FOUND_ROWS();");
	$items = $found_rows[0];
	
	if ( $items > 0 ) {
		$items_per_page = 50;
		$num_pages = ceil($items / $items_per_page);
			
		$page_links = paginate_links( array(
			'base' => add_query_arg( 'paged', '%#%' ),
			'format' => '',
			'prev_text' => __('&laquo;'),
			'next_text' => __('&raquo;'),
			'total' => $num_pages,
			'current' => $current
		));
		
		echo "<h4>$items Transactions found</h4>";
		if ( $num_pages > 1 ) {
			echo "
			<div class='tablenav'>
				<div class='tablenav-pages'>$page_links</div>
			</div>";
		}
		echo "<table class='widefat'>
		<thead>
			<tr>
				<th scope='col'>Trans ID</th>
				<th scope='col'>Key</th>
				<th scope='col'>Price</th>
				<th scope='col'>Status</th>
				<th scope='col'>Date</th>
				<th scope='col'>First</th>
				<th scope='col'>Last</th>
				<th scope='col'>Email</th>
				<th scope='col'>Remaining</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th scope='col'>Trans ID</th>
				<th scope='col'>Key</th>
				<th scope='col'>Price</th>
				<th scope='col'>Status</th>
				<th scope='col'>Date</th>
				<th scope='col'>First</th>
				<th scope='col'>Last</th>
				<th scope='col'>Email</th>
				<th scope='col'>Remaining</th>
			</tr>
		</tfoot>
		<tbody>";
		foreach($rows as $row) {
			echo "<tr>";
			echo "<td>{$row->txn_id}</td>";
			echo "<td>{$row->hash}</td>";
			echo "<td>".$row->price." ".get_option('mcs_currency')."</td>";
			echo "<td>{$row->status}</td>";
			echo "<td>{$row->transaction_date}</td>";
			echo "<td>{$row->first_name}</td>";
			echo "<td>{$row->last_name}</td>";
			echo "<td><a href='mailto:{$row->payer_email}'>{$row->payer_email}</a></td>";
			echo "<td>{$row->quantity}/{$row->total}</td>";
			echo "</tr>";
		}
		echo "</tbody>
		</table>";
	} else {
		_e('You have not yet received any payments for event submissions','my-calendar-submissions');
	}
		echo "</div>
	</div>
	</div>
	</div>
	</div>";
	?>
<div class="postbox-container" style="width:20%">
	<div class="metabox-holder">
		<div class="ui-sortable meta-box-sortables">
			<div class="postbox support">
			<h3><?php _e( 'Search Transactions','my-calendar-submissions' ); ?></h3>
			<div class='inside'>
				<?php
				echo "<form method='get' action='".admin_url('admin.php?page=my-calendar-payments')."'>";
				echo "<div class='mcs-search'>";
				echo "<p><label for='pname'>Name of Payer</label> <input type='text' id='pname' name='name' value='".@$_GET['name']."' /></p>";	
				echo "<p><label for='pemail'>Email of Payer</label> <input type='text' id='pemail' name='email' value='".@$_GET['email']."' /></p>";
				echo "<p><label for='ptrans'>Transaction ID</label> <input type='text' id='ptrans' name='txn_id' value='".@$_GET['txn_id']."' /></p>";
				echo "<p><label for='lkey'>Payment Key</label> <input type='text' id='lkey' name='hash' value='".@$_GET['hash']."' /></p>";	
				echo "<p><label for='status'>Status of payment</label> <select name='status' id='status'>";
						$selected = ( isset($_GET['status']) AND $_GET['status'] == 0 ) ? "selected='selected'" : "";
						echo "<option value='0' $selected>All</option>";
						$selected = ( isset($_GET['status']) AND $_GET['status'] == 1 ) ? "selected='selected'" : "";
						echo "<option value='1' $selected>Only Completed</option>";
						$selected = ( isset($_GET['status']) AND $_GET['status'] == 2 ) ? "selected='selected'" : "";
						echo "<option value='2' $selected>Only Invalid</option>";
					echo "</select>";
				echo "</div>";
				echo "<p class='submit'><input type='submit' class='button-secondary' value='Search Payments' /></p>";
				echo "</form>";
				echo "<div class='mcs-earnings'>";
					$earnings = "SELECT SUM(price) FROM ".my_calendar_payments_table()." WHERE item_number = 1 AND status = 'Completed'";
					$sum = $wpdb->get_var($earnings);	
				echo "<p>Earnings to date: $<strong>$sum</strong></p>";
					$earnings = "SELECT SUM(price) FROM ".my_calendar_payments_table()." WHERE YEAR(transaction_date) = '".date('Y')."' AND item_number = 1 AND status = 'Completed'";
					$sum = $wpdb->get_var($earnings);
				echo "<p>Earnings this year: $<strong>$sum</strong></p>";	
					$earnings = "SELECT SUM(price) FROM ".my_calendar_payments_table()." WHERE MONTH(transaction_date) = '".date('n')."' AND item_number = 1 AND status = 'Completed'";
					$sum = $wpdb->get_var($earnings);
				echo "<p>Earnings this month: $<strong>$sum</strong></p>
				</div>";
				?>
			</div>
			</div>
		</div>
	</div>
</div>
<?php
}

function mcs_add_payment( $post ) {
global $wpdb; 
	if ( isset($post['mc-submit-payments']) ) {
		$nonce = $_POST['_wpnonce'];
		if ( !wp_verify_nonce( $nonce,'my-calendar-payments' ) ) return;	
		$quantity = (int) $post['quantity'];	// admin email after submission
		$price = sprintf("%01.2f", $post['price'] );	// submitter email after submission
		$first_name = $post['first_name']; // subject line
		$last_name = $post['last_name'];
		$email = is_email( $post['email'] );
		$transaction_date = date('Y-m-d h:m:s',strtotime( $post['transaction_date'] ) );
			$uniqid = uniqid('E');
			$hash = mcs_uniqid( $uniqid );
		$add = array( 'item_number'=>1,'quantity'=>$quantity, 'total'=>$quantity, 'hash'=>$hash, 'txn_id'=>'Manual Entry','price'=>$price,'fee'=>'0.00','status'=>'Completed','transaction_date'=>$transaction_date,'first_name'=>$first_name,'last_name'=>$last_name,'payer_email'=>$email);
		$formats = array( '%d','%d','%d','%s','%s','%f','%f','%s','%s','%s','%s','%s' );
		$insert = $wpdb->insert( my_calendar_payments_table(), $add, $formats );
		if ( $insert ) {
			$notifications = mcs_send_notifications( $first_name, $last_name, $email, $price, $hash, $quantity );
			return "<div class=\"updated\"><p><strong>".__('New Payment Added','my-calendar-submissions')."</strong></p></div>";
		} else {
			return "<div class=\"updated error\"><p><strong>".__('New Payment was not added.','my-calendar-submissions')."</strong></p></div>";
		}
	}
	return false;
}