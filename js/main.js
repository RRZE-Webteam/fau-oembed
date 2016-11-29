
jQuery(function ($) {


    // Set all variables to be used in scope
    var frame,
            metaBox = $('#meta-box-id.postbox'); // Your meta box id here

    addPlaceholderImgLink = $(document).find('#fau-embed-add-place-holder-img');
    imgIdInput = $("#fau-embed-place-holder"),
            delImgLink = $('#fau-embed-place-holder-delete-img'),
            imgPreview = $("#fau-embed-place-holder-img-preview"),
            // ADD IMAGE LINK



            addPlaceholderImgLink.on('click', function (event) {

                event.preventDefault();

                // If the media frame already exists, reopen it.
                if (frame) {
                    frame.open();
                    return;
                }

                // Create a new media frame
                frame = wp.media({
                    title: 'Select or Upload Media Of Your Chosen Persuasion',
                    button: {
                        text: 'Use this media',
                    },
                    library: {
                        type: 'image' // limits the frame to show only images
                    },
                    multiple: false  // Set to true to allow multiple files to be selected
                });


                // When an image is selected in the media frame...
                frame.on('select', function () {

                    // Get media attachment details from the frame state
                    var attachment = frame.state().get('selection').first().toJSON();

                    // Send the attachment URL to our custom image input field.

                    imgPreview.attr('src', attachment.url);
                    imgPreview.show();

                    // Send the attachment id to our hidden input
                    imgIdInput.val(attachment.id);
                    // Hide the add image link
                    delImgLink.show();
                    // Unhide the remove image link

                });

                // Finally, open the modal on click
                frame.open();
            });

    delImgLink.on('click', function (event) {


        event.preventDefault();
         
        jQuery("#fau-embed-place-holder").val('');
      
        imgPreview.attr('src',oembed_default_place_holder_img_url);

        delImgLink.hide();

    });
    // DELETE IMAGE LINK


});
