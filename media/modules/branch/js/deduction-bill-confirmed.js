"use strict";

var DeductionBill = {
    modalBlock: null,
    resultContainer: null,
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

        DeductionBill.modalBlock = $("div#modal-summary");
        DeductionBill.resultContainer = DeductionBill.modalBlock.find("div.modal-body:first");
        $("table#list-table-8").on("click", "a.quick-view", DeductionBill.showSummary);
    },
    
    showSummary: function () {
        var deductionBillId = $(this).data("id");
        var formAction = $("input#summary-url").val() + deductionBillId;
        $.post(formAction, { id : deductionBillId }, function(reply) {
                if (reply.success && reply.valid) {
                    DeductionBill.resultContainer.html(reply.html);
                    DeductionBill.modalBlock.modal("show");
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
        DeductionBill.ready();
    }
);
