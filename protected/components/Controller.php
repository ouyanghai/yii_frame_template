<?php
/**
 * Controller is the customized base controller class.
 * All controller classes for this application should extend from this base class.
 */
class Controller extends CController
{
	/**
	 * @var string the default layout for the controller view. Defaults to '//layouts/column1',
	 * meaning using a single column layout. See 'protected/views/layouts/column1.php'.
	 */
	public $layout='//layouts/column1';
	/**
	 * @var array context menu items. This property will be assigned to {@link CMenu::items}.
	 */
	private $_assets = array();
	public $menu=array();
	public $maxFileSize = 2097152;//2M
	public $fileType = array('image/jpg','image/bmp','image/png','image/jpeg','image/gif');
	/**
	 * @var array the breadcrumbs of the current page. The value of this property will
	 * be assigned to {@link CBreadcrumbs::links}. Please refer to {@link CBreadcrumbs::links}
	 * for more details on how to specify this property.
	 */
	public function init(){
		$folder = Yii::app()->layoutPath.'/assets';
		$this->publish('app',$folder);

		if(isset($this->module->id) && !empty($this->module)){
			$folder = dirname($this->viewPath).'/layouts/assets';
			$this->publish('module',$folder);
		}
	}

	public function publish($name,$path=''){
		if($path == '') return false;
		$arr = array($name => $path);
		Yii::app()->assetManager->publish($path,false,-1,true);
		$arr[$name] = Yii::app()->assetManager->getPublishedUrl($path);
		$this->_assets = array_merge($this->_assets,$arr);
		//print_r($this->_assets);exit;
	}

	public function uploadFile($path='photo'){
		//存储文件的目录
		$dirname = Yii::app()->basePath.'/../images/'.$path;
		$filePath = array();
		if(empty($_FILES)){
			return false;
		}

		$files = $_FILES['pic'];

		if(!is_array($files['error'])){
			foreach($files as $k => $v){
				$files[$k] = array();
				$files[$k][0] = $v;
			}
		}
		$num = 0;
		foreach($files['error'] as $key => $value){
			if($value == 0){
				if($files['size'][$key] < $this->maxFileSize){
					if(in_array($files['type'][$key],$this->fileType)){
						//准备文件夹
						if(!is_dir($dirname))
							mkdir($dirname,0777,true);
						//准备文件名
						$filename = ++$num.'_'.date('Y-m-d',time()).'_'.md5(uniqid());
						$ext = strrchr($files['name'][$key],'.');
						$filename .= $ext;
						if(move_uploaded_file($files['tmp_name'][$key],$dirname.'/'.$filename)){
							array_push($filePath,$filename);
						}
					}
				}
			}
		}
		return $filePath;
	}

	public function getAssets(){
		return $this->_assets;
	}

	public $breadcrumbs=array();

	public function filterAuth($filterChain){
		
		if(Yii::app()->user->isGuest ){
			$url = Yii::app()->createAbsoluteUrl(Yii::app()->user->loginUrl);
			Yii::app()->getRequest()->redirect($url);
			return false;
		}else{
		}
		$filterChain->run();
	}


}