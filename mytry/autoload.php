<?php

function __autoload($class){

    $controller = __DIR__.DS.'controllers'.DS.$class.'.php';
    $model = __DIR__.DS."models".DS.$class.'.php';
    $classes = __DIR__.DS."classes".DS.$class.'.php';

    if(file_exists($controller)){
        require_once $controller;

    }elseif(file_exists($model)){
        require_once $model;
    }elseif(file_exists($classes)){
        require_once $classes;
    }
}

?>