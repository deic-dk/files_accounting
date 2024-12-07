<?php


OCP\App::checkAppEnabled('files_accounting');

echo \OCP\Config::getSystemValue('billingdayofmonth', 1);