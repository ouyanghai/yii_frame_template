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
	public function actionLogin(){
		$this->render('login');
	}

	public function filters(){
		return array(
			"auth - login"
		);
	}
}

?>