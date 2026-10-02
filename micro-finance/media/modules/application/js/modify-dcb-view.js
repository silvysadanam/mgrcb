"use strict";

var ModifyDcb = {
    ready: function () {
        $("a#delete-dcb").click(ModifyDcb.deleteDcb);
        $("a#reset-dcb").click(ModifyDcb.resetDcb);
        $("a#add-note").click(ModifyDcb.addNote);
        $("form#form-loan-note-modal").submit(ModifyDcb.saveNote);
        $("a#reschedule-dcb").click(ModifyDcb.rescheduleDcb);
        $("a#writeoff-dcb").click(ModifyDcb.writeoffDcb);
        $("a#activate-dcb").click(ModifyDcb.activateDcb);
        $("a#approve-dcb").click(ModifyDcb.approveDcb);
    },
    
    deleteDcb: function () {
        if (confirm("Are you sure you to permanently DELETE this DCB?")) {
            ModifyDcb.sendRequest("delete");
        }
        
        return false;
    },
    approveDcb: function () {
        if (confirm("Are you sure you to Approve this DCB?")) {
            ModifyDcb.sendRequest("approve");
        }
        
        return false;
    },
    
    resetDcb: function () {
        if (confirm("Are you sure you to RESET DCB entries?")) {
            ModifyDcb.sendRequest("reset");
        }
        
        return false;
    },
    
    writeoffDcb: function () {
        if (confirm("Are you sure you to WRITE OFF this loan?")) {
            ModifyDcb.sendRequest("writeoff");
        }
        
        return false;
    },
    
    rescheduleDcb: function () {
        if (confirm("Are you sure you to REFINANCE this loan?")) {
            window.location = $("input#loan-reschedule-url").val();
        }
        
        return false;
    },
    
    activateDcb: function () {
        if (confirm("Are you sure you to ACTIVATE this loan?")) {
            return true;
        }
        
        return false;
    },
    
    sendRequest: function (manageAction) {
        var formData = { id : $("input#loan-id").val(), action : manageAction };
        var formAction = $("input#loan-manage-url").val();
            $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    if ("delete" === manageAction) {
                        window.location = $("input#loan-cancel-url").val();
                    } else {
                        window.location.reload(true);
                    }
                } else if (reply.redirect) {
                    ApplicationCommon.reloadPage();
                } else if (reply.message) {
                    alert(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
    },
    
    addNote: function () {
        $("div#modal-loan-note").modal("show");
        $("form#form-loan-note-modal").find("textarea#loan-note").val("");
        
        return false;
    },
    
    saveNote: function () {
        var formData = {
            id : $("input#loan-id").val(),
            note : $("form#form-loan-note-modal").find("textarea#loan-note").val()
        };
        var formAction = $("input#add-note-url").val();
            $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    window.location.reload(true);
                } else if (reply.redirect) {
                    ApplicationCommon.reloadPage();
                } else if (reply.message) {
                    alert(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
    }
    
};

$(document).ready(function () {
        ModifyDcb.ready();
    }
);
