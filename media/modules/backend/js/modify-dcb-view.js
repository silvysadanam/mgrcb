"use strict";

var LoanApprove = {
    ready: function () {
        $("form#form-data-entry").submit(LoanApprove.validateForm);       
    },
    
    validateForm: function () {
        if (confirm("Are you sure you to Approve this Loan?")) {            
             $("input#approval_status").val('Approved');
             return true;
        }
        
        return false;
    }
    
};

$(document).ready(function () {
        LoanApprove.ready();
    }
);
