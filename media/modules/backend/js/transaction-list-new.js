"use strict";

var Receipt = {
    modalBlock: null,
    resultContainer: null,
    ready: function () {
        $("table#list-table-6").DataTable( {
                "aaSorting" : [],
                "lengthMenu": [[20, 50, 100, 200], [20, 50, 100, 200]],
                columnDefs: [ {
                        targets: [ 6 ],
                        searchable: false
                    }
                ]
            }
        );

        Receipt.modalBlock = $("div#modal-summary");
        Receipt.resultContainer = Receipt.modalBlock.find("div.modal-body:first");
        $("table#list-table-6").on("click", "a.quick-view", Receipt.showSummary);
    },
    
    showSummary: function () {
        var auctionChitId = $(this).data("id");
        var formAction = $("input#summary-url").val() + auctionChitId;
        $.post(formAction, { id : auctionChitId }, function(reply) {
                if (reply.success && reply.valid) {
                    Receipt.resultContainer.html(reply.html);
                    Receipt.modalBlock.modal("show");
                } else if (reply.redirect) {
                    BranchCommon.reloadPage();
                } else if (reply.message) {
                    BranchCommon.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    }
};

$(document).ready(function () {
        Receipt.ready();
    }
);
