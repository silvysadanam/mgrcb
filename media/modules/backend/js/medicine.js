"use strict";

var Medicine = {
    formModal: null,
    advanceModal: null,
    fishOptions: null,
    actions: {
        disable : 18, get_entry : 5, save_entry : 6, save_medicine : 8, delete_entry : 7, activate: 19, 
    },
    ready: function () {
        Medicine.formModal = $("div#modal-entry");
        Medicine.advanceModal = $("div#modal-advance");
        Medicine.setModal("modal-member");
        $("a#add-entry").click(Medicine.addEntry);
        $("a#delete-medicine").click(Medicine.deleteMedicine);
        $("a.edit-entry").click(Medicine.editEntry);
        $("a.delete-entry").click(Medicine.deleteEntry);
        $("a#add-medicine-type").click(Medicine.add);
        $("form#form-medicine-modal").submit(Medicine.saveMedicine);
      
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
        Medicine.setModal("modal-medicine");
        Medicine.formModal.find("h3#myModalLabel1").text("Plan for Medicine to Beneficiaries");
        Medicine.formModal.find("input[type=text]").val("");
        Medicine.formModal.find("select#prescription_valid").val(0);
        Medicine.formModal.find("select#hospital").val(0);
        Medicine.formModal.find("select#department").val(0);
        Medicine.formModal.find("input#last_bill_amount").val(0);
        return false;
    },
    
    saveMedicine: function () {
        Medicine.clearMessage();
        /*var lastBillAmount = Medicine.formModal.find("input#last_bill_amount").val();
        if (0 === lastBillAmount) {
            Medicine.showMessage("Please add bill amount");
            return false;
        }
        */
        var prescriptionValid = Medicine.formModal.find("select#prescription_valid").val();
        if (!prescriptionValid || prescriptionValid === "0") {
            Medicine.showMessage("Please select Prescription Valid For");
            return false;
        }
        
        var hospital = Medicine.formModal.find("select#hospital").val();
        if (!hospital || hospital === "0") {
            Medicine.showMessage("Please select Hospital");
            return false;
        }
        var department = Medicine.formModal.find("select#department").val();
        if (!department || department === "0") {
            Medicine.showMessage("Please select Department");
            return false;
        }
       
        var assessmentDate = Medicine.formModal.find("input#assessment_date").val();

        if (!assessmentDate) {
            Medicine.showMessage("Assessment Date not selected");
            return false;
        }
        
        var assessment_date = Medicine.formModal.find("input#assessment_date").val();
        var last_bill_amount = Medicine.formModal.find("input#last_bill_amount").val();
        var last_bill_date = Medicine.formModal.find("input#last_bill_date").val();
        
        var formData = {
            mid : Medicine.formModal.find("input#medicine-type-id").val(),
            reg_no : Medicine.formModal.find("input#reg_no").val(),
            diagnosis : Medicine.formModal.find("textarea#diagnosis").val(),
            assessment_date : assessment_date,
            prescription_valid : prescriptionValid,
            hospital : hospital,
            department : department,
            last_bill_amount : last_bill_amount,
            last_bill_date : last_bill_date,
            worker_opinion : Medicine.formModal.find("textarea#worker_opinion").val(),
            worker_name : Medicine.formModal.find("input#worker_name").val(),
            payment_type : Medicine.formModal.find("textarea#payment_type").val(),
            approved_date : Medicine.formModal.find("input#approved_date").val()
        };
        var formAction = Medicine.getManageUrl(Medicine.actions.save_medicine);
        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    BackendCommon.reloadPage();
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    Medicine.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
        
        return false;
    },
    
    addEntry: function () {
        Medicine.showEntryModal();
        Medicine.formModal.find("h3.modal-title").text("Add Entry");
        Medicine.formModal.find("input#entry-id").val(0);
    },
    
    editEntry: function () {
        var entryId = $(this).data("id");
        var formData = { id : entryId };
        var formAction = Medicine.getManageUrl(Medicine.actions.get_entry);
        
        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    Medicine.showEntryModal();
                    Medicine.formModal.find("h3.modal-title").text("Edit Entry");
                    Medicine.formModal.find("input#family_name").val(reply.result.family_name);
                    Medicine.formModal.find("input#family_relation").val(reply.result.family_relation);
                    Medicine.formModal.find("select#family_category").val(reply.result.family_category);
                    Medicine.formModal.find("input#family_age").val(reply.result.family_age);
                    Medicine.formModal.find("select#family_education").val(reply.result.family_education);
                    Medicine.formModal.find("input#family_disability").val(reply.result.family_disability);
                    Medicine.formModal.find("input#family_occupation").val(reply.result.family_occupation);
                    Medicine.formModal.find("input#family_income").val(reply.result.family_income);
                    Medicine.formModal.find("input#entry-id").val(entryId);
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    Medicine.showMessage(reply.message);
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
            var formAction = Medicine.getManageUrl(Medicine.actions.delete_entry);
            $.post(formAction, formData, function(reply) {
                    if (reply.success && reply.valid) {
                        BackendCommon.reloadPage();
                    } else if (reply.redirect) {
                        BackendCommon.reloadPage();
                    } else if (reply.message) {
                        Medicine.showMessage(reply.message);
                    } else {
                        alert("Unable to complete the request.");
                    }
                }, "json"
            );

            return false;
        }
        
        return false;
    },
    
    deleteMedicine: function () {
        if (confirm("Are you sure you to delete this Medicine?")) {
            var formData = { id : 0 };
            var formAction = Medicine.getManageUrl(Medicine.actions.remove);
            $.post(formAction, formData, function(reply) {
                    if (reply.success && reply.valid) {
                        window.location = $("input#cancel-url").val();
                    } else if (reply.redirect) {
                        BackendCommon.reloadPage();
                    } else if (reply.message) {
                        Medicine.showMessage(reply.message);
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
        Medicine.formModal.modal("show");
        Medicine.formModal.find("div.alert").hide();
    },
    
    showMessage: function (message) {
        Medicine.formModal.find("div.alert").html(message).show();
    },
    
    clearMessage: function () {
        Medicine.formModal.find("div.alert").hide();
    },
    
    showAdvanceModal: function () {
        Medicine.advanceModal.modal("show");
        Medicine.advanceModal.find("div.alert").hide();
    },
    
    getManageUrl: function (action) {
        return $("input#manage-url").val() + action;
    },
    
    
    notSelected: function (value) {
        return 0 === parseInt(value);
    },
    setModal: function (name) {
        Medicine.formModal = $("div#" + name);
        Medicine.formModal.modal("show");
        Medicine.formModal.find("div.alert").hide();
    },
    
    closeModal: function () {
        Medicine.formModal.modal("hide");
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
        
        return ! Medicine.isAmount(value);        
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
        Medicine.ready();
    }
);
