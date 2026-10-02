"use strict";

var ManageMedicine = {
    formModal: null,
    formAction: null,
    ready: function() {
        ManageMedicine.formModal = $("div#modal-medicine");
        ManageMedicine.formAction = $("input#manage-url").val();
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
        $("a#add-medicine").click(ManageMedicine.add);
        $("table#list-table-4").on("click", "a.edit-medicine", ManageMedicine.edit);
        $("table#list-table-4").on("click", "a.remove-medicine", ManageMedicine.remove);
        $("form#form-medicine-modal").submit(ManageMedicine.save);
    },
    
    add: function () {
        ManageMedicine.formModal.find("h3#myModalLabelFish").text("Add Medicine");
        ManageMedicine.formModal.find("input[type=text]").val("");
        ManageMedicine.formModal.find("input#medicine-id").val(0);
        ManageMedicine.formModal.modal("show");
        ManageMedicine.clearMessage();
    },
    
    save: function () {
        var formData = {
            action : 1,
            medicine : ManageMedicine.formModal.find("input#medicine").val(),
            name_english : ManageMedicine.formModal.find("input#name_english").val(),
            name_malayalam : ManageMedicine.formModal.find("input#name_malayalam").val(),
            id :  ManageMedicine.formModal.find("input#medicine-id").val()
        };
        
        $.post(ManageMedicine.formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    BackendCommon.reloadPage();
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    ManageMedicine.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    edit: function () {
        ManageMedicine.formModal.find("h3#myModalLabelFish").text("Edit Medicine");
        ManageMedicine.formModal.find("input#medicine").val($(this).data("medicine"));
        ManageMedicine.formModal.find("input#name_english").val($(this).data("english"));
        ManageMedicine.formModal.find("input#name_malayalam").val($(this).data("malayalam"));
        ManageMedicine.formModal.find("input#medicine-id").val($(this).data("id"));
        ManageMedicine.formModal.modal("show");
        ManageMedicine.clearMessage();
    },
    
    remove: function () {
        if (confirm("Are you sure you to delete this Medicine?")) {
            var medicineId = $(this).data("id");
            var formData = {
                action : 2,
                id : medicineId
            };

            $.post(ManageMedicine.formAction, formData, function(reply) {
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
        ManageMedicine.formModal.find("div.alert").html(message).show();
    },
    
    clearMessage: function () {
        ManageMedicine.formModal.find("div.alert").hide();
    }
    
};

$(document).ready(function() {
        ManageMedicine.ready();
    }
);
