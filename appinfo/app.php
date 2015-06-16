<?php
OCP\App::checkAppEnabled('files_accounting');
<<<<<<< HEAD
OCP\App::registerAdmin('files_accounting', 'settings');
OCP\Backgroundjob::registerJob('\OCA\Files_Accounting\Stats');

=======
//OCP\App::registerPersonal('files_accounting', 'personalsettings');
OCP\Backgroundjob::registerJob('\OCA\Files_Accounting\Stats');
>>>>>>> 6dc0038988d3f84334fa5ba04473892bb8eb2d2c
