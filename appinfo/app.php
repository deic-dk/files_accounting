<?php

OCP\App::checkAppEnabled('files_accounting');
OCP\App::registerAdmin('files_accounting', 'settings');
OCP\Backgroundjob::registerJob('\OCA\Files_Accounting\Stats');
