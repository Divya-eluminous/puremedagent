$(document).ready(function ()  
{
    $("#birth_date").mask("99-99-9999");
    
    // adding focus event to first field 
    $('input[name="name"]').focus();  
    // $('input[name="mobile_no"]').mask('+99-999-999-9999');
    $('#birth_date').datepicker({  
        changeMonth: true,  
        changeYear: true,   
        dateFormat: 'dd-mm-yy',     
        yearRange: '1920:+0',   
        startDate: new Date('1920-01-01'),
        maxDate: 0   
    });


    $("#reminder_active").click(function()
    {
        if($(this).prop("checked") != true)
        {
            swal({
              title: title,
              text: sub_title,
              type: "warning",
              showCancelButton: true,
              cancelButtonText: deleteContent.cancel,             
              confirmButtonClass: "btn-danger",
              closeOnConfirm: true,
              showLoaderOnConfirm: true
            },
            function (inputValue)
            {               
                if(inputValue == false)
                {
                    $("#reminder_active").prop("checked",true);
                }else
                {
                    $("#reminder_active").prop("checked",false);
                }
            });
        }else
        {            
           $("#reminder_active").prop("checked",true);
        }
    })


});  
 
// submitting form after validation   
$('#frmPatients').validator().on('submit', function (e)     
{
    // console.log('test'); return; 
    if (!e.isDefaultPrevented()) { 


        //start below code added on 23-feb-23 by divya for appointment date validation 
        var note = $("#note").val();
        var last_appointment = $("#last_appointment").val();
        if($('#note').length && last_appointment!=""){
            if(note.trim()!="")
            {
                console.log('inn');
                var last_appointment = $("#last_appointment").val();
                if(last_appointment=="")
                {
                    $(".err_last_appointment").html(note_patient_appointmentdate_err);
                    return false;
                }
            }
            if(last_appointment!="")
            {
                if(note.trim()=="")
                {
                    $(".err_note").html(note_patient_err);
                    return false;
                }
            }
        }
       //end below code added on 23-feb-23 for appointment date validation  



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

// handling country code select change
function handleCountrySelect(el) {
    var input = document.getElementById('format');
    if (el.value === 'other') {
        input.value = '';
        input.focus();
    } else {
        input.value = el.value;
    }
}
// initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    var sel = document.getElementById('country_code');
    if (sel) handleCountrySelect(sel);
});
//Handling country code done
// $(document).on('input','#mobile_no',function(){
//     var phone=$('#mobile_no').val();
//     //console.log(phone);
//     // console.log(phone.indexOf('0'));

//     var phoneno = /^(?:(?:\(?(?:00|\+)([1-4]\d\d|[1-9]\d?)\)?)?[\-\.\ \\\/]?)?((?:\(?\d{1,}\)?[\-\.\ \\\/]?){0,})(?:[\-\.\ \\\/]?(?:#|ext\.?|extension|x)[\-\.\ \\\/]?(\d+))?$/i;
//     if(phone.match(phoneno))
//      {
//         console.log("valid Phone Number");
//         $('#validateNumber').html("");
//         $('#savebtn').attr("disabled", false);
//         return true;
//      }
//    else
//      {
//         console.log("Not a valid Phone Number");
//         $('#validateNumber').html("Please enter the valid mobile number.");
//         $('#validateNumber').css('color','red');
//         $('#savebtn').attr("disabled", true);
//         return false; 
      
//      }

//    // return;
//     // var regexp = /^\+(?:[0-9] ?){6,14}[0-9]$/
//     // if(phone != ''){
//     //     if(phone.indexOf('0') !== 0){
//     //     // if (!regexp.test(phone)&& phone.length<0) {
//     //         $('#validateNumber').html("Please enter the valid mobile number.");
//     //         $('#validateNumber').css('color','red');
//     //         $('#savebtn').attr("disabled", true);
//     //         return false; 
//     //     }else{
//     //         // console.log('testelse'); return;
//     //         $('#validateNumber').html("");
//     //         $('#savebtn').attr("disabled", false);
//     //         return true;
//     //     }
//     // }
//     // else{
//     //     $('#validateNumber').html("");
//     // }
// });


