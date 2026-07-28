$(document).ready(function () 
{
    // adding focus event to first field
    $('input[name="name"]').focus();
    $('.bgcolorpicker').colorpicker();
    $('.textcolorpicker').colorpicker();

    $('.buttoncolorpicker').colorpicker();
    $('.screenbgcolorpicker').colorpicker();
    $('.appbrsetColorCode').colorpicker();
    $('.tabscolorpicker').colorpicker();
    $('.homescreencolorpicker').colorpicker();
    $('.menuHeadercolorpicker').colorpicker();
    $('.menubgcolorpicker').colorpicker();
    $('.darktextcolorpicker').colorpicker();
    $('.lightcolorpicker').colorpicker();
    $('.headercolorpicker').colorpicker(); //added on 30-july-24
})


$(".bgcolorpicker").on('blur', function(event){

    var background_color_code = $('.bgcolorpicker').val();
    $('.bgcolorpicker').val('');
    $('.background_color').val(background_color_code);
    $('.setColorCode').css('background-color', '"'+background_color_code+'"');
});

$(".textcolorpicker").on('blur', function(event)
{
    var text_color_code = $('.textcolorpicker').val();
    $('.textcolorpicker').val('');
    $('.text_color').val(text_color_code);
    $('.textsetColorCode').css('background-color', '"'+text_color_code+'"');
});

// BUTTON COLOR CODE
$(".buttoncolorpicker").on('blur', function(event)
{
    var button_color_code = $('.buttoncolorpicker').val();
    console.log(button_color_code);
    $('.buttoncolorpicker').val('');
    $('#button_colors_code').val(button_color_code);
    $('.buttonsetColorCode').css('background-color', '"'+button_color_code+'"');
});

//Roshani added the code for 58
$(".button_colors_code").on('keyup', function(event) {
    updateBackgroundColor('.button_colors_code', '.buttonsetColorCode');
});
//Roshani added the code for 58

// screen COLOR CODE
$(".screenbgcolorpicker").on('blur', function(event)
{
    var screen_color_code = $('.screenbgcolorpicker').val();
    $('.screenbgcolorpicker').val('');
    $('#screen_bg_color').val(screen_color_code);
    $('.screensetColorCode').css('background-color', '"'+screen_color_code+'"');
});

//Roshani added the code for 58
$("#screen_bg_color").on('keyup', function(event)
{   
    updateBackgroundColor('#screen_bg_color', '.screensetColorCode');
});
//Roshani added the code for 58

//app br color
$(".appbrcolorpicker").on('blur', function(event)
{
    var screen_color_code = $('.appbrcolorpicker').val();
    $('.appbrcolorpicker').val('');
    $('#app_bar_color').val(screen_color_code);
    $('.appbrsetColorCode').css('background-color', '"'+screen_color_code+'"');
});

//Roshani added the code for 58
$("#app_bar_color").on('keyup', function(event)
{ 
    updateBackgroundColor('#app_bar_color', '.appbrsetColorCode');
});
//Roshani added the code for 58

//tabe selection
$(".tabscolorpicker").on('blur', function(event)
{
    var screen_color_code = $('.tabscolorpicker').val();
    $('.tabscolorpicker').val('');
    $('#tabs_selection_color').val(screen_color_code);
    $('.tabssetColorCode').css('background-color', '"'+screen_color_code+'"');
});

//Roshani added the code for 58
$("#tabs_selection_color").on('keyup', function(event)
{ 
    updateBackgroundColor('#tabs_selection_color', '.tabssetColorCode');
});
//Roshani added the code for 58

//HOme Screen Option
$(".homescreencolorpicker").on('blur', function(event)
{
    var home_screen_color_code = $('.homescreencolorpicker').val();
    $('.homescreencolorpicker').val('');
    $('#home_screen_options_color').val(home_screen_color_code);
    $('.homescreensetColorCode').css('background-color', '"'+home_screen_color_code+'"');
});

//menu header Option
$(".menuHeadercolorpicker").on('blur', function(event)
{
    var menu_header_color_code = $('.menuHeadercolorpicker').val();
    $('.menuHeadercolorpicker').val('');
    $('#menu_header_colors').val(menu_header_color_code);
    $('.menuHeadersetColorCode').css('background-color', '"'+menu_header_color_code+'"');
});

//Roshani added the code for 58
$("#menu_header_colors").on('keyup', function(event)
{ 
    updateBackgroundColor('#menu_header_colors', '.menuHeadersetColorCode');
});
//Roshani added the code for 58

//menu header Option
$(".menubgcolorpicker").on('blur', function(event)
{
    var menu_bg_color_code = $('.menubgcolorpicker').val();
    $('.menubgcolorpicker').val('');
    $('#menu_bg_color').val(menu_bg_color_code);
    $('.menubgsetColorCode').css('background-color', '"'+menu_bg_color_code+'"');
});

//Roshani added the code for 58
$("#menu_bg_color").on('keyup', function(event)
{ 
    updateBackgroundColor('#menu_bg_color', '.menubgsetColorCode');
});
//Roshani added the code for 58

//menu header Option
$(".darktextcolorpicker").on('blur', function(event)
{
    var dark_color_code = $('.darktextcolorpicker').val();
    $('.darktextcolorpicker').val('');
    $('#dark_text_color').val(dark_color_code);
    $('.darktextsetColorCode').css('background-color', '"'+dark_color_code+'"');

});

//Roshani added the code for 58
$("#dark_text_color").on('keyup', function(event)
{ 
    updateBackgroundColor('#dark_text_color', '.darktextsetColorCode');
});
//Roshani added the code for 58

//light Option
$(".lightcolorpicker").on('blur', function(event)
{
    var light_color_code = $('.lightcolorpicker').val();
    $('.lightcolorpicker').val('');
    $('#light_text_color').val(light_color_code);
    $('.lightsetColorCode').css('background-color', '"'+light_color_code+'"');

}); 

//Roshani added the code for 58
$("#light_text_color").on('keyup', function(event)
{ 
    updateBackgroundColor('#light_text_color', '.lightsetColorCode');
});
//Roshani added the code for 58

//header Option added on 30-july-24
$(".headercolorpicker").on('blur', function(event)
{
    var header_color_code = $('.headercolorpicker').val();
    $('.headercolorpicker').val('');
    $('#header_text_color').val(header_color_code);
    $('.headersetColorCode').css('background-color', '"'+header_color_code+'"');

});

//Roshani added the code for 58
$("#header_text_color").on('keyup', function(event)
{ 
   updateBackgroundColor('#header_text_color', '.headersetColorCode');
});
//Roshani added the code for 58

function updateBackgroundColor(inputSelector, targetSelector) {
    const formatInput = document.querySelector(inputSelector);
    const pattern = new RegExp(formatInput.pattern);
    const isValid = pattern.test(formatInput.value);
    const colorCode = $(inputSelector).val();

    if (colorCode === "") {
        $(targetSelector).css('background-color', '');
    } else if (isValid) {
        $(targetSelector).css('background-color', colorCode);
    } else {
        $(targetSelector).css('background-color', '');
    }
}

// submitting form after validation
$('#OrdinationsForm').validator().on('submit', function (e)  
{
    if (!e.isDefaultPrevented()) {
        const $this = $(this);
        const action = $this.attr('action');
        console.log(action);
        const formData = new FormData($this[0]);
        
        $('.card-body').LoadingOverlay("show", {
            background: "rgba(165, 190, 100, 0.4)",
        });

        axios.post(action, formData)
            .then(function (response) {
                const resp = response.data;

                if (resp.status == 'success') {
                    // $this[0].reset();
                    toastr.success(resp.msg);
                    $('.card-body').LoadingOverlay("hide");
                    setTimeout(function () {
                        window.location.href = resp.url;
                    }, 2000)
                }

                if (resp.status == 'error') {
                    $('.card-body').LoadingOverlay("hide");
                    toastr.error(resp.msg);

                    const errorBag = resp.errors;

                    $.each(errorBag, function (fieldName, value) {
                        $('.err_' + fieldName).closest('.form-group').addClass('has-error has-danger');
                        $('.err_' + fieldName).text(value[0]).closest('span').show();
                    })
                }
            })
            .catch(function (error) {
                
                $('.card-body').LoadingOverlay("hide");

                const errorBag = error.response.data.errors;

                $.each(errorBag, function (fieldName, value) {
                    
                    $('.err_' + fieldName).closest('.form-group').addClass('has-error has-danger');
                    $('.err_' + fieldName).text(value[0]).closest('span').show();
                })
            });

        return false;
    }
    
})


//Roshani added the below code for reset the color picker

$(".reset").on('click', function(event){
    const classesToReset = [
        '.buttonsetColorCode',
        '.textsetColorCode',
        '.screensetColorCode',
        '.appbrsetColorCode',
        '.tabssetColorCode',
        '.homescreensetColorCode',
        '.menuHeadersetColorCode',
        '.menubgsetColorCode',
        '.darktextsetColorCode',
        '.lightsetColorCode',
        '.headersetColorCode'
    ];
    // Join the classes into a single selector string
    const selector = classesToReset.join(', ');
    // Reset the background color of all matched elements
    $(selector).css('background-color', '');
});

//Roshani added the below code for reset the color picker

