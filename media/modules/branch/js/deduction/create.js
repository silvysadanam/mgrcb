"use strict";

var DeductionBill = {
    ready: function () {
        $("input.deduction-amount").blur(DeductionBill.calculate);
        $("input#member_paid_amount").blur(DeductionBill.applyPayment);
        $("button#confirm-submit").click(DeductionBill.confirmSubmit);
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
        
        var balanceAvailable = parseInt($("input#total_available").val());
        var deductionAmount = 0;
        
        $("input.deduction-amount").each(function () {
                var itemAmount = parseInt($(this).val());
                if (itemAmount > 0) {
                    deductionAmount += itemAmount;
                }
            }
        );

        if (deductionAmount > balanceAvailable) {
            DeductionBill.showMessage("Deduction exceeds auction amount / total available balance");
            currentInput.val(0);
            deductionAmount -= parseInt(deductionValue);
        }
        
        var balance = balanceAvailable - deductionAmount;
        $("input#total_deduction").val(deductionAmount);
        $("input#balance").val(balance);
        
        return false;
    },
    
    applyPayment: function () {
        DeductionBill.clearMessage();
        var paymentAmount = $("input#member_paid_amount").val();
        if (! DeductionBill.isAmount(paymentAmount)) {
            DeductionBill.showMessage("Invalid amount \"" + paymentAmount + "\" for Amount paid by Member");
            $("input#member_paid_amount").val(0);
        }
        
        var auctionAmount = parseInt($("input#auction_amount").val());
        var available = parseInt($("input#available").val());
        var paidAmount = parseInt($("input#member_paid_amount").val());
        var advance = parseInt($("input#advance").val());
        if (paidAmount > advance) {
            DeductionBill.showMessage("Amount paid by Member exceeds limit");
            $("input#member_paid_amount").val(0);
            paidAmount = 0;
        }
        
        var totalAvailable = available + paidAmount;
        if (paidAmount > 0) {
            if (totalAvailable > auctionAmount) {
                DeductionBill.showMessage("Total available amount exceeds Auction amount");
                $("input#member_paid_amount").val(0);
                available  = 0;
                totalAvailable = available;
            }
        }
        
        $("input#total_available").val(totalAvailable);
        var balance = totalAvailable - parseInt($("input#total_deduction").val());
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
        
        var totalDeduction = parseInt($("input#total_deduction").val());
        var available = parseInt($("input#available").val());
        var paidAmount = parseInt($("input#member_paid_amount").val());
        var advance = parseInt($("input#advance").val());
        var totalAvailable = parseInt($("input#total_available").val());
        var balance = parseInt($("input#balance").val());
        
        if (totalDeduction > totalAvailable) {
            DeductionBill.showMessage("Deduction exceeds auction amount / total available balance");
            return false;
        }
        
        if (paidAmount > 0) {
            if (paidAmount > advance) {
                DeductionBill.showMessage("Amount paid by Member exceeds limit");
                return false;
            }
            
            if (balance > 0) {
                DeductionBill.showMessage("Balance after deductions should be zero");
                return false;
            }
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
    },
    
    confirmSubmit: function () {
        if (! confirm("Proceed with submitting entered data?")) {
            return false;
        }
        
        if (! DeductionBill.validateForm()) {
            return false;
        }
        
        $("form#form-deduction-bill").get(0).submit();
    }
    
};

$(document).ready(function () {
        DeductionBill.ready();
    }
);
