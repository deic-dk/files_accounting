<?php
// CONFIG: Enable debug mode. This means we'll log requests into 'ipn.log' in the same directory.
// Especially useful if you encounter network errors or other intermittent problems with IPN (validation).
// Set this to 0 once you go live or don't require logging.
define("DEBUG", 1);
// Set to 0 once you're ready to go live
define("USE_SANDBOX", 1);
define("LOG_FILE", \OC::$SERVERROOT."/apps/files_accounting/ajax/ipn.log");

$paypalAccount = \OCA\Files_Accounting\Storage_Lib::getPayPalAccount();
$mail_From = \OCA\Files_Accounting\Storage_Lib::getIssuerEmail();
$user = isset($_GET["user"])?$_GET["user"]:null;

// Read POST data
// reading posted data directly from $_POST causes serialization
// issues with array data in POST. Reading raw POST data from input stream instead.
$raw_post_data = file_get_contents('php://input');
$raw_post_array = explode('&', $raw_post_data);
$myPost = array();
foreach ($raw_post_array as $keyval) {
	$keyval = explode ('=', $keyval);
	if (count($keyval) == 2)
		$myPost[$keyval[0]] = urldecode($keyval[1]);
}
$verifiedIpn = \OCA\Files_Accounting\PayPalAP::handleIpn($myPost, USE_SANDBOX);

// IPN for preapproved payments registration
if (isset($_POST["preapproval_key"]) && isset($user) && $verifiedIpn && $_POST["approved"]) {
	if ($_POST["status"] == 'ACTIVE') {
		$user = urldecode($user);
		$result = \OCA\Files_Accounting\Storage_Lib::setPreapprovalKey($user, $_POST["preapproval_key"], $_POST["ending_date"]);		
		if ($result) {
			\OCA\Files_Accounting\ActivityHooks::preapprovedPayments($user);
		}
	} else if ($_POST["status"] == 'CANCELED') {
		\OCA\Files_Accounting\Storage_Lib::deletePreapprovalKey($user, $_POST["preapproval_key"]);	
	}
}
// IPN for basic payments
if ($verifiedIpn && isset($_POST["txn_id"]) && isset($_POST["txn_type"]) && !empty($_POST["item_name"]) ){
	// check whether the payment_status is Completed
	// check that txn_id has not been previously processed
	// check that receiver_email is your PayPal email
	// check that payment_amount/payment_currency are correct
	// process payment and mark item as paid.
	// assign posted variables to local variables
	$data['item_name'] = $_POST['item_name'];
	$data['item_number'] = $_POST['item_number'];
	$data['payment_status'] = $_POST['payment_status'];
	$data['payment_amount'] = $_POST['mc_gross'];
	$data['payment_currency'] = $_POST['mc_currency'];
	$data['txn_id'] = $_POST['txn_id'];
	$data['receiver_email'] = $_POST['receiver_email'];
	$data['payer_email'] = $_POST['payer_email'];
	$data['custom'] = $_POST['custom'];

	$valid_txnid = \OCA\Files_Accounting\Storage_Lib::checkTxnId($data['txn_id']);
	$valid_price = 	\OCA\Files_Accounting\Storage_Lib::checkPrice($data['payment_amount'], $data['item_number']);

	$mail_Subject = "Error during payment";

	if ( $valid_price && $data['receiver_email'] === $paypalAccount) {
		if ($data['payment_status'] === 'Completed' && $valid_txnid) {
			$orderid = \OCA\Files_Accounting\Storage_Lib::updatePayments($data);
			if ($orderid) {
				\OCA\Files_Accounting\Storage_Lib::updateStatus($data['item_number']);	
				\OCA\Files_Accounting\ActivityHooks::paymentComplete($data['custom'], $data['item_name']); 
				\OCP\Util::writeLog('IPN Testing', "Payment inserted into DB ", 3);
			}
			else {
				\OCP\Util::writeLog('IPN Testing', "Error inserting into DB ", 3);
			}
		}
		elseif ($data['payment_status'] === 'Declined') {
			\OCP\Util::writeLog('IPN Testing', "Payment with transaction ID ".$data['txn_id']." is declined. ", 3);
			$mail_Body  = "Payment with transaction id ".$data['txn_id']." was declined.";
			mail($paypalAccount, $mail_Subject, $mail_Body, $mail_From);
		}
		else {
			\OCP\Util::writeLog('IPN Testing', "Payment with transaction ID ".$data['txn_id']." is pending. ", 3);
			$mail_Body  = "Payment with transaction id ".$data['txn_id']." is pending.";
			mail($paypalAccount, $mail_Subject, $mail_Body, $mail_From);
		}
	}
 	else {
		\OCP\Util::writeLog('IPN Testing', "Payment with transaction ID ".$data['txn_id']." was made, but data has been changed. ", 3);
		$mail_Subject = "Error during payment";
		$mail_Body = "Payment with transaction ID ".$data['txn_id']." was made, but data has been changed.";
		mail($paypalAccount, $mail_Subject, $mail_Body, $mail_From);
	}
	
}
?>
