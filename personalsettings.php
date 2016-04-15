<?php
OCP\JSON::checkAppEnabled('files_accounting');
OCP\User::checkLoggedIn();

if(!\OCP\App::isEnabled('files_sharding') || \OCA\FilesSharding\Lib::onServerForUser()) {
	OCP\Util::addStyle('files_accounting', 'style');
	OCP\Util::addScript('files_accounting', 'personalsettings');
	//Billing Plots
	OCP\Util::addScript('files_accounting', 'google-plot');
	OCP\Util::addScript('files_accounting', 'plot');

	$tmpl = new OCP\Template( 'files_accounting', 'personalsettings.tpl');
	return $tmpl->fetchPage();
}
