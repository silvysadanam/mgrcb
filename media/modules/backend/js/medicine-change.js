"use strict";

var BeneficiaryMedicine = {
    formModal : null,
    dummyData : { id : 0 },
    actions : {
        get_medicine : 20, save_medicine : 8, get_medicine_document : 22, save_medicine_document_with_file : 21, 
        save_document_without_file : 11, remove_medicine_document : 23, 
        save_photo : 13, get_account : 14, save_account : 15, 
        
    },
    requiredSymbol : ' <span class="red">*</span>',
    ready: function () {
        $("a#edit-medicine").click(BeneficiaryMedicine.editMember);
        $("form#form-medicine-modal").submit(BeneficiaryMedicine.saveMedicine);
    
        $("a#add-document").click(BeneficiaryMedicine.addDocument);
        $("a.edit-document").click(BeneficiaryMedicine.editDocument);
        $("form#form-document-modal").submit(BeneficiaryMedicine.saveDocument);
        $("a.delete-document").click(BeneficiaryMedicine.deleteDocument);
        
        $("input#date_of_birth").daterangepicker({
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
        $("#modal-medicine select.select2").select2({ width: "100%", dropdownParent: $("#modal-medicine") });
        $("input#date_of_birth").on('apply.daterangepicker', function(ev, picker) {
              $(this).val(picker.startDate.format(picker.locale.format));
        });

        $("input#date_of_birth").on('cancel.daterangepicker', function(ev, picker) {
            $(this).val("");
        });
        
        $("a#clear-date-of-birth").click(function () {
                $("input#date_of_birth").val("");
            }
        );
       
        $("a#add-account").click(BeneficiaryMedicine.addAccount);
        $("a.edit-account").click(BeneficiaryMedicine.editAccount);
        $("form#form-account-modal").submit(BeneficiaryMedicine.saveAccount);
        $("a.delete-account").click(BeneficiaryMedicine.deleteAccount);
    },
    
    editMember: function () {
        $.post(BeneficiaryMedicine.getUrl(BeneficiaryMedicine.actions.get_medicine), BeneficiaryMedicine.dummyData, function(reply) {
                if (reply.success && reply.valid) {
                    BeneficiaryMedicine.setModal("modal-medicine");
                    BeneficiaryMedicine.formModal.find("input#reg_no").val(reply.result.reg_no);
                    BeneficiaryMedicine.formModal.find("textarea#diagnosis").val(reply.result.diagnosis);
                    BeneficiaryMedicine.formModal.find("input#assessment_date").val(reply.result.assessment_date);
                    BeneficiaryMedicine.formModal.find("select#prescription_valid").val(reply.result.prescription_valid);
                    BeneficiaryMedicine.formModal.find("select#hospital").val(reply.result.hospital);
                    BeneficiaryMedicine.formModal.find("select#department").val(reply.result.department);
                    BeneficiaryMedicine.formModal.find("input#last_bill_amount").val(reply.result.last_bill_amount);
                    BeneficiaryMedicine.formModal.find("input#last_bill_date").val(reply.result.last_bill_date);
                    BeneficiaryMedicine.formModal.find("textarea#worker_opinion").val(reply.result.worker_opinion);
                    BeneficiaryMedicine.formModal.find("input#worker_name").val(reply.result.worker_name);
                    BeneficiaryMedicine.formModal.find("textarea#payment_type").val(reply.result.payment_type);
                    BeneficiaryMedicine.formModal.find("input#approved_date").val(reply.result.approved_date);
                    BeneficiaryMedicine.formModal.find("input#medicine-type-id").val(reply.result.id);
                    
                    
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    BackendCommon.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    saveMedicine: function () {
        
        BeneficiaryMedicine.clearMessage();
        /*var lastBillAmount = Medicine.formModal.find("input#last_bill_amount").val();
        if (0 === lastBillAmount) {
            Medicine.showMessage("Please add bill amount");
            return false;
        }
        */
       
        var prescriptionValid = BeneficiaryMedicine.formModal.find("select#prescription_valid").val();
        if (!prescriptionValid || prescriptionValid === "0") {
            BeneficiaryMedicine.showMessage("Please select Prescription Valid For");
            return false;
        }
        
        var hospital = BeneficiaryMedicine.formModal.find("select#hospital").val();
        if (!hospital || hospital === "0") {
            BeneficiaryMedicine.showMessage("Please select Hospital");
            return false;
        }
        var department = BeneficiaryMedicine.formModal.find("select#department").val();
        if (!department || department === "0") {
            BeneficiaryMedicine.showMessage("Please select Department");
            return false;
        }
       
        var assessmentDate = BeneficiaryMedicine.formModal.find("input#assessment_date").val();

        if (!assessmentDate) {
            BeneficiaryMedicine.showMessage("Assessment Date not selected");
            return false;
        }
        
        var assessment_date = BeneficiaryMedicine.formModal.find("input#assessment_date").val();
        var last_bill_amount = BeneficiaryMedicine.formModal.find("input#last_bill_amount").val();
        var last_bill_date = BeneficiaryMedicine.formModal.find("input#last_bill_date").val();
        var formData = {
            mid : BeneficiaryMedicine.formModal.find("input#medicine-type-id").val(),
            reg_no : BeneficiaryMedicine.formModal.find("input#reg_no").val(),
            diagnosis : BeneficiaryMedicine.formModal.find("textarea#diagnosis").val(),
            assessment_date : assessment_date,
            prescription_valid : prescriptionValid,
            hospital : hospital,
            department : department,
            last_bill_amount : last_bill_amount,
            last_bill_date : last_bill_date,
            worker_opinion : BeneficiaryMedicine.formModal.find("textarea#worker_opinion").val(),
            worker_name : BeneficiaryMedicine.formModal.find("input#worker_name").val(),
            payment_type : BeneficiaryMedicine.formModal.find("textarea#payment_type").val(),
            approved_date : BeneficiaryMedicine.formModal.find("input#approved_date").val()
        };
        var formAction = BeneficiaryMedicine.getUrl(BeneficiaryMedicine.actions.save_medicine);
        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    BackendCommon.reloadPage();
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    BeneficiaryMedicine.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
        
        return false;
    },
    
    addDocument : function () {
        BeneficiaryMedicine.setModal("modal-document");
        BeneficiaryMedicine.formModal.find("h3#myModalLabel4").text("Add Document");
        BeneficiaryMedicine.formModal.find("input#description").val("");
        BeneficiaryMedicine.formModal.find("select").val(0);
        BeneficiaryMedicine.formModal.find("input#document-id").val(0);
        BeneficiaryMedicine.formModal.find("input#document_file").prop("required", true).val("");
        $("div#document-field").find("label:first").html("Upload file" + BeneficiaryMedicine.requiredSymbol);
    },
    
    editDocument: function () {
        var documentId = $(this).data("id");
        var formData = { id : documentId };
        
        $.post(BeneficiaryMedicine.getUrl(BeneficiaryMedicine.actions.get_medicine_document), formData, function(reply) {
                if (reply.success && reply.valid) {
                    BeneficiaryMedicine.setModal("modal-document");
                    BeneficiaryMedicine.formModal.find("h3#myModalLabel4").text("Edit Document");
                    BeneficiaryMedicine.formModal.find("input#description").val(reply.result.description);
                    BeneficiaryMedicine.formModal.find("select#document_type_id").val(reply.result.document_type_id);
                    BeneficiaryMedicine.formModal.find("input#document-id").val(documentId);
                    BeneficiaryMedicine.formModal.find("input#document_file").prop("required", false).val("");
                    $("div#document-field").find("label:first").html("Replace file (optional)");
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    BeneficiaryMedicine.setModal("modal-document");
                    BeneficiaryMedicine.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    saveDocument: function () {
        
        BeneficiaryMedicine.clearMessage();
        if ("0" === BeneficiaryMedicine.formModal.find("select#document_type_id").val()) {
            BeneficiaryMedicine.showMessage("Docuemnt Type not selected");
            return false;
        }
       
        var documentId = BeneficiaryMedicine.formModal.find("input#document-id").val();
        var fileField = $("input#document_file");
        var fileTitle = "Document";
        if (fileField.get(0).files.length > 0) {
            if (! DocumentValidator.validate(fileField)) {
                BeneficiaryMedicine.showMessage(DocumentValidator.getMessage(fileTitle));
                fileField.val("");
                return false;
            }
        }
        
        var fileData = new FormData();
        if (fileField.get(0).files.length > 0) {
            fileData.append("document", fileField.get(0).files[0]);
            fileData.append("document_id", documentId);
            fileData.append("document_type_id", BeneficiaryMedicine.formModal.find("select#document_type_id").val());
            fileData.append("description", BeneficiaryMedicine.formModal.find("input#description").val());
            $.ajax({
                    url : BeneficiaryMedicine.getUrl(BeneficiaryMedicine.actions.save_medicine_document_with_file),
                    method: "POST",
                    data: fileData,
                    contentType: false,
                    cache: false,
                    processData: false,
                    success: function(reply) {
                        if (reply.valid) {
                            BackendCommon.reloadPage();
                        } else {
                            BeneficiaryMedicine.showMessage(reply.message);
                        }
                    }
                }
            );
        } else {
            var formData = {
                id : documentId,
                document_type_id : BeneficiaryMedicine.formModal.find("select#document_type_id").val(),
                description : BeneficiaryMedicine.formModal.find("input#description").val()
            };

            $.post(BeneficiaryMedicine.getUrl(BeneficiaryMedicine.actions.save_medicine_document_with_file), formData, function(reply) {
                    if (reply.success && reply.valid) {
                        BackendCommon.reloadPage();
                    } else if (reply.redirect) {
                        BackendCommon.reloadPage();
                    } else if (reply.message) {
                        BeneficiaryMedicine.showMessage(reply.message);
                    } else {
                        alert("Unable to complete the request.");
                    }
                }, "json"
            );
        }
        
        fileField.val("");
        
        return false;
    },
    
    deleteDocument: function () {
        if (confirm("Are you sure you to delete this Document?")) {
            var documentId = $(this).data("id");
            $.post(BeneficiaryMedicine.getUrl(BeneficiaryMedicine.actions.remove_medicine_document), { id : documentId }, function(reply) {
                    if (reply.success && reply.valid) {
                        BackendCommon.reloadPage();
                    } else if (reply.redirect) {
                        BackendCommon.reloadPage();
                    } else if (reply.message) {
                        BeneficiaryMedicine.showMessage(reply.message);
                    } else {
                        alert("Unable to complete the request.");
                    }
                }, "json"
            );
        }
        
        return false;
    },
    
    photoForm: function () {
        BeneficiaryMedicine.setModal("modal-photo");
        BeneficiaryMedicine.formModal.find("input[type=file]").val("");
        
        return false;
    },
    
    uploadPhoto: function () {
        BeneficiaryMedicine.clearMessage();
        var fileField = $("input#photo_file");
        var fileTitle = "Photo";
        if (fileField.get(0).files.length > 0) {
            if (! PhotoValidator.validate(fileField)) {
                BeneficiaryMedicine.showMessage(PhotoValidator.getMessage(fileTitle));
                fileField.val("");
                return false;
            }
        } else {
            return false;
        }
        
        var fileData = new FormData();
        if (fileField.get(0).files.length > 0) {
            fileData.append("photo", fileField.get(0).files[0]);
            $.ajax({
                    url : BeneficiaryMedicine.getUrl(BeneficiaryMedicine.actions.save_photo),
                    method: "POST",
                    data: fileData,
                    contentType: false,
                    cache: false,
                    processData: false,
                    success: function(reply) {
                        if (reply.valid) {
                            BackendCommon.reloadPage();
                        } else {
                            BeneficiaryMedicine.showMessage(reply.message);
                        }
                    }
                }
            );
        }
        
        fileField.val("");
        return false;
    },
    
    addAccount: function () {
        BeneficiaryMedicine.setModal("modal-account");
        BeneficiaryMedicine.formModal.find("h3#myModalLabel6").text("Add Bank Account");
        BeneficiaryMedicine.formModal.find("input[type=text]").val("");
        BeneficiaryMedicine.formModal.find("select").val(0);
        BeneficiaryMedicine.formModal.find("input#account-id").val(0);
        
        return false;
    },
    
    editAccount: function () {
        var accountId = $(this).data("id");
        var formData = { id : accountId };
        $.post(BeneficiaryMedicine.getUrl(BeneficiaryMedicine.actions.get_account), formData, function(reply) {
                if (reply.success && reply.valid) {
                    BeneficiaryMedicine.setModal("modal-account");
                    BeneficiaryMedicine.formModal.find("h3#myModalLabel6").text("Edit Bank Account");
                    BeneficiaryMedicine.formModal.find("input#account_number").val(reply.result.account_number);
                    BeneficiaryMedicine.formModal.find("select#bank_id").val(reply.result.bank_id);
                    BeneficiaryMedicine.formModal.find("select#account_type_id").val(reply.result.account_type_id);
                    BeneficiaryMedicine.formModal.find("input#description2").val(reply.result.description);
                    BeneficiaryMedicine.formModal.find("input#account-id").val(accountId);
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    BeneficiaryMedicine.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    saveAccount: function () {
        BeneficiaryMedicine.clearMessage();
        if ("0" === BeneficiaryMedicine.formModal.find("select#bank_id").val()) {
            BeneficiaryMedicine.showMessage("Bank not selected");
            return false;
        }
        
        if ("0" === BeneficiaryMedicine.formModal.find("select#account_type_id").val()) {
            BeneficiaryMedicine.showMessage("Account type not selected");
            return false;
        }
        
        var formData = {
            id : BeneficiaryMedicine.formModal.find("input#account-id").val(),
            bank_id : BeneficiaryMedicine.formModal.find("select#bank_id").val(),
            account_number : BeneficiaryMedicine.formModal.find("input#account_number").val(),
            account_type_id : BeneficiaryMedicine.formModal.find("select#account_type_id").val(),
            description : BeneficiaryMedicine.formModal.find("input#description2").val()
        };
        
        $.post(BeneficiaryMedicine.getUrl(BeneficiaryMedicine.actions.save_account), formData, function(reply) {
                if (reply.success && reply.valid) {
                    BackendCommon.reloadPage();
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    BeneficiaryMedicine.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    getUrl: function (action) {
        return $("input#manage-url").val() + "/" + action;
    },
    
    setModal: function (name) {
        BeneficiaryMedicine.formModal = $("div#" + name);
        BeneficiaryMedicine.formModal.modal("show");
        BeneficiaryMedicine.formModal.find("div.alert").hide();
    },
    
    closeModal: function () {
        BeneficiaryMedicine.formModal.modal("hide");
    },
    
    showMessage: function (message) {
        BeneficiaryMedicine.formModal.find("div.alert").html(message).show();
        return true;
    },

    
   clearMessage: function () {
    if (!BeneficiaryMedicine.formModal || BeneficiaryMedicine.formModal.length === 0) {
        return false;
    }

    BeneficiaryMedicine.formModal.find("div.alert").hide();
    return true;
},

    
    enableMembership: function () {
        if (confirm("Are you sure you to activate this member?")) {
            var formData = { id : 0 };
            $.post(BeneficiaryMedicine.getUrl(BeneficiaryMedicine.actions.activate), formData, function(reply) {
                    if (reply.success && reply.valid) {
                        BackendCommon.reloadPage();
                    } else if (reply.redirect) {
                        BackendCommon.reloadPage();
                    } else if (reply.message) {
                        BeneficiaryMedicine.showMessage(reply.message);
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
            BeneficiaryMedicine.setModal("modal-disable");
            BeneficiaryMedicine.formModal.find("input#inactive_note").val("");
        }
        
        return false;
    },
    
    disableMembership: function () {
        var formData = {
            id : BeneficiaryMedicine.formModal.find("input#inactive_date").val(),
            note : BeneficiaryMedicine.formModal.find("input#inactive_note").val()
        };
        
        $.post(BeneficiaryMedicine.getUrl(BeneficiaryMedicine.actions.disable), formData, function(reply) {
                if (reply.success && reply.valid) {
                    //BackendCommon.reloadPage();
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    BeneficiaryMedicine.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    }
    
};

$(document).ready(function () {
        BeneficiaryMedicine.ready();
    }
);
