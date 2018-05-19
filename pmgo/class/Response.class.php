<?php
class Response extends Home{
    private static $me;
    private static $debug       = [];
    private static $data        = [];
    private static $status      = false;
    private static $error       = 0;
    
    public static function debug( $debug ){
        if(is_null(static::$me)){static::$me = new static();}
        static::$debug[]  = $debug;
        return static::$me;
    }
    
    
    public static function success( $data ){
        if(is_null(static::$me)){static::$me = new static();}
        if( is_string($data) ){
            static::$data = $data;
        }else{
            static::$data = array_merge(static::$data,$data);
        }
        return static::$me;
    }
    
    
    public static function error( $code=null,$Explain=null ){
        if(is_null(static::$me)){static::$me = new static();}
        if( !empty($code) ){
            static::$status       = false;
            static::$error        = $Explain;
        }
        return static::$me;
    }
    
    public function json(){
        $json               = [];
        if( !empty(static::$debug) ){
            $json['debug']  = static::$debug;
        }
        if( is_array(static::$data) ){
            $json           = array_merge($json,  static::$data);
        }else{
            $json['result'] = static::$data;
        }
        $json['status']     = static::$status;
        $json['error']      = static::$error;
        
        static::$data       = [];
        static::$error      = 0;
        static::$debug      = [];
        
        echo json_encode($json,JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    public function html(){
        
        $json       = [];
        if( !empty(static::$debug) ){
            $json['debug']  = static::$debug;
        }
        if( is_array(static::$data) ){
            $json           = array_merge($json,  static::$data);
        }else{
            $json['result'] = static::$data;
        }
        $json['status']     = static::$status;
        $json['error']      = static::$error;
        
        static::$data       = [];
        static::$error      = 0;
        static::$debug      = [];
        
        echo "<pre>";
        print_r($json);
        exit;
    }
    
    public function __destruct() {
        if( !empty(static::$me) && (!empty(static::$data)||!empty(static::$error)||!empty(static::$debug)) ){
            static::$me->html();
        }
    }
}