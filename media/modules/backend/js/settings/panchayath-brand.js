"use strict";

var PanchayathBrand = {
    formModal: null,
    formAction: null,
    ready: function() {
        PanchayathBrand.formModal = $("div#modal-panchayath-brand");
        PanchayathBrand.formAction = $("input#manage-url").val();
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
        $("a#add-panchayath-brand").click(PanchayathBrand.add);
        $("table#list-table-2").on("click", "a.edit-panchayath-brand", PanchayathBrand.edit);
        $("table#list-table-2").on("click", "a.remove-panchayath-brand", PanchayathBrand.remove);
        $("form#form-panchayath-brand-modal").submit(PanchayathBrand.save);
    },
    
    add: function () {
        PanchayathBrand.formModal.find("h3#myModalLabelPanchayathBrand").text("Add Panchayath");
        PanchayathBrand.formModal.find("input[type=text]").val("");
        PanchayathBrand.formModal.find("input#panchayath-brand-id").val(0);
        PanchayathBrand.formModal.modal("show");
        PanchayathBrand.clearMessage();
    },
    
    save: function () {
        var formData = {
            action : 1,
            panchayath_brand : PanchayathBrand.formModal.find("input#panchayath_brand").val(),
            id :  PanchayathBrand.formModal.find("input#panchayath-brand-id").val()
        };
        
        $.post(PanchayathBrand.formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    BackendCommon.reloadPage();
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    PanchayathBrand.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    edit: function () {
        PanchayathBrand.formModal.find("h3#myModalLabelPanchayathBrand").text("Edit Panchayath Brand");
        PanchayathBrand.formModal.find("input#panchayath_brand").val($(this).data("title"));
        PanchayathBrand.formModal.find("input#panchayath-brand-id").val($(this).data("id"));
        PanchayathBrand.formModal.modal("show");
        PanchayathBrand.clearMessage();
    },
    
    remove: function () {
        if (confirm("Are you sure you to delete this Panchayath Brand?")) {
            var panchayathBrandId = $(this).data("id");
            var formData = {
                action : 2,
                id : panchayathBrandId
            };

            $.post(PanchayathBrand.formAction, formData, function(reply) {
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
        PanchayathBrand.formModal.find("div.alert").html(message).show();
    },
    
    clearMessage: function () {
        PanchayathBrand.formModal.find("div.alert").hide();
    }
    
};

$(document).ready(function() {
        PanchayathBrand.ready();
    }
);
