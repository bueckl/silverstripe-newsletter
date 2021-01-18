
(function($){
    $.entwine('ss', function($) {
        $('table.checkboxsetwithextrafield').entwine({
            UUID: null,
            onmatch: function() {
                this._super();
                this.tableDnD({
                    onDragClass: "DnD_whiledragging",
                    dragHandle: ".dragHandle",
                    onDragStart: function(table, row) {
                        indicator = document.createElement('div');
                        $(indicator).html('Moving ...');
                        $(row).append($(indicator));
                    },
                    onDrop:function(table, row){

                        // var items = table.attr( "data-item" );
                        var tablethead = table.getElementsByTagName('thead')[0];
                        var tabletbody = table.getElementsByTagName('tbody')[0];

                        // var arr = Array.prototype.slice.call( tabletbody.getElementsByTagName('tr') );
                        var nameItem = [];
                        for (let item of tabletbody.getElementsByTagName('tr') ) {
                            nameItem.push(item.getAttribute('data-item'));
                        }

                        var nameItemJson = { ...nameItem };
                        var pageID = tablethead.getAttribute('data-page');

                        $.ajax({
                            type: "POST",
                            url: '/subscribe/checkboxsetsort',
                            data: {
                                page: pageID,
                                items : nameItemJson
                            },
                            success: (data) => {
                                console.log('....success')
                                // console.log(data)
                            }
                        });


                        jQuery($(row).find(".dragHandle")[0]).empty();

                    }
                });
            },
            onunmatch: function() {
                this._super();
            }
        });
        $('table.checkboxsetwithextrafield tbody tr').entwine({
            onmouseover: function() {
                jQuery($(this).children(".dragHandle")[0]).addClass('showDragHandle');
            },
            onmouseout: function() {
                jQuery($(this).children(".dragHandle")[0]).removeClass('showDragHandle');
            },
            onmouseup: function() {
                jQuery($(this).find(".dragHandle")[0]).empty();
            }
        });
    });
}(jQuery));
