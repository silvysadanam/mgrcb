"use strict";

var Beneficiary = {
    formModal : null,
    dummyData : { id : 0 },
    actions : {
        get_member : 1, save_member : 2, get_card : 3, save_card : 4,
        remove_card : 5, get_document : 9, save_document_with_file : 10, 
        save_document_without_file : 11, remove_document : 12, 
        save_photo : 13, get_account : 14, save_account : 15, 
        remove_account : 16, set_password : 17,
        disable : 18, activate : 19
    },
    requiredSymbol : ' <span class="red">*</span>',
    ready: function () {
        $("a#edit-beneficiary").click(Beneficiary.editMember);
        $("form#form-member-modal").submit(Beneficiary.saveMember);
        
        $("a#add-card").click(Beneficiary.addCard);
        $("a.edit-card").click(Beneficiary.editCard);
        $("form#form-card-modal").submit(Beneficiary.saveCard);
        $("a.delete-card").click(Beneficiary.deleteCard);
        
        $("a#add-boat").click(Beneficiary.addBoat);
        $("a.edit-boat").click(Beneficiary.editBoat);
        $("form#form-boat-modal").submit(Beneficiary.saveBoat);
        $("a.delete-boat").click(Beneficiary.deleteBoat);
        
        $("a#add-document").click(Beneficiary.addDocument);
        $("a.edit-document").click(Beneficiary.editDocument);
        $("form#form-document-modal").submit(Beneficiary.saveDocument);
        $("a.delete-document").click(Beneficiary.deleteDocument);
        
        $("a#upload-photo").click(Beneficiary.photoForm);
        $("form#form-photo-modal").submit(Beneficiary.uploadPhoto);
        
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
        $("#modal-beneficiary select.select2").select2({ width: "100%", dropdownParent: $("#modal-beneficiary") });
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
       
        $("a#add-account").click(Beneficiary.addAccount);
        $("a.edit-account").click(Beneficiary.editAccount);
        $("form#form-account-modal").submit(Beneficiary.saveAccount);
        $("a.delete-account").click(Beneficiary.deleteAccount);
        
        $("a#edit-password").click(Beneficiary.editPassword);
        $("form#form-password-modal").submit(Beneficiary.savePassword);
        
        $("a#disable-membership").click(Beneficiary.showDisable);
        $("a#enable-membership").click(Beneficiary.enableMembership);
         $("form#form-disable-modal").submit(Beneficiary.disableMembership);
        
    },
    
    editMember: function () {
        $.post(Beneficiary.getUrl(Beneficiary.actions.get_member), Beneficiary.dummyData, function(reply) {
                if (reply.success && reply.valid) {
                    Beneficiary.setModal("modal-beneficiary");
                    Beneficiary.formModal.find("input#name").val(reply.result.name);
                    Beneficiary.formModal.find("input#mobile").val(reply.result.mobile);
                    Beneficiary.formModal.find("select#panchayath").val(reply.result.panchayath);
                    Beneficiary.formModal.find("select#beducation").val(reply.result.beducation);
                    Beneficiary.formModal.find("select#ration_card").val(reply.result.ration_card);
                    Beneficiary.formModal.find("select#gender").val(reply.result.gender);
                    Beneficiary.formModal.find("select#disability_reason").val(reply.result.disability_reason);
                    
                    
                    Beneficiary.formModal.find("input#disability_occurred").val(reply.result.disability_occurred);
                    Beneficiary.formModal.find("textarea#address").val(reply.result.address);
                    Beneficiary.formModal.find("textarea#pensions").val(reply.result.pensions);
                    Beneficiary.formModal.find("input#date_of_birth").val(reply.result.date_of_birth);
                    Beneficiary.formModal.find("input#examination_date").val(reply.result.examination_date);
                    Beneficiary.formModal
                    .find("select#disability")
                    .val(reply.result.disability || [])
                    .trigger("change");
                    Beneficiary.formModal.find("input#land_area").val(reply.result.land_area);
                    Beneficiary.formModal.find("select#house_ownership").val(reply.result.house_ownership);
                    Beneficiary.formModal.find("input#proposed_date").val(reply.result.proposed_date);
                    
                    Beneficiary.formModal.find("select#house_type").val(reply.result.house_type);
                    Beneficiary.formModal.find("input#rented_amount").val(reply.result.rented_amount);
                    Beneficiary.formModal.find("textarea#worker_opinion").val(reply.result.worker_opinion);
                    
                    Beneficiary.formModal.find("input#aadhar_number").val(reply.result.aadhar_number);
                    Beneficiary.formModal.find("input#monthly_income").val(reply.result.monthly_income);
                    Beneficiary.formModal.find("input#worker_name").val(reply.result.worker_name);
                    Beneficiary.formModal.find("input#approved_date").val(reply.result.approved_date);
                    
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
        Beneficiary.clearMessage();
        var formData = {
            name : Beneficiary.formModal.find("input#name").val(),            
            mobile : Beneficiary.formModal.find("input#mobile").val(),
            aadhar_number : Beneficiary.formModal.find("input#aadhar_number").val(),           
            address : Beneficiary.formModal.find("textarea#address").val(),           
            date_of_birth : Beneficiary.formModal.find("input#date_of_birth").val(),
            approved_date : Beneficiary.formModal.find("input#approved_date").val(),
            
            panchayath : Beneficiary.formModal.find("select#panchayath").val(),
            beducation : Beneficiary.formModal.find("select#beducation").val(),
            ration_card : Beneficiary.formModal.find("select#ration_card").val(),
            gender : Beneficiary.formModal.find("select#gender").val(),
            disability_reason : Beneficiary.formModal.find("select#disability_reason").val(),
            disability_occurred : Beneficiary.formModal.find("input#disability_occurred").val(),
            pensions : Beneficiary.formModal.find("textarea#pensions").val(),
            examination_date : Beneficiary.formModal.find("input#examination_date").val(),
            
            disability : Beneficiary.formModal.find("select#disability").val() || [],
            land_area : Beneficiary.formModal.find("input#land_area").val(),
            house_ownership : Beneficiary.formModal.find("select#house_ownership").val(),
            proposed_date : Beneficiary.formModal.find("input#proposed_date").val(),
            house_type : Beneficiary.formModal.find("select#house_type").val(),
            rented_amount : Beneficiary.formModal.find("input#rented_amount").val(),
            worker_opinion : Beneficiary.formModal.find("textarea#worker_opinion").val(),
            monthly_income : Beneficiary.formModal.find("input#monthly_income").val(),
            worker_name : Beneficiary.formModal.find("input#worker_name").val(),
        };
        
        $.post(Beneficiary.getUrl(Beneficiary.actions.save_member), formData, function(reply) {
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
    
    addCard: function () {
        Beneficiary.setModal("modal-card");
        Beneficiary.formModal.find("h3#myModalLabel2").text("Add Card Number");
        Beneficiary.formModal.find("input[type=text]").val("");
        Beneficiary.formModal.find("select#card_type_id").val(0);
        Beneficiary.formModal.find("input#card-id").val(0);
        
        return false;
    },
    
    editCard: function () {
        var cardId = $(this).data("id");
        var formData = { id : cardId };
        $.post(Beneficiary.getUrl(Beneficiary.actions.get_card), formData, function(reply) {
                if (reply.success && reply.valid) {
                    Beneficiary.setModal("modal-card");
                    Beneficiary.formModal.find("h3#myModalLabel2").text("Edit Card Number");
                    Beneficiary.formModal.find("input#card_number").val(reply.result.card_number);
                    Beneficiary.formModal.find("input#issue_date").val(reply.result.issue_date);
                    Beneficiary.formModal.find("input#expiry_date").val(reply.result.expiry_date);
                    Beneficiary.formModal.find("select#card_type_id").val(reply.result.card_type_id);
                    Beneficiary.formModal.find("input#card-id").val(cardId);
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
        Beneficiary.clearMessage();
        if ("0" === Beneficiary.formModal.find("select#card_type_id").val()) {
            Beneficiary.showMessage("Type not selected");
            return false;
        }
        
        var formData = {
            id : Beneficiary.formModal.find("input#card-id").val(),
            card_type_id : Beneficiary.formModal.find("select#card_type_id").val(),
            card_number : Beneficiary.formModal.find("input#card_number").val(),
            issue_date : Beneficiary.formModal.find("input#issue_date").val(),
            expiry_date : Beneficiary.formModal.find("input#expiry_date").val()
        };
        
        $.post(Beneficiary.getUrl(Beneficiary.actions.save_card), formData, function(reply) {
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
    
    deleteCard: function () {
        if (confirm("Are you sure you to delete this Card Number?")) {
            var cardId = $(this).data("id");
            $.post(Beneficiary.getUrl(Beneficiary.actions.remove_card), { id : cardId }, function(reply) {
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
    
    
    
    addDocument : function () {
        Beneficiary.setModal("modal-document");
        Beneficiary.formModal.find("h3#myModalLabel4").text("Add Document");
        Beneficiary.formModal.find("input#description").val("");
        Beneficiary.formModal.find("select").val(0);
        Beneficiary.formModal.find("input#document-id").val(0);
        Beneficiary.formModal.find("input#document_file").prop("required", true).val("");
        $("div#document-field").find("label:first").html("Upload file" + Beneficiary.requiredSymbol);
    },
    
    editDocument: function () {
        var documentId = $(this).data("id");
        var formData = { id : documentId };
        
        $.post(Beneficiary.getUrl(Beneficiary.actions.get_document), formData, function(reply) {
                if (reply.success && reply.valid) {
                    Beneficiary.setModal("modal-document");
                    Beneficiary.formModal.find("h3#myModalLabel4").text("Edit Document");
                    Beneficiary.formModal.find("input#description").val(reply.result.description);
                    Beneficiary.formModal.find("select#document_type_id").val(reply.result.document_type_id);
                    Beneficiary.formModal.find("input#document-id").val(documentId);
                    Beneficiary.formModal.find("input#document_file").prop("required", false).val("");
                    $("div#document-field").find("label:first").html("Replace file (optional)");
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    Beneficiary.setModal("modal-document");
                    Beneficiary.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    saveDocument: function () {
        Beneficiary.clearMessage();
        if ("0" === Beneficiary.formModal.find("select#document_type_id").val()) {
            Beneficiary.showMessage("Docuemnt Type not selected");
            return false;
        }
        
        var documentId = Beneficiary.formModal.find("input#document-id").val();
        var fileField = $("input#document_file");
        var fileTitle = "Document";
        if (fileField.get(0).files.length > 0) {
            if (! DocumentValidator.validate(fileField)) {
                Beneficiary.showMessage(DocumentValidator.getMessage(fileTitle));
                fileField.val("");
                return false;
            }
        }
        
        var fileData = new FormData();
        if (fileField.get(0).files.length > 0) {
            fileData.append("document", fileField.get(0).files[0]);
            fileData.append("document_id", documentId);
            fileData.append("document_type_id", Beneficiary.formModal.find("select#document_type_id").val());
            fileData.append("description", Beneficiary.formModal.find("input#description").val());
            $.ajax({
                    url : Beneficiary.getUrl(Beneficiary.actions.save_document_with_file),
                    method: "POST",
                    data: fileData,
                    contentType: false,
                    cache: false,
                    processData: false,
                    success: function(reply) {
                        if (reply.valid) {
                            BackendCommon.reloadPage();
                        } else {
                            Beneficiary.showMessage(reply.message);
                        }
                    }
                }
            );
        } else {
            var formData = {
                id : documentId,
                document_type_id : Beneficiary.formModal.find("select#document_type_id").val(),
                description : Beneficiary.formModal.find("input#description").val()
            };

            $.post(Beneficiary.getUrl(Beneficiary.actions.save_document_without_file), formData, function(reply) {
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
        
        fileField.val("");
        
        return false;
    },
    
    deleteDocument: function () {
        if (confirm("Are you sure you to delete this Document?")) {
            var documentId = $(this).data("id");
            $.post(Beneficiary.getUrl(Beneficiary.actions.remove_document), { id : documentId }, function(reply) {
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
    
    photoForm: function () {
        Beneficiary.setModal("modal-photo");
        Beneficiary.formModal.find("input[type=file]").val("");
        
        return false;
    },
    
    uploadPhoto: function () {
        Beneficiary.clearMessage();
        var fileField = $("input#photo_file");
        var fileTitle = "Photo";
        if (fileField.get(0).files.length > 0) {
            if (! PhotoValidator.validate(fileField)) {
                Beneficiary.showMessage(PhotoValidator.getMessage(fileTitle));
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
                    url : Beneficiary.getUrl(Beneficiary.actions.save_photo),
                    method: "POST",
                    data: fileData,
                    contentType: false,
                    cache: false,
                    processData: false,
                    success: function(reply) {
                        if (reply.valid) {
                            BackendCommon.reloadPage();
                        } else {
                            Beneficiary.showMessage(reply.message);
                        }
                    }
                }
            );
        }
        
        fileField.val("");
        return false;
    },
    
    addAccount: function () {
        Beneficiary.setModal("modal-account");
        Beneficiary.formModal.find("h3#myModalLabel6").text("Add Bank Account");
        Beneficiary.formModal.find("input[type=text]").val("");
        Beneficiary.formModal.find("select").val(0);
        Beneficiary.formModal.find("input#account-id").val(0);
        
        return false;
    },
    
    editAccount: function () {
        var accountId = $(this).data("id");
        var formData = { id : accountId };
        $.post(Beneficiary.getUrl(Beneficiary.actions.get_account), formData, function(reply) {
                if (reply.success && reply.valid) {
                    Beneficiary.setModal("modal-account");
                    Beneficiary.formModal.find("h3#myModalLabel6").text("Edit Bank Account");
                    Beneficiary.formModal.find("input#account_number").val(reply.result.account_number);
                    Beneficiary.formModal.find("select#bank_id").val(reply.result.bank_id);
                    Beneficiary.formModal.find("select#account_type_id").val(reply.result.account_type_id);
                    Beneficiary.formModal.find("input#description2").val(reply.result.description);
                    Beneficiary.formModal.find("input#account-id").val(accountId);
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
    
    saveAccount: function () {
        Beneficiary.clearMessage();
        if ("0" === Beneficiary.formModal.find("select#bank_id").val()) {
            Beneficiary.showMessage("Bank not selected");
            return false;
        }
        
        if ("0" === Beneficiary.formModal.find("select#account_type_id").val()) {
            Beneficiary.showMessage("Account type not selected");
            return false;
        }
        
        var formData = {
            id : Beneficiary.formModal.find("input#account-id").val(),
            bank_id : Beneficiary.formModal.find("select#bank_id").val(),
            account_number : Beneficiary.formModal.find("input#account_number").val(),
            account_type_id : Beneficiary.formModal.find("select#account_type_id").val(),
            description : Beneficiary.formModal.find("input#description2").val()
        };
        
        $.post(Beneficiary.getUrl(Beneficiary.actions.save_account), formData, function(reply) {
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
    
    deleteAccount: function () {
        if (confirm("Are you sure you to delete this Bank Account?")) {
            var accountId = $(this).data("id");
            $.post(Beneficiary.getUrl(Beneficiary.actions.remove_account), { id : accountId }, function(reply) {
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
    
    editPassword: function () {
        var title = $(this).attr("title");
        Beneficiary.setModal("modal-password");
        Beneficiary.formModal.find("h3#myModalLabel7").text(title);
        Beneficiary.formModal.find("input[type=password]").val("");
        
        return false;
    },
    
    savePassword: function () {
        Beneficiary.clearMessage();
        var passwordFirst = Beneficiary.formModal.find("input#password1").val();
        var passwordRepeat = Beneficiary.formModal.find("input#password2").val();
        if ("" === passwordFirst) {
            Beneficiary.showMessage("Password not entered");
            return false;
        }
        
        if ("" === passwordRepeat) {
            Beneficiary.showMessage("Repeat Password not entered");
            return false;
        }
        
        if (passwordRepeat !== passwordFirst) {
            Beneficiary.showMessage("Password and Repeat password are not the same");
            return false;
        }
        
        var passwordLength = passwordFirst.length;
        
        if (passwordLength < 6 || passwordLength > 20) {
            Beneficiary.showMessage("Password should contain 6 - 20 characters");
            return false;
        }
        
        if (/\s+/.test(passwordFirst)) {
            Beneficiary.showMessage("Password should not contain space");
            return false;
        }
        
        
        var formData = { password1 : passwordFirst, password2 : passwordRepeat };
        
        $.post(Beneficiary.getUrl(Beneficiary.actions.set_password), formData, function(reply) {
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
    
    
    getUrl: function (action) {
        return $("input#manage-url").val() + "/" + action;
    },
    
    setModal: function (name) {
        Beneficiary.formModal = $("div#" + name);
        Beneficiary.formModal.modal("show");
        Beneficiary.formModal.find("div.alert").hide();
    },
    
    closeModal: function () {
        Beneficiary.formModal.modal("hide");
    },
    
    showMessage: function (message) {
    if (!Beneficiary.formModal || Beneficiary.formModal.length === 0) {
        console.error("Beneficiary.formModal is not set:", message);
        return false;
    }

    Beneficiary.formModal.find("div.alert").html(message).show();
    return true;
},

    
   clearMessage: function () {
    if (!Beneficiary.formModal || Beneficiary.formModal.length === 0) {
        return false;
    }

    Beneficiary.formModal.find("div.alert").hide();
    return true;
},

    
    enableMembership: function () {
        if (confirm("Are you sure you to activate this member?")) {
            var formData = { id : 0 };
            $.post(Beneficiary.getUrl(Beneficiary.actions.activate), formData, function(reply) {
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
        
        $.post(Beneficiary.getUrl(Beneficiary.actions.disable), formData, function(reply) {
                if (reply.success && reply.valid) {
                    //BackendCommon.reloadPage();
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
    
};

$(document).ready(function () {
        Beneficiary.ready();
    }
);
