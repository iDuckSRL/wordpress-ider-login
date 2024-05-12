if (typeof jQuery !== 'undefined') {
    jQuery(function() {
        jQuery('#advancedbtn').on('click', function(e) {
            e.preventDefault();

            jQuery('#advancedopts').fadeToggle();
        })
    });
}
