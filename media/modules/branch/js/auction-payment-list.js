"use strict";

var AuctionPayment = {
    modalBlock: null,
    resultContainer: null,
    ready: function () {
        $("table#list-table-5").DataTable( {
                "aaSorting" : [],
                "lengthMenu": [[20, 50, 100, 200], [20, 50, 100, 200]],
                columnDefs: [ {
                        targets: [ 5 ],
                        searchable: false
                    }
                ]
            }
        );
    }
};

$(document).ready(function () {
        AuctionPayment.ready();
    }
);
