"use strict";

var ViewDcb = {
    ready: function () {
        $("input#code").blur(ViewDcb.loadMember);
        if ($("select#member_id").is(":visible")) {
            $("select#member_id").select2();
        }
        $("select#fmc").change(ViewDcb.loadFmcMembers);
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
    },
    
    loadFmcMembers: function () {
        var fmcId = $("select#fmc").val();
        $("input#member").val("");
        $("input#code").val("");
        if (fmcId > 0) {
            $(".member-code").hide();
            $(".member-select").show();
            
            var formAction = $("input#get-fmc-members-url").val();
            $.post(formAction, { id : fmcId}, function(reply) {
                    if (reply.success && reply.valid) {
                        $("select#member_id").html(reply.html);
                        $("select#member_id").val(0);
                        $("select#member_id").select2();
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
        
        $(".member-code").show();
        $(".member-select").hide();
        $("select#member_id").select2("destroy");
        
        return false;
    }
    
};

$(document).ready(function () {
        ViewDcb.ready();
    }
);
