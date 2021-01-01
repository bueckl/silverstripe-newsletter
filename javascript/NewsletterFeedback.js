(function($){
    var feedback_button = '.action_doFeedback';
    $(feedback_button).on('click', function (e) {
        e.preventDefault()
        let id = $("input[name=Newsletter]").val();
        let email = $("input[name=Email]").val();
        let message = $("input[name=Message]").val();

        $.ajax({
            url: 'newsletter/processFeedback',
            type: 'post',
            dataType: 'text',
            data: {
                id : id,
                email : email,
                message : message
            },
            success: function (results) {
                console.log(results)
            },
            error: function (jqXHR, exception) {
                var msg = '';
                if (jqXHR.status === 0) {
                    msg = 'Not connect.\n Verify Network.';
                } else if (jqXHR.status == 404) {
                    msg = 'Requested page not found. [404]';
                } else if (jqXHR.status == 500) {
                    msg = 'Internal Server Error [500].';
                } else if (exception === 'parsererror') {
                    msg = 'Requested JSON parse failed.';
                } else if (exception === 'timeout') {
                    msg = 'Time out error.';
                } else if (exception === 'abort') {
                    msg = 'Ajax request aborted.';
                } else {
                    msg = 'Uncaught Error.\n' + jqXHR.responseText;
                }
                console.log(msg)
            }
        });


        return false;
    });
})(jQuery);