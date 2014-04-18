<?php
/**
 * sagepaynow.php
 */
defined ( '_JEXEC' ) or die ( 'Direct Access to ' . basename ( __FILE__ ) . ' is not allowed.' );

if (! class_exists ( 'vmPSPlugin' ))
	require (JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');

define ( 'SANDBOX_SERVICE_KEY', '46f0cd694581a' );
class plgVMPaymentSagePayNow extends vmPSPlugin {
	// Instance of class
	public static $_this = false;
	function __construct(& $subject, $config) {
		parent::__construct ( $subject, $config );
		
		$this->_loggable = true;
		$this->tableFields = array_keys ( $this->getTableSQLFields () );
		
		$varsToPush = array (
				'sagepaynow_service_key' => array (
						'',
						'char' 
				),
				'sagepaynow_verified_only' => array (
						'',
						'int' 
				),
				'payment_currency' => array (
						0,
						'int' 
				),
				'sandbox' => array (
						0,
						'int' 
				),
				'sandbox_service_key' => array (
						'',
						'char' 
				),
				'sandbox_merchant_id' => array (
						'',
						'char' 
				),
				'payment_logos' => array (
						'',
						'char' 
				),
				'debug' => array (
						0,
						'int' 
				),
				'status_pending' => array (
						'',
						'char' 
				),
				'status_success' => array (
						'',
						'char' 
				),
				'status_canceled' => array (
						'',
						'char' 
				),
				'countries' => array (
						0,
						'char' 
				),
				'min_amount' => array (
						0,
						'int' 
				),
				'max_amount' => array (
						0,
						'int' 
				),
				'cost_per_transaction' => array (
						0,
						'int' 
				),
				'cost_percent_total' => array (
						0,
						'int' 
				),
				'tax_id' => array (
						0,
						'int' 
				) 
		);
		
		$this->setConfigParameterable ( $this->_configTableFieldName, $varsToPush );
	}
	
	function _getSagepaynowDetails($method) {
		$sagepaynowDetails = array (
				'service_key' => $method->sagepaynow_service_key,
				'url' => 'https://paynow.sagepay.co.za/site/paynow.aspx' 
		);
		
		return $sagepaynowDetails;
	}
	
	function _getPaymentResponseHtml($sagepaynowData, $payment_name) {
		$html = "";
		/*
		 * vmdebug('Sage Pay Now response', $sagepaynowData); $html = '<table>' . "\n"; $html .= $this->getHtmlRow('SAGEPAYNOW_PAYMENT_NAME', $payment_name); $html .= $this->getHtmlRow('SAGEPAYNOW_ORDER_NUMBER', $sagepaynowData['invoice']); $html .= $this->getHtmlRow('SAGEPAYNOW_AMOUNT', $sagepaynowData['mc_gross'] . " " . $sagepaynowData['mc_currency']); $html .= '</table>' . "\n";
		 */
		
		return $html;
	}
	
	/**
	 * Check if the payment conditions are fulfilled for this payment method
	 * 
	 * @author : Valerie Isaksen
	 *        
	 * @param $cart_prices: cart
	 *        	prices
	 * @param
	 *        	$payment
	 * @return true: if the conditions are fulfilled, false otherwise
	 *        
	 */
	protected function checkConditions($cart, $method, $cart_prices) {
		$address = (($cart->ST == 0) ? $cart->BT : $cart->ST);
		
		$amount = $cart_prices ['salesPrice'];
		$amount_cond = ($amount >= $method->min_amount and $amount <= $method->max_amount or ($method->min_amount <= $amount and ($method->max_amount == 0)));
		
		$countries = array ();
		if (! empty ( $method->countries )) {
			if (! is_array ( $method->countries )) {
				$countries [0] = $method->countries;
			} else {
				$countries = $method->countries;
			}
		}
		// probably did not gave his BT:ST address
		if (! is_array ( $address )) {
			$address = array ();
			$address ['virtuemart_country_id'] = 0;
		}
		
		if (! isset ( $address ['virtuemart_country_id'] ))
			$address ['virtuemart_country_id'] = 0;
		if (in_array ( $address ['virtuemart_country_id'], $countries ) || count ( $countries ) == 0) {
			if ($amount_cond) {
				return true;
			}
		}
		
		return false;
	}
	protected function getVmPluginCreateTableSQL() {
		return $this->createTableSQL ( 'Payment Sage Pay Now Table' );
	}
	function getTableSQLFields() {
		$SQLfields = array (
				'id' => 'int(11) UNSIGNED NOT NULL AUTO_INCREMENT',
				'virtuemart_order_id' => ' int(11) UNSIGNED DEFAULT NULL',
				'order_number' => ' char(32) DEFAULT NULL',
				'virtuemart_paymentmethod_id' => ' mediumint(1) UNSIGNED DEFAULT NULL',
				'payment_name' => ' char(255) NOT NULL DEFAULT \'\' ',
				'payment_order_total' => 'decimal(15,5) NOT NULL DEFAULT \'0.00000\' ',
				'payment_currency' => 'char(3) ',
				'cost_per_transaction' => ' decimal(10,2) DEFAULT NULL ',
				'cost_percent_total' => ' decimal(10,2) DEFAULT NULL ',
				'tax_id' => ' smallint(1) DEFAULT NULL',
				'sagepaynow_response' => ' varchar(255)  ',
				'sagepaynow_response_payment_date' => ' char(28) DEFAULT NULL' 
		);
		
		return $SQLfields;
	}
	function plgVmConfirmedOrder($cart, $order) {
		if (! ($method = $this->getVmPluginMethod ( $order ['details'] ['BT']->virtuemart_paymentmethod_id ))) {
			return null;
		}
		
		if (! $this->selectedThisElement ( $method->payment_element )) {
			return false;
		}
		
		$session = JFactory::getSession ();
		$return_context = $session->getId ();
		$this->_debug = $method->debug;
		$this->logInfo ( 'plgVmConfirmedOrder order number: ' . $order ['details'] ['BT']->order_number, 'message' );
		
		if (! class_exists ( 'VirtueMartModelOrders' ))
			require (JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');
		if (! class_exists ( 'VirtueMartModelCurrency' ))
			require (JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'currency.php');
			
			// $usr = & JFactory::getUser();
		$new_status = '';
		
		$usrBT = $order ['details'] ['BT'];
		$address = ((isset ( $order ['details'] ['ST'] )) ? $order ['details'] ['ST'] : $order ['details'] ['BT']);
		
		$vendorModel = new VirtueMartModelVendor ();
		$vendorModel->setId ( 1 );
		$vendor = $vendorModel->getVendor ();
		$this->getPaymentCurrency ( $method );
		$q = 'SELECT `currency_code_3` FROM `#__virtuemart_currencies` WHERE `virtuemart_currency_id`="' . $method->payment_currency . '" ';
		$db = &JFactory::getDBO ();
		$db->setQuery ( $q );
		$currency_code_3 = $db->loadResult ();
		
		$paymentCurrency = CurrencyDisplay::getInstance ( $method->payment_currency );
		$totalInPaymentCurrency = round ( $paymentCurrency->convertCurrencyTo ( $method->payment_currency, $order ['details'] ['BT']->order_total, false ), 2 );
		$cd = CurrencyDisplay::getInstance ( $cart->pricesCurrency );
		
		$sagepaynowDetails = $this->_getSagepaynowDetails ( $method );
		
		
		$testReq = $method->debug == 1 ? 'YES' : 'NO';
		$post_variables = Array (
				// Merchant details
				'service_key' => $sagepaynowDetails ['service_key'],
				'return_url' => JROUTE::_ ( JURI::root () . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginresponsereceived&pm=' . $order ['details'] ['BT']->virtuemart_paymentmethod_id . "&o_id={$order['details']['BT']->order_number}" ),
				'cancel_url' => JROUTE::_ ( JURI::root () . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginUserPaymentCancel&on=' . $order ['details'] ['BT']->order_number . '&pm=' . $order ['details'] ['BT']->virtuemart_paymentmethod_id ),
				'notify_url' => JROUTE::_ ( JURI::root () . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginnotification&tmpl=component&on=' . $order ['details'] ['BT']->order_number . '&pm=' . $order ['details'] ['BT']->virtuemart_paymentmethod_id . "&XDEBUG_SESSION_START=session_name" . "&o_id={$order['details']['BT']->order_number}" ),
				
				// Item details
				'item_name' => JText::_ ( 'VMPAYMENT_sagepaynow_ORDER_NUMBER' ) . ': ' . $order ['details'] ['BT']->order_number,
				'item_description' => "",
				'amount' => number_format ( sprintf ( "%01.2f", $totalInPaymentCurrency ), 2, '.', '' ),
				'm_payment_id' => $order ['details'] ['BT']->virtuemart_paymentmethod_id,
				'currency_code' => $currency_code_3,
				'custom_str1' => $order ['details'] ['BT']->order_number,
				'custom_int1' => "" 
		);
		
		// Prepare data that should be stored in the database
		$dbValues ['order_number'] = $order ['details'] ['BT']->order_number;
		$dbValues ['payment_name'] = $this->renderPluginName ( $method, $order );
		$dbValues ['virtuemart_paymentmethod_id'] = $cart->virtuemart_paymentmethod_id;
		$dbValues ['sagepaynow_custom'] = $return_context;
		$dbValues ['cost_per_transaction'] = $method->cost_per_transaction;
		$dbValues ['cost_percent_total'] = $method->cost_percent_total;
		$dbValues ['payment_currency'] = $method->payment_currency;
		$dbValues ['payment_order_total'] = $totalInPaymentCurrency;
		$dbValues ['tax_id'] = $method->tax_id;
		$this->storePSPluginInternalData ( $dbValues );
		
		$html = '<form action="' . $sagepaynowDetails ['url'] . '" method="post" name="vm_sagepaynow_form" >';
		$html .= '<input type="image" name="submit" src="\images\stories\virtuemart\payment\sagepaynow.png" alt="Click to pay with Sage Pay Now" />';
		foreach ( $post_variables as $name => $value ) {
			$html .= '<input type="hidden" name="' . $name . '" value="' . htmlspecialchars ( $value ) . '" />';
		}
		$html .= '</form>';
		
		$html .= ' <script type="text/javascript">';
		$html .= ' document.vm_sagepaynow_form.submit();';
		$html .= ' </script>';
		// 2 = don't delete the cart, don't send email and don't redirect
		return $this->processConfirmedOrderPaymentResponse ( 2, $cart, $order, $html, $new_status );
	}
	function plgVmgetPaymentCurrency($virtuemart_paymentmethod_id, &$paymentCurrencyId) {
		if (! ($method = $this->getVmPluginMethod ( $virtuemart_paymentmethod_id ))) {
			return null; // Another method was selected, do nothing
		}
		if (! $this->selectedThisElement ( $method->payment_element )) {
			return false;
		}
		
		$this->getPaymentCurrency ( $method );
		$paymentCurrencyId = $method->payment_currency;
	}
	function plgVmOnPaymentResponseReceived(&$html) {
		// the payment itself should send the parameter needed.
		$virtuemart_paymentmethod_id = JRequest::getInt ( 'pm', 0 );
		
		$vendorId = 0;
		if (! ($method = $this->getVmPluginMethod ( $virtuemart_paymentmethod_id ))) {
			return null; // Another method was selected, do nothing
		}
		
		if (! $this->selectedThisElement ( $method->payment_element )) {
			return false;
		}
		
		$payment_data = JRequest::get ( 'get' );
		vmdebug ( 'plgVmOnPaymentResponseReceived', $payment_data );
		$order_number = $payment_data ['o_id'];
		
		if (! class_exists ( 'VirtueMartModelOrders' ))
			require (JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');
		
		$virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber ( $order_number );
		$payment_name = $this->renderPluginName ( $method );
		$html = $this->_getPaymentResponseHtml ( $payment_data, $payment_name );
		
		if ($virtuemart_order_id) {
			if (! class_exists ( 'VirtueMartCart' ))
				require (JPATH_VM_SITE . DS . 'helpers' . DS . 'cart.php');
				
				// get the correct cart / session
			$cart = VirtueMartCart::getCart ();
			
			// send the email ONLY if payment has been accepted
			if (! class_exists ( 'VirtueMartModelOrders' ))
				require (JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');
			
			$order = new VirtueMartModelOrders ();
			$orderitems = $order->getOrder ( $virtuemart_order_id );
			// $cart->sentOrderConfirmedEmail($orderitems);
			$cart->emptyCart ();
		}
		
		return true;
	}
	function plgVmOnUserPaymentCancel() {
		if (! class_exists ( 'VirtueMartModelOrders' ))
			require (JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');
		
		$order_number = JRequest::getVar ( 'on' );
		if (! $order_number)
			return false;
		
		$db = JFactory::getDBO ();
		$query = 'SELECT ' . $this->_tablename . '.`virtuemart_order_id` FROM ' . $this->_tablename . " WHERE  `order_number`= '" . $order_number . "'";
		
		$db->setQuery ( $query );
		$virtuemart_order_id = $db->loadResult ();
		
		if (! $virtuemart_order_id) {
			return null;
		}
		
		$this->handlePaymentUserCancel ( $virtuemart_order_id );
		return true;
	}
	
	/*
	 * plgVmOnPaymentNotification() - This event is fired by Offline Payment. It can be used to validate the payment data as entered by the user. Return: Parameters: None @author Valerie Isaksen
	 */
	function plgVmOnPaymentNotification() {
		if (! class_exists ( 'VirtueMartModelOrders' ))
			require (JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');
			
			// Include Sage Pay Now Common File
		require_once ("sagepaynow_common.inc");
		
		// Variable Initialization
		$pnError = false;
		$pnErrMsg = '';
		$pnDone = false;
		$pnData = array ();
		$pnOrderId = '';
		$pnParamString = '';
		
		// // Notify Sage Pay Now that information has been received
		if (! $pnError && ! $pnDone) {
			header ( 'HTTP/1.0 200 OK' );
			flush ();
		}
		
		// // Get data sent by Sage Pay Now
		if (! $pnError && ! $pnDone) {
			pnlog ( 'Get posted data' );
			
			// Posted variables from IPN
			$pnData = pnGetData ();
			$sagepaynow_data = $pnData;
			
			pnlog ( 'Sage Pay Now Data: ' . print_r ( $pnData, true ) );
			
			if ($pnData === false) {
				$pnError = true;
				$pnErrMsg = PN_ERR_BAD_ACCESS;
			}
		}
		
		$order_number = $sagepaynow_data ['custom_str1'];
		$virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber ( $sagepaynow_data ['custom_str1'] );
		$this->logInfo ( 'plgVmOnPaymentNotification: virtuemart_order_id  found ' . $virtuemart_order_id, 'message' );
		
		if (! $virtuemart_order_id) {
			$this->_debug = true; // force debug here
			$this->logInfo ( 'plgVmOnPaymentNotification: virtuemart_order_id not found ', 'ERROR' );
			// send an email to admin, and ofc not update the order status: exit is fine
			// $this->sendEmailToVendorAndAdmins(JText::_('VMPAYMENT_SAGEPAYNOW_ERROR_EMAIL_SUBJECT'), JText::_('VMPAYMENT_SAGEPAYNOW_UNKNOWN_ORDER_ID'));
			exit ();
		}
		
		$vendorId = 0;
		$payment = $this->getDataByOrderId ( $virtuemart_order_id );
		$method = $this->getVmPluginMethod ( $payment->virtuemart_paymentmethod_id );
		//$pnHost = ($method->sandbox ? 'sandbox' : 'www') . '.sagepay.co.za';
		
		if (! $this->selectedThisElement ( $method->payment_element )) {
			return false;
		}
		
		$this->_debug = $method->debug;
		if (! $payment) {
			$this->logInfo ( 'getDataByOrderId payment not found: exit ', 'ERROR' );
			return null;
		}
		$this->logInfo ( 'sagepaynow_data ' . implode ( '   ', $sagepaynow_data ), 'message' );
		
		pnlog ( 'Sage Pay Now IPN call received' );
		
		// // Verify data received
		if (! $pnError) {
			pnlog ( 'Verify data received' );
			
			$pnValid = pnValidData ( $pnHost, $pnParamString );
			
			if (! $pnValid) {
				$pnError = true;
				$pnErrMsg = PN_ERR_BAD_ACCESS;
			}
		}
		
		// // Check data against internal order
		if (! $pnError && ! $pnDone) {
			// pnlog( 'Check data against internal order' );
			
			// Check order amount
			if (! pnAmountsEqual ( $pnData ['amount_gross'], $payment->payment_order_total )) {
				$pnError = true;
				$pnErrMsg = PN_ERR_AMOUNT_MISMATCH;
			}
		}
		
		// // Check status and update order
		if (! $pnError && ! $pnDone) {
			pnlog ( 'Check status and update order' );
			
			$sessionid = $pnData ['custom_str1'];
			$transaction_id = $pnData ['Trace'];
			
			switch ($pnData ['payment_status']) {
				case 'COMPLETE' :
					pnlog ( '- Complete' );
					$new_status = $method->status_success;
					break;
				
				case 'FAILED' :
					pnlog ( '- Failed' );
					$new_status = $method->status_canceled;
					break;
				
				case 'PENDING' :
					pnlog ( '- Pending' );
					
					// Need to wait for "Completed" before processing
					break;
				
				default :
					// If unknown status, do nothing (safest course of action)
					break;
			}
		}
		
		// If an error occurred
		if ($pnError) {
			pnlog ( 'Error occurred: ' . $pnErrMsg );
		}
		
		// get all know columns of the table
		$response_fields ['virtuemart_order_id'] = $virtuemart_order_id;
		$response_fields ['order_number'] = $order_number;
		$response_fields ['virtuemart_payment_method_id'] = $payment->virtuemart_paymentmethod_id;
		$response_fields ['payment_name'] = $this->renderPluginName ( $method );
		$response_fields ['cost_per_transaction'] = $payment->cost_per_transaction;
		$response_fields ['cost_percent_total'] = $payment->cost_percent_total;
		$response_fields ['payment_currency'] = $payment->payment_currency;
		$response_fields ['payment_order_total'] = $totalInPaymentCurrency;
		$response_fields ['tax_id'] = $method->tax_id;
		$response_fields ['sagepaynow_response'] = $pnData ['TransactionAccepted'];
		$response_fields ['sagepaynow_response_payment_date'] = date ( 'Y-m-d H:i:s' );
		
		$this->storePSPluginInternalData ( $response_fields );
		
		$this->logInfo ( 'plgVmOnPaymentNotification return new_status:' . $new_status, 'message' );
		
		if ($virtuemart_order_id && $pnData ['TransactionAccepted'] == 'TRUE') {
			// send the email only if payment has been accepted
			if (! class_exists ( 'VirtueMartModelOrders' ))
				require (JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');
			
			$modelOrder = new VirtueMartModelOrders ();
			$order ['order_status'] = $new_status;
			$order ['virtuemart_order_id'] = $virtuemart_order_id;
			$order ['customer_notified'] = 1;
			$order ['comments'] = JTExt::sprintf ( 'VMPAYMENT_SAGEPAYNOW_PAYMENT_CONFIRMED', $order_number );
			$modelOrder->updateStatusForOneOrder ( $virtuemart_order_id, $order, true );
		}
		
		$this->emptyCart ( $return_context );
		
		// Close log
		pnlog ( '', true );
		return true;
	}
	
	/**
	 * Display stored payment data for an order
	 * 
	 * @see components/com_virtuemart/helpers/vmPSPlugin::plgVmOnShowOrderBEPayment()
	 */
	function plgVmOnShowOrderBEPayment($virtuemart_order_id, $payment_method_id) {
		if (! $this->selectedThisByMethodId ( $payment_method_id )) {
			return null; // Another method was selected, do nothing
		}
		
		$db = JFactory::getDBO ();
		$q = 'SELECT * FROM `' . $this->_tablename . '` ' . 'WHERE `virtuemart_order_id` = ' . $virtuemart_order_id;
		$db->setQuery ( $q );
		
		if (! ($paymentTable = $db->loadObject ())) {
			// JError::raiseWarning(500, $db->getErrorMsg());
			return '';
		}
		
		$this->getPaymentCurrency ( $paymentTable );
		$q = 'SELECT `currency_code_3` FROM `#__virtuemart_currencies` WHERE `virtuemart_currency_id`="' . $paymentTable->payment_currency . '" ';
		$db = &JFactory::getDBO ();
		$db->setQuery ( $q );
		$currency_code_3 = $db->loadResult ();
		$html = '<table class="adminlist">' . "\n";
		$html .= $this->getHtmlHeaderBE ();
		$html .= $this->getHtmlRowBE ( 'sagepaynow_PAYMENT_NAME', $paymentTable->payment_name );
		// $html .= $this->getHtmlRowBE('sagepaynow_PAYMENT_TOTAL_CURRENCY', $paymentTable->payment_order_total.' '.$currency_code_3);
		$code = "sagepaynow_response_";
		foreach ( $paymentTable as $key => $value ) {
			if (substr ( $key, 0, strlen ( $code ) ) == $code) {
				$html .= $this->getHtmlRowBE ( $key, $value );
			}
		}
		$html .= '</table>' . "\n";
		return $html;
	}
	
	/**
	 * Create the table for this plugin if it does not yet exist.
	 * This functions checks if the called plugin is active one.
	 * When yes it is calling the standard method to create the tables
	 * 
	 * @author Valérie Isaksen
	 *        
	 */
	function plgVmOnStoreInstallPaymentPluginTable($jplugin_id) {
		return $this->onStoreInstallPluginTable ( $jplugin_id );
	}
	
	/**
	 * This event is fired after the payment method has been selected.
	 * It can be used to store
	 * additional payment info in the cart.
	 *
	 * @author Max Milbers
	 * @author Valérie isaksen
	 *        
	 * @param VirtueMartCart $cart:
	 *        	the actual cart
	 * @return null if the payment was not selected, true if the data is valid, error message if the data is not vlaid
	 *        
	 */
	public function plgVmOnSelectCheckPayment(VirtueMartCart $cart) {
		return $this->OnSelectCheck ( $cart );
	}
	
	/**
	 * plgVmDisplayListFEPayment
	 * This event is fired to display the pluginmethods in the cart (edit shipment/payment) for exampel
	 *
	 * @param object $cart
	 *        	Cart object
	 * @param integer $selected
	 *        	ID of the method selected
	 * @return boolean True on succes, false on failures, null when this plugin was not selected.
	 *         On errors, JError::raiseWarning (or JError::raiseError) must be used to set a message.
	 *        
	 * @author Valerie Isaksen
	 * @author Max Milbers
	 */
	public function plgVmDisplayListFEPayment(VirtueMartCart $cart, $selected = 0, &$htmlIn) {
		return $this->displayListFE ( $cart, $selected, $htmlIn );
	}
	
	/*
	 * plgVmonSelectedCalculatePricePayment Calculate the price (value, tax_id) of the selected method It is called by the calculator This function does NOT to be reimplemented. If not reimplemented, then the default values from this function are taken. @author Valerie Isaksen @cart: VirtueMartCart the current cart @cart_prices: array the new cart prices @return null if the method was not selected, false if the shiiping rate is not valid any more, true otherwise
	 */
	public function plgVmonSelectedCalculatePricePayment(VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name) {
		return $this->onSelectedCalculatePrice ( $cart, $cart_prices, $cart_prices_name );
	}
	
	/**
	 * plgVmOnCheckAutomaticSelectedPayment
	 * Checks how many plugins are available.
	 * If only one, the user will not have the choice. Enter edit_xxx page
	 * The plugin must check first if it is the correct type
	 * 
	 * @author Valerie Isaksen
	 * @param
	 *        	VirtueMartCart cart: the cart object
	 * @return null if no plugin was found, 0 if more then one plugin was found, virtuemart_xxx_id if only one plugin is found
	 *        
	 */
	function plgVmOnCheckAutomaticSelectedPayment(VirtueMartCart $cart, array $cart_prices = array()) {
		return $this->onCheckAutomaticSelected ( $cart, $cart_prices );
	}
	
	/**
	 * This method is fired when showing the order details in the frontend.
	 * It displays the method-specific data.
	 *
	 * @param integer $order_id
	 *        	The order ID
	 * @return mixed Null for methods that aren't active, text (HTML) otherwise
	 * @author Max Milbers
	 * @author Valerie Isaksen
	 */
	public function plgVmOnShowOrderFEPayment($virtuemart_order_id, $virtuemart_paymentmethod_id, &$payment_name) {
		$this->onShowOrderFE ( $virtuemart_order_id, $virtuemart_paymentmethod_id, $payment_name );
	}
	
	/**
	 * This method is fired when showing when priting an Order
	 * It displays the the payment method-specific data.
	 *
	 * @param integer $_virtuemart_order_id
	 *        	The order ID
	 * @param integer $method_id
	 *        	method used for this order
	 * @return mixed Null when for payment methods that were not selected, text (HTML) otherwise
	 * @author Valerie Isaksen
	 */
	function plgVmonShowOrderPrintPayment($order_number, $method_id) {
		return $this->onShowOrderPrint ( $order_number, $method_id );
	}
	
	/**
	 * This method is fired when showing the order details in the frontend, for every orderline.
	 * It can be used to display line specific package codes, e.g. with a link to external tracking and
	 * tracing systems
	 *
	 * @param integer $_orderId
	 *        	The order ID
	 * @param integer $_lineId        	
	 * @return mixed Null for method that aren't active, text (HTML) otherwise
	 * @author Oscar van Eijk
	 *        
	 *         public function plgVmOnShowOrderLineFE( $_orderId, $_lineId) {
	 *         return null;
	 *         }
	 */
	function plgVmDeclarePluginParamsPayment($name, $id, &$data) {
		return $this->declarePluginParams ( 'payment', $name, $id, $data );
	}
	function plgVmSetOnTablePluginParamsPayment($name, $id, &$table) {
		return $this->setOnTablePluginParams ( $name, $id, $table );
	}
}
