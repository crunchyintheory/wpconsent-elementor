window.addEventListener("wpconsent_consent_saved", function() {
    //window.location.reload();
});
window.addEventListener("wpconsent_consent_processed", function(event) {
    const preferences = event.detail;
    console.log(preferences);
});

const existingPreferences = window.WPConsent.getCookie( 'wpconsent_preferences' );
let preferences = {};
try {
    // Check if the preferences are valid JSON.
    preferences = JSON.parse(existingPreferences);

    // Check if preferences keys match current slugs
    if (
        window.wpconsent.slugs && Array.isArray(window.wpconsent.slugs) &&
        !window.wpconsent.slugs.every(slug => preferences.hasOwnProperty(slug))
    ) {
        preferences = {};
    }
} catch ( e ) {
    console.error('Error parsing WPConsent preferences:', e);
}

document.querySelectorAll(".lx-wpconsent-blocked-widget").forEach(el => {
    // last argument required in IE11
    let service = el.firstElementChild.getAttribute("data-wpconsent-name");
    let category = el.firstElementChild.getAttribute("data-wpconsent-category");
    if(window.WPConsent.shouldUnlockContent(preferences, service, category)) {
        const it = document.createNodeIterator(el, NodeFilter.SHOW_COMMENT, () => NodeFilter.FILTER_ACCEPT, false);
        el.outerHTML = it.nextNode().nodeValue;
    }
});

document.querySelectorAll(".elementor-element[data-settings*='background_video_link']").forEach(el => {
    let settings = JSON.parse(el.getAttribute("data-settings"));
    let videoLink = settings.background_video_link;
    let service;
    if (-1 !== videoLink.indexOf("vimeo.com")) {
        service = "vimeo";
    } else if (videoLink.match(/^(?:https?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube\.com)/)) {
        service = "youtube";
    }
    else {
        return;
    }
    if(!window.WPConsent.shouldUnlockContent(preferences, service, "marketing")) {
        delete settings.background_video_link;
        el.setAttribute("data-settings", JSON.stringify(settings));
    }
});
