"use strict";

var AdvancePayment = {
    advanceModal : null,
    actualBalance : 0,
    advanceId : 0,
    ready: function () {
        AdvancePayment.advanceModal = $("div#modal-advance");
        AdvancePayment.actualBalance = parseInt($("input#advance-balance").val());
        AdvancePayment.advanceId = $("input#advance-id").val();
        $("a#add-repayment").click(AdvancePayment.showPayment);
        $("form#form-advance-modal input#amount").blur(AdvancePayment.calculateBalance);
        $("form#form-advance-modal").submit(AdvancePayment.savePayment);
    },
    
    showPayment: function () {
        AdvancePayment.advanceModal.modal("show");
        AdvancePayment.advanceModal.find("div.alert").hide();
        AdvancePayment.advanceModal.find("input#amount").val("");
        AdvancePayment.advanceModal.find("textarea#note").val("");
        AdvancePayment.advanceModal.find("input#balance").val(AdvancePayment.actualBalance);
    },
    
    calculateBalance: function () {
        AdvancePayment.clearMessage();
        AdvancePayment.advanceModal.find("input#balance").val(AdvancePayment.actualBalance);
        var amount = AdvancePayment.advanceModal.find("input#amount").val();
        if ("" !== amount) {
            var paymentAmount = parseInt(amount);
            if (paymentAmount > 0) {
                if (paymentAmount > AdvancePayment.actualBalance) {
                    AdvancePayment.showMessage("Amount exceeds balance");
                } else {
                    AdvancePayment.advanceModal.find("input#balance").val(AdvancePayment.actualBalance - paymentAmount);
                }
            }
        }
        
        return false;
    },
    
    savePayment: function () {
        AdvancePayment.clearMessage();
        var paymentAmount = parseInt(AdvancePayment.advanceModal.find("input#amount").val());
        if (paymentAmount < 1 || paymentAmount > AdvancePayment.actualBalance) {
            AdvancePayment.showMessage("Invalid amount");
            return false;
        }
        
        var formData = {
            id : AdvancePayment.advanceId,
            payment_date : AdvancePayment.advanceModal.find("input#payment-date").val(),
            amount : paymentAmount,
            note : AdvancePayment.advanceModal.find("textarea#note").val()
        };
        
        var formAction = AdvancePayment.getManageUrl();
        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    BranchCommon.reloadPage();
                } else if (reply.redirect) {
                    BranchCommon.reloadPage();
                } else if (reply.message) {
                    AdvancePayment.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
        
        return false;
    },
    
    showMessage: function (message) {
        AdvancePayment.advanceModal.find("div.alert").html(message).show();
    },
    
    clearMessage: function () {
        AdvancePayment.advanceModal.find("div.alert").hide();
    },
    
    getManageUrl: function (action) {
        return $("input#manage-url").val();
    }
    
};

$(document).ready(function () {
        AdvancePayment.ready();
    }
);
