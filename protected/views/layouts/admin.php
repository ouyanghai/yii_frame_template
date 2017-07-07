<!DOCTYPE html>
<html>
<head>
	<title>用户后台</title>
	<meta http-equiv="Content-Type" content="html/text; charset=utf-8"/>
	<link rel="stylesheet" type="text/css" href="<?php echo $this->assets['app']; ?>/css/admin.css">
	<script type="text/javascript" src='<?php echo $this->assets['app']; ?>/js/jquery-1.7.2.min.js'></script>
	<script type="text/javascript" src='<?php echo $this->assets['app']; ?>/js/jquery.form.js'></script>
	<script type="text/javascript" src='<?php echo $this->assets['app']; ?>/js/admin.js'></script>
</head>
<body>
<div class="header">
	<div class="user_info">
		<span><?php echo Yii::app()->user->nick; ?></span>&nbsp;&nbsp;
		<span><?php if(Yii::app()->user->level>0){echo "有效期至".date('Y-m-d',strtotime(Yii::app()->user->deadline));} ?></span>
		<a onclick="javascript:logout();">退出</a>
	</div>
</div>
<div class='content'>
	<div class='menu'>
		<ul class="menu-ul">
		<?php foreach ($this->menu as $value) {
			if(isset($value['childMenu'])){ ?>
			<li class='menu-li-f'>
				<span class='child-menu-label '><?php  echo $value['label'];?><i class='arrow-flag'></i></span>
				<ul class="child-menu-ul <?php if(in_array($this->action->id, $value['ctr'])){echo 'open';} ?>">
				<?php foreach ($value['childMenu'] as $val) { ?>
					<li><a <?php if(strstr(Yii::app()->request->url,$val['url'])){echo "style='color:#F36A5A;background-color:#3e3e3e'";} ?> href="<?php echo $this->createUrl($val['url']); ?>"> <?php echo $val['label']; ?></a></li>
				<?php } ?>
				</ul>
			</li>
			<?php }else{ ?>
				<li><a <?php if(strstr(Yii::app()->request->url,$value['url'])){echo "style='color:#F36A5A;background-color:#3e3e3e'";} ?> href="<?php echo $this->createUrl($value['url']); ?>"> <?php echo $value['label']; ?></a></li>
		<?php }}?>
		</ul>
	</div>

	<div class='right-content'>
		<?php echo $content;?>		
	</div>
</div>

</body>
</html>