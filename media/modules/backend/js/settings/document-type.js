"use strict";

var DocumentType = {
    formModal: null,
    formAction: null,
    ready: function() {
        DocumentType.formModal = $("div#modal-document-type");
        DocumentType.formAction = $("input#manage-url").val();
        $("table#list-table-2").DataTable( {
                "aaSorting" : [],
                "lengthMenu": [[20, 50, 100], [20, 50, 100]],
                columnDefs: [ {
                        targets: [ 2 ],
                        searchable: false
                    }
                ]
            }
        );
        $("a#add-document-type").click(DocumentType.add);
        $("table#list-table-2").on("click", "a.edit-document-type", DocumentType.edit);
        $("table#list-table-2").on("click", "a.remove-document-type", DocumentType.remove);
        $("form#form-document-type-modal").submit(DocumentType.save);
    },
    
    add: function () {
        DocumentType.formModal.find("h3#myModalLabelDocumentType").text("Add Document Type");
        DocumentType.formModal.find("input[type=text]").val("");
        DocumentType.formModal.find("input#document-type-id").val(0);
        DocumentType.formModal.modal("show");
        DocumentType.clearMessage();
    },
    
    save: function () {
        var formData = {
            action : 1,
            document_type : DocumentType.formModal.find("input#document_type").val(),
            id :  DocumentType.formModal.find("input#document-type-id").val()
        };
        
        $.post(DocumentType.formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    BackendCommon.reloadPage();
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    DocumentType.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    edit: function () {
        DocumentType.formModal.find("h3#myModalLabelDocumentType").text("Edit Document Type");
        DocumentType.formModal.find("input#document_type").val($(this).data("title"));
        DocumentType.formModal.find("input#document-type-id").val($(this).data("id"));
        DocumentType.formModal.modal("show");
        DocumentType.clearMessage();
    },
    
    remove: function () {
        if (confirm("Are you sure you to delete this Document Type?")) {
            var DocumentTypeId = $(this).data("id");
            var formData = {
                action : 2,
                id : DocumentTypeId
            };

            $.post(DocumentType.formAction, formData, function(reply) {
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
        DocumentType.formModal.find("div.alert").html(message).show();
    },
    
    clearMessage: function () {
        DocumentType.formModal.find("div.alert").hide();
    }
    
};

$(document).ready(function() {
        DocumentType.ready();
    }
);
