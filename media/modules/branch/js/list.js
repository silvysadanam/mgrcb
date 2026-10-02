"use strict";

$(document).ready(function () {
        for (var columns = 2; columns < 10; columns++) {
            var table = "table#list-table-" + columns.toString();
            if ($(table).length > 0) {
                $(table).DataTable( {
                        "aaSorting" : [],
                        "lengthMenu": [[20, 50, 100, 200], [20, 50, 100, 200]],
                        columnDefs: [ {
                                targets: [ columns ],
                                searchable: false
                            }
                        ]
                    }
                );
            }
        }
    }
);
