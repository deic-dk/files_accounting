<?php
/**
 * This is owncloud's function for sending emails using PHPMailer Library.
 * It has been extended to include attachments.
 */
require_once '3rdparty/phpmailer/phpmailer/class.phpmailer.php';
class Mail {
	public static function send($toaddress, $toname, $subject, $mailtext, $fromaddress, $fromname, $path,
		$html=0, $altbody='', $ccaddress='', $ccname='', $bcc='') {

		$SMTPMODE = OC_Config::getValue( 'mail_smtpmode', 'sendmail' );
		$SMTPHOST = OC_Config::getValue( 'mail_smtphost', '127.0.0.1' );
		$SMTPPORT = OC_Config::getValue( 'mail_smtpport', 25 );
		$SMTPAUTH = OC_Config::getValue( 'mail_smtpauth', false );
		$SMTPAUTHTYPE = OC_Config::getValue( 'mail_smtpauthtype', 'LOGIN' );
		$SMTPUSERNAME = OC_Config::getValue( 'mail_smtpname', '' );
		$SMTPPASSWORD = OC_Config::getValue( 'mail_smtppassword', '' );
		$SMTPDEBUG    = OC_Config::getValue( 'mail_smtpdebug', false );
		$SMTPTIMEOUT  = OC_Config::getValue( 'mail_smtptimeout', 10 );
		$SMTPSECURE   = OC_Config::getValue( 'mail_smtpsecure', '' );


		$mailo = new PHPMailer(true);
		if($SMTPMODE=='sendmail') {
			$mailo->IsSendmail();
		}elseif($SMTPMODE=='smtp') {
			$mailo->IsSMTP();
		}elseif($SMTPMODE=='qmail') {
			$mailo->IsQmail();
		}else{
			$mailo->IsMail();
		}


		$mailo->Host = $SMTPHOST;
		$mailo->Port = $SMTPPORT;
		$mailo->SMTPAuth = $SMTPAUTH;
		$mailo->SMTPDebug = $SMTPDEBUG;
		$mailo->SMTPSecure = $SMTPSECURE;
		$mailo->AuthType = $SMTPAUTHTYPE;
		$mailo->Username = $SMTPUSERNAME;
		$mailo->Password = $SMTPPASSWORD;
		$mailo->Timeout  = $SMTPTIMEOUT;

		$mailo->From = $fromaddress;
		$mailo->FromName = $fromname;;
		$mailo->Sender = $fromaddress;
		try {
			$toaddress = $toaddress;
			$mailo->AddAddress($toaddress, $toname);

			if($ccaddress<>'') $mailo->AddCC($ccaddress, $ccname);
			if($bcc<>'') $mailo->AddBCC($bcc);

			//$mailo->AddReplyTo($fromaddress, $fromname);
			if($replyTo !== '') {
				$mailo->addReplyTo($replyTo);
			}
			else{
				$mailo->addReplyTo($fromaddress, $fromname);
			}

			$mailo->WordWrap = 50;
			if($html==1) $mailo->IsHTML(true); else $mailo->IsHTML(false);

			$mailo->Subject = $subject;

			$mailo->AddAttachment($path);
			if($altbody=='') {
				$mailo->Body    = $mailtext.OC_MAIL::getfooter();
				$mailo->AltBody = '';
			}else{
				$mailo->Body    = $mailtext;
				$mailo->AltBody = $altbody;
			}
			$mailo->CharSet = 'UTF-8';

			$mailo->Send();
			unset($mailo);
			OC_Log::write('mail',
				'Mail from '.$fromname.' ('.$fromaddress.')'.' to: '.$toname.'('.$toaddress.')'.' subject: '.$subject,
				OC_Log::DEBUG);
		} catch (Exception $exception) {
			OC_Log::write('mail', $exception->getMessage(), OC_Log::ERROR);
			throw($exception);
		}
	}
}
