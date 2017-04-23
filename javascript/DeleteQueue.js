
(function($){

    $.entwine('ss', function($){
        $('#action_doDeleteQueue').entwine({
            onclick: function(e){
                var $this = $(this);
                if (confirm('Are you sure you want to delete the queue?')) {
                    $this.parents('form').trigger('submit', [this.parents('form').find('[name=action_doDeleteQueue]')]);
                    return true;
                } else {
                    return false;
                }
            }
        });
    });
})(jQuery);
