function makeLink() {
	
	u = 'https://demo.janraincapture.com/oauth/signin?response_type=code&redirect_uri=http%3A%2F%2Flocalhost%3A8888%2FCapture.Demo%2Foauth_redirect.php&client_id=4t2v8wg2npvxwsx67bjawxx4jr6uj9cx&xd_receiver=http%3A%2F%2Flocalhost%3A8888%2FCapture.Demo%2Fxdcomm.html&recover_password_callback=CAPTURE.recoverPasswordCallback&bp_channel=http%3A%2F%2Fapi.js-kit.com%2Fv1%2Fbus%2Frpxstaging%2Fchannel%2F132985991224926094';
	var link = '<a id="login_link" href="' + u + '">I am a Colorbox Capture Signin Link</a>';
	
	x_scroll_pos = window.pageXOffset, y_scroll_pos = window.pageYOffset;
	var login_element = document.createElement('div');
	$(login_element).attr({id:'login_element'}).css({'width':'250px','height':'30px' ,'position':'absolute','z-index':'2147483647 !important'}).html(link);
	document.body.insertBefore(login_element, document.body.firstChild);
	
}

$(document).ready(function(){
//	makeLink();
	$("#login_link").colorbox({iframe:true, width:"80%", height:"80%"});
	
});
