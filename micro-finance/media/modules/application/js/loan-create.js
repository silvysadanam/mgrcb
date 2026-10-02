"use strict";

var LoanCreate = {
    ready: function () {
        $("input#loan_amount,input#tenure,input#interest_rate").blur(LoanCreate.autoCalculateEMI);
        $("a#calculate-emi").click(LoanCreate.recalculateEMI);
        $("a#preview-schedule").click(LoanCreate.previewSchedule);
        $("a#use-calculated-emi").click(LoanCreate.useCalculatedEmi);
        $("input#loan_date").daterangepicker({
            timePicker: false,
            singleDatePicker: true,
            maxDate: moment(),
            minDate: new Date(2010, 0, 1),
            showDropdowns: true,
            locale: {
                format: ApplicationCommon.dateFormat
            }
        });
    },
    
    autoCalculateEMI: function () {
        LoanCreate.calculateEMI(false);
    },
    
    recalculateEMI: function () {
        LoanCreate.calculateEMI(true);
    },
    
    calculateEMI: function (displayError) {
        LoanCreate.clearMessage();
        var formData = LoanCreate.getFormData(displayError);
        if (false === formData) {
            return false;
        }
        
        var formAction = $("input#calculate-emi-url").val();
        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    $("input#calculated_emi").val(reply.emi);
                    if ("" === $("input#emi").val()) {
                        $("input#emi").val(reply.emi);
                    }
                } else if (reply.redirect) {
                    ApplicationCommon.reloadPage();
                } else if (reply.message) {
                    if (displayError) {
                        LoanCreate.showMessage(reply.message);
                    }
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
        
        return false;
    },
    
    getFormData: function (displayError) {
        var loanAmount = $("input#loan_amount").val();
        if ("" === loanAmount) {
            if (displayError) {
                LoanCreate.showMessage("Please enter Loan Amount");
            }
            return false;
        }
        if (isNaN(loanAmount)) {
            if (displayError) {
                LoanCreate.showMessage("Please enter Loan Amount");
            }
            return false;
        } 
        
        if (loanAmount < 1) {
            return false;
        }
        
        var loanTenure = $("input#tenure").val();
        if ("" === loanTenure) {
            if (displayError) {
                LoanCreate.showMessage("Please enter Tenure");
            }
            return false;
        }
        if (isNaN(loanTenure)) {
            if (displayError) {
                LoanCreate.showMessage("Please enter Tenure");
            }
            return false;
        }
        
        if (loanTenure < 6) {
            if (displayError) {
                LoanCreate.showMessage("Please enter valid Tenure");
            }
            return false;
        }
        
        var interestRate = $("input#interest_rate").val();
        if ("" === interestRate) {
            if (displayError) {
                LoanCreate.showMessage("Please enter Interest Rate");
            }
            return false;
        }
        if (isNaN(interestRate)) {
            if (displayError) {
                LoanCreate.showMessage("Please enter Interest Rate");
            }
            return false;
        }
        if (interestRate < 0 || interestRate > 24) {
            if (displayError) {
                LoanCreate.showMessage("Please enter valid Interest Rate");
            }
            return false;
        }
        
        return { amount : loanAmount, tenure : loanTenure, interest : interestRate };
    },
    
    previewSchedule: function () {
        LoanCreate.clearMessage();
        $("section#repayment-schedule").hide();
        var formData = LoanCreate.getFormData(true);
        if (false === formData) {
            return false;
        }
        
        var proposedEmi = $("input#emi").val();
        if (isNaN(proposedEmi)) {
            LoanCreate.showMessage("Please enter proposed EMI");
            return false;
        }
        
        if (proposedEmi < 1) {
            LoanCreate.showMessage("Please enter valid proposed EMI");
            return false;
        }
        
        formData.emi = proposedEmi;
        formData.loan_date = $("input#loan_date").val();
        
        var formAction = $("input#preview-schedule-url").val();
        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    $("tbody#schedule-entries").html(reply.html);
                    $("td#schedule-principal").text(reply.loan.principal);
                    $("td#schedule-tenure").text(reply.loan.tenure);
                    $("td#schedule-interest").text(reply.loan.interest);
                    $("td#schedule-emi").text(reply.loan.emi);
                    $("th#total-interest").text(reply.total.interest);
                    $("th#total-principal").text(reply.total.principal);
                    $("th#total-emi").text(reply.total.emi);
                    $("section#repayment-schedule").show();
                    $("html,body").animate({
                        scrollTop: $("section#repayment-schedule").offset().top - 50
                    }, 2000);
                } else if (reply.redirect) {
                    ApplicationCommon.reloadPage();
                } else if (reply.message) {
                    LoanCreate.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
        
        return false;
    },
    
    useCalculatedEmi: function () {
        $("input#emi").val($("input#calculated_emi").val());
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
    }
    
};

$(document).ready(function () {
        LoanCreate.ready();
    }
);
