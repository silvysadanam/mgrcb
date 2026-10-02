"use strict";

var Advance = {
    ready: function () {
        $("table#list-table-10").DataTable( {
                "aaSorting" : [],
                "lengthMenu": [[20, 50, 100, 200], [20, 50, 100, 200]],
                columnDefs: [ {
                        targets: [ 10 ],
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
