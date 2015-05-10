<?php
//error_reporting(E_ALL);
//Define directory separator
defined('DS')? null : define('DS', DIRECTORY_SEPARATOR);
require_once __DIR__.DS."autoload.php";

$ctrl = isset($_GET['ctrl'])? $_GET['ctrl'] : 'News';
$act = isset($_GET['act'])? $_GET['act'] : 'All';

$controllerClassName = $ctrl."Controller";

try {
    $controller = new $controllerClassName;
    $method = "action" . $act;
    $controller->{$method}();
}catch (Exception $e){
    $view = new View();
    $view->error = $e->getMessage();
    $view->display('error.php');

}

