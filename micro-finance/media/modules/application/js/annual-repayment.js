"use strict";

var AnnualRepayment = {
    ready: function () {
        //$("form#form-annual-repayment").submit(AnnualRepayment.validateForm);
        
        $("input#start_date,input#end_date").daterangepicker({
            timePicker: false,
            singleDatePicker: true,
            maxDate: $("input#max_date").val(),
            minDate: $("input#min_date").val(),
            showDropdowns: true,
            locale: {
                format: ApplicationCommon.dateFormat
            }
        });
        
    },
    
    validateForm: function () {
        
        return true;
    },
    
    
    showMessage: function (message) {
        $("div#validation-message").show();
        $("div#validation-message").html(message);
        $("html,body").animate({
            scrollTop: $("div#validation-message").offset().top - 50
        }, 2000);
    },
    
    clearMessage: function () {
        $("div#validation-message").hide();
    }
};

$(document).ready(function () {
        AnnualRepayment.ready();
    }
);
