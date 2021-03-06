<?php
/* This file contains functions used to process required database updates sometimes logged after PMPro is upgraded. */

/*
	Is there an update?
*/
function pmpro_isUpdateRequired() {
	$updates = get_option('pmpro_updates', array());
	return(!empty($updates));
}

/**
 * Update option to require an update.
 * @param string $update
 *
 * @since 1.8.7
 */
function pmpro_addUpdate($update) {
	$updates = get_option('pmpro_updates', array());
	$updates[] = $update;
	$updates = array_values(array_unique($updates));

	update_option('pmpro_updates', $updates, 'no');
}

/**
 * Update option to remove an update.
 * @param string $update
 *
 * @since 1.8.7
 */
function pmpro_removeUpdate($update) {
	$updates = get_option('pmpro_updates', array());
	$key = array_search($update,$updates);
	if($key!==false){
	    unset($updates[$key]);
	}

	$updates = array_values($updates);
	
	update_option('pmpro_updates', $updates, 'no');
}

/*
	Enqueue updates.js if needed
*/
function pmpro_enqueue_update_js() {
	if(!empty($_REQUEST['page']) && $_REQUEST['page'] == 'pmpro-updates') {
		wp_enqueue_script( 'pmpro-updates', plugin_dir_url( dirname(__FILE__) ) . 'js/updates.js', array('jquery'), PMPRO_VERSION );
	}
}
add_action('admin_enqueue_scripts', 'pmpro_enqueue_update_js');

/*
	Load an update via AJAX
*/
function pmpro_wp_ajax_pmpro_updates() {
	//get updates
	$updates = array_values(get_option('pmpro_updates', array()));

	//run update or let them know we're done
	if(!empty($updates)) {	
		//get the latest one and run it
		if(function_exists($updates[0]))
			call_user_func($updates[0]);
		else
			echo "[error] Function not found: " . $updates[0];
		echo ". ";
	} else {
		echo "[done]";
	}
	
	//reset this transient so we know AJAX is running
	set_transient('pmpro_updates_first_load', false, 60*60*24);
	
	//show progress
	global $pmpro_updates_progress;
	if(!empty($pmpro_updates_progress))
		echo $pmpro_updates_progress;

	exit;
}
add_action('wp_ajax_pmpro_updates', 'pmpro_wp_ajax_pmpro_updates');

/*
	Redirect away from updates page if there are no updates
*/
function pmpro_admin_init_updates_redirect() {
	if(is_admin() && !empty($_REQUEST['page']) && $_REQUEST['page'] == 'pmpro-updates' && !pmpro_isUpdateRequired()) {
		wp_redirect(admin_url('admin.php?page=pmpro-membershiplevels&updatescomplete=1'));
		exit;
	}
}
add_action('init', 'pmpro_admin_init_updates_redirect');

/*
	Show admin notice if an update is required and not already on the updates page.
*/
if(pmpro_isUpdateRequired() && (empty($_REQUEST['page']) || $_REQUEST['page'] != 'pmpro-updates'))
	add_action('admin_notices', 'pmpro_updates_notice');
	
/*
	Function to show an admin notice linking to the updates page.
*/
function pmpro_updates_notice() {
?>
<div class="update-nag">
	<p>
	<?php 
		echo __( 'Paid Memberships Pro Data Update Required', 'pmpro' );
	?>
	</p>
	<p>
	<?php 
		echo '<a class="button button-primary" href="' . admin_url('admin.php?page=pmpro-updates') . '">' . __('Start the Update', 'pmpro') . '</a>';
	?>
	</p>
</div>
<?php
}

/*
	Show admin notice when updates are complete.
*/
if(is_admin() && !empty($_REQUEST['updatescomplete']))
	add_action('admin_notices', 'pmpro_updates_notice_complete');
	
/*
	Function to show an admin notice linking to the updates page.
*/
function pmpro_updates_notice_complete() {
?>
<div class="updated notice notice-success is-dismissible">
	<p>
	<?php 
		echo __('All Paid Memberships Pro updates have finished.', 'pmpro' );
	?>
	</p>	
</div>
<?php
}