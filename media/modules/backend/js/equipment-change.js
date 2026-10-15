"use strict";

var BeneficiaryEquipment = {
    formModal : null,
    dummyData : { id : 0 },
    actions : {
        get_equipment : 25, save_equipment : 24, get_equipment_document : 26, save_equipment_document_with_file : 27, 
        save_equipment_document_without_file : 28, remove_equipment_document : 29, 
        save_photo : 13, get_account : 14, save_account : 15, 
        
    },
    requiredSymbol : ' <span class="red">*</span>',
    ready: function () {
        $("a#edit-equipment").click(BeneficiaryEquipment.editMember);
        $("form#form-equipment-modal").submit(BeneficiaryEquipment.saveEquipment);
    
        $("a#add-document").click(BeneficiaryEquipment.addDocument);
        $("a.edit-document").click(BeneficiaryEquipment.editDocument);
        $("form#form-document-modal").submit(BeneficiaryEquipment.saveDocument);
        $("a.delete-document").click(BeneficiaryEquipment.deleteDocument);
        
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
        $("#modal-equipment select.select2").select2({ width: "100%", dropdownParent: $("#modal-equipment") });
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
       
        $("a#add-account").click(BeneficiaryEquipment.addAccount);
        $("a.edit-account").click(BeneficiaryEquipment.editAccount);
        $("form#form-account-modal").submit(BeneficiaryEquipment.saveAccount);
        $("a.delete-account").click(BeneficiaryEquipment.deleteAccount);
    },
    
    editMember: function () {
        $.post(BeneficiaryEquipment.getUrl(BeneficiaryEquipment.actions.get_equipment), BeneficiaryEquipment.dummyData, function(reply) {
                if (reply.success && reply.valid) {
                    BeneficiaryEquipment.setModal("modal-equipment");
                    BeneficiaryEquipment.formModal.find("input#reg_no").val(reply.result.reg_no);
                    BeneficiaryEquipment.formModal.find("textarea#diagnosis").val(reply.result.diagnosis);
                    BeneficiaryEquipment.formModal.find("input#assessment_date").val(reply.result.assessment_date);
                    BeneficiaryEquipment.formModal.find("textarea#equipment").val(reply.result.equipment);
                    BeneficiaryEquipment.formModal.find("select#hospital").val(reply.result.hospital);
                    BeneficiaryEquipment.formModal.find("select#department").val(reply.result.department);
                    BeneficiaryEquipment.formModal.find("input#last_bill_amount").val(reply.result.last_bill_amount);
                    BeneficiaryEquipment.formModal.find("input#last_bill_date").val(reply.result.last_bill_date);
                    BeneficiaryEquipment.formModal.find("textarea#worker_opinion").val(reply.result.worker_opinion);
                    BeneficiaryEquipment.formModal.find("input#worker_name").val(reply.result.worker_name);
                    BeneficiaryEquipment.formModal.find("textarea#payment_type").val(reply.result.payment_type);
                    BeneficiaryEquipment.formModal.find("input#approved_date").val(reply.result.approved_date);
                    BeneficiaryEquipment.formModal.find("input#equipment-type-id").val(reply.result.id);
                    
                    
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
    
    saveEquipment: function () {
        BeneficiaryEquipment.clearMessage();
        /*var lastBillAmount = Equipment.formModal.find("input#last_bill_amount").val();
        if (0 === lastBillAmount) {
            Equipment.showMessage("Please add bill amount");
            return false;
        }
        */
        var equipment = BeneficiaryEquipment.formModal.find("textarea#equipment").val();
        if (!equipment || equipment === "") {
            BeneficiaryEquipment.showMessage("Please fill Name of the Equipment");
            return false;
        }
        
        var hospital = BeneficiaryEquipment.formModal.find("select#hospital").val();
        if (!hospital || hospital === "0") {
            BeneficiaryEquipment.showMessage("Please select Hospital");
            return false;
        }
        var department = BeneficiaryEquipment.formModal.find("select#department").val();
        if (!department || department === "0") {
            BeneficiaryEquipment.showMessage("Please select Department");
            return false;
        }
       
        var assessmentDate = BeneficiaryEquipment.formModal.find("input#assessment_date").val();

        if (!assessmentDate) {
            BeneficiaryEquipment.showMessage("Assessment Date not selected");
            return false;
        }
        
        var assessment_date = BeneficiaryEquipment.formModal.find("input#assessment_date").val();
        var last_bill_amount = BeneficiaryEquipment.formModal.find("input#last_bill_amount").val();
        var last_bill_date = BeneficiaryEquipment.formModal.find("input#last_bill_date").val();
        
        var formData = {
            mid : BeneficiaryEquipment.formModal.find("input#equipment-type-id").val(),
            reg_no : BeneficiaryEquipment.formModal.find("input#reg_no").val(),
            diagnosis : BeneficiaryEquipment.formModal.find("textarea#diagnosis").val(),
            assessment_date : assessment_date,
            equipment : equipment,
            hospital : hospital,
            department : department,
            last_bill_amount : last_bill_amount,
            last_bill_date : last_bill_date,
            worker_opinion : BeneficiaryEquipment.formModal.find("textarea#worker_opinion").val(),
            worker_name : BeneficiaryEquipment.formModal.find("input#worker_name").val(),
            payment_type : BeneficiaryEquipment.formModal.find("textarea#payment_type").val(),
            approved_date : BeneficiaryEquipment.formModal.find("input#approved_date").val()
        };
        var formAction = BeneficiaryEquipment.getUrl(BeneficiaryEquipment.actions.save_equipment);
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
    
    addDocument : function () {
        BeneficiaryEquipment.setModal("modal-document");
        BeneficiaryEquipment.formModal.find("h3#myModalLabel4").text("Add Document");
        BeneficiaryEquipment.formModal.find("input#description").val("");
        BeneficiaryEquipment.formModal.find("select").val(0);
        BeneficiaryEquipment.formModal.find("input#document-id").val(0);
        BeneficiaryEquipment.formModal.find("input#document_file").prop("required", true).val("");
        $("div#document-field").find("label:first").html("Upload file" + BeneficiaryEquipment.requiredSymbol);
    },
    
    editDocument: function () {
        var documentId = $(this).data("id");
        var formData = { id : documentId };
        
        $.post(BeneficiaryEquipment.getUrl(BeneficiaryEquipment.actions.get_equipment_document), formData, function(reply) {
                if (reply.success && reply.valid) {
                    BeneficiaryEquipment.setModal("modal-document");
                    BeneficiaryEquipment.formModal.find("h3#myModalLabel4").text("Edit Document");
                    BeneficiaryEquipment.formModal.find("input#description").val(reply.result.description);
                    BeneficiaryEquipment.formModal.find("select#document_type_id").val(reply.result.document_type_id);
                    BeneficiaryEquipment.formModal.find("input#document-id").val(documentId);
                    BeneficiaryEquipment.formModal.find("input#document_file").prop("required", false).val("");
                    $("div#document-field").find("label:first").html("Replace file (optional)");
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    BeneficiaryEquipment.setModal("modal-document");
                    BeneficiaryEquipment.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    saveDocument: function () {
        
        BeneficiaryEquipment.clearMessage();
        if ("0" === BeneficiaryEquipment.formModal.find("select#document_type_id").val()) {
            BeneficiaryEquipment.showMessage("Docuemnt Type not selected");
            return false;
        }
       
        var documentId = BeneficiaryEquipment.formModal.find("input#document-id").val();
        var fileField = $("input#document_file");
        var fileTitle = "Document";
        if (fileField.get(0).files.length > 0) {
            if (! DocumentValidator.validate(fileField)) {
                BeneficiaryEquipment.showMessage(DocumentValidator.getMessage(fileTitle));
                fileField.val("");
                return false;
            }
        }
        
        var fileData = new FormData();
        if (fileField.get(0).files.length > 0) {
            fileData.append("document", fileField.get(0).files[0]);
            fileData.append("document_id", documentId);
            fileData.append("document_type_id", BeneficiaryEquipment.formModal.find("select#document_type_id").val());
            fileData.append("description", BeneficiaryEquipment.formModal.find("input#description").val());
            $.ajax({
                    url : BeneficiaryEquipment.getUrl(BeneficiaryEquipment.actions.save_equipment_document_with_file),
                    method: "POST",
                    data: fileData,
                    contentType: false,
                    cache: false,
                    processData: false,
                    success: function(reply) {
                        if (reply.valid) {
                            BackendCommon.reloadPage();
                        } else {
                            BeneficiaryEquipment.showMessage(reply.message);
                        }
                    }
                }
            );
        } else {
            var formData = {
                id : documentId,
                document_type_id : BeneficiaryEquipment.formModal.find("select#document_type_id").val(),
                description : BeneficiaryEquipment.formModal.find("input#description").val()
            };

            $.post(BeneficiaryEquipment.getUrl(BeneficiaryEquipment.actions.save_equipment_document_with_file), formData, function(reply) {
                    if (reply.success && reply.valid) {
                        BackendCommon.reloadPage();
                    } else if (reply.redirect) {
                        BackendCommon.reloadPage();
                    } else if (reply.message) {
                        BeneficiaryEquipment.showMessage(reply.message);
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
            $.post(BeneficiaryEquipment.getUrl(BeneficiaryEquipment.actions.remove_equipment_document), { id : documentId }, function(reply) {
                    if (reply.success && reply.valid) {
                        BackendCommon.reloadPage();
                    } else if (reply.redirect) {
                        BackendCommon.reloadPage();
                    } else if (reply.message) {
                        BeneficiaryEquipment.showMessage(reply.message);
                    } else {
                        alert("Unable to complete the request.");
                    }
                }, "json"
            );
        }
        
        return false;
    },
    
    photoForm: function () {
        BeneficiaryEquipment.setModal("modal-photo");
        BeneficiaryEquipment.formModal.find("input[type=file]").val("");
        
        return false;
    },
    
    uploadPhoto: function () {
        BeneficiaryEquipment.clearMessage();
        var fileField = $("input#photo_file");
        var fileTitle = "Photo";
        if (fileField.get(0).files.length > 0) {
            if (! PhotoValidator.validate(fileField)) {
                BeneficiaryEquipment.showMessage(PhotoValidator.getMessage(fileTitle));
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
                    url : BeneficiaryEquipment.getUrl(BeneficiaryEquipment.actions.save_photo),
                    method: "POST",
                    data: fileData,
                    contentType: false,
                    cache: false,
                    processData: false,
                    success: function(reply) {
                        if (reply.valid) {
                            BackendCommon.reloadPage();
                        } else {
                            BeneficiaryEquipment.showMessage(reply.message);
                        }
                    }
                }
            );
        }
        
        fileField.val("");
        return false;
    },
    
    
    getUrl: function (action) {
        return $("input#manage-url").val() + "/" + action;
    },
    
    setModal: function (name) {
        BeneficiaryEquipment.formModal = $("div#" + name);
        BeneficiaryEquipment.formModal.modal("show");
        BeneficiaryEquipment.formModal.find("div.alert").hide();
    },
    
    closeModal: function () {
        BeneficiaryEquipment.formModal.modal("hide");
    },
    
    showMessage: function (message) {
        BeneficiaryEquipment.formModal.find("div.alert").html(message).show();
        return true;
    },

    
   clearMessage: function () {
    if (!BeneficiaryEquipment.formModal || BeneficiaryEquipment.formModal.length === 0) {
        return false;
    }

    BeneficiaryEquipment.formModal.find("div.alert").hide();
    return true;
},
};

$(document).ready(function () {
        BeneficiaryEquipment.ready();
    }
);
