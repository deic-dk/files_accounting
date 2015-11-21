<?php
OCP\JSON::checkAppEnabled('files_accounting');
OCP\User::checkLoggedIn();
OCP\Util::addStyle('files_accounting', 'style');
OCP\Util::addScript('files_accounting', 'personalsettings');

$tmpl = new OCP\Template( 'files_accounting', 'personalsettings.tpl');
return $tmpl->fetchPage();
