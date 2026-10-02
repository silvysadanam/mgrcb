"use strict";

var Receipt = {
    entryModal: null,
    receiptModal: null,
    HeadOptions: null,
    actions: {
        remove : 1, get_entry : 2, save_entry : 3, delete_entry : 4, edit_receipt : 5,
    },
    ready: function () {
        Receipt.entryModal = $("div#modal-entry");
        Receipt.receiptModal = $("div#modal-receiptEdit");
        $("a#add-entry").click(Receipt.addEntry);
        $("a#delete-transaction").click(Receipt.deleteReceipt);
        $("a.edit-entry").click(Receipt.editEntry);
        $("table#list-table-2").on("click", "a.edit-receipt", Receipt.editReceipt);
        $("a.delete-entry").click(Receipt.deleteEntry);
        $("form#form-receipt-modal").submit(Receipt.saveEntry);
        $("form#form-receiptEdit-modal").submit(Receipt.saveReceiptEntry);
    },
    
    addEntry: function () {
        Receipt.showEntryModal();
        Receipt.entryModal.find("h3.modal-title").text("Add Entry");
        Receipt.entryModal.find("input[type=number]").val("");
        Receipt.entryModal.find("select#head_id").val(0);
        Receipt.entryModal.find("input#entry-id").val(0);
    },
    
    editReceipt: function () {
        var entryId = $(this).data("id");
        Receipt.receiptModal.find("input#receipt_date").val($(this).data("date")); 
        Receipt.receiptModal.find("input#non_member").val($(this).data("non_member")); 
        Receipt.receiptModal.find("textarea#note").val($(this).data("note"));
        Receipt.receiptModal.find("input#entry-id").val(entryId);         
        Receipt.showEditModal();
        Receipt.clearMessage();
    },
    
    editEntry: function () {
        var entryId = $(this).data("id");
        var formData = { id : entryId };
        var formAction = Receipt.getManageUrl(Receipt.actions.get_entry);

        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    Receipt.showEntryModal();
                    Receipt.entryModal.find("h3.modal-title").text("Edit Entry");
                    Receipt.entryModal.find("select#head_id").val(reply.result.headID);
                    Receipt.entryModal.find("input#amount").val(reply.result.amount);
                    Receipt.entryModal.find("input#entry-id").val(entryId);
                } else if (reply.redirect) {
                    BranchCommon.reloadPage();
                } else if (reply.message) {
                    Receipt.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
        
        return false;
    },
    
    saveEntry: function () {
        Receipt.clearMessage();
        
        var headId = Receipt.entryModal.find("select#head_id").val();
        if ("0" === headId) {
            Receipt.showMessage("Head not selected");
            return false;
        }
        
        
        var entryAmount = Receipt.entryModal.find("input#amount").val();
        if (! Receipt.isAmount(entryAmount)) {
            Receipt.showMessage("Invalid amount");
            return false;
        }
        
        var entryId = Receipt.entryModal.find("input#entry-id").val();
        var formData = {
            id : entryId,
            headID : headId,
            amount : entryAmount
        };
        
        var formAction = Receipt.getManageUrl(Receipt.actions.save_entry);
        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    BranchCommon.reloadPage();
                } else if (reply.redirect) {
                    BranchCommon.reloadPage();
                } else if (reply.message) {
                    Receipt.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
        
        return false;
    },
    
      saveReceiptEntry: function () {
        Receipt.clearMessage();
        
        var receiptDate = Receipt.receiptModal.find("input#receipt_date").val();
        if ("" === receiptDate) {
            Receipt.showMessage("Date not selected");
            return false;
        }
        
        var nonMember = Receipt.receiptModal.find("input#non_member").val();
      /*  if ("" === nonMember) {
            Receipt.showMessage("Non Member details not provided");
            return false;
        }
      */  
        var remarks = Receipt.receiptModal.find("textarea#note").val();
        if ("" === remarks) {
            Receipt.showMessage("Narration details not provided");
            return false;
        }
        var entryId = Receipt.receiptModal.find("input#entry-id").val();
        var formData = {
            id : entryId,
            transDate : receiptDate,
            non_member : nonMember,
            remarks : remarks
        };
        
        var formAction = Receipt.getManageUrl(Receipt.actions.edit_receipt);
        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    BranchCommon.reloadPage();
                } else if (reply.redirect) {
                    BranchCommon.reloadPage();
                } else if (reply.message) {
                    Receipt.showErrorMessage(reply.message);
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
            var formAction = Receipt.getManageUrl(Receipt.actions.delete_entry);
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
    
    deleteReceipt: function () {
        if (confirm("Are you sure you to delete this Receipt?")) {
            var formData = { id : 0 };
            var formAction = Receipt.getManageUrl(Receipt.actions.remove);
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
        Receipt.entryModal.modal("show");
        Receipt.entryModal.find("div.alert").hide();
    },
    
    showMessage: function (message) {
        Receipt.entryModal.find("div.alert").html(message).show();
    },
    
      showEditModal: function () {
        Receipt.receiptModal.modal("show");
        Receipt.receiptModal.find("div.alert").hide();
    },
    
     showErrorMessage: function (message) {
        Receipt.receiptModal.find("div.alert").html(message).show();
    },
    
    clearMessage: function () {
        Receipt.entryModal.find("div.alert").hide();
    },
    
    showAdvanceModal: function () {
        Receipt.advanceModal.modal("show");
        Receipt.advanceModal.find("div.alert").hide();
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
        
        return ! Receipt.isAmount(value);        
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
        Receipt.ready();
    }
);
