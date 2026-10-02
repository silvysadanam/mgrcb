"use strict";

var LoanCreate = {
    defaultOption: '<option value="0">--select--</option>',
    mfLoan: null,
    ready: function () {
        LoanCreate.mfLoan = $("input#loan-source-mf").val();
        $("input#loan_amount,input#tenure,input#interest_rate").blur(LoanCreate.calculateEMI);
        $("select#fmc_id").change(LoanCreate.loadMembers);
        $("select#payment_type").change(LoanCreate.enableType);  
        $("select#loan_type_id").change(LoanCreate.enablePaymentType);  
        $("select#loan_source_id").change(LoanCreate.enablePaymentType); 
        $("select#member_id").change(LoanCreate.getMemberName);
        $("form#form-loan-create").submit(LoanCreate.validateForm);
        $("button#preview-schedule").click(LoanCreate.previewSchedule);
        $("input#loan_date").inputmask("dd/mm/yyyy");
        $("select#loan_source_id").change(LoanCreate.getLoanNumber);
    },
    enablePaymentType: function() {
    var sourceType = $("select#loan_type_id").val();
    var loanSource = $("select#loan_source_id").val();
    var memberID = $("select#member_id").val();

    var bulkLoanIds = [19];
    var fmcIDs = [240, 241, 242, 243, 244, 245, 246, 247, 248, 249, 250, 251, 252, 253, 254, 255, 256, 257, 259];

    if (
        bulkLoanIds.includes(parseInt(sourceType)) &&
        fmcIDs.includes(parseInt(memberID)) ||
        parseInt(loanSource) === 4
    ) {
        $("#payment_type").removeAttr("disabled");
        $("#payment_type option[value='Cash']").removeAttr("disabled");
    } else {
        $("#payment_type option[value='Cash']").attr("disabled", "disabled");
        $("select#bank_id").removeAttr("disabled");
        $("#payment_type")
            .removeAttr("disabled")
            .prop("selectedIndex", 1);
    }
},
     enableType: function()
    {
        var paymentType = $("select#payment_type").val();
        if(paymentType == 'Bank'){
            $("select#bank_id").removeAttr("disabled");
        } else {
            $("select#bank_id").attr('disabled', 'disabled');
            $("select#bank_id").val(0);
        }
        
    },
    loadMembers: function () {
        var fmcId = parseInt($(this).val());
        if (fmcId > 0) {
            var formAction = $("input#load-members-url").val();
            $.post(formAction, { id : fmcId }, function(reply) {
                    if (reply.success && reply.valid) {
                        $("select#member_id").html(reply.result);
                        $("input#member_name").val("");
                    } else if (reply.redirect) {
                        ApplicationCommon.reloadPage();
                    } else if (reply.message) {
                        LoanCreate.showMessage(reply.message);
                    } else {
                        alert("Unable to complete the request.");
                    }
                }, "json"
            );
        } else {
            $("select#member_id").html(LoanCreate.defaultOption);
            $("input#member_name").val("");
        }
    },
    
    getMemberName: function () {
        var memberId = parseInt($(this).val());
        if (memberId > 0) {
            var formAction = $("input#get-member-url").val();
            $.post(formAction, { id : memberId }, function(reply) {
                    if (reply.success && reply.valid) {
                        $("input#member_name").val(reply.result);
                    } else if (reply.redirect) {
                        ApplicationCommon.reloadPage();
                    } else if (reply.message) {
                        LoanCreate.showMessage(reply.message);
                    } else {
                        alert("Unable to complete the request.");
                    }
                }, "json"
            );
        } else {
            $("input#member_name").val("");
        }
    },
    
    validateForm: function () {
        if (LoanCreate.notSelected($("select#member_id").val())) {
            LoanCreate.showMessage("Member not selected");
            return false;
        }
        
        if (LoanCreate.notSelected($("select#loan_source_id").val())) {
            LoanCreate.showMessage("SOURCE not selected");
            return false;
        }
        
        if (LoanCreate.notSelected($("select#loan_type_id").val())) {
            LoanCreate.showMessage("Type not selected");
            return false;
        }
        
        if (LoanCreate.notSelected($("input#emi").val())) {
            LoanCreate.showMessage("EMI is invalid");
            return false;
        }
        
        return true;
    },
    
    calculateEMI: function () {
        LoanCreate.clearMessage();
        $("input#emi").val("0");
        var formData = LoanCreate.getFormData();
        if (false === formData) {
            $("button#preview-schedule").prop("disabled", true);
            return false;
        }
        
        var formAction = $("input#calculate-emi-url").val();
        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    $("input#emi").val(reply.result);
                    if ("" !== $("input#loan_date").val()) {
                        $("button#preview-schedule").prop("disabled", false);
                    }
                } else if (reply.redirect) {
                    ApplicationCommon.reloadPage();
                } else if (reply.message) {
                    $("input#emi").val(0);
                    LoanCreate.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
        
        return false;
    },
    
    previewSchedule: function () {
        LoanCreate.clearMessage();
        var formData = LoanCreate.getFormData();
        var loan_date = $("input#loan_date").val();
        if ("" === loan_date) {
            return false;
        }
        if ($("input#emi").val() < 1) {
            return false;
        }
        
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
                    LoanCreate.showMessage(reply.message);
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
    },
    
    getLoanNumber: function  () {
        var loanSource = $(this).val();
        var loanDate = $("input#loan_date").val();
        
        if (loanSource === LoanCreate.mfLoan) {
            var formData = {
                id : loanSource,
                loan_date : loanDate
            };
            var formAction = $("input#get-loan-number-url").val();
            $.post(formAction, formData, function(reply) {
                    if (reply.success && reply.valid) {
                        $("input#account_number").prop("required", false).prop("readonly", true).val(reply.result);
                    } else if (reply.redirect) {
                        ApplicationCommon.reloadPage();
                    } else if (reply.message) {
                        LoanCreate.showMessage(reply.message);
                    } else {
                        alert("Unable to complete the request.");
                    }
                }, "json"
            );
        } else {
            $("input#account_number").prop("readonly", false).prop("required", true).val("");
        }
    }
    
};

$(document).ready(function () {
        LoanCreate.ready();
    }
);
