<?php 

OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::callCheck();

$cancelUrl = 'https://'.$_SERVER['SERVER_NAME'].'/index.php/settings/personal?cancel=true#userapps'; 
$returnUrl = 'https://'.$_SERVER['SERVER_NAME'].'/index.php/settings/personal?success=true#userapps';
$currencyCode = \OCA\Files_Accounting\Storage_Lib::getBillingCurrency();
$maxTotalAmountOfAllPayments = '2000'; // TODO
$maxNumberOfPayments = '12'; // TODO
$paymentPeriod = 'MONTHLY'; // TODO
$maxAmountPerPayment = '1.0'; // TODO
$dateOfMonth =  \OCA\Files_Accounting\Storage_Lib::getBillingDayOfMonth();
$ipnNotificationUrl = 'https://'.$_SERVER['SERVER_NAME'].'/index.php/apps/files_accounting/ajax/paypal.php';
 
$paypalCredentials = \OCA\Files_Accounting\Storage_Lib::getPayPalApiCredentials();

$options = array(
    'cancelUrl' => $cancelUrl,
    'returnUrl' => $returnUrl,
    'currencyCode' => $currencyCode,
    'startingDate' => date('Y-m-d'),
    'endingDate' => date('Y-m-d', strtotime('+1 year')),
    'maxTotalAmountOfAllPayments' => $maxTotalAmountOfAllPayments, // The maximum total amount of all payments, cannot exceed $2,000 USD or the equivalent in other currencies
    'maxNumberOfPayments' => $maxNumberOfPayments,
    'paymentPeriod' => $paymentPeriod,
    'dateOfMonth' => $dateOfMonth,
    'maxAmountPerPayment' => $maxAmountPerPayment, // The maximum amount per payment, it cannot exceed the value in maxTotalAmountOfAllPayments
    'ipnNotificationUrl' => $ipnNotificationUrl
    //'pinType' => 'REQUIRED'

);

PayPalAP::setAuth($paypalCredentials[0], $paypalCredentials[1], $paypalCredentials[2]);
$response = PayPalAP::preApproval($options);

OC_JSON::success(array('data' => array('url'=>$response)));
