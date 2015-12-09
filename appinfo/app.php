<?php
OCP\App::checkAppEnabled('files_accounting');
OCP\App::registerAdmin('files_accounting', 'settings');
OCP\App::registerPersonal('files_accounting', 'personalsettings');
OCP\Backgroundjob::registerJob('OCA\Files_Accounting\Stats');

OC::$CLASSPATH['Bill_Activity']   ='apps/files_accounting/lib/activity.php';
OC::$CLASSPATH['Hooks'] = 'apps/activity/lib/hooks.php';
OC::$CLASSPATH['Mail'] = 'apps/files_accounting/lib/mail.php';
\OCA\Files_Accounting\ActivityHooks::register();

\OC::$server->getActivityManager()->registerExtension(function() {
	return new Bill_Activity(
		\OC::$server->query('L10NFactory'),
		\OC::$server->getURLGenerator(),
		\OC::$server->getActivityManager(),
		\OC::$server->getConfig()
	);
});

