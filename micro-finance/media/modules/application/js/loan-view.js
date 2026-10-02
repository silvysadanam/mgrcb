"use strict";

var LoanView = {
    ready: function () {
        $("a#delete-loan").click(LoanView.deleteLoan);
        $("a#reset-dcb").click(LoanView.resetDcb);
    },
    
    deleteLoan: function () {
        if (confirm("Are you sure you to DELETE this Loan?")) {
            LoanView.sendRequest(1);
        }
        
        return false;
    },
    
    resetDcb: function () {
        if (confirm("Are you sure you to RESET DCB of this Loan?")) {
            LoanView.sendRequest(2);
        }
    },
    
    sendRequest: function (action) {
        var formData = { id : $("input#loan-id").val(), type : action };
        var formAction = $("input#loan-delete-url").val();
            $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    if (1 === action) {
                        window.location = $("input#loan-cancel-url").val();
                    } else {
                        window.location.reload(true);
                    }
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
    
};

$(document).ready(function () {
        LoanView.ready();
    }
);
