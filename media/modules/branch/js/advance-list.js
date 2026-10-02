"use strict";

var Advance = {
    ready: function () {
        $("table#list-table-8").DataTable( {
                "aaSorting" : [],
                "lengthMenu": [[20, 50, 100, 200], [20, 50, 100, 200]],
                columnDefs: [ {
                        targets: [ 8 ],
                        searchable: false
                    }
                ]
            }
        );
    }
};

$(document).ready(function () {
        Advance.ready();
    }
);
