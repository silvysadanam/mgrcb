"use strict";

$(document).ready(function () {
        $("table#list-table-13").DataTable( {
                "aaSorting" : [],
                "lengthMenu": [[20, 50, 100, 200, 300, 500], [20, 50, 100, 200, 300, 500]],
                columnDefs: [ {
                        targets: [ 13 ],
                        searchable: false
                    }
                ]
            }
        );
    }
);
