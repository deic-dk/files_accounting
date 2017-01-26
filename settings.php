<?php
OC_Util::checkAdminUser();
OCP\Util::addScript('files_accounting', 'settings');
OCP\Util::addStyle('files_accounting', 'style');
$tmpl = new OCP\Template( 'files_accounting', 'settings.tpl');
$defaultFreeQuota = OCP\Config::getAppValue('files_accounting', 'default_freequota');
$tmpl->assign('default_freequota', $defaultFreeQuota);
$currency = OCA\Files_Accounting\Storage_Lib::getBillingCurrency();
$tmpl->assign('currency', $currency);
$gifts = OCA\Files_Accounting\Storage_Lib::getGiftInfos();
$tmpl->assign('gifts', $gifts);
if(\OCP\App::isEnabled('files_sharding')){
	$sites = \OCA\FilesSharding\Lib::dbGetSitesList();
	$tmpl->assign('sites', $sites);
}
return $tmpl->fetchPage();
