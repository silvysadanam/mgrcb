"use strict";

var Voucher = {
    entryModal: null,
    voucherModal: null,
    HeadOptions: null,
    actions: {
        remove : 1, get_entry : 2, save_entry : 3, delete_entry : 4, edit_voucher : 5,
    },
    ready: function () {
        Voucher.entryModal = $("div#modal-entry");
        Voucher.voucherModal = $("div#modal-voucher");
        $("a#add-entry").click(Voucher.addEntry);
        $("a#delete-transaction").click(Voucher.deleteVoucher);
        $("table#list-table-2").on("click", "a.edit-voucher", Voucher.editVoucher);
        $("a.edit-entry").click(Voucher.editEntry);
        $("a.delete-entry").click(Voucher.deleteEntry);
        $("form#form-receipt-modal").submit(Voucher.saveEntry);
        $("form#form-voucher-modal").submit(Voucher.saveVoucherEntry);
    },
   
    addEntry: function () {
        Voucher.showEntryModal();
        Voucher.entryModal.find("h3.modal-title").text("Add Entry");
        Voucher.entryModal.find("input[type=number]").val("");
        Voucher.entryModal.find("select#head_id").val(0);
        Voucher.entryModal.find("input#entry-id").val(0);
    },
    
    editVoucher: function () {
        var entryId = $(this).data("id");
        Voucher.voucherModal.find("input#receipt_date").val($(this).data("date")); 
        Voucher.voucherModal.find("input#non_member").val($(this).data("non_member")); 
        Voucher.voucherModal.find("textarea#note").val($(this).data("note"));
        Voucher.voucherModal.find("select#payment_type").val($(this).data('payment_type'));
        Voucher.voucherModal.find("select#bank_id").val($(this).data('bank_id'));
        Voucher.voucherModal.find("input#entry-id").val(entryId); 
        Voucher.voucherModal.find("input#mf_loan").val($(this).data('mf_loan'));         
        if ($(this).data('mf_loan') == 1) {
            Voucher.voucherModal.find("select#payment_type").prop("disabled", true);
            Voucher.voucherModal.find("select#bank_id").prop("disabled", true);
        } else {
            Voucher.voucherModal.find("select#payment_type").prop("disabled", false);
            Voucher.voucherModal.find("select#bank_id").prop("disabled", false);
        }
        Voucher.showEditModal();
        Voucher.clearMessage();
    },
    
    editEntry: function () {
        var entryId = $(this).data("id");
        var formData = { id : entryId };
        var formAction = Voucher.getManageUrl(Voucher.actions.get_entry);

        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    Voucher.showEntryModal();
                    Voucher.entryModal.find("h3.modal-title").text("Edit Entry");
                    Voucher.entryModal.find("select#head_id").val(reply.result.headID);
                    Voucher.entryModal.find("input#amount").val(reply.result.amount);
                    Voucher.entryModal.find("input#entry-id").val(entryId);
                } else if (reply.redirect) {
                    BranchCommon.reloadPage();
                } else if (reply.message) {
                    Voucher.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
        
        return false;
    },
    
    saveEntry: function () {
        Voucher.clearMessage();
        
        var headId = Voucher.entryModal.find("select#head_id").val();
        if ("0" === headId) {
            Voucher.showMessage("Head not selected");
            return false;
        }
        
        
        var entryAmount = Voucher.entryModal.find("input#amount").val();
        if (! Voucher.isAmount(entryAmount)) {
            Voucher.showMessage("Invalid amount");
            return false;
        }
        
        var entryId = Voucher.entryModal.find("input#entry-id").val();
        var formData = {
            id : entryId,
            headID : headId,
            amount : entryAmount
        };
        
        var formAction = Voucher.getManageUrl(Voucher.actions.save_entry);
        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    BranchCommon.reloadPage();
                } else if (reply.redirect) {
                    BranchCommon.reloadPage();
                } else if (reply.message) {
                    Voucher.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
        
        return false;
    },
    
    saveVoucherEntry: function () {
        Voucher.clearMessage();
        
        var receiptDate = Voucher.voucherModal.find("input#receipt_date").val();
        if ("" === receiptDate) {
            Voucher.showErrorMessage("Date not selected");
            return false;
        }
        
        var nonMember = Voucher.voucherModal.find("input#non_member").val();
       /* if ("" === nonMember) {
            Voucher.showMessage("Non Member details not provided");
            return false;
        }
        */
        var remarks = Voucher.voucherModal.find("textarea#note").val();
        if ("" === remarks) {
            Voucher.showErrorMessage("Narration details not provided");
            return false;
        }
        var bankID = Voucher.voucherModal.find("select#bank_id").val();
        var mf_loan = Voucher.voucherModal.find("input#mf_loan").val();
        var payment_type = Voucher.voucherModal.find("select#payment_type").val();    
        if ("Bank" === payment_type && bankID == 0) {
            Voucher.showErrorMessage("Bank not selected");
            return false;
        }
        var entryId = Voucher.voucherModal.find("input#entry-id").val();
        var formData = {
            id : entryId,
            transDate : receiptDate,
            non_member : nonMember,
            particulars : remarks,
            ByTo : payment_type,
            transBy : bankID,
            mfLoan : mf_loan
        };
        
        var formAction = Voucher.getManageUrl(Voucher.actions.edit_voucher);
        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    BranchCommon.reloadPage();
                } else if (reply.redirect) {
                    BranchCommon.reloadPage();
                } else if (reply.message) {
                    Voucher.showErrorMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
        
        return false;
    },
    
    deleteEntry: function () {
        if (confirm("Are you sure you to delete this Entry?")) {
            var entryId = $(this).data("id");
            var formData = { id : entryId };
            var formAction = Voucher.getManageUrl(Voucher.actions.delete_entry);
            $.post(formAction, formData, function(reply) {
                    if (reply.success && reply.valid) {
                        BranchCommon.reloadPage();
                    } else if (reply.redirect) {
                        BranchCommon.reloadPage();
                    } else if (reply.message) {
                        BranchCommon.showMessage(reply.message);
                    } else {
                        alert("Unable to complete the request.");
                    }
                }, "json"
            );

            return false;
        }
        
        return false;
    },
    
    deleteVoucher: function () {
        if (confirm("Are you sure you to delete this Voucher?")) {
            var formData = { id : 0 };
            var formAction = Voucher.getManageUrl(Voucher.actions.remove);
            $.post(formAction, formData, function(reply) {
                    if (reply.success && reply.valid) {
                        window.location = $("input#cancel-url").val();
                    } else if (reply.redirect) {
                        BranchCommon.reloadPage();
                    } else if (reply.message) {
                        BranchCommon.showMessage(reply.message);
                    } else {
                        alert("Unable to complete the request.");
                    }
                }, "json"
            );

            return false;
        }
        
        return false;
    },
    
    showEntryModal: function () {
        Voucher.entryModal.modal("show");
        Voucher.entryModal.find("div.alert").hide();
    },
    
    showEditModal: function () {
        Voucher.voucherModal.modal("show");
        Voucher.voucherModal.find("div.alert").hide();
    },
    
    showMessage: function (message) {
        Voucher.entryModal.find("div.alert").html(message).show();
    },
    
    showErrorMessage: function (message) {
        Voucher.voucherModal.find("div.alert").html(message).show();
    },
    
    clearMessage: function () {
        Voucher.entryModal.find("div.alert").hide();
    },
    
    showAdvanceModal: function () {
        Voucher.advanceModal.modal("show");
        Voucher.advanceModal.find("div.alert").hide();
    },
    
    getManageUrl: function (action) {
        return $("input#manage-url").val() + action;
    },
    
    
    notSelected: function (value) {
        return 0 === parseInt(value);
    },
    
    notNumber: function (value) {
        if ("" === value) {
            return true;
        }
        
        if (isNaN(value)) {
            return true;
        }
        
        if ("0" === value) {
            return true;
        }
        
        return ! Voucher.isAmount(value);        
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
        
        //return /^[1-9]\d*$/.test(value);   
        var p = new RegExp(/^[0-9]+([.][0-9]+)?$/);
        if(p.test(value)){
          return true;
        } else {
            return false;
        }
    }
};

$(document).ready(function () {
        Voucher.ready();
    }
);
