/*global $,window,document*/
/**
 * Created by gosha on 12/12/13.
 */

var Config = Config || {};

$(function () {
    'use strict';
    Config.tooltip_config = {delay: {show: 500, hide: 0}};
    // Highlight current anchor
    var path = window.location.pathname;
    $('a[href="' + path + '"]').addClass('selected');
});

/**
 * Applies & cancels ajax loading effect on divs.
 * Example usage: when loading a different set of posts.
 * @type {{apply: Function, cancel: Function}}
 */
var LoadingEffect = {
    apply: function (elem$) {
        'use strict';
        if (elem$.hasClass('le-loading')) { return; }
        var wrapper$ = '<div class="le-wrapper"></div>',
            overlay$ = '<div class="le-overlay"></div>';
        elem$.addClass('le-loading').wrap(wrapper$).after(overlay$);
    },
    cancel: function (elem$) {
        'use strict';
        elem$.removeClass('le-loading').unwrap().siblings('.le-overlay').remove();
    }
};

function d(myvar) {
    'use strict';
    window.console.log(myvar);
}

function preload(arrayOfImages) {
    'use strict';
    $(arrayOfImages).each(function () {
        $('<img/>')[0].src = this;
    });
}

$(document).ready(function () {
    'use strict';
    $(".pop-up").fancybox({
        maxWidth: 1000,
        maxHeight: 800,
        fitToView: true,
        autoSize: true,
        closeClick: false,
        openEffect: 'none',
        closeEffect: 'none',
        openSpeed: 0,
        closeSpeed: 0,
        scrolling: 'no'
    });
});
