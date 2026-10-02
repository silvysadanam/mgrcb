"use strict";

var Settings = {
    formModal: null,
    formAction: null,
    ready: function() {
        Settings.formModal = $("div#modal-settings-type");
        Settings.formAction = $("input#manage-url").val();
       /* $("table#list-table-2").DataTable( {
                "aaSorting" : [],
                "lengthMenu": [[20, 50, 100], [20, 50, 100]],
                columnDefs: [ {
                        targets: [ 2 ],
                        searchable: false
                    }
                ]
            }
        );*/
        $("a#add-settings-type").click(Settings.add);
        $("table#list-table-2").on("click", "a.edit-settings-type", Settings.edit);
        $("table#list-table-2").on("click", "a.remove-settings-type", Settings.remove);
        $("form#form-settings-type-modal").submit(Settings.save);
    },
    
    add: function () {
        Settings.formModal.find("h3#myModalLabelSettings").text("Add Settings");
        Settings.formModal.find("input[type=text]").val("");
        Settings.formModal.find("input#head-id").val(0);
        Settings.formModal.find("input#fmc_id").val($(this).data("fmc_id"));
        Settings.formModal.modal("show");
        Settings.clearMessage();
    },
    
    save: function () {
        var formData = {
            action : 1,
            code : Settings.formModal.find("input#head_code").val(),
            voucher_no : Settings.formModal.find("input#voucher_no").val(),
            receipt_no : Settings.formModal.find("input#receipt_no").val(),
            financial_year : Settings.formModal.find("select#financial_year").val(),
            id :  Settings.formModal.find("input#head-id").val(),
            fmc_id : Settings.formModal.find("input#fmc_id").val()
        };
        
        $.post(Settings.formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    BackendCommon.reloadPage();
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    Settings.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    edit: function () {
        Settings.formModal.find("h3#myModalLabelSettings").text("Edit Settings");
        Settings.formModal.find("input#head_code").val($(this).data("head_code"));
        Settings.formModal.find("input#voucher_no").val($(this).data("voucher_no"));
        Settings.formModal.find("input#receipt_no").val($(this).data("receipt_no"));
        Settings.formModal.find("select#financial_year").val($(this).data("financial_year"));
        Settings.formModal.find("input#head-id").val($(this).data("id"));
        Settings.formModal.find("input#fmc_id").val($(this).data("fmc_id"));
        Settings.formModal.modal("show");
        Settings.clearMessage();
    },
    
    remove: function () {
        if (confirm("Are you sure you to delete this Card Type?")) {
            var SettingsId = $(this).data("id");
            var formData = {
                action : 2,
                id : SettingsId
            };

            $.post(Settings.formAction, formData, function(reply) {
                    if (reply.success && reply.valid) {
                        BackendCommon.reloadPage();
                    } else if (reply.redirect) {
                        BackendCommon.reloadPage();
                    } else if (reply.message) {
                        alert(reply.message);
                    } else {
                        alert("Unable to complete the request.");
                    }
                }, "json"
            );
        }

        return false;
    },
            
    showMessage: function (message) {
        Settings.formModal.find("div.alert").html(message).show();
    },
    
    clearMessage: function () {
        Settings.formModal.find("div.alert").hide();
    }
    
};

$(document).ready(function() {
        Settings.ready();
    }
);
