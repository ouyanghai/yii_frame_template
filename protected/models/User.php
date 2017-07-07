<?php

class User extends CActiveRecord{

	static public function model($className = __CLASS__){
		return parent::model($className);
	}

	public function tableName(){
		return '{{user}}';
	}

	public function __construct(){

	}
	/*
	public function attributeLabels(){
		return array(
			'username'=>'用户名:',
			'password'=>'密&nbsp;&nbsp;码:'
		);
	}
	*/
	public function rules(){
		return array(
			array('tel,password','required'),
			array('qq,email,tel,username,created,password,updated','safe'),
			//array('password','pwdValidate','message'=>'pwd wrong')
		);
	}

	public function pwdValidate(){
	}
	
}

?>