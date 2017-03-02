<?php 

OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::callCheck();

$user = \OCP\User::getUser();
$cancelUrl = 'https://'.$_SERVER['SERVER_NAME'].OC::$WEBROOT.'/index.php/settings/personal?cancel=true#userapps'; 
$returnUrl = 'https://'.$_SERVER['SERVER_NAME'].OC::$WEBROOT.'/index.php/settings/personal?success=true#userapps';
$currencyCode = \OCA\Files_Accounting\Storage_Lib::getBillingCurrency();
$maxTotalAmountOfAllPayments = '2000'; // TODO
$maxNumberOfPayments = '12'; // TODO
$paymentPeriod = 'MONTHLY'; // TODO
$maxAmountPerPayment = '10.0'; // TODO
$dateOfMonth =  \OCA\Files_Accounting\Storage_Lib::getBillingDayOfMonth();
$ipnNotificationUrl = 'https://'.$_SERVER['SERVER_NAME'].'/index.php/apps/files_accounting/ajax/paypal.php?user='.urlencode($user);

$paypalCredentials = \OCA\Files_Accounting\Storage_Lib::getPayPalApiCredentials();

$options = array(
    'cancelUrl' => $cancelUrl,
    'returnUrl' => $returnUrl,
    'currencyCode' => $currencyCode,
    'startingDate' => date('Y-m-d'),
    'endingDate' => date('Y-m-d', strtotime('+364 days')),
    'maxTotalAmountOfAllPayments' => $maxTotalAmountOfAllPayments, // The maximum total amount of all payments, cannot exceed $2,000 USD or the equivalent in other currencies
    'maxNumberOfPayments' => $maxNumberOfPayments,
    'paymentPeriod' => $paymentPeriod,
//    'dateOfMonth' => $dateOfMonth,
    'maxAmountPerPayment' => $maxAmountPerPayment, // The maximum amount per payment, it cannot exceed the value in maxTotalAmountOfAllPayments
    'ipnNotificationUrl' => $ipnNotificationUrl
    //'pinType' => 'REQUIRED'

);

\OCA\Files_Accounting\PayPalAP::setAuth($paypalCredentials['username'],
		$paypalCredentials['password'], $paypalCredentials['signature'],
		$paypalCredentials['appid'], empty($paypalCredentials['appid'])?'sandbox':'production');
$response = \OCA\Files_Accounting\PayPalAP::preApproval($options);

OC_JSON::success(array('data' => array('url'=>$response)));
