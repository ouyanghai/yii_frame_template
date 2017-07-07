<?php

    require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."Pithy".DIRECTORY_SEPARATOR."Pithy.class.php");

    class PithyLoader
    { 
        public function __construct()
        {   
            define("PITHY_MODE","extend");
            define("PITHY_RUNTIME",Yii::app()->runtimePath.DIRECTORY_SEPARATOR);
            Pithy::run(Yii::app()->params["pithy"]);
            Pithy::import("#.Top.*");
            Pithy::import("#.Open.*");
            Pithy::import("#.Util.*");
            Pithy::$logger=array($this,"log");
        }
        
        public function init()
        {
            
        }

        public function log($args)
        {  
            $msg=$args["message"];
            $level=strtolower($args["level"]);
            $category=$args["category"];
            return Yii::log($msg,$level,$category); 
        }

    }
?>