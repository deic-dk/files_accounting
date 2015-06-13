<?php
OCP\App::checkAppEnabled('files_accounting');
//OCP\App::registerPersonal('files_accounting', 'personalsettings');
OC::$CLASSPATH['Stats'] ='apps/files_accounting/lib/stats.php';
OCP\Backgroundjob::registerJob('Stats');
