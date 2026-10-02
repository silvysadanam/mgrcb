"use strict";

var Voucher = {
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

        Voucher.modalBlock = $("div#modal-summary");
        Voucher.resultContainer = Voucher.modalBlock.find("div.modal-body:first");
        $("table#list-table-6").on("click", "a.quick-view", Voucher.showSummary);
    },
    
    showSummary: function () {
        var auctionChitId = $(this).data("id");
        var formAction = $("input#summary-url").val() + auctionChitId;
        $.post(formAction, { id : auctionChitId }, function(reply) {
                if (reply.success && reply.valid) {
                    Voucher.resultContainer.html(reply.html);
                    Voucher.modalBlock.modal("show");
                } else if (reply.redirect) {
                    ApplicationCommon.reloadPage();
                } else if (reply.message) {
                    ApplicationCommon.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    }
};

$(document).ready(function () {
        Voucher.ready();
    }
);
