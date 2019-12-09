<?php

/**
 * Load whatever is neccessarry for the current system to function
 */
function pn_load_system() {
	define( '_JEXEC', 1 );
	define('JPATH_BASE', '../../..');
	define('DS', '/');

	require_once ( JPATH_BASE .'/includes/defines.php' );
	require_once ( JPATH_BASE .'/includes/framework.php' );
	require_once( JPATH_BASE . DS . 'libraries' . DS . 'joomla' . DS . 'factory.php' );


	/* Create the Application */
	$app = JFactory::getApplication('site');
	$app->initialise();

	if (!class_exists( 'VmConfig' ))
		require(JPATH_BASE . '/administrator' . DS . 'components' . DS . 'com_virtuemart'.DS.'helpers'.DS.'config.php');

	VmConfig::loadConfig();

	if (!class_exists( 'VmModel' ))
		require(JPATH_BASE . '/administrator' . DS . 'components' . DS . 'com_virtuemart'.DS.'helpers'.DS.'vmmodel.php');

	if (!class_exists( 'plgVMPaymentPayNow' ))
		require "paynow.php";

	return $app;
}

/**
 * Load PayNow functions/files
 */
function pn_load_paynow() {
	require_once 'paynow_common.inc';
	return;
}

/**
 * Get the URL we'll redirect users to when coming back from the gateway (for when they choose EFT/Retail)
 */
function pn_get_redirect_url() {
	return JPATH_BASE .'/index.php?option=com_virtuemart&view=user&layout=edit';
}

function pn_do_curl($url) {

	pnlog(__FUNCTION__ . " called!");

	$ch = curl_init();

	curl_setopt($ch,CURLOPT_URL, $url);
	curl_setopt($ch,CURLOPT_POST, true);
	curl_setopt($ch,CURLOPT_POSTFIELDS, $_POST);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

	//execute post
	$result = curl_exec($ch);

	//close connection
	curl_close($ch);

	pnlog("Curl result ($url): \r\n" . print_r($result, true));
}

/**
 * Check if this is a 'callback' stating the transaction is pending.
 */
function pn_is_pending() {
	return isset($_POST['TransactionAccepted'])
		&& $_POST['TransactionAccepted'] == 'false'
		&& stristr($_POST['Reason'], 'pending');
}

// Load System
$app = pn_load_system();

// Load PayNow
pn_load_paynow();

// Redirect URL for users using EFT/Retail payments to notify them the order's pending
$url_for_redirect = pn_get_redirect_url();

pnlog(__FILE__ . " POST: " . print_r($_REQUEST, true) );

if( isset($_POST) && !empty($_POST) && !pn_is_pending() ) {

	// This is the notification coming in!
	// Act as an IPN request and forward request to Credit Card method.
	// Logic is exactly the same

	$pm_m_id = isset($_POST['amp;pm']) ? $_POST['amp;pm'] : null;
	if( !$pm_m_id )
		$pm_m_id = isset($_POST['pm']) ? $_POST['pm'] : null;
	if( !$pm_m_id )
		$pm_m_id = isset($_GET['pm']) ? $_GET['pm'] : null;

	$order_number = isset($_POST['Reference']) ? $_POST['Reference'] : null; //$order ['details'] ['BT']->order_number;
	$virtuemart_paymentmethod_id = $pm_m_id ; // $order ['details'] ['BT']->virtuemart_paymentmethod_id;

	// POST to
	$url = JURI::base() . '../../../index.php?option=com_virtuemart&view=pluginresponse&task=pluginnotification&tmpl=component&on=' . $order_number . '&pm=' . $virtuemart_paymentmethod_id . "&o_id={$order_number}";
	pn_do_curl($url);

	// No need to redirect. This is only for notifications

	die();

} else {
	// Probably calling the "redirect" URL

	pnlog(__FILE__ . ' Probably calling the "redirect" URL');

	if( $url_for_redirect ) {

		// $app = JFactory::getApplication();
		$app->redirect($url_for_redirect, $msg, $msgType='message');

		// header ( "Location: {$url_for_redirect}" );
	} else {
	    die( "No 'redirect' URL set." );
	}
}

die( PN_ERR_BAD_ACCESS );
