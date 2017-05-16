
(function($){

    $.entwine('ss', function($){

        var popup = $('.popup-processQueue');
        if(popup.length == 0){
            $('body').append('<div class="popup-processQueue cms-content-header h2"></div>');
            popup = $('.popup-processQueue');
        }
        var dialog = popup.dialog({
            autoOpen: false,
            height: 200,
            width: 600,
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
        var opened = false;
        var closed = false;
        popup.on( "dialogclose", function( event, ui ) {
            opened = false;
            closed = true;
        } );

        var popup_button_selector = '#action_doProcessQueue';
        $(popup_button_selector).entwine({

            onclick: function(e){
                closed = false;
                var $this = this;
                $this.addClass('loading');
                var url = this.data('url');
                var poll = function() {
                    $.get(url, function(data) {
                        $this.removeClass('loading');
                        var html = '';
                        if (data.Status == 'Sent') {
                            html = '<h2>Processing done</h2>';
                        } else {
                            html = '<h3>Processing. Remaining to be processed:</h3><h2>' + data.Remaining + '/' + data.Total + '</h2><em>Don\'t close this modal before processing is done!</em>';
                        }
                        dialog.html(html);
                        if (!opened && !closed) {
                            dialog.dialog( "open" );
                            opened = true;
                        }
                        if (!closed) {
                            if (data.Status == 'Sending') {
                                setTimeout(function() {
                                    poll();
                                }, 500);
                            } else if (data.Status == 'Sent') {
                                location.reload();
                            }
                        }
                    }, 'json')
                };
                poll();
                return false;
            }
        });
    });
})(jQuery);
