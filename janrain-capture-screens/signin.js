//set the flow name for this screen here
janrain.settings.capture.flowName = 'plugins';
janrain.settings.width = 310;

// call our code/token exchanger, and use the token to set up a capture session
function getTokenForCode(code, redirect_uri) {
	var url = '/wp-admin/admin-ajax.php?action=janrain_capture_redirect_uri';
	url += "&code=" + code;
	window.location.href = url;
}

function janrainReturnExperience() {
	var span = document.getElementById('traditionalWelcomeName');
	var name = janrain.capture.ui.getReturnExperienceData("displayName");
	if (span && name) {
		span.innerHTML = "Welcome back, " + name + "!";
	}
}
function janrainCaptureWidgetOnLoad() {
    //check for access token in localStorage and create session
    if(localStorage && localStorage.getItem("janrainCaptureTokenWP")) {
        janrain.capture.ui.createCaptureSession(localStorage.getItem("janrainCaptureTokenWP"));
        localStorage.removeItem("janrainCaptureTokenWP");
    }

    function handleCaptureLogin(result) {
        // console.log ("exchanging code for token...");
        getTokenForCode(result.authorizationCode, janrain.settings.capture.redirectUri);
    }

    janrain.events.onCaptureSessionFound.addHandler(function(result){
	    // console.log ("capture session found");
    });
    janrain.events.onCaptureSessionNotFound.addHandler(function(result){
	    // console.log ("capture session not found");
    });
	janrain.events.onCaptureAccessDenied.addHandler(function(result){
		janrain.capture.ui.createCaptureSession(access_token);
	});
	janrain.events.onCaptureScreenShow.addHandler(function(result){
		if (result.screen == "returnTraditional") {
			janrainReturnExperience();
		}
	});
    janrain.events.onCaptureLoginSuccess.addHandler(handleCaptureLogin);
    janrain.events.onCaptureRegistrationSuccess.addHandler(handleCaptureLogin);

    janrain.capture.ui.start();

}
