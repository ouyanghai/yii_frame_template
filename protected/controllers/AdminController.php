<?php
class AdminController extends TopController{
	public function init(){
		parent::init();
		$this->layout='//layouts/admin';
	}

	public function actionIndex(){
		$this->render('index');
	}

	public function actionList(){
		$this->render('list');
	}

	//登录页面
	public function actionLoginPage(){
		$this->layout='//layouts/column3';
		$this->render('login');
	}

	public function actionLogout(){
		// 清空用户之前的登录状态
        Yii::app()->user->clearStates();
        Yii::app()->user->logout();
        echo json_encode("ok");
	}

	public function filters(){
		return array(
			"auth - loginPage"
		);
	}
}

?>