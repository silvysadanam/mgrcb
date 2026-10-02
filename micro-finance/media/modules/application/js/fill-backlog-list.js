"use strict";

var FillBacklog = {
    ready: function () {
        $("input#code").blur(FillBacklog.loadMember);
        $("select#fmc").change(FillBacklog.loadMember);
    },
    
    loadMember: function () {
        var memberCode = $("input#code").val();
        if ("" === memberCode) {
            $("input#member").val("");
            return false;
        }
        
        var fmcId = $("select#fmc").val();
        var formAction = $("input#get-member-url").val();
        $.post(formAction, { id : fmcId, code :  memberCode }, function(reply) {
                if (reply.success && reply.valid) {
                    $("input#member").val(reply.name);
                    $("input#code").val(reply.code);
                } else if (reply.redirect) {
                    ApplicationCommon.reloadPage();
                } else if (reply.message) {
                    alert(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
        
        return false;
    }
    
};

$(document).ready(function () {
        FillBacklog.ready();
    }
);
