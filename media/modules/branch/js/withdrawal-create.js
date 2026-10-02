"use strict";

var Withdrawal = {
    ready: function () {
        $("form#form-withdrawal").submit(Withdrawal.validateForm);
        $("a#check-balance").click(Withdrawal.loadBalance);
    },
    
    loadBalance: function () {
        Withdrawal.clearMessage();
        var withdrawalDate = $("input#withdrawal_date").val();
        $("td#check-withdrawal-date").text(withdrawalDate);
        $("td#check-member").text("");
        $("td#check-deduction-type").text("");
        $("td#check-deduction-date").text("");
        $("td#check-closing-balance").text("");
        $("td#check-withdrawal-amount").text("");
        $("td#check-modified-closing").text("");
        $("td#check-last").text("");
        
        var memberId = $("select#member_id").val();
        if ("0" === memberId) {
            Withdrawal.showMessage("Member not selected");
            return false;
        }
        
        var deductionTypeId = $("select#deduction_type_id").val();
        if ("0" === deductionTypeId) {
            Withdrawal.showMessage("Deduction Type not selected");
            return false;
        }
        
        if (! Withdrawal.isAmount($("input#withdrawal_amount").val())) {
            Withdrawal.showMessage("Amount is invalid");
            return false;
        }
        
        var formAction = $("input#check-balance-url").val();
        var formData = {
            member_id : memberId,
            deduction_type_id : deductionTypeId,
            withdrawal_amount : $("input#withdrawal_amount").val(),
            withdrawal_date: $("input#withdrawal_date").val()
        };
        
        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    if (reply.result.valid) {
                        $("td#check-withdrawal-date").text(reply.result.withdrawal_date);
                        $("td#check-member").text(reply.result.member);
                        $("td#check-deduction-type").text(reply.result.deduction_type);
                        $("td#check-deduction-date").text(reply.result.deduction_date);
                        $("td#check-closing-balance").text(reply.result.closing_balance);
                        $("td#check-withdrawal-amount").text(reply.result.withdrawal_amount);
                        $("td#check-modified-closing").text(reply.result.modified_closing);
                        $("td#check-last").text(reply.result.last);
                    } else {
                        Withdrawal.showMessage(reply.result.message);
                    }
                } else if (reply.redirect) {
                    BranchCommon.reloadPage();
                } else if (reply.message) {
                    Withdrawal.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
    },
    
    validateForm: function () {
        Withdrawal.clearMessage();
        if (Withdrawal.notSelected($("select#member_id").val())) {
            Withdrawal.showMessage("Member not selected");
            return false;
        }
        
        if (Withdrawal.notSelected($("select#deduction_type_id").val())) {
            Withdrawal.showMessage("Deduction Type not selected");
            return false;
        }
        
        if (! Withdrawal.isAmount($("input#withdrawal_amount").val())) {
            Withdrawal.showMessage("Amount is invalid");
            return false;
        }
        
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
    },
    
    notSelected: function (value) {
        return 0 === parseInt(value);
    },
    
    isAmount: function (value) {
        if ("" === value) {
            return false;
        }
        
        if (isNaN(value)) {
            return false;
        }
        
        if ("0" === value) {
            return false;
        }
        
        return /^[1-9]\d*$/.test(value);   
    }
    
};

$(document).ready(function () {
        Withdrawal.ready();
    }
);
