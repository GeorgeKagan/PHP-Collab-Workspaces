var Whiteboard = {
    defMegaAdderWidth: 910,
    defMegaAdderHeight: 500,
    handleImage: function(file){
        var reader = new FileReader();
        reader.onload = function(event){
            var img = new Image();
            img.className  = "annotatable";
            img.onload = function(){
                Wall.PostAdder.hideNonImportantUi();
                var newDimens = Whiteboard.Canvas.resizeImageObject(img);
                $('#post-add-upload-preview').append(img);
                $('#mega-adder').addClass('annotating');
                Whiteboard.Canvas.drawImage(img, newDimens);
                anno.makeAnnotatable($('#post-add-upload-preview .annotatable')[0]);
                Wall.iso.layout();
            };
            img.src = event.target.result;
        };
        reader.readAsDataURL(file);
    },
    /**
     * Show an icon representing a user drawing on his whiteboard
     * Info pushed from nodejs server
     * @param user
     */
    broadcastActivity: function(user) {
        var name = user.name.replace(' ', '_');
        var id = 'wb-' + name;
        if ($('#' + id).length) {
            return;
        }
        var avatar = user.fb_user ? 'http://graph.facebook.com/' + user.fb_user + '/picture' : '/assets/img/default-avatar.jpg';
        var classes = user.type === 'tutor' ? 'tutor' : '';
        var $icon = $('<small id="' + id + '" title="' + user.name + '" href="#fwb_' + name + '">' +
            '<span class="online"></span>' +
            '<img class="img-circle ' + classes + '" src="' + avatar + '"  width="30" height="30"></small>');
        var tooltip_config = Config.tooltip_config;
        tooltip_config.placement = 'bottom';
        $icon.appendTo('#wb-activities').tooltip(tooltip_config);
    }
};

Whiteboard.Canvas = {
    resizeImageObject: function(img) {
        var maxWidth = 1168; // Max width for the image
        var maxHeight = 1400;    // Max height for the image
        var ratio = 0;  // Used for aspect ratio
        var width = img.width;    // Current image width
        var height = img.height;  // Current image height
        var new_width = width, new_height = height;
        var resized = false;
        // Check if the current width is larger than the max
        if (width > maxWidth){
            ratio = maxWidth / width;   // get ratio for scaling image
            new_width = maxWidth; // Set new width
            new_height = height * ratio;  // Scale height based on ratio
            height = height * ratio;    // Reset height to match scaled image
            width = width * ratio;    // Reset width to match scaled image
            resized = true;
        }
        // Check if current height is larger than max
        if (height > maxHeight){
            ratio = maxHeight / height; // get ratio for scaling image
            new_height = maxHeight;   // Set new height
            new_width = width * ratio;    // Scale width based on ratio
            width = width * ratio;    // Reset width to match scaled image
            height = height * ratio;    // Reset height to match scaled image
            resized = true;
        }
        if (!resized && (width > Whiteboard.defMegaAdderWidth || height > Whiteboard.defMegaAdderHeight)) {
            resized = true;
        }
        return {resized: resized, width: new_width, height: new_height};
    },
    drawImage: function(img, newDimens) {
        img.width = newDimens.width;
        img.height = newDimens.height;
        $('#post-add-upload-preview > img').css({width: img.width, height: img.height});
    }
};