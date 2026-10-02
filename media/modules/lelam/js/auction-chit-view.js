"use strict";

var AuctionChit = {
    entryModal: null,
    advanceModal: null,
    fishOptions: null,
    actions: {
        remove : 1, get_entry : 2, save_entry : 3, delete_entry : 4, save_advance : 5
    },
    ready: function () {
        AuctionChit.entryModal = $("div#modal-entry");
        AuctionChit.advanceModal = $("div#modal-advance");
        $("a#add-entry").click(AuctionChit.addEntry);
        $("a#edit-advance").click(AuctionChit.editAdvance);
        $("a#delete-auction-chit").click(AuctionChit.deleteAuctionChit);
        $("a.edit-entry").click(AuctionChit.editEntry);
        $("a.delete-entry").click(AuctionChit.deleteEntry);
        $("form#form-auction-modal").submit(AuctionChit.saveEntry);
        $("form#form-advance-modal").submit(AuctionChit.saveAdvance);
    },
    
    addEntry: function () {
        AuctionChit.showEntryModal();
        AuctionChit.entryModal.find("h3.modal-title").text("Add Entry");
        AuctionChit.entryModal.find("input[type=number]").val("");
        AuctionChit.entryModal.find("select#fish_id").val(0);
        AuctionChit.entryModal.find("input#entry-id").val(0);
    },
    
    editEntry: function () {
        var entryId = $(this).data("id");
        var formData = { id : entryId };
        var formAction = AuctionChit.getManageUrl(AuctionChit.actions.get_entry);
        
        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    AuctionChit.showEntryModal();
                    AuctionChit.entryModal.find("h3.modal-title").text("Edit Entry");
                    AuctionChit.entryModal.find("select#fish_id").val(reply.result.fish_id);
                    AuctionChit.entryModal.find("input#amount").val(reply.result.amount);
                    AuctionChit.entryModal.find("input#quantity").val(reply.result.quantity);
                    AuctionChit.entryModal.find("input#entry-id").val(entryId);
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
    },
    
    saveEntry: function () {
        AuctionChit.clearMessage();
        
        var fishId = AuctionChit.entryModal.find("select#fish_id").val();
        if ("0" === fishId) {
            AuctionChit.showMessage("Medicine not selected");
            return false;
        }
        
        
        var entryAmount = AuctionChit.entryModal.find("input#amount").val();
        if (! AuctionChit.isAmount(entryAmount)) {
            AuctionChit.showMessage("Invalid amount");
            return false;
        }
        
        var entryQuantity = AuctionChit.entryModal.find("input#quantity").val();
        if (! AuctionChit.isQuantity(entryQuantity)) {
            AuctionChit.showMessage("Invalid Weight");
            return false;
        }

        var entryId = AuctionChit.entryModal.find("input#entry-id").val();
        var formData = {
            id : entryId,
            fish_id : fishId,
            amount : entryAmount,
            quantity : entryQuantity
        };
        
        var formAction = AuctionChit.getManageUrl(AuctionChit.actions.save_entry);
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
    },
    
    editAdvance: function () {
        AuctionChit.showAdvanceModal();
        AuctionChit.advanceModal.find("input#advance").val($("input#current-advance").val());
    },
    
    saveAdvance: function () {
        AuctionChit.advanceModal.find("div.alert").hide();
        var advanceAmount = AuctionChit.advanceModal.find("input#advance").val();
        if (! AuctionChit.isQuantity(advanceAmount)) {
            AuctionChit.advanceModal.find("div.alert").html("Invalid Advance").show();
            return false;
        }
        
        var formData = {
            id : 0,
            advance : advanceAmount
        };
        
        var formAction = AuctionChit.getManageUrl(AuctionChit.actions.save_advance);
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
    },
    
    deleteEntry: function () {
        if (confirm("Are you sure you to delete this Entry?")) {
            var entryId = $(this).data("id");
            var formData = { id : entryId };
            var formAction = AuctionChit.getManageUrl(AuctionChit.actions.delete_entry);
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
    
    deleteAuctionChit: function () {
        if (confirm("Are you sure you to delete this Auction Chit?")) {
            var formData = { id : 0 };
            var formAction = AuctionChit.getManageUrl(AuctionChit.actions.remove);
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
        AuctionChit.entryModal.modal("show");
        AuctionChit.entryModal.find("div.alert").hide();
    },
    
    showMessage: function (message) {
        AuctionChit.entryModal.find("div.alert").html(message).show();
    },
    
    clearMessage: function () {
        AuctionChit.entryModal.find("div.alert").hide();
    },
    
    showAdvanceModal: function () {
        AuctionChit.advanceModal.modal("show");
        AuctionChit.advanceModal.find("div.alert").hide();
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
        
        return ! AuctionChit.isAmount(value);        
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
    },
    
    isQuantity: function (value) {
        if ("" === value) {
            return true;
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
        AuctionChit.ready();
    }
);
