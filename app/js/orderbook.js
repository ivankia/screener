$(document).ready(function() {
    setInterval(function() {
        $("#spinner").show();

        $.ajax({
            url: "http://dev.huemae.ru/{{file_cache}}.html",
            cache: false
        }).done(function(html) {
            $("#mainFrame table").html(html);
            $("#statsCurrentPrice").html($("#currentPrice").html());
            $("title").text($("#currentPrice").html());

            /*
            $(".inf").forEach(function (el) {
                var ids = $(this).attr("data-content");
                var idsList = ids.match(/(\d+)|(\d+)/);

                var id1 = idsList[0];
                var id2 = idsList[1];

                $(this).parent().prev().prev().attr("alt", id1);
                $(this).parent().next().next().attr("alt", id2);
            });
            */
        });

        $("#spinner").hide();
    }, 3000);
});

$(document).keypress(function(event) {
    var keyCode = (event.keyCode ? event.keyCode : event.which);

    //console.log(keyCode);

    if (keyCode == 113) {
        $("#instrument-tools").hide();
    }

    if (keyCode == 119) {
        $("#instrument-tools").show();
    }

    if (keyCode == 114) {
        $.ajax({
            url: "http://dev.huemae.ru:88/d.php",
            context: document.body
        }).done(function() {
            alert("Reload delta-server complete ...");
        });
    }

    if (keyCode == 116) {
        $.ajax({
            url: "http://dev.huemae.ru:88/s.php",
            context: document.body
        }).done(function() {
            alert("Reload screener complete ...");
        });
    }

    if (keyCode == 103) {
        if (window.document.location.pathname == "/xbtusd_zoom.html") {
            window.document.location = "http://dev.huemae.ru/xbtusd.html";
        } else {
            window.document.location = "http://dev.huemae.ru/xbtusd_zoom.html"
        }
    }
});
