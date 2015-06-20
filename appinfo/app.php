<?php
OCP\App::checkAppEnabled('files_accounting');
OCP\App::registerAdmin('files_accounting', 'settings');
OCP\App::registerPersonal('files_accounting', 'personalsettings');
OCP\Backgroundjob::registerJob('\OCA\Files_Accounting\Stats');
