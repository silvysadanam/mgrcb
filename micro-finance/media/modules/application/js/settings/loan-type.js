"use strict";

var LoanType = {
    formModal: null,
    formAction: null,
    ready: function() {
        LoanType.formModal = $("div#modal-loan-type");
        LoanType.formAction = $("input#manage-url").val();
        $("table#list-table-2").DataTable( {
                "aaSorting" : [],
                "lengthMenu": [[20, 50, 100], [20, 50, 100]],
                columnDefs: [ {
                        targets: [ 2 ],
                        searchable: false
                    }
                ]
            }
        );
        $("a#add-loan-type").click(LoanType.add);
        $("table#list-table-2").on("click", "a.edit-loan-type", LoanType.edit);
        $("table#list-table-2").on("click", "a.remove-loan-type", LoanType.remove);
        //$("a.edit-loan-type").click(LoanType.edit);
        //$("a.remove-loan-type").click(LoanType.remove);
        $("form#form-loan-type-modal").submit(LoanType.save);
    },
    
    add: function () {
        LoanType.formModal.find("h3#myModalLabelLoanType").text("Add Loan Type");
        LoanType.formModal.find("input[type=text]").val("");
        LoanType.formModal.find("input#loan-type-id").val(0);
        LoanType.formModal.modal("show");
        LoanType.clearMessage();
    },
    
    save: function () {
        var formData = {
            action : 1,
            loan_type : LoanType.formModal.find("input#loan-type").val(),
            id :  LoanType.formModal.find("input#loan-type-id").val()
        };
        
        
        $.post(LoanType.formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    ApplicationCommon.reloadPage();
                } else if (reply.redirect) {
                    ApplicationCommon.reloadPage();
                } else if (reply.message) {
                    LoanType.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    edit: function () {
        LoanType.formModal.find("h3#myModalLabelLoanType").text("Edit Loan Type");
        LoanType.formModal.find("input[type=text]").val($(this).data("title"));
        LoanType.formModal.find("input#loan-type-id").val($(this).data("id"));
        LoanType.formModal.modal("show");
        LoanType.clearMessage();
    },
    
    remove: function () {
        if (confirm("Are you sure you to remove this Loan Type?")) {
            var loanTypeId = $(this).data("id");
            var formData = {
                action : 2,
                id : loanTypeId
            };

            $.post(LoanType.formAction, formData, function(reply) {
                    if (reply.success && reply.valid) {
                        ApplicationCommon.reloadPage();
                    } else if (reply.redirect) {
                        ApplicationCommon.reloadPage();
                    } else if (reply.message) {
                        alert(reply.message);
                    } else {
                        alert("Unable to complete the request.");
                    }
                }, "json"
            );
        }

        return false;
    },
            
    showMessage: function (message) {
        $("div#js-validation-error").html(message).show();
    },
    
    clearMessage: function () {
        $("div.alert,div#js-validation-error").hide();
    }
    
};

$(document).ready(function() {
        LoanType.ready();
    }
);
