jQuery(function(){
  function parseDim(rel, dim) {
    var r = rel.split(';');
    for (var i in r) {
      var k = r[i].split(':');
      if (k[0].indexOf(dim) > -1) {
        return k[1];
      }
    }
  }
	jQuery(".modal-link").colorbox({iframe:true, width:function(){return parseDim(jQuery(this).attr('rel'), 'width');}, height:function(){return parseDim(jQuery(this).attr('rel'), 'height');}, scrolling: false, overlayClose: false, current: '', next: '', previous: ''});
	
	// Rewrite the logout link in the top bar. 
	// TODO: This does not reference the logout link in the admin pages.
	var logout_link = jQuery("#wp-admin-bar-logout > a").attr("href") + "&redirect_to=" + encodeURI(document.location.href);
	jQuery("#wp-admin-bar-logout > a").attr("href", logout_link);
});

var CAPTURE = {
  resize: function(jargs) {
    var args = jQuery.parseJSON(jargs);
    jQuery.colorbox.resize({ innerWidth: args.w, innerHeight: args.h });
    if(typeof janrain_capture_on_resize == 'function') {
      janrain_capture_on_resize(args);
    }
  },
  closeAuth: function() {
    jQuery.colorbox.close();
    if(typeof janrain_capture_on_close_auth == 'function') {
      janrain_capture_on_close_auth();
    }
  },
  closeRecoverPassword: function() {
    jQuery.colorbox.close();
    if(typeof janrain_capture_on_close_recover_password == 'function') {
      janrain_capture_on_close_recover_password();
    }
  },
  closeProfile: function() {
    jQuery.colorbox.close();
    if(typeof janrain_capture_on_close_profile == 'function') {
      janrain_capture_on_close_profile();
    }
  },
  token_expired: function() {
    jQuery.ajax({
      url: ajaxurl,
      data: {
        action: 'janrain_capture_refresh_token',
        refresh_token: this.read_cookie('janrain_capture_refresh_token')
      },
      dataType: 'json',
      success: function(data) {
        CAPTURE.save_tokens(data.access_token, data.refresh_token, data.expires_in);
        var iframe = jQuery(".cboxIframe");
        var src = iframe.attr('src');
        src = src.replace(/[\?\&]access_token\=[^\&]+/,'');
        var sep = (src.indexOf('?') > 0) ? '&' : '?';
        iframe.attr('src', src + sep + 'access_token=' + data.access_token);
      }
    });
  },
  save_tokens: function(access_token, refresh_token, expires_in) {
    var xdate=new Date();
    xdate.setSeconds(xdate.getSeconds()+expires_in);
    document.cookie='janrain_capture_access_token='+access_token+'; expires='+xdate.toUTCString() + '; path=/';
    var ydate=new Date();
    ydate.setDate(ydate.getDate()+30);
    document.cookie='janrain_capture_refresh_token='+refresh_token+'; expires='+ydate.toUTCString() + '; path=/';
  },
  read_cookie: function(k,r){return(r=RegExp('(^|; )'+encodeURIComponent(k)+'=([^;]*)').exec(document.cookie))?r[2]:null;},
  bp_ready: function() {
    if (typeof(window.Backplane) != 'undefined') {
      var channelId = Backplane.getChannelID();
      if (typeof(channelId) != 'undefined' && typeof(janrain_capture_on_bp_ready) == 'function')
        janrain_capture_on_bp_ready(channelId);
    }
  }
}
