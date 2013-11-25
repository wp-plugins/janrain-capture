jQuery(function(){
  function parseDim(rel, dim) {
    var r = rel.split(';');
    for (var i in r) {
      if ( !r.hasOwnProperty(i) || r[i].indexOf(':') === false )
        continue;
      var k = r[i].split(':');
      if (k[0].indexOf(dim) > -1) {
        return k[1];
      }
    }
  }
  var jcbo = {
	    iframe: true,
	    width: function() {
	      if (jQuery(this).attr('rel'))
	        return parseDim(jQuery(this).attr('rel'), 'width');
	      else
	        return '700px';
	    },
	    height: function() {
	      if (jQuery(this).attr('rel'))
	        return parseDim(jQuery(this).attr('rel'), 'height');
	      else
	        return '700px';
	    },
	    scrolling: false,
	    overlayClose: false,
	    current: '',
	    next: '',
	    previous: ''
	  };
  jQuery("#wp-admin-bar-my-account a:not('#wp-admin-bar-logout a')").colorbox(jcbo);
  jQuery(".janrain_capture_anchor").colorbox(jcbo);
});

var CAPTURE = {
  resize: function(jargs) {
    var args = jQuery.parseJSON(jargs);
    jQuery.colorbox.resize({ innerWidth: args.w, innerHeight: args.h });
    if(typeof(janrain_capture_on_resize) == 'function') {
      janrain_capture_on_resize(args);
    }
  },
  closeAuth: function() {
    jQuery.colorbox.close();
    if(typeof(janrain_capture_on_close_auth) == 'function') {
      janrain_capture_on_close_auth();
    } else {
      location.reload(true);
    }
  },
  closeRecoverPassword: function() {
    jQuery.colorbox.close();
    if(typeof(janrain_capture_on_close_recover_password) == 'function') {
      janrain_capture_on_close_recover_password();
    }
  },
  closeProfile: function() {
    jQuery.colorbox.close();
    CAPTURE.profileUpdate();
    if(typeof(janrain_capture_on_close_profile) == 'function') {
      janrain_capture_on_close_profile();
    }
  },
  profileUpdate: function() {
    jQuery.ajax({
      url: ajaxurl,
      method: 'POST',
      data: {
        action: 'janrain_capture_profile_update'
      },
      success: function(data) {
        if (data == '1') {
          if (typeof(janrain_capture_on_profile_update) == 'function')
            janrain_capture_on_profile_update();
          else
            location.reload(true);
        }
      }
    });
  },
  token_expired: function() {
    if (typeof(janrain_capture_on_token_expired) == 'function') {
      janrain_capture_on_token_expired();
    }
  },
  bp_ready: function() {
    if (typeof(window.Backplane) != 'undefined') {
      var channelId = Backplane.getChannelID();
      if (typeof(channelId) != 'undefined' && typeof(janrain_capture_on_bp_ready) == 'function')
        janrain_capture_on_bp_ready(channelId);

      channelId = encodeURIComponent(channelId);
      var ssotrue = false;
      var ssojs = null;
      jQuery('script').each(function() {
			if(jQuery(this).attr('src')) {
			  ssojs = jQuery(this).attr('src');
		      if ( undefined != ssojs && ssojs.search(/sso.js/i) > 0 ) { 
		    	  ssotrue = true;
		    	  return false;
		      }
			}
	      });
      if (ssotrue) { 
		// do sso - 
		console.log('Federated');
		sso_login_obj.bp_channel = Backplane.getChannelID();
		JANRAIN.SSO.CAPTURE.check_login(sso_login_obj);
      }
      jQuery('a.janrain_capture_signin').each(function(){
        jQuery(this).attr("href", jQuery(this).attr("href") + "&bp_channel=" + channelId).click(function(){
          Backplane.expectMessages("identity/login");
        });
      });
    }
  }
}
