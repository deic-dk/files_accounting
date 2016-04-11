<?php
OCP\JSON::checkAppEnabled('files_accounting');
OCP\User::checkLoggedIn();

OC::$CLASSPATH['PersonalUtil'] = OC::$SERVERROOT.'/themes/deic_theme_oc7/settings/lib/personalutil.php';
if(!\OCP\App::isEnabled('files_sharding') || \OCA\FilesSharding\Lib::onServerForUser() &&
		substr($_SERVER['REQUEST_URI'], -9)==='#userapps') {
	OCP\Util::addStyle('files_accounting', 'style');
	OCP\Util::addScript('files_accounting', 'personalsettings');
	//Billing Plots
	OCP\Util::addScript('files_accounting', 'google-plot');
	OCP\Util::addScript('files_accounting', 'plot');

	$tmpl = new OCP\Template( 'files_accounting', 'personalsettings.tpl');
	return $tmpl->fetchPage();
}
