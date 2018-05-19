<?php
//app根目录
define('AppPathRoot',   substr(dirname(__FILE__),0,-3) );
//app配置目录（本目录需要写权限）
define('AppPathInc',    AppPathRoot."/inc/");
//app下载目录(本目录需要写权限)
define('AppPathDown',   AppPathRoot."/upload/");
//app的类文件目录
define('AppPathClass',  AppPathRoot."/class/");

require_once (AppPathInc."config.php");
require_once (AppPathInc."function.php");
require_once (AppPathClass."Home.class.php");
require_once (AppPathClass."Request.class.php");
require_once (AppPathClass."Response.class.php");


//自动加载部分
spl_autoload_register(function($className){
    if( is_file(AppPathClass."{$className}.class.php") ){
        include_once AppPathClass."{$className}.class.php";
    }else{
        die(json_encode(['status'=>false,'error'=>"autoload class {$className} error!"]));
    }
});