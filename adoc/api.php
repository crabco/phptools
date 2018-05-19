<?php
//配置登录密码,如果为空则不需要登录
define('ToolsLoginPass', '123456');



if( !empty($_GET['debug']) ){
    error_reporting(0);
}

define('ObjectListName', "object_list");

function run(){
    $Run        = ['status'=>false,'error'=>'接口尚未支持'];
    $Act        = strtolower($_GET['act']);
    
    /**
     * 登录
     */
    if( $Act=='login' ){
        $PassWord   = $_GET['pass'];
        if( !ExistsPass($PassWord) ){
            return ['status'=>false,'error'=>'密码不正确'];
        }else{
            return ['status'=>true,'error'=>'','token'=>$PassWord];
        }
    }
    
    /**
     * 判断是否登录
     */
    $Token      = ( empty($_COOKIE['token']) )? "" : $_COOKIE['token'];
    if( !ExistsToken($Token) ){
        return ['status'=>false,'error'=>'尚未登录'];
    }
    
    if( $Act=='login_exists' ){
        return ['status'=>true,'error'=>''];
    }
    
    
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
        if( !empty($PostData['object_request']) ){
            foreach($PostData['object_request'] as $vs){
                if( empty($vs['name'])||!ExistsKeyName($vs['name']) )continue;
                $ObjectInfo["object_request"][$vs['name']]   = $vs['value'];
            }
        }
        if( !empty($PostData['object_response']) ){
            foreach($PostData['object_response'] as $vs){
                if( empty($vs['name'])||!ExistsKeyName($vs['name']) )continue;
                $ObjectInfo["object_response"][$vs['name']]   = $vs['value'];
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
        
        //开始备份
        copy(ObjectListName.".json",ObjectListName.date("-YmdHis").".json");
        copy($ObjectID.".json",$ObjectID.date("-YmdHis").".json");
        
        $ObjectAll                 = get_object();
        $ObjectInfo                = $ObjectAll[$ObjectID];
        $ApiAll                    = get_json($ObjectID);
        $ApiAll                    = ApiRowToJson($ApiAll);
        
        
        if( $_GET['type']=="word" ){
            
            $Word                   = "";
            $Word                  .= "<div style='text-align:center;font-family: 微软雅黑;'>{$ObjectInfo['object_name']}</div>";
            $Word                  .= "<div style='text-align:center;margin-bottom:10em;'>".date("Y年m月d日")."版本</div>";
            $Word                  .= "<div style='text-indent:;'>{$ObjectInfo["object_exp"]}</div>";
            $Word                  .= "<h1 style='text-indent:;'>1、 预定义数据</h1>";
            $Word                  .= "<h2 style='text-indent:;'>1.1、接口地址：</h2>";
            $Word                  .= "<div style='text-indent: 3em;'>{$ObjectInfo["object_host"]}</div>";
            
            if( !empty($ObjectInfo['object_header']) ){
                $Word              .= "<h2 style='text-indent:;'>1.2、预定义header：</h2>";
                $Word              .= "<div style='margin-left: 3em;background-color:#ececec;padding: 5px;border-radius: 5px;'>".json_encode($ObjectInfo['object_header'],JSON_UNESCAPED_UNICODE)."</div>";
            }
            
            if( !empty($ObjectInfo['object_request']) ){
                $Word              .= "<h2 style='text-indent:;'>1.3、预定义Request：</h2>";
                $Word              .= "<div style='margin-left: 3em;background-color:#ececec;padding: 5px;border-radius: 5px;'>".json_encode($ObjectInfo['object_request'],JSON_UNESCAPED_UNICODE)."</div>";
            }
            
            
            if( !empty($ObjectInfo['object_response']) ){
                $Word              .= "<h2 style='text-indent:;'>1.4、预定义Response：</h2>";
                $Word              .= "<div style='margin-left: 3em;background-color:#ececec;padding: 5px;border-radius: 5px;'>".json_encode($ObjectInfo['object_response'],JSON_UNESCAPED_UNICODE)."</div>";
            }
            
            if( !empty($ApiAll) ){
                $Word              .= "<h1 style='text-indent:;'>2、接口定义</h1>";
                $i                  = 1;
                foreach($ApiAll as $vs=>$rs){
                    $Word          .= "<h2 style='text-indent:;'>2.{$i}、{$rs["api_name"]}</h2>";
                    $apiurl         = ( preg_match('/^http\:\/\//i', $rs['api_url']) )? $rs['api_url'] : $ObjectInfo['object_host'].$rs['api_url'];
                    $Word          .= "<div style='margin-left: 3em;'>{$rs['api_exp']}</div>";
                    $Word          .= "<div style='margin-left: 3em;background-color:#ececec;padding: 5px;border-radius: 5px;margin-top:10px;'>接口地址: {$apiurl}</div>";
                    $Word          .= "<div style='margin-left: 3em;margin-top:10px;'>请求方式: <b>".strtoupper($rs['api_type'])."</b></div>";
                    
                    if( !empty($rs['api_header']) ){
                        $Word              .= "<h3 style='text-indent:;margin-left: 3em'>2.{$i}.1 header：</h3>";
                        $Word              .= "<div style='font-size:12px;margin-left: 4em;background-color:#ececec;padding: 5px;border-radius: 5px;'>".print_r($rs['api_header'],true)."</div>";
                    }
                    
                    if( !empty($rs['api_request']) ){
                        $Word              .= "<h3 style='text-indent:;margin-left: 3em'>2.{$i}.2 Request：</h3>";
                        $Word              .= "<div style='font-size:12px;margin-left: 4em;background-color:#ececec;padding: 5px;border-radius: 5px;'><pre>".print_r($rs['api_request'],true)."</pre></div>";
                    }

                    if( !empty($rs['api_response']) ){
                        $Word              .= "<h3 style='text-indent:;margin-left: 3em'>2.{$i}.3 可能返回的值模拟：</h3>";
                        $Word              .= "<div style='font-size:12px;margin-left: 4em;background-color:#ececec;padding: 5px;border-radius: 5px;'><pre>".print_r($rs['api_response_json'],true)."</pre></div>";
                    }

                    $i++;
                }
            }
            
            $Word                   = iconv("utf-8", "gb2312", $Word);
            
            $FileName               = $ObjectInfo['object_name']."-".date("Y年m月d日")."版本";
            header('Content-Type:application/msword');
            header('Content-Disposition: attachment; filename="'.$FileName.'.doc"');
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office"
                    xmlns:w="urn:schemas-microsoft-com:office:word"
                    xmlns="http://www.w3.org/TR/REC-html40">
                    <style>h1{font-size:18px;}h2{font-size:16px}h3{font-size:14px}div{font-size:12px}</style>
                    <body style="font-size:12px;padding:2em;">';
            echo $Word;
            exit;
        }
        
        
        
        //导出为网页
        $HTML                      = file_get_contents("index.html");
        
        $SelfURL                   = "http://".$_SERVER['HTTP_HOST']."/".$_SERVER['REQUEST_URI'];
        $SelfURL                   = preg_replace('/api.php[^\r]+/i','index.html',$SelfURL);
        
        $FileName                  = $ObjectInfo['object_name']."-".date("Y年m月d日")."版本";
        $HTML                      = preg_replace('/LocalObject[ ]+\=[ ]+null;/i', "LocalObject = " . json_encode($Object), $HTML);
        $HTML                      = preg_replace('/LocalApiRow[ ]+\=[ ]+null;/i', "LocalApiRow = " . json_encode($ApiAll), $HTML);
        $HTML                      = preg_replace('/\<title\>[^<]+/i', "<title>{$FileName}", $HTML);
        $HTML                      = str_replace('https://github.com/crabco/phptools',$SelfURL,$HTML);
        
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

function ExistsPass( &$Pass ){
    if( empty(ToolsLoginPass) ){
        return true;
    }else{
        if( sha1(ToolsLoginPass)!=sha1($Pass) ){
            return false;
        }else{
            $Sale   = rand(1000,9999);
            $Pass   = md5($Sale.ToolsLoginPass.$Sale).$Sale;
            return true;
        }
    }
}

function ExistsToken($Token){
    
    if( defined(ToolsLoginPass)||empty(ToolsLoginPass) ){
        return true;
    }
    
    if( !preg_match('/^[a-z0-9]{20,60}$/i', $Token) ){
        return false;
    }
    
    $Sale       = substr($Token,-4);
    $Pass       = md5($Sale.ToolsLoginPass.$Sale).$Sale;
    
    if( $Pass!=$Token ){
        return false;
    }else{
        return true;
    }
}


/**
 * 输出
 */
echo json_encode(run(),JSON_UNESCAPED_UNICODE);
