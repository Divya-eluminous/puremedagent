 
$('#birth_date').mask("99-99-9999");  


// $('#birth_date').datepicker({ 
//         changeMonth: true,
//         changeYear: true,
//         dateformat: 'dd-mm-yy',
//     });

$('#birth_date').datepicker({ 
        dateFormat: 'dd-mm-yy',  
        changeMonth: true,
        changeYear: true, 
        yearRange: '1920:+0',
        startDate: new Date('1920-01-01')     
    });
 $('#userLogin').validator().on('submit', function (e) 
  {
      
    $('.login-submit-btn').prop('disabled', true);
      if (!e.isDefaultPrevented()) 
     {
        const $this    = $(this); 
        const action   = $this.attr('action');
        const formData = new FormData($this[0]); 
        // console.log(action);
        //  console.log(formData);
        // return false;
        $.LoadingOverlay("show", {
           background  : "rgba(165, 190, 100, 0)",
        });

        axios.post(action,formData)
        .then(function (response) 
        {
           const resp =  response.data;
           $.LoadingOverlay("hide");
           if (resp.status == 'success') 
           {
              // $this[0].reset();
              
              //toastr.success(resp.msg);
              setTimeout(function()
              {
                  window.location.href = resp.url;

              }, 5000)
           }

           if (resp.status == 'error') 
           {
              $('.login-submit-btn').prop('disabled', false);
              toastr.error(resp.msg);
           }
        })
        .catch(function (error) 
        {
          
           $.LoadingOverlay("hide");

           const errorBag = error.response.data.errors;

           $.each(errorBag, function(fieldName, value) 
           {
              $('.err_'+fieldName).closest('div').addClass('has-error has-danger'); 
              $('.err_'+fieldName).text(value[0]).closest('span').show(); 
           })

        }); 

        return false;
     }
  })

function sendOtp() {

    //var patient_id = $("#patient_id").val();
    var first_name = $("#first_name").val();
    var family_name = $("#family_name").val();
    var country_code = $("#country_code").val();
    var mobile_no = $("#mobile_no").val();

    // console.log(doctor_id);
    // console.log(appointment_type_id);
    // console.log(appointment_date);

    //$("#appointment_date").blur();
    //return false;
    if (first_name != "" && family_name != "" && country_code != "" && mobile_no != "") {
        var action = WEBURL + '/online-appointment/send-otp';
        $('.card-body').LoadingOverlay("show", {
            background: "rgba(165, 190, 100, 0.4)",
        });
        axios.post(action, {
                first_name: first_name,
                family_name: family_name,
                country_code: country_code,
                mobile_no: mobile_no,
            })
            .then(response => {
              const resp =  response.data;
                $('.card-body').LoadingOverlay("hide");

                  $("#otp_code").attr('required',true);
                  if (resp.status == 'success') 
                  {
                    toastr.success(resp.msg);
                  }

                  if (resp.status == 'error') 
                  {
                    toastr.error(resp.msg);
                  }
               
            })
            .catch(error => {
                $('.card-body').LoadingOverlay("hide");
            })
    }else{
       toastr.error('Bitte füllen Sie die erforderlichen Felder aus');
    }

    return false;
}