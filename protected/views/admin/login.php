<!DOCTYPE html>
<html>
<head>
	<title>用户登录</title>
	<meta http-equiv="Content-Type" content="html/text; charset=utf-8"/>
	<link rel="stylesheet" type="text/css" href="<?php echo $this->assets['app']; ?>/css/admin.css">
	<script type="text/javascript" src='<?php echo $this->assets['app']; ?>/js/jquery-1.7.2.min.js'></script>
	<script type="text/javascript" src='<?php echo $this->assets['app']; ?>/js/jquery.form.js'></script>
	<script type="text/javascript" src='<?php echo $this->assets['app']; ?>/js/admin.js'></script>
</head>
<body>
<div id='login-module'>
	<form id='login-form'>
		<div class='input-row'>
			用户名:
			<input type='text' id='login-name' />	
		</div>
		<div class='input-row'>
			密&nbsp;&nbsp;码:
			<input type='password' id='login-password' />
		</div>
		<div class='input-row'>
			<input type='button' id='login-btn' value='登录' />
		</div>
	</form>
</div>
</body>
</html>