 
$('#birth_date').mask("99-99-9999");  

   // added by vijay 19/4/2024
function hideErrorMessage() {
    var birthDateInput = document.getElementById("birth_date");
    var errorMessage = document.querySelector(".err_birth_date");
    if (errorMessage) {
        errorMessage.style.display = "none";
    }
}
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
        startDate: new Date('1920-01-01'),
        maxDate: 0     
    });

//Added by Roshani for chnage value after click on dropdown value
// document.addEventListener('DOMContentLoaded', function() {
//     const countryCodeSelect = document.getElementById('country_code');
//     const formatInput = document.getElementById('format');
//     const loginButton = document.getElementById('login-submit-btn'); // Corrected ID selectorfunction
//     function validateInput() 
//     {
//         const pattern = new RegExp(formatInput.pattern);
//         const isValid = pattern.test(formatInput.value);
//         if (isValid) 
//         {
//             $("#format").click(); // Trigger click event on element with ID 'format'
//             $("#format").blur();  // Trigger blur event on element with ID 'format'
//         } else {
//         }
//     }
//     countryCodeSelect.addEventListener('change', function() {
//         formatInput.value = this.value;
//         validateInput();
//     });
//     formatInput.addEventListener('input', validateInput);
//     validateInput(); // Initial validation on load 
// });

//Added by Roshani for chnage value after click on dropdown value
 
 $('#userLogin').validator().on('submit', function (e) 
  {
    /************* Roshani added below code for CR #230 **********/
    var checkDOB = checkBirthDate();
    if( checkDOB == 1)
    {
        event.preventDefault();
        return;
    }
    /************* Roshani added below code for CR #230 **********/
      // added by vijay 22/4/2024
      setTimeout(function () {
          $("#mobile_no").blur();
      }, 100);
      // $('.login-submit-btn').prop('disabled', true);
      // added by vijay 19/4/2024
      // var birthDateInput = document.getElementById("birth_date");
      // if (!birthDateInput.value) {
      //     var errorMessage = document.querySelector(".err_birth_date");
      //     errorMessage.textContent = "Geburtsdatum-Feld ist erforderlich.";
      //     errorMessage.style.display = "block";
      // } else {
      //     var errorMessage = document.querySelector(".err_birth_date");
      //     if (errorMessage) {
      //         errorMessage.style.display = "none";
      //     }
      // }
      var mobileInput = document.getElementById("mobile_no");
      if (!mobileInput.value) {
          var mobileNumberInput = $("#mobile_no");
          var formGroup = mobileNumberInput.closest(".form-group.row");
          formGroup.addClass("has-error has-danger");
          //   var mobileErrorMessage = document.querySelector(".err_mobile_no");
          //   mobileErrorMessage.textContent = "Mobile number is required.";
          //   mobileErrorMessage.style.display = "block";
      } else {
          var mobileErrorMessage = document.querySelector(".err_mobile_no");
          if (mobileErrorMessage) {
              mobileErrorMessage.style.display = "none";
          }
      }
      //end


      if($("#isLogin").val()!="" && $("#isLogin").val()==0 && $("#email").val()==""){

               $("#email_div").removeAttr("style");

               var errorMessage = document.querySelector(".err_email_login");
             // errorMessage.textContent = "E-Mail-Adresse-Feld ist erforderlich.";
                errorMessage.textContent = "Bitte geben Sie eine gültige Email Adresse an.";
              errorMessage.style.display = "block";
               $(".login-submit-btn").prop("disabled", false);
               return false;
       }
       else if($("#isLogin").val()!="" && $("#isLogin").val()==0 && $("#email").val()!=""){

            //start added on 15may24
                var isValid = validateEmail($("#email").val());
                console.log('isValid==>'+isValid);
                if (!isValid) {
                   // $('#email').removeAttr('data-error');
                     var errorMessage = document.querySelector(".err_email_login");
                    errorMessage.textContent = "Bitte geben Sie eine gültige Email Adresse an.";
                    errorMessage.style.display = "block";
                     $(".login-submit-btn").prop("disabled", false);
                     return false;
                }else{
                    var emailErrorMessage = document.querySelector(".err_email_login");
                      if (emailErrorMessage) {
                          emailErrorMessage.style.display = "none";
                      }
                }
            //end added on 15may24  

           
       }
       else if($("#isLogin").val()!="" && $("#isLogin").val()==1)
       {
        //this elseif condition added on 21-may-24 to hide the email div if login patient
          $("#email_div").hide();
       }

      //$("#sendPatientOtp").prop("disabled",true); //hide on 17-may-24



      if (!e.isDefaultPrevented()) {
          const $this = $(this);
          const action = $this.attr("action");
          const formData = new FormData($this[0]);
          // return false;
          $.LoadingOverlay("show", {
              background: "rgba(165, 190, 100, 0)",
          });

          axios
              .post(action, formData)
              .then(function (response) {
                  const resp = response.data;
                  $.LoadingOverlay("hide");

                  console.log('innn response');
                  console.log(resp);
                
                   $("#isLogin").val(resp.isLogin);

                   $("#dbEmailExists").val(resp.dbEmailExists); //added on 27-may-24

                   $("#patient_email").val(resp.patient_email);


                   //in case of registration show email field
                   if(resp.isLogin==0 && $("#email").val()==""){
                           $("#email_div").removeAttr("style");
                           $(".login-submit-btn").prop("disabled", false);
                           return false;
                   }
                   else if(resp.isLogin==0 && $("#email").val()!="")
                   {
                       //start added on 15may24
                        var isValid = validateEmail($("#email").val());
                        if (!isValid) {
                           // $('#email').removeAttr('data-error');
                             var errorMessage = document.querySelector(".err_email_login");
                            errorMessage.textContent = "Bitte geben Sie eine gültige Email Adresse an.";
                            errorMessage.style.display = "block";
                             $(".login-submit-btn").prop("disabled", false);
                             return false;
                        }else{
                            var emailErrorMessage = document.querySelector(".err_email_login");
                              if (emailErrorMessage) {
                                  emailErrorMessage.style.display = "none";
                              }
                        }
                        //end added on 15may24

                     
                   }//else
                   else if($("#isLogin").val()!="" && $("#isLogin").val()==1)
                   {
                    //this elseif condition added on 21-may-24 to hide the email div if login patient
                      $("#email_div").hide();
                   }

                  

                  // ############# Roshani Added this code on 13-mar-24 #################

                   //alert($("#password").val());

                    if (resp.status == 'custom_error_password' && $("#password").val()=="") {

                     $('.card-body').LoadingOverlay("hide");
                     $('.err_password').closest('.form-group').addClass('has-error has-danger');
                     $('.err_password').text(resp.msg).closest('span').show();

                    }                   
                    else{
                        //added else condition
                        $('.err_password').closest('.form-group').removeClass('has-error has-danger');
                        $('.err_password').text(resp.msg).closest('span').hide();
                        $("#hid_pass_check").val("");

                    }

                  // ############# Roshani Added this code on 13-mar-24 #################
                  if (resp.status == "success") {
                      if (resp.data != "") {
                          const email = resp.data.patient_email; // assuming data is your JSON object
                          const patient_id = resp.data.patient_id; // assuming data is your JSON object

                          document.getElementById("emailInput").value = email;
                          document.getElementById("patient_id").value =
                              patient_id;
                      }
                      $(".login-submit-btn").prop("disabled", false);

                      if (resp.password_show == "yes") {
                          $("#password_div").removeAttr("style");
                          $("#hid_pass_check").val("show_password_error");
                          //  added by vijay 19/4/24
                           var birthDateInput =
                               document.getElementById("birth_date");
                           var formatInput = document.getElementById("format");
                           var countryCodeInput =
                               document.getElementById("country_code");
                           var mobileNoInput =
                               document.getElementById("mobile_no");

                           document.getElementById("birth_date_hidden").value =
                               birthDateInput.value;
                           document.getElementById("format_hidden").value =
                               formatInput.value;
                           document.getElementById(
                               "country_code_hidden"
                           ).value = countryCodeInput.value;
                           
                           document.getElementById("mobile_no_hidden").value =
                               mobileNoInput.value;

                           var birthDateInput =
                               document.getElementById("birth_date");
                           birthDateInput.disabled = true;

                           var mobileCountryCodeInput =
                               document.getElementById("format");
                           mobileCountryCodeInput.disabled = true;

                           var mobileCountryCode =
                               document.getElementById("country_code");
                           mobileCountryCode.disabled = true;

                           var mobileNumber =
                               document.getElementById("mobile_no");
                           mobileNumber.disabled = true;

                          //end
                      }                    
                       else {
                        
                         $("#sendPatientOtp").prop("disabled",false);
                         $("#otp_button").show(); //added on 17-may-24
                         $("#otp_field").show(); //added on 17-may-24


                          //added on 20-may-24 to make password field readonly         
                           var password = document.getElementById("password");
                            password.readOnly = true ;
                           password.style.backgroundColor = '#e9ecef';
                           //added on 20-may-24 to make password field readonly 

 

                          if($("#otp_code").val()=="")
                          {
                              $("#shown_otp_field").val(1);

                              // var errorMessage = document.querySelector(".err_otp_code");
                              // errorMessage.textContent = "Das Opp-Feld ist erforderlich.";
                              // errorMessage.style.display = "block";
                               $(".login-submit-btn").prop("disabled", true);
                               return false;
                          }else{
                             if($("#otp_code").val()!="")
                             {
                                console.log('in not empty otp value');
                                var emailErrorMessage = document.querySelector(".err_otp_code");
                                  if (emailErrorMessage) {
                                      emailErrorMessage.style.display = "none";
                                }    

                                 $(".card-body").LoadingOverlay("hide");
                                // toastr.success(resp.msg);
                                 window.location.href = resp.url; // Redirect immediately
                             }
                          }
                         
                      }//else
                  }

                  if (resp.status == "error") {
                      $(".login-submit-btn").prop("disabled", false);
                      //$("#sendPatientOtp").prop("disabled",true);//hide on 17-may-24
                      toastr.error(resp.msg);
                  }
              })
              .catch(function (error) {
                  $.LoadingOverlay("hide");

                  const errorBag = error.response.data.errors;

                  $.each(errorBag, function (fieldName, value) {
                      $(".err_" + fieldName)
                          .closest("div")
                          .addClass("has-error has-danger");
                      $(".err_" + fieldName)
                          .text(value[0])
                          .closest("span")
                          .show();
                  });
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
    var birth_date = $("#birth_date").val();
    // console.log(doctor_id);
    // console.log(appointment_type_id);
    // console.log(appointment_date);

    //$("#appointment_date").blur();
    //return false;
    if (first_name != "" && family_name != "" && country_code != "" && mobile_no != ""  && birth_date != "") {
        var action = WEBURL + '/online-appointment/send-otp';
        $('.card-body').LoadingOverlay("show", {
            background: "rgba(165, 190, 100, 0.4)",
        });
        axios.post(action, {
                first_name: first_name,
                family_name: family_name,
                country_code: country_code,
                mobile_no: mobile_no,
                birth_date:birth_date,
            })
            .then(response => {
              const resp =  response.data;
                $('.card-body').LoadingOverlay("hide");

                  $("#otp_code").attr('required',true);
                  if (resp.status == 'success') 
                  {
                    $('.login-submit-btn').prop('disabled', false); //added on 8-sept-22
                    toastr.success(resp.msg);
                  }

                  if (resp.status == 'error') 
                  {
                    $('.login-submit-btn').prop('disabled', true); //added on 8-sept-22
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
//Roshni added below code 25-03-2024
$(document).ready(function()
{
   $('#frmforgetPasswordWeb').validator().on('submit', function (e) 
   {
      if (!e.isDefaultPrevented()) 
      {
         const $this    = $(this); 
         const action   = $this.attr('action');
         const formData = new FormData($this[0]); 
         axios.post(action,formData)
         .then(function (response) 
         {
            const resp =  response.data;

            if (resp.status == 'success') 
            {
               $this[0].reset();
               toastr.success(resp.msg);
               $('.card').LoadingOverlay("hide");
               setTimeout(function()
               {
                  window.location.href = resp.url;

               }, 2000)
            }

            if (resp.status == 'error') 
            {
               $('.card').LoadingOverlay("hide");
               toastr.error(resp.msg);
            }
         })
         .catch(function (error) 
         {
            $('.card').LoadingOverlay("hide");

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
})
//Roshani added this code on 26-03-2024


//start added on 13-may-24
function sendOtpPatient() {
    console.log("in sendOtpPatient");

    //var patient_id = $("#patient_id").val();
    var isLogin = $("#isLogin").val();
    var country_code = $("#country_code").val();
    var mobile_no = $("#mobile_no").val();
    var birth_date = $("#birth_date").val();

    var password = $("#password").val(); //added on 16-may-24

    var dbEmailExists = $("#dbEmailExists").val(); //added on 27-may-24
    var format = $("#format").val();//added on 27-may-24

    console.log("in format");
    console.log(format);



    if(isLogin==1)
    {
        var email = $("#patient_email").val();
    }
    if(isLogin==0)
    {
        var email = $("#email").val();     
    }


    if (country_code != "" && mobile_no != ""  && birth_date != "" && dbEmailExists!="" && format!="") 
    {

        var action = WEBURL + '/online-appointment/send-patient-otp';
        $('.card-body').LoadingOverlay("show", {
            background: "rgba(165, 190, 100, 0.4)",
        });
        axios.post(action, {
                email: email,
                country_code: country_code,
                mobile_no: mobile_no,
                birth_date:birth_date,
                isLogin:isLogin,
                password:btoa(password),
                dbEmailExists:dbEmailExists,
                format:format
            })
            .then(response => {
              const resp =  response.data;
                $('.card-body').LoadingOverlay("hide");

                  $("#otp_code").attr('required',true);
                  if (resp.status == 'success') 
                  {
                    $('.login-submit-btn').prop('disabled', false); //added on 8-sept-22
                    toastr.success(resp.msg);

                     $("#otp_button").show(); //added on 17-may-24
                  }

                  if (resp.status == 'error') 
                  {
                    $('.login-submit-btn').prop('disabled', true); //added on 8-sept-22
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
//end added on 13-may-24



$('#email').on('keyup blur', function() {
    var email = $(this).val().trim();
    
    if (email === "") {
        //console.log('in empty email.....');
       // $(".err_email_login").text("E-Mail-Adresse-Feld ist erforderlich.");
         var errorMessage = document.querySelector(".err_email_login");
          // errorMessage.textContent = "E-Mail-Adresse-Feld ist erforderlich.";
          errorMessage.textContent = "Bitte geben Sie eine gültige Email Adresse an.";
          errorMessage.style.display = "block";
          $(".login-submit-btn").prop("disabled", true);
          //$(".sendPatientOtp").prop("disabled", true); //added on 16-may-24
    } 
    else{
         console.log('in not empty email.......');
         //$(".err_email_login").text("");
          if (email != "") 
          {
                var isValid = validateEmail($("#email").val());
                //console.log('isValid==>'+isValid);
                if (!isValid) {
                   // $('#email').removeAttr('data-error');
                     var errorMessage = document.querySelector(".err_email_login");
                    errorMessage.textContent = "Bitte geben Sie eine gültige Email Adresse an.";
                    errorMessage.style.display = "block";
                     $(".login-submit-btn").prop("disabled", false);
                     // $(".sendPatientOtp").prop("disabled", true);//added on 16-may-24
                     return false;
                }else{
                    var emailErrorMessage = document.querySelector(".err_email_login");
                      if (emailErrorMessage) {
                          emailErrorMessage.style.display = "none";
                      }
                }
          }
      
    }
   
});

function validateEmail(email) {
    // Regular expression for email validation
    //var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    var emailPattern = /^([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/;

    return emailPattern.test(email);
}

//end added on 14-may-24

/***************** Roshani added this for CR #229 *****************/
$(document).ready(function() {
    $("#show_hide_password a").on('click', function(event) {
        event.preventDefault();
        if($('#show_hide_password input').attr("type") == "text"){
            $('#show_hide_password input').attr('type', 'password');
            $('#show_hide_password i').addClass( "fa-eye-slash" );
            $('#show_hide_password i').removeClass( "fa-eye" );
        }else if($('#show_hide_password input').attr("type") == "password"){
            $('#show_hide_password input').attr('type', 'text');
            $('#show_hide_password i').removeClass( "fa-eye-slash" );
            $('#show_hide_password i').addClass( "fa-eye" );
        }
    });
});
/***************** Roshani added this for CR #229 *****************/
/************* Roshani added below code for CR #230 **********/

// Function to check if the date is valid
function isValidDate(day, month, year) {
   // const dateObj = new Date(`${year}-${month}-${day}`);//commented on 6-jan-25 for #280 issue
    const dateObj = new Date(Date.UTC(year, month - 1, day));//added on 6-jan-25 for #280 issue 
    console.log("dateObj===>");
    console.log(dateObj);  
    
    
    // Check if the date is valid and corresponds to the input values
    return (dateObj && 
            dateObj.getFullYear() == year && 
            (dateObj.getMonth() + 1) == month && 
            dateObj.getDate() == day);
}

// Function to display errors
function showError(message, day, month, year) {
    document.querySelector('.err_birth_date').textContent = message;

    // Highlight invalid fields
    if (day === '') {
        document.getElementById('day').style.border = '1px solid red';
    }
    if (month === '') {
        document.getElementById('month').style.border = '1px solid red';
    }
    if (year === '') {
        document.getElementById('year').style.border = '1px solid red';
    }
}

// Main function to validate and process the date
function checkBirthDate() {
    document.querySelector('.err_birth_date').textContent = '';
    document.querySelectorAll('.form-control').forEach(el => el.style.border = '');

    const day = document.getElementById('day').value;
    const month = document.getElementById('month').value;
    const year = document.getElementById('year').value;
    var dobFlagError = 0; 
    if (day === '' || month === '' || year === '') {
        showError('Geburtsdatum-Feld ist erforderlich.', day, month, year);
        dobFlagError = 1;
    } else {
        // Combine the date parts in d-m-y format
        const completeDOB = `${day}-${month}-${year}`;
        
        // Call the date validation function
        if (isValidDate(day, month, year)) {
        dobFlagError = 0;

            document.getElementById('birth_date').value = completeDOB; // Set the value in d-m-y format
        } else {
        dobFlagError = 1;

            showError('Ungültiges Datum eingegeben.', day, month, year);
        }

    }
    return dobFlagError;
}
    /************* Roshani added below code for CR #230 **********/
function handleCountrySelect(el) {
      var input = document.getElementById('format');
      if (el.value === 'other') {
          input.value = '';
          input.focus();
      } else {
          input.value = el.value;
      }
  }