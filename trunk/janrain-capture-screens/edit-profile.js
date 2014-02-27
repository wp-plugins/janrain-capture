janrain.settings.capture.flowName = 'plugins';
janrain.settings.capture.screenToRender = 'editProfile';
janrain.settings.width = 310;

function janrainCaptureWidgetOnLoad() {
    // check for access token in localStorage and create session
    if(localStorage && localStorage.getItem("janrainCaptureTokenWP")) {
        janrain.capture.ui.createCaptureSession(localStorage.getItem("janrainCaptureTokenWP"));
        localStorage.removeItem("janrainCaptureTokenWP");
    }
    janrain.capture.ui.start();
}
