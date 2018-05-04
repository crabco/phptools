<?php
define('ObjectListName', "object_list");

function run(){
    $Run        = ['status'=>false,'error'=>'接口尚未支持'];
    $Act        = strtolower($_GET['act']);
    
    /**
     * 获取项目列表信息
     */
    if( $Act=='show_object' ){
        $Run        = ['status'=>true,'error'=>'','object'=>get_list()];
    }
    
    /**
     * 保存或者创建项目
     */
    if( $Act=='save_object' ){
        $PostData       = $_POST['data'];
        $ObjectID       = ( !empty($ObjectID) )? $PostData['object_id'] : NewRandId();
        
        if( empty($PostData['object_name']) ){
            $Run        = ['status'=>false,'error'=>'项目名称不能为空'];
            return $Run;
        }
        
        $ObjectInfo     = [
            'object_name'  => $PostData['object_name'],
            'object_exp'   => $PostData['object_exp'],
            'object_host'  => $PostData['object_host'],
            'object_header' => [],
        ];
        
        if( !empty($PostData['object_header']) ){
            foreach($PostData['object_header'] as $vs){
                $ObjectInfo["object_header"][$vs['name']]   = $vs['value'];
            }
        }
        
        if( !set_list($ObjectID, $ObjectInfo) ){
            $Run        = ['status'=>false,'error'=>'存储失败'];
        }else{
            $Run        = ['status'=>true,'error'=>'',"object_id"=>$ObjectID];
        }
    }
    
    
    
    /**
     * 创建或者修改接口
     */
    if( $Act=='save_api' ){
        $PostData       = $_POST['data'];
        $ObjectID       = $PostData['object_id'];
        
        if( empty($PostData['object_id']) ){
            $ObjectID   = NewRandId();
        }
        
        if( !set_json($ObjectID, $PostData) ){
            $Run        = ['status'=>false,'error'=>'存储失败'];
        }else{
            $Run        = ['status'=>true,'error'=>'','date'=>get_json($ObjectID)];
        }
    }
    
    
    
    return $Run;
}


//读取项目列表
function get_list(){
    static $ObjectList;
    if( empty($ObjectList) ) $ObjectList   = get_json(ObjectListName);
    return $ObjectList;
}
//写入项目列表
function set_list($ObjectId,$ObjectInfo=null,$Delete=false){
    $ObjectList = get_list();
    
    if( $Delete!=true ){
        $ObjectList[$ObjectId]  = $ObjectInfo;
    }else{
        unset($ObjectList[$ObjectId]);
    }
    
    set_json(ObjectListName, $ObjectList);
    return true;
}

//读取数据
function get_json($Name){
    $Name   .= ".json";
    $Text    = ( is_file($Name) )? file_get_contents($Name) : '';
    if( empty($Text) ){$Text   = '[]';}
    return json_decode($Text,true);
}
//写入数据
function set_json($Name,$Data){
    $Name   .= ".json";
    $Text   = json_encode($Data,JSON_UNESCAPED_UNICODE);
    $Save   = file_put_contents($Name, $Text);
    return $Save;
}
//移除指定数据
function rm_json(){
    $Name   .= ".json";
    $Rm     = ( is_file($Name) )? unlink($Name) : true;
    return $Rm;
}
function NewRandId(){
    $Time   = date("YmdHis");
    $char   = '0123456789abcdefghjkmnpqrstuvwxy';
    while(strlen($Time)<20){
        $Time   .= $char[ mt_rand(0, strlen($char)-1) ];
    }
    if( !empty($param) ){
        $Time   = $param . $Time;
    }
    return $Time;
}
function ExistsRandId($Id){
    return preg_match('/^[0-9]{1,1}[a-z0-9]{19,19}/i', $Id);
}

/**
 * 输出
 */
echo json_encode(run(),JSON_UNESCAPED_UNICODE);
