"use strict";

var LoanEdit = {
    ready: function () {
        $("form#form-loan-change").submit(LoanEdit.validateForm);
        $("input#loan_amount,input#tenure,input#interest_rate").blur(LoanEdit.calculateEMI);
        $("button#preview-schedule").click(LoanEdit.previewSchedule);
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
    
    calculateEMI: function () {
        LoanEdit.clearMessage();
        $("input#emi").val("0");
        var formData = LoanEdit.getFormData();
        if (false === formData) {
            $("button#preview-schedule").prop("disabled", true);
            return false;
        }
        
        var formAction = $("input#calculate-emi-url").val();
        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    $("input#emi").val(reply.result);
                    $("button#preview-schedule").prop("disabled", false);
                } else if (reply.redirect) {
                    ApplicationCommon.reloadPage();
                } else if (reply.message) {
                    $("input#emi").val(0);
                    LoanEdit.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
        
        return false;
    },
    
    previewSchedule: function () {
        LoanEdit.clearMessage();
        var formData = LoanEdit.getFormData();        
        formData.emi = $("input#emi").val();
        formData.loan_date = $("input#loan_date").val();
        
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
                    LoanEdit.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
        
        return false;
    },
    
    getFormData: function () {
        var loanAmount = $("input#loan_amount").val();
        if ("" === loanAmount) {
            return false;
        }
        if (isNaN(loanAmount)) {
            return false;
        } 
        
        if (loanAmount < 1) {
            return false;
        }
        
        var loanTenure = $("input#tenure").val();
        if ("" === loanTenure) {
            return false;
        }
        if (isNaN(loanTenure)) {
            return false;
        }
        
        if (loanTenure < 6 || loanTenure > 120) {
            return false;
        }
        
        var interestRate = $("input#interest_rate").val();
        if ("" === interestRate) {
            return false;
        }
        if (isNaN(interestRate)) {
            return false;
        }
        if (interestRate < 0.0 || interestRate > 24.0) {
            return false;
        }
        
        return { amount : loanAmount, tenure : loanTenure, interest : interestRate };
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
        LoanEdit.ready();
    }
);
