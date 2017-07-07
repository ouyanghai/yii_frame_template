<!DOCTYPE html>
<html>
<head>
	<title>用户注册</title>
	<meta http-equiv="Content-Type" content="html/text; charset=utf-8"/>
	<link rel="stylesheet" type="text/css" href="<?php echo $this->assets['app']; ?>/css/admin.css">
	<script type="text/javascript" src='<?php echo $this->assets['app']; ?>/js/jquery-1.7.2.min.js'></script>
	<script type="text/javascript" src='<?php echo $this->assets['app']; ?>/js/jquery.form.js'></script>
	<script type="text/javascript" src='<?php echo $this->assets['app']; ?>/js/admin.js'></script>
</head>
<body>
<div id='login-module'>
	<form id='regist-form'>
		<div class='input-row' style='height:50px;'>
			SIGN UP<br/><span style='font-size:12px;color:red;' id='regist_tip'></span>
		</div>
		<div class='input-row'>
			<input type='text' id='register-name' placeholder='NAME'/>	
		</div>
		<div class='input-row'>
			<input type='text' id='register-tel' placeholder='TELEPHONE'/>	
		</div>
		<div class='input-row'>
			<input type='password' id='register-password' placeholder='PASSWORD'/>
		</div>
		<div class='input-row'>
			<input type='password' id='re-register-password' placeholder='RE-TYPE PASSWORD'/>
		</div>
		<div class='input-row'>
			<p class='p-register'>
				Already registered? <a href='/admin/loginPage'>Sign In</a>
			</p>
		</div>
		<div class='input-row'>
			<input type='button' id='register-btn' value='Sign Up' />
		</div>
	</form>
</div>
</body>
</html>