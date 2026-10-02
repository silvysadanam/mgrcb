"use strict";

var LoanEdit = {
    ready: function () {
        $("form#form-loan-change").submit(LoanEdit.validateForm);
        $("input#loan_date").daterangepicker({
                timePicker: false,
                singleDatePicker: true,
                maxDate: moment(),
                minDate: new Date(2010, 0, 1),
                showDropdowns: true,
                locale: {
                    format: ApplicationCommon.dateFormat
                }
            }
        );
    },
    
    validateForm: function () {
        if (LoanEdit.notSelected($("input#emi").val())) {
            LoanEdit.showMessage("EMI is invalid");
            return false;
        }
        
        return true;
    },
    
    showMessage: function (message) {
        console.log("show message x");
        $("div#validation-message").show();
        $("div#validation-message").html(message);
        $("html,body").animate({
            scrollTop: $("div#validation-message").offset().top - 50
        }, 2000);
    },
    
    clearMessage: function () {
        $("div#validation-message").hide();
    },
    
    notSelected: function (value) {
        return 0 === parseInt(value);
    }
    
};

$(document).ready(function () {
        LoanEdit.ready();
    }
);
