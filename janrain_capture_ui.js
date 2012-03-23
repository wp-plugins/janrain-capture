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
  jQuery(".modal-link").colorbox({
    iframe: true,
    width: function() {
      return parseDim(jQuery(this).attr('rel'), 'width');
    },
    height: function() {
      return parseDim(jQuery(this).attr('rel'), 'height');
    },
    scrolling: false,
    overlayClose: false,
    current: '',
    next: '',
    previous: ''
  });
  if (CAPTURE.read_cookie('janrain_capture_refresh_token')) {
    jQuery(".janrain_capture_anchor.capture-auth").show();
  } else {
    jQuery(".janrain_capture_anchor.capture-anon").show();
  }
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
    }
    jQuery(".janrain_capture_anchor.capture-anon").hide();
    jQuery(".janrain_capture_anchor.capture-auth").show();
  },
  closeRecoverPassword: function() {
    jQuery.colorbox.close();
    if(typeof(janrain_capture_on_close_recover_password) == 'function') {
      janrain_capture_on_close_recover_password();
    }
  },
  closeProfile: function() {
    jQuery.colorbox.close();
    CAPTURE.save_user_attrs();
    if(typeof(janrain_capture_on_close_profile) == 'function') {
      janrain_capture_on_close_profile();
    }
  },
  token_expired: function() {
    jQuery.ajax({
      url: ajaxurl,
      data: {
        action: 'janrain_capture_refresh_token',
        refresh_token: CAPTURE.read_cookie('janrain_capture_refresh_token')
      },
      dataType: 'json',
      success: function(data) {
        CAPTURE.save_tokens(data.access_token, data.refresh_token, data.expires_in);
        var iframe = jQuery(".cboxIframe");
        var src = iframe.attr('src');
        src = src.replace(/([\?\&]access_token\=)[^\&]+/, function(m, g) {
          return g + data.access_token;
        });
        iframe.attr('src', src);
      }
    });
  },
  logout: function() {
    document.cookie = 'janrain_capture_access_token=; expires=Thu, 01-Jan-70 00:00:01 GMT; path=/';
    document.cookie = 'janrain_capture_refresh_token=; expires=Thu, 01-Jan-70 00:00:01 GMT; path=/';
    document.cookie = 'janrain_capture_user_attrs=; expires=Thu, 01-Jan-70 00:00:01 GMT; path=/';
    if (typeof(window.Backplane) != 'undefined')
      document.cookie = 'backplane-channel=; expires=Thu, 01-Jan-70 00:00:01 GMT; path=/';
    if (typeof(janrain_capture_on_logout) == 'function')
      janrain_capture_on_logout();
  },
  save_tokens: function(access_token, refresh_token, expires_in) {
    var xdate=new Date();
    xdate.setSeconds(xdate.getSeconds()+expires_in);
    document.cookie='janrain_capture_access_token='+access_token+'; expires='+xdate.toUTCString() + '; path=/';
    var ydate=new Date();
    ydate.setDate(ydate.getDate()+janrain_capture_refresh_duration);
    document.cookie='janrain_capture_refresh_token='+refresh_token+'; expires='+ydate.toUTCString() + '; path=/';
  },
  save_user_attrs: function() {
    jQuery.ajax({
      url: ajaxurl,
      data: {
        action: 'janrain_capture_profile_update',
        access_token: CAPTURE.read_cookie('janrain_capture_access_token')
      },
      dataType: 'text',
      success: function(userData) {
        if (userData != '-1') {
          var expires = CAPTURE.read_cookie('janrain_capture_expires');
          document.cookie='janrain_capture_user_attrs='+encodeURIComponent(userData)+'; expires='+expires+'; path=/';
        }
      }
    });
  },
  read_cookie: function(k,r){return(r=RegExp('(^|; )'+encodeURIComponent(k)+'=([^;]*)').exec(document.cookie))?r[2]:null;},
  bp_ready: function() {
    if (typeof(window.Backplane) != 'undefined') {
      var channelId = Backplane.getChannelID();
      if (typeof(channelId) != 'undefined' && typeof(janrain_capture_on_bp_ready) == 'function')
        janrain_capture_on_bp_ready(channelId);
      jQuery('a.capture-anon').each(function(){
        channelId = encodeURIComponent(channelId);
        jQuery(this).attr("href", jQuery(this).attr("href") + "&bp_channel=" + channelId).click(function(){
          Backplane.expectMessages("identity/login");
        });
      });
    }
  }
}
