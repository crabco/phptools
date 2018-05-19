<?php

/**
 * Mongodb操作类,仅支持单例模式
 */
use MongoDB\Driver\Manager;
use MongoDB\Driver\Command;
use MongoDB\Driver\Query;
use MongoDB\Driver\Exception;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\WriteConcern;
use MongoDB\Driver\ReadPreference;

class Base{
    protected static $me;
    protected static $Error;
    protected static $Conn;
    protected static $DataBase;
    protected static $Collection;

    

    //检测是否安装了PHP的MONGODB插件
    public static function ExistsMongo(){
        return class_exists('Mongodb\Driver\Manager',false);
    }
    
    //判断数据库字段或者名称是否合法
    public static function ExistsFields($name){
        return preg_match('/^[a-z0-9-_]{3,100}$/i', $name);
    }
    
    //判断是否已经进行了数据库操作连接
    public static function ExistsConnection(){
        return !is_null(static::$Conn);
    }

    //连接数据库
    public static function Connection( $URL,$Option=array() ){
        if( !static::ExistsMongo() ){
            return false;
        }
        try{
            static::$Conn       = new Manager($URL,$Option);
        }catch(Exception $e) {
            static::$Conn       = null;
            static::$Error      = $e->getMessage();
            return false;
        }
        return true;
    }
    
    //显示所有库列表
    public static function baseList(){
        if( is_null(static::$Conn) ){
            static::$Error  = 'Not Open Mongodb!';
            return [];
        }
        if( !$Bson = static::command('admin', ['listDatabases'=>1] ) ){
            return [];
        }
        $Bson->setTypeMap( ['root' => 'array', 'document' => 'array','array'=>'array'] );
        
        return $Bson->toArray();
    }
    
    //删除一个库文件,返回仅表示执行状态，不表示删除状态，如果需要查看是否删除成功，需要再次对比库列表
    public static function baseDrop( $BaseName ){
        if( in_array($BaseName,['admin','local']) ){
            static::$Error  = 'Not allowed to be deleted.';
            return false;
        }
        if( !$Bson=static::command($BaseName, ['dropDatabase'=>1]) ){
            return false;
        }else{
            return true;
        }
    }
    
    //选择一个库
    public static function baseUse( $BaseName ){
        if( is_null(static::$me) ){static::$me = new static;}
        static::$DataBase   = $BaseName;
        return static::$me;
    }
    
    //显示当前库的用户
    public static function userList(){
        if( !$Bson=static::query( static::$DataBase.".system.users",[]) ){
            return [];
        }
        $Bson->setTypeMap( ['root' => 'array', 'document' => 'array','array'=>'array'] );
        return $Bson->toArray();
    }
    //添加/修改用户
    public static function userInsert(string $user,string $pass,array $roles){
        $User   = ['createUser'=>$user,'pwd'=>$pass,'roles'=>$roles];
        try{
            if( !$Bson=static::command(static::$DataBase, $User) ){
                return false;
            }
        }catch(RuntimeException $e){
            static::$Error  = $e->getMessage();
            return false;
        }
        return true;
    }
    //移除用户
    public static function userDrop(string $user ){
        $cmd    = ['dropUser'=>$user];
        try{
            if( !$Bson=static::command(static::$DataBase, $cmd) ){
                return false;
            }
        }catch(RuntimeException $e){
            static::$Error  = $e->getMessage();
            return false;
        }
        return true;
    }
    //显示Functions
    public static function funList(){
        if( !$Bson=static::query( static::$DataBase.".system.js",[]) ){
            return [];
        }
        $Bson->setTypeMap( ['root' => 'array', 'document' => 'array','array'=>'array'] );
        return $Bson->toArray();
    }
    
    //添加/修改Functions
    public static function funInsert(){
        return true;
    }
    
    //移除Functions
    public static function funDrop($funName){
        return true;
    }
    
    //显示集合列表
    public static function collList(){
        if( empty(static::$DataBase) ){
            static::$Error  = 'Please choose database';
            return false;
        }
        $cmd    = ['listCollections'=>1];
        try{
            $Bson   = static::command(static::$DataBase, $cmd);
        } catch (Exception $e) {
            static::$Error  = $e->getMessage();
            return false;
        }
        $Bson->setTypeMap( ['root' => 'array', 'document' => 'array','array'=>'array'] );
        return $Bson->toArray();
    }
    //选择一个集合
    public static function collUse( $CollName ){
        if( empty(static::$DataBase) ){
            static::$Error  = 'Please choose database';
            return false;
        }
        if( is_null(static::$me) ){static::$me = new static;}
        static::$Collection = $CollName;
        return static::$me;
    }
    /**
     * 创建一个集合
     * @param string $collName 集合或者视图的名称
     * @param bool $autoIndexId 指定false禁用该_id字段上的索引自动创建 。
     * @param bool $MaxLimit 是否限制集合的数据上限，如果限制，则系统会将超出部分写入记录尾部覆盖过期数据
     * @param int $MaxBeyt 限制表的字节大小限制，优先
     * @param int $MaxSize 限制表的记录总数限制，滞后
     */
    public static function collInsert($collName,$autoIndexId=true,$MaxLimit=false,$MaxBeyt=0,$MaxSize=0){
        if( empty(static::$DataBase) ){
            static::$Error  = 'Please choose database';
            return false;
        }
        $cmd    = ['create'=>$collName,'autoIndexId'=>$autoIndexId];
        if( $MaxLimit==true ){
            $cmd['capped']  = true;
            $cmd['size']    = $MaxBeyt;
            $cmd['max']     = $MaxSize;
        }
        try{
            $Bson=static::command(static::$DataBase, $cmd);
        } catch (RuntimeException $e){
            static::$Error  = $e->getMessage();
            return false;
        }
        return true;
    }
    //修改一个集合名称
    public static function collReName($collName,$collNameNew,$dropTarget=false){
        if( empty(static::$DataBase) ){
            static::$Error  = 'Please choose database';
            return false;
        }
        $cmd    = ['renameCollection'=>static::$DataBase.".".$collName,'to'=>static::$DataBase.".".$collNameNew,'dropTarget'=>$dropTarget];
        try{
            $Bson=static::command('admin', $cmd);
        } catch (RuntimeException $e){
            static::$Error  = $e->getMessage();
            return false;
        }
        return true;
    }
    //删除一个集合
    public static function collDrop($collName=null){
        if( $collName==null ){$collName=static::$Collection;}
        if( empty(static::$DataBase)||empty($collName) ){
            static::$Error  = 'Please choose database Or Collections';
            return false;
        }
        
        $cmd    = ['drop'=>$collName];
        try{
            $Bson=static::command(static::$DataBase, $cmd);
        } catch (RuntimeException $e){
            static::$Error  = $e->getMessage();
            return false;
        }
        return true;
    }
    //清空一个集合
    public static function collClear($collName){
        if( empty(static::$DataBase) ){
            static::$Error  = 'Please choose database';
            return false;
        }
        static::collUse($collName);
        
        $bulk    = new BulkWrite(['ordered' => true]);
        $bulk->delete([]);
        $opt     = new WriteConcern(WriteConcern::MAJORITY, 1000);
        try{
            $Bson = static::bulk($bulk,$opt);
        } catch (RuntimeException $e){
            static::$Error  = $e->getMessage();
            return false;
        }
        return true;
    }
    
    //显示所有索引
    public static function indexesList($collName=null){
        if( $collName==null ){$collName=static::$Collection;}
        if( empty(static::$DataBase)||empty($collName) ){
            static::$Error  = 'Please choose database Or Collections';
            return false;
        }
        $cmd    = ['listIndexes'=>$collName];
        if( !$Bson = static::command(static::$DataBase,$cmd ) ){
            return false;
        }
        $Bson->setTypeMap( ['root' => 'array', 'document' => 'array','array'=>'array'] );
        return $Bson->toArray();
    }
    
    /**
     * 创建一个索引
     * @param string $name 索引名称
     * @param array $key 索引字段数组
     * @param bool $unique 指定是否为唯一索引
     * @param bool $background 后台创建索引，不阻塞前台操作
     * @param string $collName 针对那个collections进行创建
     * @param array $opt 其他配置
     * @return boolean
     * https://docs.mongodb.com/manual/reference/command/createIndexes/#dbcmd.createIndexes
     */
    public static function indexesInsert(string$name,array$key,bool$unique=false,bool$background=true,string $collName=null,array $opt=null){
        if( $collName==null ){$collName=static::$Collection;}
        if( empty(static::$DataBase)||empty($collName) ){
            static::$Error  = 'Please choose database Or Collections';
            return false;
        }
        //KEY是否合法
        if( empty($key) ){static::$Error ='';return false;}
        foreach($key as $vs=>$rs){
            if( !static::ExistsFields($vs)||$rs!=0||$rs!=1 ){
                static::$Error  = 'Indexes Key Error!';
                return false;
            }
        }
        $cmd    = [
            'createIndexes'=>$collName,
            'indexes'=>[
                'key'=>$key,
                'name'=>$name,
                'background'=>$background,
                'unique'=>$unique
                ]
            ];
        
        if( isset($opt['writeConcern']) ){ $cmd['writeConcern']                                  = $opt['writeConcern'];}
        if( isset($opt['partialFilterExpression']) ){$cmd['indexes']['partialFilterExpression']  = $opt['partialFilterExpression'];}
        if( isset($opt['sparse']) ){$cmd['indexes']['sparse']                                    = $opt['sparse'];}
        if( isset($opt['expireAfterSeconds']) ){$cmd['indexes']['expireAfterSeconds']            = $opt['expireAfterSeconds'];}
        if( isset($opt['storageEngine']) ){$cmd['indexes']['storageEngine']                      = $opt[''];}
        if( isset($opt['weights']) ){$cmd['indexes']['weights']                                  = $opt['weights'];}
        if( isset($opt['default_language']) ){$cmd['indexes']['default_language']                = $opt['default_language'];}
        if( isset($opt['language_override']) ){$cmd['indexes']['language_override']              = $opt['language_override'];}
        if( isset($opt['textIndexVersion']) ){$cmd['indexes']['textIndexVersion']                = $opt['textIndexVersion'];}
        if( isset($opt['2dsphereIndexVersion']) ){$cmd['indexes']['2dsphereIndexVersion']        = $opt['2dsphereIndexVersion'];}
        if( isset($opt['bits']) ){$cmd['indexes']['bits']                                        = $opt['bits'];}
        if( isset($opt['min']) ){$cmd['indexes']['min']                                          = $opt['min'];}
        if( isset($opt['max']) ){$cmd['indexes']['max']                                          = $opt['max'];}
        if( isset($opt['bucketSize']) ){$cmd['indexes']['bucketSize']                            = $opt['bucketSize'];}
        if( isset($opt['collation']) ){$cmd['indexes']['collation']                              = $opt['collation'];}
        
        if( !$Bson = static::command(static::$DataBase,$cmd ) ){
            return false;
        }
        return true;
    }
    //重建索引
    public static function indexesRe($collName=null){
        if( $collName==null ){$collName=static::$Collection;}
        if( empty(static::$DataBase)||empty($collName) ){
            static::$Error  = 'Please choose database Or Collections';
            return false;
        }
        
        $cmd    = ['reIndex'=>$collName];
        if( !$Bson = static::command(static::$DataBase,$cmd ) ){
            return false;
        }
        return true;
    }
    //删除一个索引
    public static function indexesDrop($IndexesName='*',$collName=null){
        if( $collName==null ){$collName=static::$Collection;}
        if( empty(static::$DataBase)||empty($collName) ){
            static::$Error  = 'Please choose database Or Collections';
            return false;
        }
        $cmd = ['dropIndexes'=>$collName,'index'=>$IndexesName];
        if( !$Bson = static::command(static::$DataBase,$cmd ) ){
            return false;
        }
        return true;
    }
    //创建一条数据
    public static function bulkInsert($Data,$collName=null){
        if( $collName==null ){$collName=static::$Collection;}
        if( empty(static::$DataBase)||empty($collName) ){
            static::$Error  = 'Please choose database Or Collections';
            return false;
        }
        if( is_array($Data) ){
            static::$Error  = 'Data Type Error.';
            return false;
        }
        static::collUse($collName);
        if( isset($Data[0])&&is_array($Data[0]) ){$Data[0]=$Data;}
        
        
        $bulk       = new BulkWrite();
        $run        = [];
        foreach($Data as $Vs=>$Rs){
            $Bson   = static::DataToBson($Rs);
            if( !$Bson ){
                static::$Error  = "Data Error:".json_encode($Rs);
                return false;
            }
            $run[]  = $bulk->insert($Bson);
        }
        $opt        = new WriteConcern(WriteConcern::MAJORITY, 1000);
        try{
            $Ex     = static::bulk($bulk,$opt);
        } catch (RuntimeException $e){
            static::$Error  = $e->getMessage();
            return false;
        }
        return $run;
    }
    //修改一条数据
    public static function bulkUpdate($Data,$filter,$collName=null){
        if( $collName==null ){$collName=static::$Collection;}
        if( empty(static::$DataBase)||empty($collName) ){
            static::$Error  = 'Please choose database Or Collections';
            return false;
        }
        if( is_array($Data) ){
            static::$Error  = 'Data Type Error.';
            return false;
        }
        static::collUse($collName);
        if( isset($Data[0])&&is_array($Data[0]) ){$Data[0]=$Data;}
        
        
        $bulk       = new BulkWrite();
        $run        = [];
        foreach($Data as $Vs=>$Rs){
            $Bson   = static::DataToBson($Rs);
            if( !$Bson ){
                static::$Error  = "Data Error:".json_encode($Rs);
                return false;
            }
            $run[]  = $bulk->update($filter,['$set'=>$Bson],['multi' => false, 'upsert' => false]);
        }
        $opt        = new WriteConcern(WriteConcern::MAJORITY, 1000);
        try{
            $Ex     = static::bulk($bulk,$opt);
        } catch (RuntimeException $e){
            static::$Error  = $e->getMessage();
            return false;
        }
        return $run;
    }
    /**
     * 删除一条数据
     * @param type $filter 删除条件
     * @param type $Greedy 是否贪婪，如果为假则仅删除第一条
     * @param type $collName
     * @return boolean
     */
    public static function bulkDelete($filter,$Greedy=true,$collName=null){
        if( $collName==null ){$collName=static::$Collection;}
        if( empty(static::$DataBase)||empty($collName) ){
            static::$Error  = 'Please choose database Or Collections';
            return false;
        }
        static::collUse($collName);
        $Greedy     = ( $Greedy==true )? 0 : 1;
        
        $bulk       = new BulkWrite();
        $run        = $bulk->delete($filter,['limit'=>$Greedy]);
        $opt        = new WriteConcern(WriteConcern::MAJORITY, 1000);
        try{
            $Ex     = static::bulk($bulk,$opt);
        } catch (RuntimeException $e){
            static::$Error  = $e->getMessage();
            return false;
        }
        return $run;
    }
    //条件查询数据
    /**
        awaitData   bool    阻止而不是返回任何数据。一段时间后，超时。有用的tailable光标
        batchSize   integer 每批返回的文档数
        exhaust     bool    在多个“应答”数据包中完全压缩数据流。当你拉下大量数据时，你知道你想把它全部收回。
        limit       integer 要退回的文件的数量
        modifiers   array   修改查询输出或行为的元运算符
        noCursorTimeout     bool    不超时的游标已闲置超过10minutes
        oplogReplay         bool    内部服务器中的旗帜
        partial             bool    从人的部分结果如果一些碎片下来（而不是抛出一个错误）
        projection          array|object specifies字段对返回的布尔值或使用投影算子
        readConcern         MongoDB\Driver\ReadConcern 查询副本集和副本集碎片的隔离级别。此选项要求wiredtiger存储引擎和MongoDB 3.2或更高版本。
        skip                integer     返回前跳过的文档数。
        slaveOk     bool            是否允许复制集子查询
        sort        array|object    返回匹配文档的排序方式。
        tailable    bool            当最后一个数据被检索时，游标不会关闭。稍后您可以恢复此游标。
     */
    public static function whereQuery($filter,$options=[],$collName=null){
        if( $collName==null ){$collName=static::$Collection;}
        
        if( empty(static::$DataBase)||empty($collName) ){
            static::$Error  = 'Please choose database Or Collections';
            return false;
        }
        $Query      = new Query($filter, $options);
        $Read       = new ReadPreference(ReadPreference::RP_PRIMARY);
        
        try{
            $Cursor     = static::$Conn->executeQuery(static::$DataBase.'.'.$collName, $Query, $Read);
        }catch(Exception $e) {
            static::$Error      = $e->getMessage();
            return false;
        }
        return $Cursor;
    }
    //最终执行命令，批量
    protected static function bulk(BulkWrite $bulk,$opt){
        if( empty(static::$DataBase)||empty(static::$Collection) ){
            static::$Error  = 'Please choose database Or Collections';
            return false;
        }
        try{
            $Bson   = static::$Conn->executeBulkWrite( static::$DataBase.'.'.static::$Collection,$bulk,$opt );
        } catch (BulkWriteException $e){
            static::$Error      = $e->getMessage();
            return false;
        }
        return $Bson;
    }
    
    //最终执行命令
    //MONGODB支持的管理命令请参考 https://docs.mongodb.com/manual/reference/command/nav-administration/
    protected static function command(string $db,array $cmd){
        try{
            $Bson               = static::$Conn->executeCommand($db,new Command( $cmd ));
        }catch(Exception $e) {
            static::$Error      = $e->getMessage();
            return false;
        }
        return $Bson;
    }
    //最终执行查询
    protected static function query(string $db,array $cmd){
        try{
            $Bson               = static::$Conn->executeQuery($db,new Query( $cmd ));
        }catch(Exception $e) {
            static::$Error      = $e->getMessage();
            return false;
        }
        return $Bson;
    }
    //将一条数据的数组内容变为BSON数据
    public static function DataToBson($Json){
        $Bson = $Json;
        return $Bson;
    }
    public static function GetError(){
        return static::$Error;
    }
}

