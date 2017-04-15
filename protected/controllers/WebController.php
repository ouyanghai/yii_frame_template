<?php
class WebController extends TopController{
	public function init(){
		parent::init();
		$this->layout = "//layouts/web";
	}

	public function actionIndex(){
		$this->render("index");
	}
	
}

?>