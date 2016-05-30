<?php
namespace OCA\Files_Accounting;

use \OCP\Util;
use \OCP\User;
use \OCP\DB;

class ActivityHooks {
	public static function register() {
		Util::connectHook('OC_Activity', 'post_create', 'ActivityHooks', 'invoiceCreate');
		Util::connectHook('OC_Activity', 'complete', 'ActivityHooks', 'paymentComplete');
		// hooking up the activity manager
		$am = \OC::$server->getActivityManager();
		$am->registerConsumer(function() {
			return new Consumer();
		});
	}

	public static function invoiceCreate($user, $params) {
		ActivityHooks::addNotificationsForAction($user, $params, \Bill_Activity::TYPE_INVOICE, 'new_invoice');
	}
	
	public static function paymentComplete($user, $params) {
		ActivityHooks::addNotificationsForAction($user, $params, \Bill_Activity::TYPE_INVOICE, 'payment_complete');
	}
	
	public static function spaceExceed($user, $params) {
		ActivityHooks::addNotificationsForAction($user, $params, \Bill_Activity::TYPE_INVOICE, 'exceeded_space'); 
	}
	
	public static function preapprovedPayments($user) {
		ActivityHooks::addNotificationsForAction($user, '', \Bill_Activity::TYPE_INVOICE, 'preapproved_payments');
	}

	public static function automaticPaymentComplete($user, $params) {
		ActivityHooks::addNotificationsForAction($user, $params, \Bill_Activity::TYPE_INVOICE, 'automatic_payment_complete'); 
	}
	public static function addNotificationsForAction($user, $bill, $activityType, $subject) {
		ActivityHooks::addNotificationsForUser(
			$user, $subject,
			$bill,
			\OCA\UserNotification\Data::PRIORITY_MEDIUM,
			$activityType
		);
	}
	
	protected static function addNotificationsForUser($user, $subject, $path, $priority , $type ) {
		$app = 'files_accounting';	
		$link = $url = \OCA\Files_Accounting\Storage_Lib::getBillingURL($user, false);
		ActivityHooks::send($app, $subject, array($path), '', array(), '', $link, $user, $type, $priority);
	}
	
	public static function send($app, $subject, $subjectparams = array(), $message = '', $messageparams = array(), $file = '', $link = '', $affecteduser = '', $type = '', $prio) {
		$timestamp = time();
		$user = $affecteduser;	
		$auser = $affecteduser;

		// store in DB
		$query = DB::prepare('INSERT INTO `*PREFIX*activity`(`app`, `subject`, `subjectparams`, `message`, `messageparams`, `file`, `link`, `user`, `affecteduser`, `timestamp`, `priority`, `type`)' . ' VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )');
		$query->execute(array($app, $subject, serialize($subjectparams), $message, serialize($messageparams), $file, $link, $user, $auser, $timestamp, $prio, $type));

		// fire a hook so that other apps like notification systems can connect
		Util::emitHook('OC_Activity', 'post_event', array('app' => $app, 'subject' => $subject, 'user' => $user, 'affecteduser' => $affecteduser, 'message' => $message, 'file' => $file, 'link'=> $link, 'prio' => $prio, 'type' => $type));

		return true;
	}

}
