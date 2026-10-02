"use strict";

var CashReceipt = {
    formModal: null,
    formAction: null,
    ready: function() {
        CashReceipt.formModal = $("div#modal-head-type");
        CashReceipt.formAction = $("input#manage-url").val();
        $("select#payment_type").change(CashReceipt.enableType);   
        $("a#add-head-type").click(CashReceipt.add);
        $("form#form-head-type-modal").submit(CashReceipt.save);
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
    add: function () {
        CashReceipt.formModal.find("h3#myModalLabelCashReceipt").text("Add Cash Receipt");
        CashReceipt.formModal.find("input#head-id").val(0);
        CashReceipt.formModal.find("input#fmc_id").val($(this).data("fmc_id"));
        CashReceipt.formModal.find("input#receipt_date").val($(this).data("receipt_date"));
        CashReceipt.formModal.find("input#receipt_number").val($(this).data("receipt_number"));
        CashReceipt.formModal.modal("show");
        CashReceipt.clearMessage();
    },
    
    save: function () {
        var formData = {
            action : 1,
            headID : CashReceipt.formModal.find("select#head_id").val(),
            transDate : CashReceipt.formModal.find("input#receipt_date").val(),
            transNo : CashReceipt.formModal.find("input#receipt_number").val(),
            amount : CashReceipt.formModal.find("input#amount").val(),
            transBy : CashReceipt.formModal.find("select#bank_id").val(),
            particulars : CashReceipt.formModal.find("textarea#note").val(),
            payment_type : CashReceipt.formModal.find("select#payment_type").val(),
            bank_id : CashReceipt.formModal.find("select#bank_id").val(),
            fmc_id : CashReceipt.formModal.find("input#fmc_id").val()
        };
        
        $.post(CashReceipt.formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    CashReceipt.reloadPage();
                } else if (reply.redirect) {
                    CashReceipt.reloadPage();
                } else if (reply.message) {
                    CashReceipt.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    reloadPage: function() {
        window.location.reload(true);
    },
            
    showMessage: function (message) {
        CashReceipt.formModal.find("div.alert").html(message).show();
    },
    
    clearMessage: function () {
        CashReceipt.formModal.find("div.alert").hide();
    }
    
};

$(document).ready(function() {
        CashReceipt.ready();
    }
);
