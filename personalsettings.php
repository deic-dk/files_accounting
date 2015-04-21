<?php
OCP\JSON::checkAppEnabled('files_accounting');
OCP\User::checkLoggedIn();


OCP\Util::addScript('files_accounting', 'personalsettings');
$tmpl = new OCP\Template( 'files_accounting', 'personalsettings.tpl');
return $tmpl->fetchPage();
