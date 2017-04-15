<?php
class TopController extends Controller{
	public function init(){
		parent::init();
		/*
		Yii::app()->user->setState('themes',"classic");
		if(isset(Yii::app()->user->themes) && Yii::app()->user->$themes=="classic"){
            //重新设置主题路径
            Yii::app()->themeManager->setBasePath(Yii::app()->basePath. "/themes");
            Yii::app()->theme =Yii::app()->user->themes;
            
            // 发布 当前主题 的 views 目录下的资源文件
            if (!empty($this->module) && isset($this->module->id)){
                //重新设置布局路径

                Yii::app()->setLayoutPath( Yii::app()->theme->viewPath .DIRECTORY_SEPARATOR."layouts");
                
                //发布样式
                $folder = Yii::app()->layoutPath . "/assets";
                $this->publish("theme",$folder);
            }    
        }
        */
	}
}

?>