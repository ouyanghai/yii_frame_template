<?php
    // +----------------------------------------------------------------------
    // | PithyPHP [ 精练PHP ]
    // +----------------------------------------------------------------------
    // | Copyright (c) 2010 http://pithy.cn All rights reserved.
    // +----------------------------------------------------------------------
    // | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
    // +----------------------------------------------------------------------
    // | Author: jenvan <jenvan@pithy.cn>
    // +----------------------------------------------------------------------

    // 如果 Pithy 已经运行，则返回
    if(defined("PITHY")) return;

    // 定义开始执行的时间    
    define("PITHY_TIME",microtime(true));

    class Pithy{
        
        static public $logger=null;

        static private $_instance=array();   
        static private $_config = array();
        static private $_data = array();

        static private $_count = array();
        static private $_log = array();
        static private $_trace = array();  
        
        static public $bug = array();  

       
        // 初始化
        static public function init($config=null){

            // 定义常量
            define("PITHY",true);
            define("VERSION","0.30");            
            define('MEMORY_LIMIT_ON',function_exists('memory_get_usage'));
            define('IS_WIN',strstr(PHP_OS, 'WIN') ? 1 : 0 );
            define('IS_CGI',substr(PHP_SAPI, 0,3)=='cgi' || substr(PHP_SAPI, 0,6)=='apache' ? 1 : 0 );
            define('IS_CLI',PHP_SAPI=='cli'? 1 : 0 ); 


            // 定义运行模式： mvc | lite | extend
            defined("PITHY_MODE") || define("PITHY_MODE", "lite");


            // 定义路径相关常量，如果未显式定义则定义之

            //  系统根目录
            defined("PITHY_ROOT") || define("PITHY_ROOT", dirname(__FILE__).DIRECTORY_SEPARATOR);
            // 框架目录
            defined("PITHY_SYSTEM") || define("PITHY_SYSTEM", PITHY_ROOT."SYSTEM".DIRECTORY_SEPARATOR);    
            // 扩展目录
            defined("PITHY_LIBRARY") || define("PITHY_LIBRARY", PITHY_ROOT."Library".DIRECTORY_SEPARATOR);    
            // 项目目录
            defined("PITHY_HOME") || define("PITHY_HOME", PITHY_ROOT."Home".DIRECTORY_SEPARATOR); 
            // 应用目录
            defined("PITHY_APPLICATION") || define("PITHY_APPLICATION", PITHY_HOME."_PROTECTED_".DIRECTORY_SEPARATOR);    

            // 临时目录及其子目录
            defined("PITHY_RUNTIME") || define("PITHY_RUNTIME", PITHY_HOME."~RUNTIME~".DIRECTORY_SEPARATOR);
            defined("PATH_LOG") || define("PATH_LOG", PITHY_RUNTIME."log".DIRECTORY_SEPARATOR);        
            defined("PATH_DATA") || define("PATH_DATA", PITHY_RUNTIME."data".DIRECTORY_SEPARATOR);        
            defined("PATH_CACHE") || define("PATH_CACHE", PITHY_RUNTIME."cache".DIRECTORY_SEPARATOR);        
            defined("PATH_TEMP") || define("PATH_TEMP", PITHY_RUNTIME."temp".DIRECTORY_SEPARATOR); 


            // 加载配置文件（首先加载参数配置，参数配置为空则加载用户的配置；如果用户配置不存在，则使用系统默认配置）
            if(!empty($config)){
                if(is_array($config)){
                    self::config($config);   
                }
                else{
                    self::config(self::load($config));
                }
            }
            else{
                $_filepath=PITHY_HOME."config.php";     
                if(!self::exists($_filepath)){
                    $_filepath=PITHY_SYSTEM."Pithy.config.php";
                } 
                self::config(self::load($_filepath));
                unset($_filepath);    
            }          



            // 检查执行环境

            // 检查配置是否为空
            if(empty(self::$_config))
                exit("Configuration error!"); 

            // 检查文件自动加载路径
            $paths=self::config("APP.AUTOLOAD_PATH");
            if(empty($paths))
                $paths=array();
            elseif(is_string($paths))
                $paths=explode(",",$paths);
            $paths=array_unique(array_merge(explode(',',"@"),$paths));
            self::config("APP.AUTOLOAD_PATH",$paths);

            // 检查是否允许注册AUTOLOAD方法，并将本类的autoload方法放在最前面
            if(self::config("APP.AUTOLOAD") && function_exists('spl_autoload_register')){
                $arr=spl_autoload_functions(); 
                $arr=empty($arr)?array():$arr;

                $funcs=$arr;
                foreach($funcs as $func){
                    spl_autoload_unregister($func);    
                }

                array_unshift($arr,array('Pithy','autoload')); 

                $funcs=$arr;
                foreach($funcs as $func){
                    spl_autoload_register($func);    
                }
            }


            // 检查并设置系统时区 PHP5支持
            if(function_exists('date_default_timezone_set'))
                date_default_timezone_set(self::config("APP.TIMEZONE"));


            // 非调试状态下结束检查
            if(self::config("APP.DEBUG")==false)
                return;


            // 检查PHP版本及PHP相关的必须信息
            if(version_compare(PHP_VERSION,'5.0.0','<')){
                exit("PHP Version must >5.0 !");
            }

            // 检查目录是否存在
            $dirs=array(PITHY_RUNTIME,PATH_LOG,PATH_DATA,PATH_CACHE,PATH_TEMP);
            foreach($dirs as $dir){
                if(!is_dir($dir)){
                    @mkdir($dir,0777,true);
                    @chmod($dir,0777);     
                }     
            } 

        }

        // 执行MVC操作
        static public function exec(){ 

            // 设置 header
            if(!headers_sent()){
                // 默认使用 utf-8 编码
                header("Content-type: text/html; charset=utf-8");
                // Session 初始化
                if(self::config('SESSION.AUTOSTART'))  
                    session_start();    
            }

            // 设置不进行变量转义
            if(version_compare(PHP_VERSION,'6.0.0','<')) {
                @set_magic_quotes_runtime(0);
                if(get_magic_quotes_gpc()==1){                                  
                    array_walk_recursive($_GET,create_function('&$v,$k','$v=stripslashes($v);'));
                    array_walk_recursive($_POST,create_function('&$v,$k','$v=stripslashes($v);'));
                    array_walk_recursive($_COOKIE,create_function('&$v,$k','$v=stripslashes($v);'));
                }            
            } 

            // 增加自动加载路径
            self::config("APP.AUTOLOAD_PATH",array_unique(array_merge(explode(',',"#,@.Util"),self::config("APP.AUTOLOAD_PATH"))));

            // 加载 MVC 启动文件                
            self::load("Bootstrap");
        }

        // 运行
        static public function run($config=null){

            // 初始化
            self::init($config);

            if(PITHY_MODE=="extend"){
                // 导入扩展库目录
                self::import("#.*");     
            }
            else{               
                // 设置异常和错误接口
                set_exception_handler(array('Pithy','exception'));
                set_error_handler(array('Pithy','error'));
            }

            if(PITHY_MODE=="mvc"){
                // 导入核心库目录
                self::import("@.Core.*");
                // 导入项目目录 
                self::import("~.*");
                // 执行 MVC 操作
                self::exec();     
            }
        }  



        /*********************************************************/
        /************************ 文件及配置工具 *******************/  
        /*********************************************************/ 

        // 判断文件是否存在
        static public function exists(&$filepath){
            if(empty($filepath))
                return false;                                
                
            // 将 filepath 转换成真实路径            
            if(in_array(substr($filepath,0,1),array("~","@","#"))){
                $pos=strpos($filepath,DIRECTORY_SEPARATOR);
                $filepath1=substr($filepath,0,$pos);
                $filepath2=substr($filepath,$pos);
                $filepath=str_replace(array("~","@","#",),array(PITHY_APPLICATION,PITHY_SYSTEM,PITHY_LIBRARY),str_replace(".",DIRECTORY_SEPARATOR,$filepath1)).DIRECTORY_SEPARATOR.$filepath2;
            }
            $filepath=str_replace(array("\\","\\\\","//"),array("/","/","/"),$filepath);                       

            // 判断数据缓存中是否存在
            static $data = array();
            if(!empty(self::$_config) && self::config("APP.DEBUG")==false && isset($data[$filepath])){
                return $data[$filepath];
            }

            $rtn=$data[$filepath]=is_file($filepath); 

            return $rtn; 
        }   

        // 载入文件
        static public function load($name,$base="",$rtn=false){

            if(empty($name)) return;             

            $filename=strstr(substr($name,-5),".")!="" ? $name : $name.".php";

            $paths=array();
            if( in_array(substr($filename,0,1),array("~","@","#","*")) || strstr($filename,"/")!="" || strstr($filename,"\\")!="" ){
                $paths[]=$filename;                
            }
            else{
                if(empty($base) && strstr($filename,DIRECTORY_SEPARATOR)==""){
                    $paths=self::config("APP.AUTOLOAD_PATH");    
                }    
                elseif(is_array($base)){
                    $paths=$base;
                }
                elseif(is_string($base) && strstr($base,",")!=""){
                    $paths=explode(",",$base);
                }

                if(empty($paths)){                    
                    $paths[]=$base.DIRECTORY_SEPARATOR.$filename;                                           
                }
                else{
                    array_walk($paths,create_function('&$v,$k',"$"."v.=DIRECTORY_SEPARATOR.'".$filename."';"));
                }    
            }

            //self::dump($paths);

            foreach($paths as $path){                
                if(self::exists($path)){                   
                    $content = require($path);
                    return $rtn ? true : $content;
                }                       
                //echo "$path not exists!<br>";
            }

            if($rtn)
                return false;

            trigger_error("load ($filename) failed!",E_USER_WARNING); 
            return; 
        }

        // 保存文件
        static public function save($name,$content="",$append=false){
            if(empty($name)) 
                return false;

            $mode=0777;                 
            $filename = strstr($name,".") ? $name : PATH_DATA.$name.".php";
            $dir = dirname($filename);
            if(!is_dir($dir)){
                @mkdir($dir,$mode,true);
                @chmod($dir,$mode);     
            }

            $flag=$append?FILE_APPEND:LOCK_EX;                    
            $rtn=@file_put_contents($filename,$content,$flag);
            @chmod($filename,$mode);
            return $rtn;
        }

        // 缓存数据        
        static public function data($name,$value=""){

            if(!defined("PATH_DATA"))
                return;

            // 数据缓存路径
            $filename = PATH_DATA.$name.".php";

            // 如果$value不为默认的空值
            if('' !== $value) {
                if(is_null($value)) {
                    // 删除缓存
                    unset(self::$_data[$name]);
                    return unlink($filename);
                }
                else{
                    // 缓存数据
                    self::$_data[$name]=$value;
                    return self::save($filename,"<?php\nreturn ".var_export($value,true).";\n?>");
                }
            }

            // 获取缓存数据
            if(is_file($filename)) {
                $value = require($filename);
                self::$_data[$name] = $value;
            }
            else{                
                $value = null;
                self::$_data[$name] = $value;
            }

            // 返回缓存数据
            return $value;
        }

        // 配置参数
        static public function config($name=null,$value=null){              

            // 无参数时获取所有
            if(empty($name)) 
                return self::$_config; 

            // 批量设置
            if(is_array($name)){
                $name=preg_replace_callback("/'([^']+)' =>/",create_function('$matches','return "\'".strtolower($matches[1])."\' =>";'),var_export($name,true));
                eval("$"."name=$name;");
                //return self::$_config = array_merge_recursive(self::$_config,$name);
                return self::$_config = $name + self::$_config;
            }
            if(is_array($value)){
                $value=preg_replace_callback("/'([^']+)' =>/",create_function('$matches','return "\'".strtolower($matches[1])."\' =>";'),var_export($value,true));
                eval("$"."value=$value;"); 
            }

            // 执行设置获取或赋值，支持 . 操作（最多支持四维) 
            if(is_string($name)) {                
                $name = explode('.',strtolower($name));
                if( count($name)==1 ){
                    if(is_null($value))
                        return self::$_config[$name[0]];
                    self::$_config[$name[0]] = $value;    
                }
                elseif( count($name)==2 && isset(self::$_config[$name[0]])){
                    if(is_null($value) && isset(self::$_config[$name[0]][$name[1]]))                        
                        return self::$_config[$name[0]][$name[1]];
                    self::$_config[$name[0]][$name[1]] = $value;    
                }
                elseif( count($name)==3 && isset(self::$_config[$name[0]],self::$_config[$name[0]][$name[1]])){
                    if(is_null($value) && isset(self::$_config[$name[0]][$name[1]][$name[2]]))
                        return self::$_config[$name[0]][$name[1]][$name[2]];
                    self::$_config[$name[0]][$name[1]][$name[2]] = $value;      
                }
                elseif( count($name)==4 && isset(self::$_config[$name[0]],self::$_config[$name[0]][$name[1]],self::$_config[$name[0]][$name[1]][$name[2]]) ){
                    if(is_null($value) && isset(self::$_config[$name[0]][$name[1]][$name[2]][$name[3]]))
                        return self::$_config[$name[0]][$name[1]][$name[2]][$name[3]];
                    self::$_config[$name[0]][$name[1]][$name[2]][$name[3]] = $value;      
                }
                return null;
            }

            // 非法参数返回 null
            return null;           
        }

        // 合并参数
        public static function merge($a,$b){
            foreach($b as $k=>$v){
                if(is_array($v) && isset($a[$k]) && is_array($a[$k]))
                    $a[$k]=self::merge($a[$k],$v);
                elseif(is_integer($k))
                    isset($a[$k]) ? $a[]=$v : $a[$k]=$v;
                else
                    $a[$k]=$v;
            }
            return $a;
        }


        /*********************************************************/
        /************************ 类工具 *************************/  
        /*********************************************************/ 


        // 自动导入类文件
        static public function autoload($class){
            if(self::config("APP.AUTOLOAD") && isset($class[0])){ 
                if(self::load($class.".class.php","",true)==true){           
                    return;
                }
                if(PITHY_MODE!="extend")
                    trigger_error("Class ($class) not exists!",E_USER_ERROR);
                return; 
            }
            if(PITHY_MODE!="extend")
                trigger_error("Class ($class) not defined!",E_USER_ERROR);
            return;            
        } 

        // 获取指定类的实体
        static public function instance($class,$args="",$indefinite=false){
            if(!isset(self::$_instance[$class])) {
                if(class_exists($class)){
                    if(empty($args)){                        
                        self::$_instance[$class] = new $class();   
                    }
                    elseif(!is_array($args) || $indefinite==false){
                        self::$_instance[$class] = new $class($args);
                    }
                    else{                        
                        $keys=array_keys($args);    
                        $params='$args['.(count($keys)>1?implode('],$args[',$keys):0).']';
                        eval('self::$_instance[$class] = new $class('.$params.');');

                        /*
                        // Note: ReflectionClass::newInstanceArgs() is available for PHP 5.1.3+
                        // $class=new ReflectionClass($class);
                        // $object=$class->newInstanceArgs($args);
                        $_class=new ReflectionClass($class);
                        self::$_instance[$class]=call_user_func_array(array($_class,'newInstance'),$args);
                        */
                    }                        
                }                      
                else{
                    return trigger_error("Object ($class) not exists!",E_USER_ERROR);
                }
            }            
            return self::$_instance[$class];
        } 

        // 导入类 ： 采用命名空间的方式导入类，例如：import('Db')   import('@.Template.PithyTemplate')  import('#.Top.TopApi')  import('#.Top.*')  import('~.Model.User')   import(HOME.'/test.php')
        static public function import($name,$args="",$rtn=false){
            if(empty($name)) 
                return;

            if(substr($name,-2)==".*"){
                self::config("APP.AUTOLOAD_PATH",array_unique(array_merge(array(substr($name,0,-2)),self::config("APP.AUTOLOAD_PATH"))));
                return;
            }

            $base="";
            if(in_array(substr($name,0,1),array("@","#","~"))){
                $pos=strrpos($name,".");
                $base=substr($name,0,$pos);                
                $class=substr($name,$pos+1);  
                $filename=$class.".class.php";
                if(substr($name,0,1)=="~"){
                    list($a,$b,$c)=explode(".",$name);
                    $class=$c.$b;
                    $filename=ucfirst($c).".".strtolower($b).".php";                    
                }
            }
            elseif(strstr($name,".")==""){                 
                $filename=$name.".class.php";
                $class=$name;
            }
            else{
                $filename=$name;  
                $class=str_replace(".class","",basename($filename,".php"));
            }

            //echo $base."|".$filename."|".$class;

            if(self::load($filename,$base,true)){
                return self::instance($class,$args,true);    
            }
            elseif($rtn){ 
                return;
            }            

            trigger_error("import ($filename : $name) failed!",E_USER_WARNING); 
            return;
        }      





        /*********************************************************/
        /************************ 调试工具 ***********************/  
        /*********************************************************/     

        // 指标统计 
        static public function benchmark($tag){


        }   

        // 累计计数
        static public function count($key,$step=1){
            if(!isset(self::$_count[$key])) {
                self::$_count[$key] = 0;
            }
            if(empty($step))
                return self::$_count[$key];
            else
                self::$_count[$key] = self::$_count[$key]+(int)$step;
        } 

        // 变量输出
        static public function dump(){
            
            $params = func_get_args();
            if( empty($params) )
                return null;
                
            $var = $params[0];
            
            $label = "";
            if( isset($params[1]) && is_string($params[1]) )
                $label = $params[1];
            if( isset($params[2]) && is_string($params[2]) )
                $label = $params[2];
            
            $echo = true;    
            if( isset($params[1]) && is_bool($params[1]) )
                $echo = $params[1];
            if( isset($params[2]) && is_bool($params[2]) )
                $echo = $params[2];
            
            
            $output = print_r($var, true);   
			if( IS_CLI ){
				$label = empty($label) ? $label : $label."\r\n";
				$output = $label.$output;
			}
			else{
				$output = ini_get('html_errors') ? htmlspecialchars($output,ENT_QUOTES) : $output;    
				$output = "<pre>".$output."</pre>";            
				if( !empty($label) )
					$output = "<fieldset><legend style='margin-top:10px;padding:5px;font-weight:600;background:#CCC;'> ".$label." </legend>".$output."</fieldset>";
			}
			
            if( $echo )
                echo $output;
            
            return $output;
        }  
        
        // 跟踪调试
        static public function trace($msg,$traces=array()){

            if(empty($msg))
                return self::$_trace;  

            if(empty($traces) || !is_array($traces)){
                if(function_exists("debug_backtrace"))                 
                    $traces=debug_backtrace();
                else
                    $traces=array();
            }

            if(!empty($traces)){ 
                $msg.=" \r\n-------------------------------\r\n";                               
                foreach($traces as $t){
                    $msg.="# ";
                    if(isset($t["file"]))
                        $msg.=$t["file"]." [".$t["line"]."] \r\n  ";
                    else
                        $msg.="[PHP inner-code] \r\n  ";
                    if(isset($t["class"]))
                        $msg.=$t["class"].$t["type"];
                    $msg.=$t["function"]."(";
                    if(isset($t["args"]) && sizeof($t["args"])>0){
                        $count=0;
                        foreach($t["args"] as $item){
                            
                            if(is_string($item)){
                                $str=str_replace(array("\r","\n","\r\n"), "", $item);
                                if(strlen($item)>200)
                                    $msg.="'". substr($str, 0, 200) . "...'";
                                else
                                    $msg.="'" . $str . "'";
                            }
                            elseif(is_int($item) || is_float($item))
                                $msg.=$item;
                            elseif(is_object($item))
                                $msg.=get_class($item);
                            elseif(is_array($item)){
                                if($count<3){
                                    @array_walk($item, create_function('&$v,$k','if( is_object($v) ){ $v = "<OBJECT>".get_class($v); } if( is_resource($v) ){ $v = "<RESOURCE>".get_resource_type($v); }'));    
                                    $msg.=str_replace(array("\r","\n","\r\n"), "", var_export($item, true));
                                }
                                else
                                    $msg.="array(".count($item).")"; 
                            }                                
                            elseif(is_bool($item))
                                $msg.=$item ? "true" : "false";
                            elseif(is_null($item))
                                $msg.="NULL";
                            elseif(is_resource($item))
                                $msg.=get_resource_type($item);

                            $count++;
                            if (count($t["args"])>$count)
                                $msg.=", ";
                        }
                    }                    
                    $msg.=") \r\n";
                }
                $msg.=(IS_CLI?"":" \r\n".date("Y-m-d H:i:s")." | ".$_SERVER["SERVER_ADDR"]." : ".$_SERVER["REMOTE_ADDR"]);            
            }
            
            self::$_trace[] = $msg;
            count( self::$_trace ) <= 100 || array_splice(self::$_trace, 0, -100);
            
            return $msg;
        }

        // 日志记录
        static public function log($message="",$options=null,$force=false){ 
            
            // 如果日志内容为空，则表示返回之前记录的所有日志内容（全局静态属性 Pithy::_log）
            if(empty($message))
                return self::$_log; 

            // 支持的日志记录类型
            $types=array(
            "SYSTEM"=>0,
            "MAIL"=>1,
            "TCP"=>2,
            "FILE"=>3,
            );
            
            // 支持的日志记录级别
            $levels=array(
            "ALERT",
            "ERROR",
            "WARNING",
            "NOTICE",
            "INFO",
            "DEBUG",
            );
            
            // 默认的日志设置参数
            $config=array(
            "type"=>"FILE",         // 日志记录类型
            "level"=>"ERROR",       // 日志记录级别
            "destination"=>"common",// 日志记录位置  PATH_LOG/Ymd/common.log
            "extra"=>"",            // 日志扩展信息（日志记录类型为 MAIL 和 TCP 时使用，参见 error_log 函数)
            ); 

            // 参数是数字时，设置日志记录类型
            if(is_int($options))
                $config["type"]=$options;            
            
            // 参数是字符串时，设置日志记录级别或位置
            if(is_string($options)){
                if(in_array(strtoupper($options),$levels))
                    $config["level"]=strtoupper($options); 
                else
                    $config["destination"]=$options;
            }
            
            // 参数是数组时，将其同默认参数合并
            if(is_array($options))
                $config=array_merge($config,$options);
                
            // 校验和设置相关变量            
            $folder=PATH_LOG.date('Ymd').DIRECTORY_SEPARATOR;   
            $now = date("Y-m-d H:i:s");
            
            // 最终的日志参数
            $type = in_array($config["type"],array_keys($types)) ?  $types[$config["type"]] : $types["FILE"] ;
            $level = in_array(strtoupper($config["level"]),$levels) ? strtoupper($config["level"]) : "INFO" ;
            $destination = empty($config["destination"]) ? $folder.strtolower($level).".log" : ( ( strstr($config["destination"],"/") || strstr($config["destination"],"\\") ) ? $config["destination"] : $folder.$config["destination"].".log");   
            $extra = $config["extra"];         

            // 执行内部日志处理程序
            if( ( empty(self::$logger) || $force ) && ( self::config("APP.DEBUG") || self::config("APP.LOG_LEVEL") || in_array($level,self::config("APP.LOG_LEVEL")) ) ){
                
                // 拼接最终日志内容(如果已经拼接好，则不需拼接)，并放入全局公共属性中
                $msg = $message;
                if( !preg_match("/^[\d]{4}/", $msg) ){
                    //$msg=str_replace(array("\r","\n"),array("\t","\t"),$message);
                    $msg="{$now} [{$level}] {$msg}";    
                } 
                array_push(self::$_log, $msg);               
                count( self::$_log ) <= 1000 || array_splice(self::$_log, 0, -1000); 
                
                // 文件类型的日志记录预处理
                if($type==$types["FILE"]){ 
                    if(!is_dir($folder) && ( @mkdir($folder,0777,true)==false || @chmod($folder,0777)==false )){
                        array_push(self::$_log,"$now [ALERT] Can not mkdir($folder)!");
                        return;        
                    }
                    if(is_file($destination) && floor(self::config("APP.LOG_FILE_SIZE")) <= filesize($destination) ){
                        extract(pathinfo($destination));
                        rename($destination,$dirname.DIRECTORY_SEPARATOR.$basename."_".time().".".$extension);
                    }                  
                }
                
                // 调用 php 自带的日志记录函数
                error_log($msg."\r\n", $type, $destination, $extra);
            }
            
            // 执行外部日志处理程序 (如果定义了外部的日志处理程序并且没有强制使用内部的，则使用外部日志处理程序来处理日志)
            if( !empty(self::$logger) && !$force ){
                $args=array(
                    "message"=>$message,
                    "level"=>$level,
                    "category"=>"Pithy.Extend.".basename($destination,".log"),                     
                );            
                return call_user_func_array(self::$logger,array($args)); 
            }                                 
        }   

         // 错误处理
        static public function error(){      
           
            if( 4 > func_num_args() )
                return;
                
            $params = func_get_args();
            
            $errno = $params[0];
            $errstr = $params[1];
            $errfile = $params[2];
            $errline = $params[3];
            
            
            // 是否终止执行，并输出错误
            $halt = true;
            
            // 错误类型
            $type = "error"; 

            // 设置日志类型
            switch ($errno) {
                case E_NOTICE:
                case E_USER_NOTICE:
                    $halt = false;
                    $type = "notice";
                    break;
                case E_WARNING:
                case E_USER_WARNING:
                    $type = "warning";
                    break;
                case E_ERROR:
                case E_USER_ERROR:
                    $type = "error";
                    break;
                default:
                    $type = "alert";                                                                                                 
                    break;
            }
            
            $msg = $errstr;            
            
            // 调试错误
            $bug = self::trace($msg, debug_backtrace()); 
            array_push(self::$bug, $bug);
            
            // 记录错误 
            $info = $errfile."(".$errline.") -=> ".( (self::config("APP.DEBUG") || self::config("APP.ERROR_TRACE")) ? $bug : $msg );
            
            if( isset($params[4]) && !empty($params[4]) && ( self::config("APP.DEBUG") || self::config("APP.ERROR_TRACE") ) ){
                $param = array_slice($params[4], 0, 10);                
                @array_walk($param, create_function('&$v,$k','if( is_array($v) ){ $v = "<ARRAY>".count($v); } if( is_object($v) ){ $v = "<OBJECT>".get_class($v); } if( is_resource($v) ){ $v = "<RESOURCE>".get_resource_type($v); }'));
                $info .= " \r\n-------------------------------\r\n".var_export($param, true)."\r\n\r\n";
            }            
            
            if( self::config("APP.ERROR_LOG") ) 
                self::log($info, array("destination" => "pithy_".$type/*."_".basename($errfile)*/, "level" => strtoupper($type)), true);
            
            // 输出错误
            if( $halt )
                self::halt($info, true); 
        } 

        // 异常处理
        static public function exception($e){  

            $e=(array) $e;   
            
            $trace=array();
            $traces=array();

            $keys=array("message","code","file","line","trace");
            foreach($e as $k=>$v){
                foreach($keys as $key){
                    if(strstr($k,$key)<>""){
                        if($key=="trace")
                            //$traces+=$v;
                            $traces=$v;
                        else
                            $trace[$key]=$v;    
                    }    
                }                                
            }
            $trace["function"]="throw new Exception";
            $trace["args"]=array($trace["message"]);
            array_unshift($traces,$trace);
            $traces=array_merge(debug_backtrace(),$traces);

            //self::dump($e);
            //self::dump($traces); 
            
            $msg = $trace["message"]; 
            
            // 调试异常              
            $bug = self::trace($msg, $traces); 
            array_push(self::$bug, $bug);            
                
             // 记录错误 
            $info = $trace["file"]."(".$trace["line"].") -=> ".( (self::config("APP.DEBUG") || self::config("APP.ERROR_TRACE")) ? $bug : $msg );
            if( self::config("APP.ERROR_LOG") ) 
                self::log($info, array("destination"=> "pithy_exception"/*."_".basename($trace["file"])*/,"level"=>"ALERT"), true);    

            // 输出异常
            self::halt($info, true);
        }  





        /*********************************************************/
        /************************ 系统工具 ***********************/  
        /*********************************************************/ 

        // 终止执行
        static public function halt($msg,$error=false){ 

            // 分析输出内容             
            if($error && self::config("APP.ERROR_DISPLAY")==false){ 
                $msg=self::config("APP.ERROR_MESSAGE");                
            }

			// 如果不是字符串则转换
            if(!is_string($msg)){
                $msg=self::dump($msg,false);
            }

            // 显示要输出的内容
            if(!IS_CLI){
				
				// 如果是网址则重定向到其他页面，否则进行编码
                if(substr($msg,0,7)=="http://" || substr($msg,0,1)=="/"){
                    self::redirect($msg);
                }
				
				// 如果没有发送头部，则发送编码
                if(!headers_sent())
                    header("Content-type: text/html; charset=utf-8");

                $msg=htmlentities($msg);
                $msg=$error==true?"<h1>".str_replace("\r\n\r\n","</h1><pre>",$msg)."</pre>":$msg;
                $msg=($error==false || strstr($msg,"<pre>")<>"")?$msg:"<pre>".$msg."</pre>";                
                $msg="<div style='position:absolute;bottom:0px;right:0px;padding:10px;background:#FFFFCC;border:solid 1px #CCCCFF;color:#FF3333;font-size:14px;line-height:22px;'>".$msg."</div>";  
            }

            echo $msg;
            exit;
        }

        // 页面重定向
        static public function redirect($url,$time=0,$msg=''){
            //多行URL地址支持
            $url = str_replace(array("\n", "\r"), '', $url);
            if(empty($msg))
                $msg = "系统将在{$time}秒之后自动跳转到{$url}！"; 

            if(!headers_sent()) {
                if(0===$time) {
                    header("Location: {$url}");
                }
                else {
                    header("refresh:{$time};url={$url}");
                    echo($msg);
                }                
            }
            else {                                      

                if($time>0 and $time<1){
                    $str = "<script language='javascript'>setTimeout(function(){self.location='$url'},".($time*1000).")</script>";
                }
                else{
                    $str = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
                }

                if($time!=0)
                    $str .= $msg;

                echo($str);
            } 

            exit;            
        }         

        // 会话处理
        static public function cookie($name,$value="",$option="",$init=true){

            // 默认设置
            $config = array(
            'prefix' => self::config('COOKIE.PREFIX'), // cookie 名称前缀
            'expire' => self::config('COOKIE.EXPIRE'), // cookie 保存时间
            'path'   => self::config('COOKIE.PATH'),   // cookie 保存路径
            'domain' => self::config('COOKIE.DOMAIN'), // cookie 有效域名
            );
            // 参数设置(会覆盖黙认设置)
            if (!empty($option)) {
                if (is_numeric($option))
                    $option = array('expire'=>$option);
                elseif( is_string($option) )
                    parse_str($option,$option);
                $config = array_merge($config,array_change_key_case($option));
            }

            $domain=strtolower($_SERVER["SERVER_NAME"]);
            list($a,$b)=explode(".",strrev($domain));

            $prefix=!empty($config["prefix"])?$config["prefix"]:str_replace(".","_",$domain);        
            $expire=is_null($value)?time()-3600:(intval($config["expire"])>0?time()+intval($config["expire"]):0);
            $path=!empty($config["path"])?$config["path"]:"/";
            $domain=!empty($config["domain"])?$config["domain"]:strrev("$a.$b"); 


            // 获取 cookie
            if(!is_null($value) && $value==""){
                if(strstr($name,".")<>""){
                    $key="_COOKIE['".$prefix."']['".str_replace(".","']['",$name)."']";
                    $key_root="_COOKIE['".$prefix."']";
                    $key_parent=substr($key,0,strrpos($key,"["));
                    eval("$"."_cookie=isset($".$key_root.",$".$key_parent.",$".$key.")?$".$key.":null;");    
                }
                else{            
                    $_cookie=isset($_COOKIE[$prefix],$_COOKIE[$prefix][$name])?$_COOKIE[$prefix][$name]:null;
                }
                return $_cookie;        
            }


            // 初始化，删除 cookie 、整理 value
            if($init){ 

                // 删除 cookie
                if(is_null($value)){

                    $_cookie=self::cookie($name);
                    //echo "<xmp>".print_r($_cookie,true)."</xmp>";

                    // 值为 null 的 cookie 直接返回
                    if(!is_null($_cookie)){ 

                        // 值为非数组型的 cookie 直接赋值（删除）；否则进行整理（数组型 cookie 无法一次删除，需将所有 value 设置成 null），然后通过赋值的方式删除            
                        if(!is_array($_cookie)){
                            self::cookie($name,null,null,false);                    
                        }
                        else{
                            array_walk_recursive($_cookie,create_function('&$v,$k','$v=null;'));                        
                            self::cookie($name,$_cookie,null,false);                    
                        }                
                    }

                    return;
                }        

                // 整理 value            
                $root=strstr($name,".")<>""?substr($name,0,strpos($name,".")):$name; // 根节点
                if($root<>$name){
                    $$root=array();
                    $key=$root."['".str_replace(".","']['",substr(strstr($name,"."),1))."']";
                    eval("$".$key."=$"."value;");                                
                    $value=$$root;
                }
                //echo "<xmp>".print_r($value,true)."</xmp>";
                self::cookie($root,$value,$option,false);            
                return;
            }

            //echo "<xmp>[$name]\r\n\r\ncookie = ".print_r(self::cookie($name),true)."</xmp><xmp>value = ".print_r($value,true)."</xmp>")


            // 设置 cookie

            // 如果需要赋值的变量为数组，则将数组分解分别赋值
            if(is_array($value)){
                foreach($value as $k=>$v){                
                    self::cookie($name.".".$k,$v,$option,false);                
                }
                return;
            }

            // 如果之前的 cookie 为数组，则先清空再赋值
            if(is_array(self::cookie($name))){
                self::cookie($name,null,null,true);
            }            

            // 设置新 cookie
            $rtn=setcookie($prefix."[".str_replace(".","][",$name)."]",$value,$expire,$path,$domain); 
            //echo $rtn;

            // 设置 $_COOKIE 变量
            if(!isset($_COOKIE[$prefix])){
                $_COOKIE[$prefix]=array();    
            }
            if(strstr($name,".")==""){
                // 根节点赋值 
                if(is_null($value)){
                    unset($_COOKIE[$prefix][$name]);
                }
                else{
                    $_COOKIE[$prefix][$name]=$value;
                }
            }
            else{
                // 子节点赋值
                $key="_COOKIE['".$prefix."']['".str_replace(".","']['",$name)."']";  
                $key_parent=substr($key,0,strrpos($key,"["));
                $name_parent=substr($name,0,strrpos($name,"."));
                //echo "$key -> $key_parent ->$name_parent";
                if(is_null($value)){                    
                    eval("if(is_array($".$key_parent.")){unset($".$key.");};");
                    eval("if(empty($".$key_parent.")){self::cookie('$name_parent',null,null,false);}");
                }
                else{                
                    eval("$".$key_parent."=isset($".$key_parent.") && is_array($".$key_parent.")?$".$key_parent.":array();");
                    eval("$".$key."='".$value."';");
                }                
            }
        }      

        // 执行系统命令
        static public function execute($bin,$args,$asyn=true){  
            if(!$asyn){
                passthru($bin." ".$args,$rtn);
                return $rtn;
            }
            if(IS_WIN){
                $cmd="start cmd /c \"".$bin." ".$args."\"";
            }
            else{
                $cmd=$bin." ".$args." 1>/dev/null 2>&1";
                if(self::config("APP.DEBUG")){
                    $cmd=$bin." ".$args." 1>".PATH_LOG."error_".basename($bin).".log 2>&1";
                    self::log($cmd."\r\n\r\n",PATH_LOG."execute_".basename($bin).".log");
                }             
                if(strstr($bin,"mysql")==""){
                    $cmd="nohup ".$cmd."  &"; 
                }               
            }
            return pclose(popen($cmd,"r"));
        }    

    }
?>
