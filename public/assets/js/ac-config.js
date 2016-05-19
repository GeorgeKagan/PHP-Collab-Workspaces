/*global window*/
/**
 * Add members that represent features you might want to toggle on/off during development.
 * Then check using isEnabled in code.
 * true = enabled, false = disabled
 * On non-dev envs, all features are enabled
 * * This file should not be tracked for changes
 */
var AcConfig = {
    chat: false,
    isEnabled: function (feature) {
        'use strict';
        if (window.env !== 'dev') {
            return true;
        }
        return AcConfig[feature];
    }
}