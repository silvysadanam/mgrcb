"use strict";

var LoanClose = {
    ready: function () {
        $("form#form-data-entry").submit(LoanClose.validateForm);
        $("input#payment_date").daterangepicker({
                timePicker: false,
                singleDatePicker: true,
                maxDate: $("input#max-date").val(),
                minDate: $("input#min-date").val(),
                showDropdowns: true,
                locale: {
                    format: ApplicationCommon.dateFormat
                }
            }
        );
    },
    
    validateForm: function () {
        if (confirm("Are you sure you to Close this Loan?")) {
            return true;
        }
        
        return false;
    }
    
};

$(document).ready(function () {
        LoanClose.ready();
    }
);
