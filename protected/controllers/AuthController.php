<?php
class AuthController extends Controller{
	private $_identity = null;

	public function actionLogin(){
		if(empty($_POST['username']) || empty($_POST['password'])){
			Yii::app()->getRequest()->redirect("/admin/login");
			exit;
		}

		if($this->_identity===null){
			$this->_identity=new UserIdentity($_POST['username'],$_POST['password']);
		}

		if(!$this->_identity->authenticate()){
			return false;	
		}

		Yii::app()->user->login($this->_identity,3600);
		return true;
	}
}

?>