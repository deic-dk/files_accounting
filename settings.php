<?php
OC_Util::checkAdminUser();
OCP\Util::addScript('files_accounting', 'settings');
$tmpl = new OCP\Template( 'files_accounting', 'settings.tpl');
return $tmpl->fetchPage();
