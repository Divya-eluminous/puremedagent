$(document).ready(function ()  
{
    
    // adding focus event to first field 
    $('input[name="name"]').focus();  
 

});  
 
// submitting form after validation   
$('#frmPatients').validator().on('submit', function (e)     
{
    // console.log('test'); return; 
    if (!e.isDefaultPrevented()) { 

        const $this = $(this);
        const action = $this.attr('action'); 
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
});

$(document).on('input','#family_doctor',function(){
    var name= $('#family_doctor').val();
    //var validate_name =  /^(?:(?:\(?([a-z]\d\d|[A-Z]\d?)\)?)?[\-\.\ \\\/]?)?((?:\(?\d{1,}\)?[\-\.\ \\\/]?))(?:[\-\.\ \\\/]?(?:#|ext\.?|extension|x)[\-\.\ \\\/]?(\d+))?$/i;; 
    var validate_name =  /^[^\d]+$/;
    if(name != ''){
        if(name.match(validate_name))
        {
            $('#validate_name').html("");
            $('#savebtn').removeClass("disabled");
            return true;
        }
        else
        {
            $('#validate_name').html(familyNameText);
            $('#validate_name').css('color','red');
            $('#savebtn').addClass("disabled");
            return false; 
        }
    }
    else{
        $('#validate_name').html("");
        $('#savebtn').removeClass("disabled");
    }
});



