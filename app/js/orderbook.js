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
        });

        $("#spinner").hide();
    }, 3000);
});