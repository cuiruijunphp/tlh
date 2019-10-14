function pay(){
	$('#ewm').attr('src', '')
    $('.abs_mid_win').show();
    $.get('/manage/pay/getCodeUrl', function(r) {
    	if (r.code == 0) {
    		console.log(r.data.url)

    		var init = setInterval(function () {
    			$.get('/manage/pay/checkCallBack?od=' + r.data.ordernum, function(re) {
    				console.log(re)
    				if (re.code == 0) {
    					init = window.clearInterval(init)
                        window.location.href = '/manage/pay/finished?od=' + r.data.ordernum;
    					// window.location.href = '/manage/curriculum/list';
    				}
    			})
    		}, 2000);

    		$('#ewm').attr('src', r.data.url)
    	}
    })
}



function close_win(){
    $('.abs_mid_win').hide();
}