"use strict";

var StaffChange = {
    formModal : null,
    dummyData : { id : 0 },
    actions : {
        get_member : 1, save_member : 2, get_card : 3, save_card : 4,
        remove_card : 5, get_boat : 6, save_boat : 7, remove_boat : 8,
        get_document : 9, save_document_with_file : 10, 
        save_document_without_file : 11, remove_document : 12, 
        save_photo : 13, get_account : 14, save_account : 15, 
        remove_account : 16, set_password : 17,
        disable : 18, activate : 19
    },
    requiredSymbol : ' <span class="red">*</span>',
    ready: function () {
        $("a#edit-member").click(StaffChange.editMember);
        $("form#form-member-modal").submit(StaffChange.saveMember);
        
        $("a#add-card").click(StaffChange.addCard);
        $("a.edit-card").click(StaffChange.editCard);
        $("form#form-card-modal").submit(StaffChange.saveCard);
        $("a.delete-card").click(StaffChange.deleteCard);
        
        $("a#add-boat").click(StaffChange.addBoat);
        $("a.edit-boat").click(StaffChange.editBoat);
        $("form#form-boat-modal").submit(StaffChange.saveBoat);
        $("a.delete-boat").click(StaffChange.deleteBoat);
        
        $("a#add-document").click(StaffChange.addDocument);
        $("a.edit-document").click(StaffChange.editDocument);
        $("form#form-document-modal").submit(StaffChange.saveDocument);
        $("a.delete-document").click(StaffChange.deleteDocument);
        
        $("a#upload-photo").click(StaffChange.photoForm);
        $("form#form-photo-modal").submit(StaffChange.uploadPhoto);
        
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
       
        $("a#add-account").click(StaffChange.addAccount);
        $("a.edit-account").click(StaffChange.editAccount);
        $("form#form-account-modal").submit(StaffChange.saveAccount);
        $("a.delete-account").click(StaffChange.deleteAccount);
        
        $("a#edit-password").click(StaffChange.editPassword);
        $("form#form-password-modal").submit(StaffChange.savePassword);
        
        $("a#disable-membership").click(StaffChange.showDisable);
        $("a#enable-membership").click(StaffChange.enableStaff);
         $("form#form-disable-modal").submit(StaffChange.disableStaff);
        
    },
    
    editMember: function () {
        $.post(StaffChange.getUrl(StaffChange.actions.get_member), StaffChange.dummyData, function(reply) {
                if (reply.success && reply.valid) {
                    StaffChange.setModal("modal-member");
                    StaffChange.formModal.find("input#name").val(reply.result.name);
                    StaffChange.formModal.find("input#email").val(reply.result.email);
                    StaffChange.formModal.find("input#mobile").val(reply.result.mobile);
                    StaffChange.formModal.find("input#aadhar_number").val(reply.result.aadhar_number);
                    StaffChange.formModal.find("input#pan_number").val(reply.result.pan_number);
                    StaffChange.formModal.find("input#parish").val(reply.result.parish);
                    StaffChange.formModal.find("input#landline").val(reply.result.landline);
                    StaffChange.formModal.find("input#whatsapp").val(reply.result.whatsapp);
                    StaffChange.formModal.find("input#designation_type_id").val(reply.result.designation_type_id);
                    StaffChange.formModal.find("textarea#address").val(reply.result.address);
                    StaffChange.formModal.find("input#approved_date").val(reply.result.approved_date);
                    StaffChange.formModal.find("input#date_of_birth").val(reply.result.date_of_birth);
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
    
    saveMember: function () {
        StaffChange.clearMessage();
        var formData = {
            name : StaffChange.formModal.find("input#name").val(),
            mobile : StaffChange.formModal.find("input#mobile").val(),
            aadhar_number : StaffChange.formModal.find("input#aadhar_number").val(),
            pan_number : StaffChange.formModal.find("input#pan_number").val(),
            landline : StaffChange.formModal.find("input#landline").val(),
            email : StaffChange.formModal.find("input#email").val(),
            whatsapp : StaffChange.formModal.find("input#whatsapp").val(),
            address : StaffChange.formModal.find("textarea#address").val(),
            designation_type_id : StaffChange.formModal.find("select#designation_type_id").val(),
            date_of_birth : StaffChange.formModal.find("input#date_of_birth").val(),
            approved_date : StaffChange.formModal.find("input#approved_date").val()
        };
        
        $.post(StaffChange.getUrl(StaffChange.actions.save_member), formData, function(reply) {
                if (reply.success && reply.valid) {
                    BackendCommon.reloadPage();
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    StaffChange.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    addCard: function () {
        StaffChange.setModal("modal-card");
        StaffChange.formModal.find("h3#myModalLabel2").text("Add Card Number");
        StaffChange.formModal.find("input[type=text]").val("");
        StaffChange.formModal.find("select#card_type_id").val(0);
        StaffChange.formModal.find("input#card-id").val(0);
        
        return false;
    },
    
    editCard: function () {
        var cardId = $(this).data("id");
        var formData = { id : cardId };
        $.post(StaffChange.getUrl(StaffChange.actions.get_card), formData, function(reply) {
                if (reply.success && reply.valid) {
                    StaffChange.setModal("modal-card");
                    StaffChange.formModal.find("h3#myModalLabel2").text("Edit Card Number");
                    StaffChange.formModal.find("input#card_number").val(reply.result.card_number);
                    StaffChange.formModal.find("input#issue_date").val(reply.result.issue_date);
                    StaffChange.formModal.find("input#expiry_date").val(reply.result.expiry_date);
                    StaffChange.formModal.find("select#card_type_id").val(reply.result.card_type_id);
                    StaffChange.formModal.find("input#card-id").val(cardId);
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
    
    saveCard: function () {
        StaffChange.clearMessage();
        if ("0" === StaffChange.formModal.find("select#card_type_id").val()) {
            StaffChange.showMessage("Type not selected");
            return false;
        }
        
        var formData = {
            id : StaffChange.formModal.find("input#card-id").val(),
            card_type_id : StaffChange.formModal.find("select#card_type_id").val(),
            card_number : StaffChange.formModal.find("input#card_number").val(),
            issue_date : StaffChange.formModal.find("input#issue_date").val(),
            expiry_date : StaffChange.formModal.find("input#expiry_date").val()
        };
        
        $.post(StaffChange.getUrl(StaffChange.actions.save_card), formData, function(reply) {
                if (reply.success && reply.valid) {
                    BackendCommon.reloadPage();
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    StaffChange.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    deleteCard: function () {
        if (confirm("Are you sure you to delete this Card Number?")) {
            var cardId = $(this).data("id");
            $.post(StaffChange.getUrl(StaffChange.actions.remove_card), { id : cardId }, function(reply) {
                    if (reply.success && reply.valid) {
                        BackendCommon.reloadPage();
                    } else if (reply.redirect) {
                        BackendCommon.reloadPage();
                    } else if (reply.message) {
                        StaffChange.showMessage(reply.message);
                    } else {
                        alert("Unable to complete the request.");
                    }
                }, "json"
            );
        }
        
        return false;
    },
    
    
    
    addDocument : function () {
        StaffChange.setModal("modal-document");
        StaffChange.formModal.find("h3#myModalLabel4").text("Add Document");
        StaffChange.formModal.find("input#description").val("");
        StaffChange.formModal.find("select").val(0);
        StaffChange.formModal.find("input#document-id").val(0);
        StaffChange.formModal.find("input#document_file").prop("required", true).val("");
        $("div#document-field").find("label:first").html("Upload file" + StaffChange.requiredSymbol);
    },
    
    editDocument: function () {
        var documentId = $(this).data("id");
        var formData = { id : documentId };
        $.post(StaffChange.getUrl(StaffChange.actions.get_document), formData, function(reply) {
                if (reply.success && reply.valid) {
                    StaffChange.setModal("modal-document");
                    StaffChange.formModal.find("h3#myModalLabel4").text("Edit Document");
                    StaffChange.formModal.find("input#description").val(reply.result.description);
                    StaffChange.formModal.find("select#document_type_id").val(reply.result.document_type_id);
                    StaffChange.formModal.find("input#document-id").val(documentId);
                    StaffChange.formModal.find("input#document_file").prop("required", false).val("");
                    $("div#document-field").find("label:first").html("Replace file (optional)");
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    StaffChange.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    saveDocument: function () {
        StaffChange.clearMessage();
        if ("0" === StaffChange.formModal.find("select#document_type_id").val()) {
            StaffChange.showMessage("Docuemnt Type not selected");
            return false;
        }
        
        var documentId = StaffChange.formModal.find("input#document-id").val();
        var fileField = $("input#document_file");
        var fileTitle = "Document";
        if (fileField.get(0).files.length > 0) {
            if (! DocumentValidator.validate(fileField)) {
                StaffChange.showMessage(DocumentValidator.getMessage(fileTitle));
                fileField.val("");
                return false;
            }
        }
        
        var fileData = new FormData();
        if (fileField.get(0).files.length > 0) {
            fileData.append("document", fileField.get(0).files[0]);
            fileData.append("document_id", documentId);
            fileData.append("document_type_id", StaffChange.formModal.find("select#document_type_id").val());
            fileData.append("description", StaffChange.formModal.find("input#description").val());
            $.ajax({
                    url : StaffChange.getUrl(StaffChange.actions.save_document_with_file),
                    method: "POST",
                    data: fileData,
                    contentType: false,
                    cache: false,
                    processData: false,
                    success: function(reply) {
                        if (reply.valid) {
                            BackendCommon.reloadPage();
                        } else {
                            StaffChange.showMessage(reply.message);
                        }
                    }
                }
            );
        } else {
            var formData = {
                id : documentId,
                document_type_id : StaffChange.formModal.find("select#document_type_id").val(),
                description : StaffChange.formModal.find("input#description").val()
            };

            $.post(StaffChange.getUrl(StaffChange.actions.save_document_without_file), formData, function(reply) {
                    if (reply.success && reply.valid) {
                        BackendCommon.reloadPage();
                    } else if (reply.redirect) {
                        BackendCommon.reloadPage();
                    } else if (reply.message) {
                        StaffChange.showMessage(reply.message);
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
            $.post(StaffChange.getUrl(StaffChange.actions.remove_document), { id : documentId }, function(reply) {
                    if (reply.success && reply.valid) {
                        BackendCommon.reloadPage();
                    } else if (reply.redirect) {
                        BackendCommon.reloadPage();
                    } else if (reply.message) {
                        StaffChange.showMessage(reply.message);
                    } else {
                        alert("Unable to complete the request.");
                    }
                }, "json"
            );
        }
        
        return false;
    },
    
    photoForm: function () {
        StaffChange.setModal("modal-photo");
        StaffChange.formModal.find("input[type=file]").val("");
        
        return false;
    },
    
    uploadPhoto: function () {
        StaffChange.clearMessage();
        var fileField = $("input#photo_file");
        var fileTitle = "Photo";
        if (fileField.get(0).files.length > 0) {
            if (! PhotoValidator.validate(fileField)) {
                StaffChange.showMessage(PhotoValidator.getMessage(fileTitle));
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
                    url : StaffChange.getUrl(StaffChange.actions.save_photo),
                    method: "POST",
                    data: fileData,
                    contentType: false,
                    cache: false,
                    processData: false,
                    success: function(reply) {
                        if (reply.valid) {
                            BackendCommon.reloadPage();
                        } else {
                            StaffChange.showMessage(reply.message);
                        }
                    }
                }
            );
        }
        
        fileField.val("");
        return false;
    },
    
    addAccount: function () {
        StaffChange.setModal("modal-account");
        StaffChange.formModal.find("h3#myModalLabel6").text("Add Bank Account");
        StaffChange.formModal.find("input[type=text]").val("");
        StaffChange.formModal.find("select").val(0);
        StaffChange.formModal.find("input#account-id").val(0);
        
        return false;
    },
    
    editAccount: function () {
        var accountId = $(this).data("id");
        var formData = { id : accountId };
        $.post(StaffChange.getUrl(StaffChange.actions.get_account), formData, function(reply) {
                if (reply.success && reply.valid) {
                    StaffChange.setModal("modal-account");
                    StaffChange.formModal.find("h3#myModalLabel6").text("Edit Bank Account");
                    StaffChange.formModal.find("input#account_number").val(reply.result.account_number);
                    StaffChange.formModal.find("select#bank_id").val(reply.result.bank_id);
                    StaffChange.formModal.find("select#account_type_id").val(reply.result.account_type_id);
                    StaffChange.formModal.find("input#description2").val(reply.result.description);
                    StaffChange.formModal.find("input#account-id").val(accountId);
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    StaffChange.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    saveAccount: function () {
        StaffChange.clearMessage();
        if ("0" === StaffChange.formModal.find("select#bank_id").val()) {
            StaffChange.showMessage("Bank not selected");
            return false;
        }
        
        if ("0" === StaffChange.formModal.find("select#account_type_id").val()) {
            StaffChange.showMessage("Account type not selected");
            return false;
        }
        
        var formData = {
            id : StaffChange.formModal.find("input#account-id").val(),
            bank_id : StaffChange.formModal.find("select#bank_id").val(),
            account_number : StaffChange.formModal.find("input#account_number").val(),
            account_type_id : StaffChange.formModal.find("select#account_type_id").val(),
            description : StaffChange.formModal.find("input#description2").val()
        };
        
        $.post(StaffChange.getUrl(StaffChange.actions.save_account), formData, function(reply) {
                if (reply.success && reply.valid) {
                    BackendCommon.reloadPage();
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    StaffChange.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    deleteAccount: function () {
        if (confirm("Are you sure you to delete this Bank Account?")) {
            var accountId = $(this).data("id");
            $.post(StaffChange.getUrl(StaffChange.actions.remove_account), { id : accountId }, function(reply) {
                    if (reply.success && reply.valid) {
                        BackendCommon.reloadPage();
                    } else if (reply.redirect) {
                        BackendCommon.reloadPage();
                    } else if (reply.message) {
                        StaffChange.showMessage(reply.message);
                    } else {
                        alert("Unable to complete the request.");
                    }
                }, "json"
            );
        }
        
        return false;
    },
    
    editPassword: function () {
        var title = $(this).attr("title");
        StaffChange.setModal("modal-password");
        StaffChange.formModal.find("h3#myModalLabel7").text(title);
        StaffChange.formModal.find("input[type=password]").val("");
        
        return false;
    },
    
    savePassword: function () {
        StaffChange.clearMessage();
        var passwordFirst = StaffChange.formModal.find("input#password1").val();
        var passwordRepeat = StaffChange.formModal.find("input#password2").val();
        if ("" === passwordFirst) {
            StaffChange.showMessage("Password not entered");
            return false;
        }
        
        if ("" === passwordRepeat) {
            StaffChange.showMessage("Repeat Password not entered");
            return false;
        }
        
        if (passwordRepeat !== passwordFirst) {
            StaffChange.showMessage("Password and Repeat password are not the same");
            return false;
        }
        
        var passwordLength = passwordFirst.length;
        
        if (passwordLength < 6 || passwordLength > 20) {
            StaffChange.showMessage("Password should contain 6 - 20 characters");
            return false;
        }
        
        if (/\s+/.test(passwordFirst)) {
            StaffChange.showMessage("Password should not contain space");
            return false;
        }
        
        
        var formData = { password1 : passwordFirst, password2 : passwordRepeat };
        
        $.post(StaffChange.getUrl(StaffChange.actions.set_password), formData, function(reply) {
                if (reply.success && reply.valid) {
                    BackendCommon.reloadPage();
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    StaffChange.showMessage(reply.message);
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
        StaffChange.formModal = $("div#" + name);
        StaffChange.formModal.modal("show");
        StaffChange.formModal.find("div.alert").hide();
    },
    
    closeModal: function () {
        StaffChange.formModal.modal("hide");
    },
    
    showMessage: function (message) {
        StaffChange.formModal.find("div.alert").html(message).show();
    },
    
    clearMessage: function () {
        StaffChange.formModal.find("div.alert").hide();
    },
    
    enableStaff: function () {
        if (confirm("Are you sure you to activate this member?")) {
            var formData = { id : 0 };
            $.post(StaffChange.getUrl(StaffChange.actions.activate), formData, function(reply) {
                    if (reply.success && reply.valid) {
                        BackendCommon.reloadPage();
                    } else if (reply.redirect) {
                        BackendCommon.reloadPage();
                    } else if (reply.message) {
                        StaffChange.showMessage(reply.message);
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
            StaffChange.setModal("modal-disable");
            StaffChange.formModal.find("input#inactive_note").val("");
        }
        
        return false;
    },
    
    disableStaff: function () {
        var formData = {
            id : StaffChange.formModal.find("input#inactive_date").val(),
            note : StaffChange.formModal.find("input#inactive_note").val()
        };
        
        $.post(StaffChange.getUrl(StaffChange.actions.disable), formData, function(reply) {
                if (reply.success && reply.valid) {
                    //BackendCommon.reloadPage();
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    StaffChange.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    }
    
};

$(document).ready(function () {
        StaffChange.ready();
    }
);
