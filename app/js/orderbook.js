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

            $(".inf").forEach(function (el) {
                var ids = $(this).attr("data-content");
                var idsList = ids.match(/(\d+)|(\d+)/);

                var id1 = idsList[0];
                var id2 = idsList[1];

                $(this).parent().prev().prev().attr("alt", id1);
                $(this).parent().next().next().attr("alt", id2);
            });
        });

        $("#spinner").hide();
    }, 3000);
});