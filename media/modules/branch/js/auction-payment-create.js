"use strict";

var AuctionPayment = {
    ready: function () {
        $("form#form-auction").submit(AuctionPayment.validateForm);
        $("select#donor_id").change(AuctionPayment.loadBalance);
        $("a#check-balance").click(AuctionPayment.loadBalance);
    },
    
    loadBalance: function () {
        var paymentDate = $("input#payment_date").val();
        $("td#check-balance-date").text(paymentDate);
        var donorId = $("select#donor_id").val();
        if ("0" === donorId) {
            $("td#check-balance-donor").text("");
            $("td#check-balance-opening").text("");
            $("td#check-balance-amount").text("");
            $("td#check-balance-advance").text("");
            $("td#check-balance-payment").text("");
            $("td#check-balance-closing").text("");
            return;
        }
        
        var formAction = $("input#check-balance-url").val();
        var formData = { id : donorId, payment_date : paymentDate};
        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    $("td#check-balance-date").text(reply.result.payment_date);
                    $("td#check-balance-donor").text(reply.result.donor);
                    $("td#check-balance-opening").text(reply.result.opening);
                    $("td#check-balance-amount").text(reply.result.amount);
                    $("td#check-balance-advance").text(reply.result.advance);
                    $("td#check-balance-payment").text(reply.result.payment);
                    $("td#check-balance-closing").text(reply.result.closing);
                } else if (reply.redirect) {
                    BranchCommon.reloadPage();
                } else if (reply.message) {
                    BranchCommon.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
    },
    
    validateForm: function () {
        AuctionPayment.clearMessage();
        if (AuctionPayment.notSelected($("select#donor_id").val())) {
            AuctionPayment.showMessage("Donor not selected");
            return false;
        }
        
        if (! AuctionPayment.isAmount($("input#amount").val())) {
            AuctionPayment.showMessage("Amount is invalid");
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
        AuctionPayment.ready();
    }
);
