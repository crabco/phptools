<?php
class Request extends Home{
    
    public static function get($Name=null){
        if(is_null($Name) ){
            return $_GET;
        }
        
        return ( isset($_GET[$Name]) )? $_GET[$Name] : null;
    }
    
    
    public static function post($Name=null){
        if(is_null($Name) ){
            return $_POST;
        }
        
        return ( isset($_POST[$Name]) )? $_POST[$Name] : null;
    }
    
    public static function cookie($Name=null){
        if(is_null($Name) ){
            return $_COOKIE;
        }
        
        return ( isset($_COOKIE[$Name]) )? $_COOKIE[$Name] : null;
    }
    
}
