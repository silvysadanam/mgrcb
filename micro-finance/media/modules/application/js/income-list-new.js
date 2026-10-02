"use strict";

var Income = {
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

        Income.modalBlock = $("div#modal-summary");
        Income.resultContainer = Income.modalBlock.find("div.modal-body:first");
        $("table#list-table-5").on("click", "a.quick-view", Income.showSummary);
    },
    
    showSummary: function () {
        var auctionChitId = $(this).data("id");
        var formAction = $("input#summary-url").val() + auctionChitId;
        $.post(formAction, { id : auctionChitId }, function(reply) {
                if (reply.success && reply.valid) {
                    Income.resultContainer.html(reply.html);
                    Income.modalBlock.modal("show");
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
        Income.ready();
    }
);
