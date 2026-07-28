$(document).ready(function() {
jQuery(document).ready(function($){
    var deviceAgent = navigator.userAgent.toLowerCase();
    var agentID = deviceAgent.match(/(ipad)/);
    if (agentID) {
        $('html').addClass('ipad');
    }
});

    $('ul.notice-lists.list-group.list-checkbox li').each(function(i) {
        $(this).find('.checkbox-container input').change(function() {
            if ($(this).prop("checked") == true) {
                $(this).parents('li').find('.action-wrapper').addClass('active');
            } else {
                // $(this).parents().find  ('.action-wrapper').removeClass('active');
                console.log($(this).parents('li').find('.action-wrapper').removeClass('active'));
            }
        })
    });
    // if(.prop("checked") == true){
    //     $(this).parents('li').find('.action-wrapper').addClass('active');
    // }


    // var code = $('.custome-code').val();
    // if (code != '') {
    //     $('.custom').css({
    //         'background-color': code
    //     });
    // }
    // setTimeout(function() {
    //     // console.log($('.color-selector .sbHolder a.sbSelector').text());
    //     if ($('.color-selector .sbHolder a.sbSelector').text() == 'Custom') {
    //         $('.custom-code').show();
    //         var code = $('.custom-code').val();
    //         $('.custom').css({
    //             'background-color': code
    //         });
    //         if (code != '') {
    //             $('.custom-code').on("change paste keyup", function() {
    //                 code = $(this).val();
    //                 $('.custom').css({
    //                     'background-color': code
    //                 });
    //             });
    //         }
    //     }
    // }, 100);

    $('.file-upload').change(function(event) {
        $(this).parents('.w-50').next().children().text(event.currentTarget.files[0].name);
    });
    // $('ul.notice-lists.list-group.clear.mh-200').mCustomScrollbar({
    //     theme: "light"
    // });
    $(".left-sidebar").mCustomScrollbar({
        scrollButtons: {
            enable: true
        },
        theme: "minimal-dark",
        scrollbarPosition: "inside"
    });
    $(".m-select").mCustomScrollbar({
        scrollButtons: {
            enable: true
        },
        theme: "3d-thick"
    });


    $('.plus-sign').click(function(e) {
        e.preventDefault();
        $(this).prev('div').removeClass('add-to-do-list-hide');
        $(this).find('img').attr('src')
    });
    $(function() {
        $("#select-country, #select-state, #select-order-state, .my-select").selectbox();
    });
    $('.hover-img').mouseenter(function() {
        var test = $(this).find("span img").attr('src');
        $(this).find("span img").attr('org-path', test);
        var tarr = test.split('/');
        var file = tarr[tarr.length - 1];
        var filename = file.split('.')[0];
        var checking = filename.split('-')[1];
        if (checking === 'Active') {
            $(this).find("span img").attr('src', test.replace(filename, filename));
        } else {
            var hoverfile = filename + '-Active';
            $(this).find("span img").attr('src', test.replace(filename, hoverfile));
        }
    });

    $('.hover-img').mouseleave(function() {
        var orgpath = $(this).find("span img").attr('org-path');
        $(this).find("span img").attr('src', orgpath).removeAttr('org-path');

    });
    $('.Customize-Permissions').click(function(e) {
        e.preventDefault();
        var text = '';
        if ($(this).text() === "customize") {
            text = 'hide';
        } else {
            text = 'customize';
        }
        $(this).text(text);
        $('#Customize-Permissions').toggle();
    });
    $(".sub-menu").change(function() {
        var text = '';
        if ($(this).find("em").text() === "on") {
            text = 'off';
        } else {
            text = 'on';
        }
        $(this).find("em").text(text);
    });


});
if (navigator.userAgent.search("MSIE") >= 0) {
    $('html').addClass('MSIE');
} else if (navigator.userAgent.search("Safari") >= 0 && navigator.userAgent.search("Chrome") < 0) {
    $('html').addClass('Safari');
} else if (navigator.userAgent.search("Opera") >= 0) {
    $('html').addClass('Opera');
} else if (navigator.platform.search("MacIntel") >= 0 && navigator.userAgent.search("Chrome") >= 0) {
    $('html').addClass('macchrome');
} else if (navigator.userAgent.search("Chrome") >= 0) {
    $('html').addClass('Chrome');
} else if (navigator.platform.search("MacIntel") >= 0 && navigator.userAgent.search("Firefox") >= 0) {
    $('html').addClass('macFirefox');
} else if (navigator.userAgent.search("Firefox") >= 0) {
    $('html').addClass('Firefox');
}