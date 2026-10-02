"use strict";

var Beneficiary = {
    entryModal: null,
    advanceModal: null,
    fishOptions: null,
    actions: {
        disable : 18, get_entry : 5, save_entry : 6, delete_entry : 7, activate: 19
    },
    ready: function () {
        Beneficiary.entryModal = $("div#modal-entry");
        Beneficiary.advanceModal = $("div#modal-advance");
        Beneficiary.setModal("modal-member");
        $("a#add-entry").click(Beneficiary.addEntry);
        $("a#edit-advance").click(Beneficiary.editAdvance);
        $("a#delete-beneficiary").click(Beneficiary.deleteBeneficiary);
        $("a.edit-entry").click(Beneficiary.editEntry);
        $("a.delete-entry").click(Beneficiary.deleteEntry);
        $("form#form-auction-modal").submit(Beneficiary.saveEntry);
        $("form#form-advance-modal").submit(Beneficiary.saveAdvance);
        $("a#disable-beneficiary").click(Beneficiary.showDisable);
        $("a#enable-beneficiary").click(Beneficiary.enableMembership);
        $("form#form-disable-modal").submit(Beneficiary.disableMembership);
        
    },
    
    addEntry: function () {
        Beneficiary.showEntryModal();
        Beneficiary.entryModal.find("h3.modal-title").text("Add Entry");
        Beneficiary.entryModal.find("input#entry-id").val(0);
    },
    
    editEntry: function () {
        var entryId = $(this).data("id");
        var formData = { id : entryId };
        var formAction = Beneficiary.getManageUrl(Beneficiary.actions.get_entry);
        
        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    Beneficiary.showEntryModal();
                    Beneficiary.entryModal.find("h3.modal-title").text("Edit Entry");
                    Beneficiary.entryModal.find("input#family_name").val(reply.result.family_name);
                    Beneficiary.entryModal.find("input#family_relation").val(reply.result.family_relation);
                    Beneficiary.entryModal.find("select#family_category").val(reply.result.family_category);
                    Beneficiary.entryModal.find("input#family_age").val(reply.result.family_age);
                    Beneficiary.entryModal.find("select#family_education").val(reply.result.family_education);
                    Beneficiary.entryModal.find("input#family_disability").val(reply.result.family_disability);
                    Beneficiary.entryModal.find("input#family_occupation").val(reply.result.family_occupation);
                    Beneficiary.entryModal.find("input#family_income").val(reply.result.family_income);
                    Beneficiary.entryModal.find("input#entry-id").val(entryId);
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    Beneficiary.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
        
        return false;
    },
    
    saveEntry: function () {
        Beneficiary.clearMessage();
        
        var familyName = Beneficiary.entryModal.find("input#family_name").val();
        if ("" === familyName) {
            Beneficiary.showMessage("Please add Name");
            return false;
        }
        
        var familyRelation = Beneficiary.entryModal.find("input#family_relation").val();
        if ("" === familyRelation) {
            Beneficiary.showMessage("Please add Relationship");
            return false;
        }
        
        var familyCategory = Beneficiary.entryModal.find("select#family_category").val();
        if (!familyCategory || familyCategory === "0") {
            Beneficiary.showMessage("Please select Gender");
            return false;
        }
        var familyAge = Beneficiary.entryModal.find("input#family_age").val();
        if ("" === familyAge) {
            Beneficiary.showMessage("Please add Age");
            return false;
        }
        var entryId = Beneficiary.entryModal.find("input#entry-id").val();
        var familyEducation = Beneficiary.entryModal.find("select#family_education").val();
        if (!familyEducation || familyEducation === "0") {
            Beneficiary.showMessage("Please select Education");
            return false;
        }
       
        var familyDisability = Beneficiary.entryModal.find("input#family_disability").val();
        var familyOccupation = Beneficiary.entryModal.find("input#family_occupation").val();
        var familyIncome = Beneficiary.entryModal.find("input#family_income").val();
        
        var formData = {
            id : entryId,
            family_name : familyName,
            family_relation : familyRelation,
            family_category : familyCategory,
            family_age : familyAge,
            family_education : familyEducation,
            family_disability : familyDisability,
            family_occupation : familyOccupation,
            family_income : familyIncome
        };
        
        var formAction = Beneficiary.getManageUrl(Beneficiary.actions.save_entry);
        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    BackendCommon.reloadPage();
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    Beneficiary.showMessage(reply.message);
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
            var formAction = Beneficiary.getManageUrl(Beneficiary.actions.delete_entry);
            $.post(formAction, formData, function(reply) {
                    if (reply.success && reply.valid) {
                        BackendCommon.reloadPage();
                    } else if (reply.redirect) {
                        BackendCommon.reloadPage();
                    } else if (reply.message) {
                        Beneficiary.showMessage(reply.message);
                    } else {
                        alert("Unable to complete the request.");
                    }
                }, "json"
            );

            return false;
        }
        
        return false;
    },
    
    deleteBeneficiary: function () {
        if (confirm("Are you sure you to delete this Beneficiary?")) {
            var formData = { id : 0 };
            var formAction = Beneficiary.getManageUrl(Beneficiary.actions.remove);
            $.post(formAction, formData, function(reply) {
                    if (reply.success && reply.valid) {
                        window.location = $("input#cancel-url").val();
                    } else if (reply.redirect) {
                        BackendCommon.reloadPage();
                    } else if (reply.message) {
                        Beneficiary.showMessage(reply.message);
                    } else {
                        alert("Unable to complete the request.");
                    }
                }, "json"
            );

            return false;
        }
        
        return false;
    },
    enableMembership: function () {
        if (confirm("Are you sure you to activate this member?")) {
            var formData = { id : 0 };
            $.post(Beneficiary.getManageUrl(Beneficiary.actions.activate), formData, function(reply) {
                    if (reply.success && reply.valid) {
                        BackendCommon.reloadPage();
                    } else if (reply.redirect) {
                        BackendCommon.reloadPage();
                    } else if (reply.message) {
                        Beneficiary.showMessage(reply.message);
                    } else {
                        alert("Unable to complete the request.");
                    }
                }, "json"
            );
        }
        
        return false;
    },
    
    showDisable: function () {
        if (confirm("Are you sure you to disable this member?")) {
            Beneficiary.setModal("modal-disable");
            Beneficiary.formModal.find("input#inactive_note").val("");
        }
        
        return false;
    },
    
    disableMembership: function () {
        var formData = {
            id : Beneficiary.formModal.find("input#inactive_date").val(),
            note : Beneficiary.formModal.find("input#inactive_note").val()
        };
        
        $.post(Beneficiary.getManageUrl(Beneficiary.actions.disable), formData, function(reply) {
                if (reply.success && reply.valid) {
                    BackendCommon.reloadPage();
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    Beneficiary.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    showEntryModal: function () {
        Beneficiary.entryModal.modal("show");
        Beneficiary.entryModal.find("div.alert").hide();
    },
    
    showMessage: function (message) {
        Beneficiary.entryModal.find("div.alert").html(message).show();
    },
    
    clearMessage: function () {
        Beneficiary.entryModal.find("div.alert").hide();
    },
    
    showAdvanceModal: function () {
        Beneficiary.advanceModal.modal("show");
        Beneficiary.advanceModal.find("div.alert").hide();
    },
    
    getManageUrl: function (action) {
        return $("input#manage-url").val() + action;
    },
    
    
    notSelected: function (value) {
        return 0 === parseInt(value);
    },
    setModal: function (name) {
        Beneficiary.formModal = $("div#" + name);
        Beneficiary.formModal.modal("show");
        Beneficiary.formModal.find("div.alert").hide();
    },
    
    closeModal: function () {
        Beneficiary.formModal.modal("hide");
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
        
        return ! Beneficiary.isAmount(value);        
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
        Beneficiary.ready();
    }
);
