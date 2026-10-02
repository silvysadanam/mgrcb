"use strict";

var DeductionBill = {
    ready: function () {
        $("input.deduction-amount").blur(DeductionBill.calculate);
        $("form#form-deduction-bill").submit(AuctionPayment.validateForm);
    },
    
    calculate: function () {
        DeductionBill.clearMessage();
        var currentInput = $(this);
        var deductionValue = currentInput.val();
        var title = currentInput.data("title");
        if (! DeductionBill.isAmount(deductionValue)) {
            DeductionBill.showMessage("Invalid amount \"" + deductionValue + "\" for " + title);
            currentInput.val(0);
        }
        
        var balanceAvailable = parseInt($("input#available").val());
        var deductionAmount = 0;
        
        $("input.deduction-amount").each(function () {
                var itemAmount = parseInt($(this).val());
                if (itemAmount > 0) {
                    deductionAmount += itemAmount;
                }
            }
        );

        if (deductionAmount > balanceAvailable) {
            DeductionBill.showMessage("Deduction exceeds auction amount / available balance");
            currentInput.val(0);
            deductionAmount -= parseInt(deductionValue);
        }
        
        var balance = balanceAvailable - deductionAmount;
        $("input#total_deduction").val(deductionAmount);
        $("input#balance").val(balance);
        
        return false;
    },
    
    validateForm: function () {
        DeductionBill.clearMessage();
        
        var valid = true;
        $("input.deduction-amount").each(function () {
                if (! DeductionBill.isAmount($(this).val())) {
                    DeductionBill.showMessage("Invalid amount for " + $(this).data('title'));
                    valid = false;
                    
                    return false;
                }
            }
        );

        if (! valid) {
            return false;
        }
        
        if (parseInt($("input#total_deduction").val()) > parseInt($("input#available").val())) {
            DeductionBill.showMessage("Deduction exceeds auction amount / available balance");
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
    
    isAmount: function (value) {
        if ("" === value) {
            return false;
        }
        
        if (isNaN(value)) {
            return false;
        }
        
        if ("0" === value) {
            return true;
        }
        
        return /^[1-9]\d*$/.test(value);   
    }
    
};

$(document).ready(function () {
        DeductionBill.ready();
    }
);
