"use strict";

var Equipment = {
    formModal: null,
    advanceModal: null,
    fishOptions: null,
    actions: {
        disable : 18, get_entry : 5, save_entry : 6, save_equipment : 24, delete_entry : 7, activate: 19, 
    },
    ready: function () {
        Equipment.formModal = $("div#modal-entry");
        Equipment.advanceModal = $("div#modal-advance");
        Equipment.setModal("modal-member");
        $("a#add-entry").click(Equipment.addEntry);
        $("a#delete-equipment").click(Equipment.deleteEquipment);
        $("a.edit-entry").click(Equipment.editEntry);
        $("a.delete-entry").click(Equipment.deleteEntry);
        $("a#add-equipment-type").click(Equipment.add);
        $("form#form-equipment-modal").submit(Equipment.saveEquipment);
      
        $("input#assessment_date").daterangepicker({
            timePicker: false,
            singleDatePicker: true,
            autoUpdateInput: false,
            showDropdowns: true,
            minDate: $("input#start-date").val(),
            maxDate: $("input#end-date").val(),
            locale: {
                format: BackendCommon.dateFormat,
                cancelLabel: "Clear"
            }
        });
        
        $("input#assessment_date").on('apply.daterangepicker', function(ev, picker) {
              $(this).val(picker.startDate.format(picker.locale.format));
        });

        $("input#assessment_date").on('cancel.daterangepicker', function(ev, picker) {
            $(this).val("");
        });
        
    },
   add: function () {
        Equipment.setModal("modal-equipment");
        Equipment.formModal.find("h3#myModalLabel1").text("Plan for Equipment to Beneficiaries");
        Equipment.formModal.find("input[type=text]").val("");
        Equipment.formModal.find("select#hospital").val(0);
        Equipment.formModal.find("select#department").val(0);
        Equipment.formModal.find("input#last_bill_amount").val(0);
        return false;
    },
    
    saveEquipment: function () {
        Equipment.clearMessage();
        /*var lastBillAmount = Equipment.formModal.find("input#last_bill_amount").val();
        if (0 === lastBillAmount) {
            Equipment.showMessage("Please add bill amount");
            return false;
        }
        */
        var equipment = Equipment.formModal.find("textarea#equipment").val();
        if (!equipment || equipment === "") {
            Equipment.showMessage("Please fill Name of Equipment");
            return false;
        }
        
        var hospital = Equipment.formModal.find("select#hospital").val();
        if (!hospital || hospital === "0") {
            Equipment.showMessage("Please select Hospital");
            return false;
        }
        var department = Equipment.formModal.find("select#department").val();
        if (!department || department === "0") {
            Equipment.showMessage("Please select Department");
            return false;
        }
       
        var assessmentDate = Equipment.formModal.find("input#assessment_date").val();

        if (!assessmentDate) {
            Equipment.showMessage("Assessment Date not selected");
            return false;
        }
        
        var assessment_date = Equipment.formModal.find("input#assessment_date").val();
        var last_bill_amount = Equipment.formModal.find("input#last_bill_amount").val();
        var last_bill_date = Equipment.formModal.find("input#last_bill_date").val();
        
        var formData = {
            mid : Equipment.formModal.find("input#equipment-type-id").val(),
            reg_no : Equipment.formModal.find("input#reg_no").val(),
            diagnosis : Equipment.formModal.find("textarea#diagnosis").val(),
            assessment_date : assessment_date,
            equipment : equipment,
            hospital : hospital,
            department : department,
            last_bill_amount : last_bill_amount,
            last_bill_date : last_bill_date,
            worker_opinion : Equipment.formModal.find("textarea#worker_opinion").val(),
            worker_name : Equipment.formModal.find("input#worker_name").val(),
            payment_type : Equipment.formModal.find("textarea#payment_type").val(),
            approved_date : Equipment.formModal.find("input#approved_date").val()
        };
        var formAction = Equipment.getManageUrl(Equipment.actions.save_equipment);
        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    BackendCommon.reloadPage();
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    Equipment.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
        
        return false;
    },
    
    addEntry: function () {
        Equipment.showEntryModal();
        Equipment.formModal.find("h3.modal-title").text("Add Entry");
        Equipment.formModal.find("input#entry-id").val(0);
    },
    
    editEntry: function () {
        var entryId = $(this).data("id");
        var formData = { id : entryId };
        var formAction = Equipment.getManageUrl(Equipment.actions.get_entry);
        
        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    Equipment.showEntryModal();
                    Equipment.formModal.find("h3.modal-title").text("Edit Entry");
                    Equipment.formModal.find("input#family_name").val(reply.result.family_name);
                    Equipment.formModal.find("input#family_relation").val(reply.result.family_relation);
                    Equipment.formModal.find("select#family_category").val(reply.result.family_category);
                    Equipment.formModal.find("input#family_age").val(reply.result.family_age);
                    Equipment.formModal.find("select#family_education").val(reply.result.family_education);
                    Equipment.formModal.find("input#family_disability").val(reply.result.family_disability);
                    Equipment.formModal.find("input#family_occupation").val(reply.result.family_occupation);
                    Equipment.formModal.find("input#family_income").val(reply.result.family_income);
                    Equipment.formModal.find("input#entry-id").val(entryId);
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    Equipment.showMessage(reply.message);
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
            var formAction = Equipment.getManageUrl(Equipment.actions.delete_entry);
            $.post(formAction, formData, function(reply) {
                    if (reply.success && reply.valid) {
                        BackendCommon.reloadPage();
                    } else if (reply.redirect) {
                        BackendCommon.reloadPage();
                    } else if (reply.message) {
                        Equipment.showMessage(reply.message);
                    } else {
                        alert("Unable to complete the request.");
                    }
                }, "json"
            );

            return false;
        }
        
        return false;
    },
    
    deleteEquipment: function () {
        if (confirm("Are you sure you to delete this Equipment?")) {
            var formData = { id : 0 };
            var formAction = Equipment.getManageUrl(Equipment.actions.remove);
            $.post(formAction, formData, function(reply) {
                    if (reply.success && reply.valid) {
                        window.location = $("input#cancel-url").val();
                    } else if (reply.redirect) {
                        BackendCommon.reloadPage();
                    } else if (reply.message) {
                        Equipment.showMessage(reply.message);
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
        Equipment.formModal.modal("show");
        Equipment.formModal.find("div.alert").hide();
    },
    
    showMessage: function (message) {
        Equipment.formModal.find("div.alert").html(message).show();
    },
    
    clearMessage: function () {
        Equipment.formModal.find("div.alert").hide();
    },
    
    showAdvanceModal: function () {
        Equipment.advanceModal.modal("show");
        Equipment.advanceModal.find("div.alert").hide();
    },
    
    getManageUrl: function (action) {
        return $("input#manage-url").val() + action;
    },
    
    
    notSelected: function (value) {
        return 0 === parseInt(value);
    },
    setModal: function (name) {
        Equipment.formModal = $("div#" + name);
        Equipment.formModal.modal("show");
        Equipment.formModal.find("div.alert").hide();
    },
    
    closeModal: function () {
        Equipment.formModal.modal("hide");
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
        
        return ! Equipment.isAmount(value);        
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
        Equipment.ready();
    }
);
