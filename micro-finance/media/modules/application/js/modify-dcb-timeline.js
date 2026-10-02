"use strict";

var ModifyDcb = {
    ready: function () {
        $("table#table-timeline").DataTable( {
                "lengthMenu": [[20, 50, 100, 200], [20, 50, 100, 200]],
                "order": [[ 0, "desc" ]],
                columnDefs: [ {
                        targets: [ 0 ],
                        searchable: false
                    }
                ]
            }
        );
    }
    
};

$(document).ready(function () {
        ModifyDcb.ready();
    }
);
