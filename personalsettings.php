<?php
OCP\JSON::checkAppEnabled('files_accounting');
OCP\User::checkLoggedIn();

OC::$CLASSPATH['Stats'] ='apps/files_accounting/lib/stats.php';
//Stats::UpdateMonthlyAverage();

OCP\Util::addStyle('files_accounting', 'style');
OCP\Util::addScript('files_accounting', 'personalsettings');
$tmpl = new OCP\Template( 'files_accounting', 'personalsettings.tpl');
return $tmpl->fetchPage();
