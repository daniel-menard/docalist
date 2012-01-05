<?php
/*
$start=microtime(true);
//define('SHORTINIT',true);
require_once('wordpress/wp-load.php');
$end = microtime(true);
echo "Chargement wordpress : ", ($end-$start);
// echo 'user : ', var_export(get_current_user());
// var_export(current_user_can('manage_options'));
// var_export(get_categories());

 */
require_once('../vendor/Fab2/Runtime.php');
Runtime::setup('debug');
