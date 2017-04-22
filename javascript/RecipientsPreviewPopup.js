
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
            width: 800,
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


        var popup_button_selector = '#action_doPreviewRecipients';
        var message_form_selector = '#Form_MessageForm';

        $(popup_button_selector).entwine({

            onclick: function(e){

                var url = this.data('url');
                var EditFormURL = this.data('edit-form-url');
                alert(url);
                $.ajax({
                    url         : url,
                    'success'   : function(data){
                        dialog.html(data);
                        dialog.dialog( "open" );
                    }
                });
                return false;
            }

        });


    });


})(jQuery);
