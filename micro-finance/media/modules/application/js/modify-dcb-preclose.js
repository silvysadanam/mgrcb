"use strict";

var LoanClose = {
    ready: function () {
        $("form#form-data-entry").submit(LoanClose.validateForm);
        $("input#payment_date").daterangepicker({
                timePicker: false,
                singleDatePicker: true,
                maxDate: $("input#payment-max-date").val(),
                minDate: $("input#min-date").val(),
                showDropdowns: true,
                locale: {
                    format: ApplicationCommon.dateFormat
                }
            }
        );
        /*var $closing = $("input#closing_date");
        var minDate = $closing.data('min-date');
        var maxDate = moment().endOf('month'); 
        var format = ApplicationCommon.dateFormat;  
        $closing.daterangepicker*/
        $("input#closing_date").daterangepicker({
                timePicker: false,
                singleDatePicker: true,
                maxDate: $("input#max-date").val(),
                minDate: $("input#min-date").val(),
               // minDate: moment(minDate, format),
               // maxDate: maxDate,
                showDropdowns: true,
                locale: {
                    format: ApplicationCommon.dateFormat
                }
            }
        );

        $("input#closing_date").on('apply.daterangepicker', function(ev, picker) {
              $(this).val(picker.startDate.format(picker.locale.format));
              LoanClose.calculateAmount();
        });
    },
    
    validateForm: function () {
        if (confirm("Are you sure you to Preclose this Loan?")) {
            return true;
        }
        
        return false;
    },
    
    calculateAmount: function () {
        var formData = {
            id : $("input#loan-id").val(),
            closing_date : $("input#closing_date").val()
        };
        
        var formAction = $("input#calculate-amount-url").val();
        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    $("input#interest").val(reply.result.interest);
                    $("input#penal_interest").val(reply.result.penal_interest);
                    $("input#total").val(reply.result.total);
                } else if (reply.redirect) {
                    ApplicationCommon.reloadPage();
                } else if (reply.message) {
                    LoanWriteOff.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
        
        return false;
    }
    
};

$(document).ready(function () {
        LoanClose.ready();
    }
);
