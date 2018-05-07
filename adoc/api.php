<?php
if( !empty($_GET['debug']) ){
    error_reporting(0);
}

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
        $ObjectID       = ( !empty($PostData['object_id']) )? $PostData['object_id'] : NewRandId();
        
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
     * 移除项目
     */
    if( $Act=='del_object' ){
        $ObjectID       = ( !empty($_GET['oid']) )? $_GET['oid'] : "";
        if( empty($ObjectID) || !ExistsRandId($ObjectID) ){
            return ['status'=>false,'error'=>'项目非法'];
        }
        $ObjectList     = get_object();
        if( !isset($ObjectList[$ObjectID]) ){
            return ['status'=>false,'error'=>'项目非法'];
        }
        
        set_object($ObjectID,null,true);
        rm_json($ObjectID);
        
        $Run['status']  = true;
    }
    
    
    
    /**
     * 获取接口列表
     * 
     */
    if( $Act=="show_api" ){
        $ObjectID       = ( !empty($_GET['oid']) )? $_GET['oid'] : "";
        if( empty($ObjectID) || !ExistsRandId($ObjectID) ){
            return ['status'=>false,'error'=>'项目非法'];
        }
        $ApiID          = ( !empty($_GET['api_id']) )? trim($_GET['api_id']) : "";
        if( !empty($ApiID)&&!ExistsRandId($ApiID) ){
            return ['status'=>false,'error'=>'接口序号非法'];
        }
        
        $ApiAll         = get_json($ObjectID);
        
        
        //根据接口名称排序
        usort($ApiAll, "ApiDomSort");
        $ApiDom         = [];
        foreach($ApiAll as $Rs){
            $ApiDom[ $Rs["api_id"] ] = $Rs;
        }
        unset($ApiAll);
        
        //将列对象转换为JSON对象
        $ApiDom    = ApiRowToJson($ApiDom);
        
        if( empty($ApiID) ){
            $Run['status']  = true;
            $Run['error']   = "";
            $Run['data']    = $ApiDom;
        }else{
            if( !isset($ApiDom[$ApiID]) ){
                return ['status'=>false,'error'=>'不存在的接口'];
            }
            $Run['status']  = true;
            $Run['error']   = '';
            $Run['ApiInfo'] = $ApiDom[$ApiID];
        }
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
                if( trim($vs['name'])==""||!ExistsKeyName($vs['name']) ){continue;}
//                $vs['empty']    = ( $vs['empty']!='true'||$vs['empty']!=true )? true : false;
                $vs['left']     = ( empty($vs['left'])||trim($vs['left'])=='undefined' )? "" : $vs['left'];
                $ApiInfo["api_response"][]   = ['left'=>$vs['left'],'name'=>$vs['name'],'value'=>$vs['value'],'exp'=>$vs['exp']];
            }
        }
        
        if( empty($ObjectID) || !ExistsRandId($ObjectID) || !isset($ObjectList[$ObjectID]) ){
            return ['status'=>false,'error'=>'项目非法或者不存在'];;
        }
        
        usort($ApiInfo['api_header'],"ApiInfoSort");
        usort($ApiInfo['api_request'],"ApiInfoSort");
//        usort($ApiInfo['api_response'],"ApiInfoSort");
        
        $ObjectApiDom           = get_json($ObjectID);
        $ObjectApiDom[$ApiID]   = $ApiInfo;
        
        $ApiInfos               = ApiRowToJson([0=>$ApiInfo]);
        $ApiInfo                = $ApiInfos[0];
        
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
        $ObjectID       = ( !empty($_GET['oid']) )? $_GET['oid'] : "";
        if( empty($ObjectID) || !ExistsRandId($ObjectID) ){
            return ['status'=>false,'error'=>'项目非法'];
        }
        $ApiID          = trim($_GET['api_id']);
        if( !empty($ApiID)&&!ExistsRandId($ApiID) ){
            return ['status'=>false,'error'=>'接口序号非法'];
        }
        
        $ApiAll         = get_json($ObjectID);
        
        if( empty($ApiAll) ){
            return ['status'=>false,'error'=>'项目不存在'];
        }
        
        if( !isset($ApiAll[$ApiID]) ){
            return ['status'=>false,'error'=>'接口不存在'];
        }
        
        unset($ApiAll[$ApiID]);
        set_json($ObjectID, $ApiAll);
        
        $Run['status']  = true;
        
    }
    
    //导出文件
    if( $Act=="export" ){
        
        $ObjectID       = ( !empty($_GET['oid']) )? $_GET['oid'] : "";
        if( empty($ObjectID) || !ExistsRandId($ObjectID) ){
            return ['status'=>false,'error'=>'项目非法'];
        }
        
        //导出为网页
        $HTML                      = file_get_contents("index.html");
        
        $ObjectAll                 = get_object();
        $Object[$ObjectID]         = $ObjectAll[$ObjectID];
        $ApiAll                    = get_json($ObjectID);
        
        $FileName                  = $Object[$ObjectID]['object_name']."-".date("Y年m月d日")."版本";
        $HTML                      = preg_replace('/LocalObject[ ]+\=[ ]+null;/i', "LocalObject = " . json_encode($Object), $HTML);
        $HTML                      = preg_replace('/LocalApiRow[ ]+\=[ ]+null;/i', "LocalApiRow = " . json_encode($ApiAll), $HTML);
        $HTML                      = preg_replace('/\<title\>[^<]+/i', "<title>{$FileName}", $HTML);
        
        header('Content-Type:text/html');
        header('Content-Disposition: attachment; filename="'.$FileName.'.html"');
        echo $HTML;
        exit;
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
function rm_json($Name){
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

function ApiInfoSort($A,$B){
    if( $A['name']=='status'||$A['name']=='error'||$A['name']=='results' ){
        return -1;
    }
    if( $B['name']=='status'||$B['name']=='error'||$B['name']=='results' ){
        return 1;
    }
    
    if( $A['name']>$B['name'] ){
        return -1;
    }elseif( $A['name']==$B['name'] ){
        return 0;
    }else{
        return 1;
    }
}

function ApiDomSort($A,$B){
    if( $A['api_name'] > $B['api_name'] ){
        return -1;
    }elseif( $A['api_name']==$B['api_name'] ){
        return 0;
    }else{
        return 1;
    }
}

/**
 * 行数据转JSON
 * @param type $Response
 * @return boolean
 */
function ApiRowToJson($Response){
    
    if( !empty($Response) ){
        foreach($Response as $Vs=>$Rs){
            $Dom    = $Rs['api_response'];
            
//            //临时处理,将原有数据非对象载入对象
//            if( count($Dom)>3 && !isset($Dom['results']['left']) ){
//                usort($Dom,"ApiInfoSort");
//                foreach($Dom as $Vss=>$Rss){
//                    if( $Rss['name']!='status' && $Rss['name']!='error' && $Rss['name']!='results' ){
//                        $Dom[$Vss]['left']    = "-";
//                    }
//                }
//                $Response[$Vs]['api_response'] = $Dom;
//            }
            
            
            $Json   = [];
            $Left   = [];
            if( count($Dom)>0 ){
                foreach($Dom as $Vss=>$Rss){
                    
                    $Eval   = '';
                    
                    if( empty($Rss['left'])||trim($Rss['left'])=="undefined" ){
                        $Rss['left']    = "";
                    }
                    $Li                 = strlen($Rss['left']);
                    
                    $Left[ $Li ]        = "[\"{$Rss['name']}\"]";
                    
                    for($i=$Li-1;$i>=0;$i--){
                        $Eval = $Left[$i].$Eval;
                    }
                    
                    $Val    = ( $Rss['value']=='""' )? "" : $Rss['value'];
                    $Val    = ( $Val=="''" )? "" : $Val;
                    
                    if( gettype($Val)=="string" ){
                        $Val    = addcslashes($Val,"\"");
                        $Val    = "\"{$Val}\"";
                    }
                    
                    $Eval   = 'if( !is_array($Json'. $Eval . ') ){ $Json'. $Eval . '=[]; }'
                            . 'if( !isset($Json'. $Eval . ') ){ $Json'. $Eval . '=[]; }'
                            . ' $Json'. $Eval . '["'.$Rss['name'].'"] = '. $Val .';';
                    try{
                        eval($Eval);
                    }catch (Exception $e){
                        $Json   = ['status'=>false,'error'=>'数组格式错误'];
                    }
                }
                $Response[$Vs]['api_response_json']  = $Json;
            }
        }
    }
    return $Response;
}

/**
 * 输出
 */
echo json_encode(run(),JSON_UNESCAPED_UNICODE);
