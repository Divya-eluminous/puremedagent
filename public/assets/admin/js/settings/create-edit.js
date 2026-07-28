$(document).ready(function () 
{
    // adding focus event to first field
    $('input[name="name"]').focus();
})

// submitting form after validation
$('#settingForm').validator().on('submit', function (e) 
{
    if (!e.isDefaultPrevented()) {

        const $this = $(this);
        const action = $this.attr('action');
        const formData = new FormData($this[0]);

        if(editor!==""){
            formData.append('setting_value', editor.getData());
        }
        
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



function validateSettingValue() {
    // console.log("test");return;

    var setting_key = $("#setting_key").val(); 
    // console.log(setting_key); return;
    if(setting_key == 'TIME_SLOTS_DURATION'){
        // console.log("test"); return;
        var validate_exp = /^\d*$/; 
        if(setting_key.match(validate_exp))
         {
            console.log("valid name");
            $('#validate_value').html("");
            $('#savebtn').attr("disabled", false);
            return true;
         }
       else
         {
            console.log("Not a name");
            $('#validate_value').html("Please enter the valid input.");
            $('#validate_value').css('color','red');
            $('#savebtn').attr("disabled", true);
            return false;      
        }
    // } else if(setting_key == 'HOSPITAL_WAITING_HOURS'){
    //     // console.log("test"); return;
    //     var validate_exp = /^[0-9]+$/;
    //     if(setting_key.match(validate_exp))
    //      {
    //         console.log("valid name"); return;
    //         $('#validate_value').html("");
    //         $('#savebtn').attr("disabled", false);
    //         return true;
    //      }
    //    else
    //      {
    //         console.log("Not a name"); return;
    //         $('#validate_value').html("Please enter the valid input.");
    //         $('#validate_value').css('color','red');
    //         $('#savebtn').attr("disabled", true);
    //         return false;      
    //     }
    // } else if(setting_key == 'APP_LOGGED_MINS'){
    //     // console.log("test");return;
    //     var validate_exp = /^[0-9]+$/;
    //     if(setting_key.match(validate_exp))
    //      {
    //         console.log("valid name");
    //         $('#validate_value').html("");
    //         $('#savebtn').attr("disabled", false);
    //         return true;
    //      }
    //    else
    //      {
    //         console.log("Not a name");
    //         $('#validate_value').html("Please enter the valid input.");
    //         $('#validate_value').css('color','red');
    //         $('#savebtn').attr("disabled", true);
    //         return false;      
    //     }
    }  else{
        return true;
    } 
}


