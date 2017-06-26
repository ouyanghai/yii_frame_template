$(function(){
	$("#login-btn").click(function(){
		var name = $("#login-name").val();
		var pass = $("#login-password").val();
		$.ajax({
			type:'post',
			dataType:'json',
			url:'/auth/login',
			data:{'username':name,"password":pass},
			success:function(msg){
				if(msg.status=='error'){
					return false;
				}
				window.location.href="/web/index";
			}
		});
	});

	$("#logout-btn").click(function(){
		$.ajax({
			type:'post',
			dataType:'json',
			url:'/admin/logout',
			data:{},
			success:function(msg){
				if(msg.status=='error'){
					return false;
				}
				window.location.href="/web/index";
			}
		});
	});
});