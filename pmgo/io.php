<?php
require_once 'inc/auto.inc.php';



/**
 * 登录方法
 */
function RunLogin(){
    $User   = FunLoginFind();
    
    
    
    
}

//创建管理用户
function RunLoginAdd(){
    $User   = FunLoginFind();
    
    if( count($User)>10 ){
        return Response::error(400,'管理帐号超过允许上限')->json();
    }
    
    
}

//修改管理用户信息
function RunLoginEdit(){
    
}

//移除管理用户
function RunLoginRemove(){
    
}



/**
 * 获取已经配置的服务器列表
 * @return type
 */
function RunServer(){
    
}
//新增服务器列表
function RunServerAdd(){
    
}
//修改服务器列表
function RunServerEdit(){
    
}
//移除服务器列表
function RunServerRemove(){
    
}



/**
 * 获取服务器的库列表
 * @return type
 */
function RunBase(){
    
}
//新增库
function RunBaseAdd(){
    
}
//修改库
function RunBaseEdit(){
    
}
//移除库
function RunBaseRemove(){
    
}


/**
 * 获取服务器的管理帐号
 */
function RunBaseUser(){
    
}
//新增服务器管理帐号
function RunBaseUserAdd(){
    
}
//修改服务器管理帐号
function RunBaseUserEdit(){
    
}
//移除服务器管理帐号
function RunBaseUserRemove(){
    
}

/**
 * 获取集合列表
 * @return type
 */
function RunCollection(){
    
}
//新增集合
function RunCollectionAdd(){
    
}
//修改集合
function RunCollectionEdit(){
    
}
//删除集合
function RunCollectionRemove(){
    
}

/**
 * 获取数据列表
 * @return type
 */
function RunItem(){
    
}
//新增数据
function RunItemAdd(){
    
}
//修改数据
function RunItemEdit(){
    
}
//移除数据
function RunItemRemove(){
    
}
//查询数据
function RunItemFind(){
    
}


//=======================================执行方法========================
function run(){
    $Act    = Request::get('act');
    $Act    = ( empty($Act) )? Request::post('act') : $Act;
    
    //如果接口方法错误
    if( empty($Act)||!preg_match('/^[a-z0-9_]+$/i', $Act) ){
        return Response::error(401,'非法操作')->json();
    }
    if( !function_exists("Run{$Act}") ){
        return Response::error(401,'不存在的接口')->json();
    }
    
    //登录检测,某些接口无需检测则排除
    $LoginToken         = Request::cookie("logintoken");
    if( preg_match('/^[a-z0-9_]+$/i', $LoginToken) || (false === ExistsLogin( $LoginToken ) && !in_array($Act, array('login'))) ){
        return Response::error(401,'尚未登录')->json();
    }
    
    $Run    = "Run{$Act}";
    $Run();
}

run();