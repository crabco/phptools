<?php
error_reporting(0);

define('ObjectListName', "object_list");

function run(){
    $Run        = ['status'=>false,'error'=>'接口尚未支持'];
    $Act        = strtolower($_GET['act']);
    
    /**
     * 获取项目列表信息
     */
    if( $Act=='show_object' ){
        $Run        = ['status'=>true,'error'=>'','object'=>get_object()];
    }
    
    /**
     * 保存或者创建项目
     */
    if( $Act=='save_object' ){
        $PostData       = $_POST['data'];
        $ObjectID       = ( !empty($ObjectID) )? $PostData['object_id'] : NewRandId();
        
        if( !ExistsRandId($ObjectID) ){
            return ['status'=>false,'error'=>'项目序号非法'];
        }
        
        if( empty($PostData['object_name']) ){
            return ['status'=>false,'error'=>'项目名称不能为空'];
        }
        
        $ObjectInfo     = [
            'object_name'  => $PostData['object_name'],
            'object_exp'   => $PostData['object_exp'],
            'object_host'  => $PostData['object_host'],
            'object_header' => [],
        ];
        
        if( !empty($PostData['object_header']) ){
            foreach($PostData['object_header'] as $vs){
                if( empty($vs['name'])||!ExistsKeyName($vs['name']) )continue;
                $ObjectInfo["object_header"][$vs['name']]   = $vs['value'];
            }
        }
        
        if( !set_object($ObjectID, $ObjectInfo) ){
            $Run        = ['status'=>false,'error'=>'存储失败'];
        }else{
            $Run        = ['status'=>true,'error'=>'',"object_id"=>$ObjectID];
        }
    }
    
    
    
    
    /**
     * 获取接口列表
     * 
     */
    if( $Act=="show_api" ){
        $ObjectID       = ( !empty($_GET['oid']) )? $_GET['oid'] : "";
        if( empty($ObjectID) || !ExistsRandId($ObjectID) ){
            return ['status'=>false,'error'=>'项目非法'];;
        }
        return ['status'=>true,'error'=>'','data'=>get_json($ObjectID)];
    }
    
    
    
    /**
     * 创建或者修改接口
     */
    if( $Act=='save_api' ){
        $PostData       = $_POST['data'];
        $ObjectID       = $PostData['object_id'];
        $ObjectList     = get_object();
        $ApiID          = ( !empty($PostData['api_id']) )? $PostData['api_id'] : NewRandId();
        $ApiInfo        = [
            'object_id'     => $PostData['object_id'],
            'api_id'        => $ApiID,
            'api_name'      => $PostData['api_name'],
            'api_url'       => $PostData['api_url'],
            'api_type'      => strtolower($PostData['api_type']),
            'api_exp'       => $PostData['api_exp'],
            'api_header'    => [],
            'api_request'   => [],
            'api_response'  => [],
        ];
        
        if( empty($PostData['api_name']) ){
            return ['status'=>false,'error'=>'接口名称不能为空'];
        }
        
        if( !empty($PostData['api_header']) ){
            foreach($PostData['api_header'] as $vs){
                if( empty($vs['name']) || !ExistsKeyName($vs['name']) ){continue;}
                $ApiInfo["api_header"][]   = ['name'=>$vs['name'],'value'=>$vs['value'],'exp'=>$vs['exp'],'empty'=>$vs['empty']];
            }
        }
        if( !empty($PostData['api_request']) ){
            foreach($PostData['api_request'] as $vs){
                if( empty($vs['name']) || !ExistsKeyName($vs['name']) ){continue;}
                $vs['empty'] = ( $vs['empty']!='true'||$vs['empty']!=true )? true : false;
                $ApiInfo["api_request"][]   = ['name'=>$vs['name'],'value'=>$vs['value'],'exp'=>$vs['exp'],'empty'=>$vs['empty']];
            }
        }
        if( !empty($PostData['api_response']) ){
            foreach($PostData['api_response'] as $vs){
                if( empty($vs['name'])||!ExistsKeyName($vs['name']) ){continue;}
                $vs['empty'] = ( $vs['empty']!='true'||$vs['empty']!=true )? true : false;
                $ApiInfo["api_response"][]   = ['name'=>$vs['name'],'value'=>$vs['value'],'exp'=>$vs['exp'],'empty'=>$vs['empty']];
            }
        }
        
        if( empty($ObjectID) || !ExistsRandId($ObjectID) || !isset($ObjectList[$ObjectID]) ){
            return ['status'=>false,'error'=>'项目非法或者不存在'];;
        }
        
        $ObjectApiDom           = get_json($ObjectID);
        $ObjectApiDom[$ApiID]   = $ApiInfo;
        
        
        if( !set_json($ObjectID, $ObjectApiDom) ){
            $Run['status']          = false;
            $Run['error']           = '存储失败';
        }else{
            $Run['status']          = true;
            $Run['error']           = '';
            $Run['ApiInfo']         = $ApiInfo;
        }
    }
    
    
    /**
     * 删除接口
     */
    if( $Act=="del_api" ){
        $Run['status']  = true;
    }
    
    return $Run;
}






//读取项目列表
function get_object(){
    static $ObjectList;
    if( empty($ObjectList) ) $ObjectList   = get_json(ObjectListName);
    return $ObjectList;
}
//写入项目列表
function set_object($ObjectId,$ObjectInfo=null,$Delete=false){
    $ObjectList = get_object();
    
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
    return preg_match('/^[0-9]{1,1}[a-z0-9]{19,19}$/i', $Id);
}
function ExistsKeyName($Name){
    return preg_match('/^[a-z0-9_-]{1,50}$/i', $Name);
}

/**
 * 输出
 */
echo json_encode(run(),JSON_UNESCAPED_UNICODE);
