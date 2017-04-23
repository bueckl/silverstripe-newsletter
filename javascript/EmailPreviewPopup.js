
(function($){

    $.entwine('ss', function($){

        var popup = $('.popup');
        if(popup.length == 0){
            $('body').append('<div class="popup"></div>');
            popup = $('.popup');
        }
        var dialog = popup.dialog({
            autoOpen: false,
            height: 550,
            width: 660,
            modal: true,
            buttons: [
                {
                  text: "OK",
                  click: function() {
                    $( this ).dialog( "close" );
                  }
                }
              ]
        });
        var popup_button_selector = '#action_doPreviewEmail';
        $(popup_button_selector).entwine({

            onclick: function(e){
                // Show loading indicator
                this.addClass('loading');
                // Trigger save button
                this.parents('form').trigger('submit', [this.parents('form').find('[name=action_doSave]')]);
                var url = this.data('url');
                // Wait for 1s before we query ajax and show the dialog
                setTimeout(function() {
                    $.ajax({
                        url         : url,
                        'success'   : function(data){
                            dialog.html(data);
                            dialog.dialog( "open" );
                        }
                    });
                }, 1000);

                return false;
            }
        });
    });
})(jQuery);
