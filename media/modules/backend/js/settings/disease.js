"use strict";

var ManageDisease = {
    formModal: null,
    formAction: null,
    ready: function() {
        ManageDisease.formModal = $("div#modal-disease");
        ManageDisease.formAction = $("input#manage-url").val();
        $("table#list-table-4").DataTable( {
                "aaSorting" : [],
                "lengthMenu": [[20, 50, 100], [20, 50, 100]],
                columnDefs: [ {
                        targets: [ 4 ],
                        searchable: false
                    }
                ]
            }
        );
        $("a#add-disease").click(ManageDisease.add);
        $("table#list-table-4").on("click", "a.edit-disease", ManageDisease.edit);
        $("table#list-table-4").on("click", "a.remove-disease", ManageDisease.remove);
        $("form#form-disease-modal").submit(ManageDisease.save);
    },
    
    add: function () {
        ManageDisease.formModal.find("h3#myModalLabelDisease").text("Add Disease");
        ManageDisease.formModal.find("input[type=text]").val("");
        ManageDisease.formModal.find("input#disease-id").val(0);
        ManageDisease.formModal.modal("show");
        ManageDisease.clearMessage();
    },
    
    save: function () {
        var formData = {
            action : 1,
            disease : ManageDisease.formModal.find("input#disease").val(),
            name_english : ManageDisease.formModal.find("input#name_english").val(),
            name_malayalam : ManageDisease.formModal.find("input#name_malayalam").val(),
            id :  ManageDisease.formModal.find("input#disease-id").val()
        };
        
        $.post(ManageDisease.formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    BackendCommon.reloadPage();
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    ManageDisease.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    edit: function () {
        ManageDisease.formModal.find("h3#myModalLabelDisease").text("Edit Disease");
        ManageDisease.formModal.find("input#disease").val($(this).data("disease"));
        ManageDisease.formModal.find("input#name_english").val($(this).data("english"));
        ManageDisease.formModal.find("input#name_malayalam").val($(this).data("malayalam"));
        ManageDisease.formModal.find("input#disease-id").val($(this).data("id"));
        ManageDisease.formModal.modal("show");
        ManageDisease.clearMessage();
    },
    
    remove: function () {
        if (confirm("Are you sure you to delete this Disease?")) {
            var diseaseId = $(this).data("id");
            var formData = {
                action : 2,
                id : diseaseId
            };

            $.post(ManageDisease.formAction, formData, function(reply) {
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
        ManageDisease.formModal.find("div.alert").html(message).show();
    },
    
    clearMessage: function () {
        ManageDisease.formModal.find("div.alert").hide();
    }
    
};

$(document).ready(function() {
        ManageDisease.ready();
    }
);
