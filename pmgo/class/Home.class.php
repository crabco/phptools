<?php
class Home{
    
    protected $Err;
    protected static $Error;
    
    
    public function GetErr(){
        return $this->Err;
    }
    
    public static function GetError(){
        return static::$Error;
    }
}

