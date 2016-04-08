<?php
OC_Util::checkAdminUser();
OCP\Util::addScript('files_accounting', 'settings');
$tmpl = new OCP\Template( 'files_accounting', 'settings.tpl');
$defaultFreeQuota = OCP\Config::getAppValue('files_accounting', 'default_freequota');
$tmpl->assign('default_freequota', $defaultFreeQuota);
return $tmpl->fetchPage();
