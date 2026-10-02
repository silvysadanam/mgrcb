"use strict";

var LoanSource = {
    formModal: null,
    formAction: null,
    ready: function() {
        LoanSource.formModal = $("div#modal-loan-source");
        LoanSource.formAction = $("input#manage-url").val();
        $("table#list-table-3").DataTable( {
                "aaSorting" : [],
                "lengthMenu": [[20, 50, 100], [20, 50, 100]],
                columnDefs: [ {
                        targets: [ 3 ],
                        searchable: false
                    }
                ]
            }
        );
        $("a#add-loan-source").click(LoanSource.add);
        $("table#list-table-3").on("click", "a.edit-loan-source", LoanSource.edit);
        $("table#list-table-3").on("click", "a.remove-loan-source", LoanSource.remove);
        /*
        $("a.edit-loan-source").click(LoanSource.edit);
        $("a.remove-loan-source").click(LoanSource.remove);
         * 
         */
        $("form#form-loan-source-modal").submit(LoanSource.save);
    },
    
    add: function () {
        LoanSource.formModal.find("h3#myModalLabelLoanSource").text("Add Loan Source");
        LoanSource.formModal.find("input[type=text]").val("");
        LoanSource.formModal.find("select#loan-source-type").val(0);
        LoanSource.formModal.find("input#loan-source-id").val(0);
        LoanSource.formModal.modal("show");
        LoanSource.clearMessage();
    },
    
    save: function () {
        var formData = {
            action : 1,
            loan_source : LoanSource.formModal.find("input#loan-source").val(),
            loan_source_type : LoanSource.formModal.find("select#loan-source-type").val(),
            id :  LoanSource.formModal.find("input#loan-source-id").val()
        };
        
        $.post(LoanSource.formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    ApplicationCommon.reloadPage();
                } else if (reply.redirect) {
                    ApplicationCommon.reloadPage();
                } else if (reply.message) {
                    LoanSource.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    edit: function () {
        LoanSource.formModal.find("h3#myModalLabelLoanSource").text("Edit Loan Source");
        LoanSource.formModal.find("input[type=text]").val($(this).data("title"));
        LoanSource.formModal.find("select#loan-source-type").val($(this).data("type"));
        LoanSource.formModal.find("input#loan-source-id").val($(this).data("id"));
        LoanSource.formModal.modal("show");
        LoanSource.clearMessage();
    },
    
    remove: function () {
        if (confirm("Are you sure you to remove this Loan Source?")) {
            var loanTypeId = $(this).data("id");
            var formData = {
                action : 2,
                id : loanTypeId
            };

            $.post(LoanSource.formAction, formData, function(reply) {
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
        LoanSource.ready();
    }
);
