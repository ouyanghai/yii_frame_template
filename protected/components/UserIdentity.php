<?php

/**
 * UserIdentity represents the data needed to identity a user.
 * It contains the authentication method that checks if the provided
 * data can identity the user.
 */
class UserIdentity extends CUserIdentity
{

	private $_info = array();
	public $tel,$password;

	// 构造函数
    public function __construct()
    {              
        
    }
	/**
	 * Authenticates a user.
	 * The example implementation makes sure if the username and password
	 * are both 'demo'.
	 * In practical applications, this should be changed to authenticate
	 * against some persistent user identity storage (e.g. database).
	 * @return boolean whether authentication succeeds.
	 */
	public function authenticate()
	{	

		$user = User::model()->find('tel=:tel and password=:password',array(':tel'=>$this->tel,':password'=>$this->password));
		
		if($user == null){
			$this->errorCode = self::ERROR_USERNAME_INVALID;
		}
		else{
			//$this->setPersistentStates(array('level'=>$user->level,'nick'=>$user->nick,'deadline'=>$user->deadline)); 
			//username用有值才能login成功
			$this->username = $user->nick;

			$this->set("tel",$user->tel);
			$this->set("level",$user->level);
			$this->set("nick",$user->nick);
			$this->set("deadline",$user->deadline);
			$this->errorCode=self::ERROR_NONE;
		}
		return !$this->errorCode;
	}

	// 赋值
	public function set($name,$value=""){
		if(!is_array($this->_info)){
			$this->_info=array();
		}
		
		if($name!='' && $value!=''){
			$this->_info[$name]=$value;
		}
	} 

	public function get($name=""){
		if(empty($this->_info)){
			return null;
		}

		if(empty($name)){
			return $this->_info;
		}

		if(!empty($this->_info[$name])){
			return $this->_info[$name];
		}
		
	}
}