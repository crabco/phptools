<?php
/**
 * 检查token是否合法,如果合法,则返回用户的登录帐号
 * @param type $Token
 * @return boolean
 */
function FunExistsLogin($Token){
    return false;
}

/**
 * 返回软件管理用户的帐号密码列表
 */
function FunLoginFind($UserName=null){
    
    $User   = [];       // ['user_id'=>'','user_name'=>'','user_pass'=>'']
    
    if(is_null($UserName) )return $User;
    
    
}


function FunLoginModify($UserName,$UserPass){
    
}

