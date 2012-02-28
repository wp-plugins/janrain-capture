$(document).ready(function(){
	$("#login_link").colorbox({iframe:true, width:"80%", height:"80%"});
	
	// Rewrite the logout link in the top bar. 
	// TODO: This does not reference the logout link in the admin pages.
	var logout_link = $("#wp-admin-bar-logout > a").attr("href") + "&redirect_to=" + encodeURI(document.location.href);
	$("#wp-admin-bar-logout > a").attr("href", logout_link);
});
