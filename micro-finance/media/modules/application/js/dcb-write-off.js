"use strict";

var LoanWriteOff = {
    ready: function () {
        $("form#form-loan-write-off").submit(LoanWriteOff.validateForm);
        
        $("input#write_off_date").daterangepicker({
            timePicker: false,
            singleDatePicker: true,
            maxDate: moment(),
            minDate: $("input#last-date").val(),
            showDropdowns: true,
            locale: {
                format: ApplicationCommon.dateFormat
            }
        });
        
        $("input.paid-date").daterangepicker({
            timePicker: false,
            singleDatePicker: true,
            autoUpdateInput: false,
            showDropdowns: true,
            maxDate: moment(),
            minDate: $("input#last-date").val(),
            locale: {
                format: ApplicationCommon.dateFormat,
                cancelLabel: "Clear"
            }
        });
        
        $("input.paid-date").on('apply.daterangepicker', function(ev, picker) {
              $(this).val(picker.startDate.format(picker.locale.format));
        });

        $("input.paid-date").on('cancel.daterangepicker', function(ev, picker) {
            $(this).val("");
        });
        
        $("input#write_off_date").on('apply.daterangepicker', function(ev, picker) {
              $(this).val(picker.startDate.format(picker.locale.format));
              LoanWriteOff.calculateOverdue();
        });
        $("input#member_paid_amount").blur(LoanWriteOff.recalculateAmount);
    },
    
    calculateOverdue: function () {
        var formData = {
            start_date : $("input#last-date").val(),
            end_date : $("input#write_off_date").val(),
            outstanding : $("input#outstanding-amount").val(),
            interest : $("input#interest-rate").val(),
            amount : 0
        };
        
        var formAction = $("input#calculate-overdue-url").val();
        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    $("input#write_off_interest").val(reply.result.overdue);
                    $("input#write_off_amount").val(reply.result.actual);
                    LoanWriteOff.recalculateAmount();
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
    },
    
    recalculateAmount: function () {
        var total = parseFloat($("input#write_off_amount").val());
        var paid = parseFloat($("input#member_paid_amount").val());
        if (paid > total) {
            LoanWriteOff.showMessage("Member paid amount cannot be more than total write off amount");
            $("input#member_paid_amount").val(0);
            paid = 0;
        }
        
        var balance = total - paid;
        $("input#balance_written_off_amount").val(balance.toFixed(2));
    },
    
    validateForm: function () {
        return confirm("Are you sure you to proceed with writing off this loan?");
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
        LoanWriteOff.ready();
    }
);
