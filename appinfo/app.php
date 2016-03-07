<?php
OCP\App::checkAppEnabled('files_accounting');
OCP\App::registerAdmin('files_accounting', 'settings');
OCP\App::registerPersonal('files_accounting', 'personalsettings');

OC::$CLASSPATH['OCA\Files_Accounting\Stats'] = 'apps/files_accounting/lib/backgrounjob/stats.php';
require_once('apps/files_accounting/lib/backgroundjob/stats.php');
OCP\Backgroundjob::registerJob('OCA\Files_Accounting\Stats');

OC::$CLASSPATH['Bill_Activity']   ='apps/files_accounting/lib/activity.php';
OC::$CLASSPATH['Hooks'] = 'apps/activity/lib/hooks.php';
OC::$CLASSPATH['Mail'] = 'apps/files_accounting/lib/mail.php';
OC::$CLASSPATH['PersonalUtil'] = OC::$SERVERROOT.'/themes/deic_theme_oc7/settings/lib/personalutil.php';

\OCA\Files_Accounting\ActivityHooks::register();

\OC::$server->getActivityManager()->registerExtension(function() {
	return new Bill_Activity(
		\OC::$server->query('L10NFactory'),
		\OC::$server->getURLGenerator(),
		\OC::$server->getActivityManager(),
		\OC::$server->getConfig()
	);
});

