"use strict";

var Notification = {
    ready: function() {
        $("table#notification-table").DataTable( {
            "order": [[ 0, "desc" ]],
            columnDefs: [  {
                    targets: [ 3 ],
                    orderable: false,
                    searchable: false
                }
            ]
        } );
    }
    
};

$(document).ready(function() {
        Notification.ready();
    }
);
