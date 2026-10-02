"use strict";

var LoanReschedule = {
    ready: function () {
        $("input#loan_amount,input#tenure,input#interest_rate").blur(LoanReschedule.calculateEMI);
        $("input#overdue_interest_rate").blur(LoanReschedule.calculateOverdue);
        $("form#form-loan-refinance").submit(LoanReschedule.validateForm);
        $("button#preview-schedule").click(LoanReschedule.previewSchedule);
        $("input#loan_date").daterangepicker({
            timePicker: false,
            singleDatePicker: true,
            maxDate: moment(),
            minDate: $("input#new-start-date").val(),
            showDropdowns: true,
            locale: {
                format: ApplicationCommon.dateFormat
            }
        });
        
        $("input#loan_date").on('apply.daterangepicker', function(ev, picker) {
              $(this).val(picker.startDate.format(picker.locale.format));
              LoanReschedule.calculateOverdue();
        });
    },
    
    calculateOverdue: function () {
        var formData = {
            start_date : $("input#new-start-date").val(),
            end_date : $("input#loan_date").val(),
            outstanding : $("input#outstanding-amount").val(),
            interest : $("input#overdue_interest_rate").val(),
            amount : 0
        };
        
        var formAction = $("input#calculate-overdue-url").val();
        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    $("input#overdue_days").val(reply.result.days);
                    $("input#overdue_amount").val(reply.result.overdue);
                    $("input#loan_amount").val(reply.result.amount);
                    LoanReschedule.calculateEMI();
                } else if (reply.redirect) {
                    ApplicationCommon.reloadPage();
                } else if (reply.message) {
                    $("input#emi").val(0);
                    LoanReschedule.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
        
        return false;
    },
    
    validateForm: function () {
        LoanReschedule.clearMessage();
        if ($("input#refinance_amount").val() < 1){
            LoanReschedule.showMessage("Fresh Loan Amount is required");
            return false;
        }
        
        if (LoanReschedule.notSelected($("input#emi").val())) {
            LoanReschedule.showMessage("EMI is invalid");
            return false;
        }
        
        return confirm("Are you sure you to proceed with refinancing and rescheduling this loan?");
    },
    
    calculateEMI: function () {
        LoanReschedule.clearMessage();
        $("input#emi").val("0");
        var formData = {
            amount : $("input#loan_amount").val(),
            tenure : $("input#tenure").val(),
            interest : $("input#interest_rate").val()
        };
                
        var formAction = $("input#calculate-emi-url").val();
        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    $("input#emi").val(reply.result);
                } else if (reply.redirect) {
                    ApplicationCommon.reloadPage();
                } else if (reply.message) {
                    $("input#emi").val(0);
                    LoanReschedule.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
        
        return false;
    },
    
    previewSchedule: function () {
        LoanReschedule.clearMessage();
        var formData = {
            amount : $("input#loan_amount").val(),
            tenure : $("input#tenure").val(),
            interest : $("input#interest_rate").val(),
            emi : $("input#emi").val(),
            loan_date : $("input#loan_date").val()
        };      
        
        var formAction = $("input#preview-schedule-url").val();
        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    $("tbody#schedule-preview-content").html(reply.html);
                    $("td#preview-date").text(reply.loan.loan_date);
                    $("td#preview-amount").text(reply.loan.principal);
                    $("td#preview-rate").text(reply.loan.interest);
                    $("td#preview-tenure").text(reply.loan.tenure);
                    $("td#preview-emi").text(reply.loan.emi);
                    $("td#preview-broken-period").text(reply.loan.broken_period);
                    $("th#total-interest").text(reply.total.interest);
                    $("th#total-principal").text(reply.total.principal);
                    $("th#total-emi").text(reply.total.emi);
                    $("div#modal-schedule-preview").modal("show");
                } else if (reply.redirect) {
                    ApplicationCommon.reloadPage();
                } else if (reply.message) {
                    LoanReschedule.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
        
        return false;
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
    },
    
    notSelected: function (value) {
        return 0 === parseInt(value);
    }
    
};

$(document).ready(function () {
        LoanReschedule.ready();
    }
);
