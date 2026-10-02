"use strict";

var Income = {
    entryModal: null,
    advanceModal: null,
    HeadOptions: null,
    actions: {
        remove : 1, get_entry : 2, save_entry : 3, delete_entry : 4
    },
    ready: function () {
        Income.entryModal = $("div#modal-entry");
        Income.advanceModal = $("div#modal-advance");
        $("a#add-entry").click(Income.addEntry);
        $("a#delete-transaction").click(Income.deleteIncome);
        $("a.edit-entry").click(Income.editEntry);
        $("a.delete-entry").click(Income.deleteEntry);
        $("form#form-receipt-modal").submit(Income.saveEntry);
    },
    
    addEntry: function () {
        Income.showEntryModal();
        Income.entryModal.find("h3.modal-title").text("Add Entry");
        Income.entryModal.find("input[type=number]").val("");
        Income.entryModal.find("select#head_id").val(0);
        Income.entryModal.find("input#entry-id").val(0);
    },
    
    editEntry: function () {
        var entryId = $(this).data("id");
        var formData = { id : entryId };
        var formAction = Income.getManageUrl(Income.actions.get_entry);

        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    Income.showEntryModal();
                    Income.entryModal.find("h3.modal-title").text("Edit Entry");
                    Income.entryModal.find("select#head_id").val(reply.result.headID);
                    Income.entryModal.find("input#amount").val(reply.result.amount);
                    Income.entryModal.find("input#entry-id").val(entryId);
                } else if (reply.redirect) {
                    ApplicationCommon.reloadPage();
                } else if (reply.message) {
                    Income.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
        
        return false;
    },
    
    saveEntry: function () {
        Income.clearMessage();
        
        var headId = Income.entryModal.find("select#head_id").val();
        if ("0" === headId) {
            Income.showMessage("Head not selected");
            return false;
        }
        
        
        var entryAmount = Income.entryModal.find("input#amount").val();
        if (! Income.isAmount(entryAmount)) {
            Income.showMessage("Invalid amount");
            return false;
        }
        
        var entryId = Income.entryModal.find("input#entry-id").val();
        var formData = {
            id : entryId,
            headID : headId,
            amount : entryAmount
        };
        
        var formAction = Income.getManageUrl(Income.actions.save_entry);
        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    ApplicationCommon.reloadPage();
                } else if (reply.redirect) {
                    ApplicationCommon.reloadPage();
                } else if (reply.message) {
                    ApplicationCommon.showMessage(reply.message);
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
            var formAction = Income.getManageUrl(Income.actions.delete_entry);
            $.post(formAction, formData, function(reply) {
                    if (reply.success && reply.valid) {
                        ApplicationCommon.reloadPage();
                    } else if (reply.redirect) {
                        ApplicationCommon.reloadPage();
                    } else if (reply.message) {
                        ApplicationCommon.showMessage(reply.message);
                    } else {
                        alert("Unable to complete the request.");
                    }
                }, "json"
            );

            return false;
        }
        
        return false;
    },
    
    deleteIncome: function () {
        if (confirm("Are you sure you to delete this Income?")) {
            var formData = { id : 0 };
            var formAction = Income.getManageUrl(Income.actions.remove);
            $.post(formAction, formData, function(reply) {
                    if (reply.success && reply.valid) {
                        window.location = $("input#cancel-url").val();
                    } else if (reply.redirect) {
                        ApplicationCommon.reloadPage();
                    } else if (reply.message) {
                        ApplicationCommon.showMessage(reply.message);
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
        Income.entryModal.modal("show");
        Income.entryModal.find("div.alert").hide();
    },
    
    showMessage: function (message) {
        Income.entryModal.find("div.alert").html(message).show();
    },
    
    clearMessage: function () {
        Income.entryModal.find("div.alert").hide();
    },
    
    showAdvanceModal: function () {
        Income.advanceModal.modal("show");
        Income.advanceModal.find("div.alert").hide();
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
        
        return ! Income.isAmount(value);        
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
        Income.ready();
    }
);
