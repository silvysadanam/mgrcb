"use strict";

var AuctionChit = {
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

        AuctionChit.modalBlock = $("div#modal-summary");
        AuctionChit.resultContainer = AuctionChit.modalBlock.find("div.modal-body:first");
        $("table#list-table-6").on("click", "a.quick-view", AuctionChit.showSummary);
    },
    
    showSummary: function () {
        var auctionChitId = $(this).data("id");
        var formAction = $("input#summary-url").val() + auctionChitId;
        $.post(formAction, { id : auctionChitId }, function(reply) {
                if (reply.success && reply.valid) {
                    AuctionChit.resultContainer.html(reply.html);
                    AuctionChit.modalBlock.modal("show");
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
        AuctionChit.ready();
    }
);
