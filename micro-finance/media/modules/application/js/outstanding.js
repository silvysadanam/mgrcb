"use strict";

$(document).ready(function () {
        $("a.scroll-to").on("click", function (e) {
            e.preventDefault();
            var targetBlock = $("div#" + $(this).data("target"));
            $("html, body").stop().animate({
                    "scrollTop": targetBlock.offset().top
                }, 800, "swing", function () {}
            );
    
            return false;
        });
    }
);
