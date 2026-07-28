$(document).ready(function () {


    // adding focus event to first field
    $('input[name="name"]').focus();

    $('#birth_date').datepicker({ 

        dateFormat: 'dd-mm-yy',  
        changeMonth: true,
        changeYear: true, 
        yearRange: '1920:+0',
        startDate: new Date('1920-01-01'),
        maxDate: 0         
    });

    $('#appointment_date').datepicker({
        // changeMonth: true,
        // changeYear: true,
     //  dateFormat: 'yy-mm-dd',
       dateFormat: 'dd-mm-yy', //swapnil added  on 10-jan-23
        orientation: "bottom",
        autoclose: true,
        todayHighlight: true,
        
    });


    $('#search_birth_date').datepicker({ 
      dateFormat: 'dd-mm-yy',       
    });
    $('#birth_date').mask("99-99-9999");    
   // $('#appointment_date').mask("9999-99-99");     //commneted by  swapnil 10-jan-23
    $('#appointment_date').mask("99-99-9999");  // code added by swapnil 10-jan-23 
    $('#search_birth_date').mask("99-99-9999"); 
    
    // $("#appointment_date").datepicker("setDate", new Date());
})


//commented below code on 23-sept-24
/*
$("#suggesstion_patient_id").keyup(function () 
{ 
	  $("#search_birth_date").val('');   
          var searchKey = $(this).val();          
           var birthdateKey = $("#search_birth_date").val();
          if(searchKey=='' && birthdateKey ==''){          
            $("#suggesstion-box-patient").empty();
          }else{

            $.ajax({
              type: "GET",
              url: ADMINURL + "/dashboard/patients",
              data: 'keyword=' + searchKey+'&popup=1&birthdateKey='+birthdateKey, 
              success: function (response) {

                var len = 0;
                if (response['data'] != null) {
                  len = response['data'].length;
                  var data = response['data'];
                }
                if (len > 0) {
                  for (var i = 0; i < len; i++) {
                    var patient_name = response['data'][i].first_name;
                    var lname = response['data'][i].family_name;

                    if (lname != null) {
                      patient_name += "-" + lname;
                    }
                  }
                  $("#suggesstion-box-patient").show();
                  $("#suggesstion-box-patient").html(response['data']);
                  
                  $("#suggesstion-box-patient").on('change','#patient_id',function()
                  {
                    $("#search_birth_date").val($("#patient_id option:selected").attr('title'));
                  });

		              var dob = $("#patient_id option:first").attr('title');
                  $("#search_birth_date").val(dob);

                  $('#search_birth_date').datepicker({ 
                    dateFormat: 'dd-mm-yyyy',       
                  }); 
                  $("#patient_id").css("background", "#FFF");

                  document.getElementById("patient_id").addEventListener("search", function(event) {
                    $("#suggesstion-box-patient").empty();
                  });
                }else{
                   $("#suggesstion-box-patient").empty();
                }
              }
            });
          }
});*/



/***********changed below code on 23-sept-24*****************************/

let typingTimer;                // Timer identifier for debouncing
let doneTypingInterval = 500;   // Time in ms after the user stops typing (500ms)
let currentRequest = null;      // Store the current AJAX request to abort if necessary  

$("#suggesstion_patient_id").keyup(function () 
{ 
    $("#search_birth_date").val('');   
    var searchKey = $(this).val();          
     var birthdateKey = $("#search_birth_date").val();
    if(searchKey=='' && birthdateKey ==''){          
      $("#suggesstion-box-patient").empty();
    }
    else
    {

        if(searchKey.length < 3){
              searchPatient=0;
              $("#suggesstion-box-patient").empty();
              return false;
        }

        clearTimeout(typingTimer);  // Clear previous timer

        // Set a new timer to delay the AJAX request
        typingTimer = setTimeout(function () 
        {
            // Abort previous request if there is one
            if (currentRequest !== null) {
                currentRequest.abort();
            }

            // Make the new AJAX request
            currentRequest =  $.ajax({
                  type: "GET",
                  url: ADMINURL + "/dashboard/patients",
                  data: 'keyword=' + searchKey+'&popup=1&birthdateKey='+birthdateKey, 
                  success: function (response) {

                    var len = 0;
                    if (response['data'] != null) {
                      len = response['data'].length;
                      var data = response['data'];
                    }
                    if (len > 0) {
                      for (var i = 0; i < len; i++) {
                        var patient_name = response['data'][i].first_name;
                        var lname = response['data'][i].family_name;

                        if (lname != null) {
                          patient_name += "-" + lname;
                        }
                      }
                      $("#suggesstion-box-patient").show();
                      $("#suggesstion-box-patient").html(response['data']);
                      
                      $("#suggesstion-box-patient").on('change','#patient_id',function()
                      {
                        $("#search_birth_date").val($("#patient_id option:selected").attr('title'));
                      });

                      var dob = $("#patient_id option:first").attr('title');
                      $("#search_birth_date").val(dob);

                      $('#search_birth_date').datepicker({ 
                        dateFormat: 'dd-mm-yyyy',       
                      }); 
                      $("#patient_id").css("background", "#FFF");

                      document.getElementById("patient_id").addEventListener("search", function(event) {
                        $("#suggesstion-box-patient").empty();
                      });
                    }else{
                       $("#suggesstion-box-patient").empty();
                    }
                  },
                  error: function (xhr, textStatus) {
                    if (textStatus !== 'abort') {
                        console.log('Error: ', textStatus);
                    }
                  },
                  complete: function () {
                      currentRequest = null;  // Clear the current request once done
                  }
                });

        }, doneTypingInterval);  // Wait for the specified interval (debouncing)
  }//else
});
/***********changed above code on 23-sept-24*****************************/


$("#search_birth_date").change(function () {
   console.log($("#suggesstion_patient_id").val());
   var searchKey = '';
   if(typeof $("#suggesstion_patient_id").val()!='undefined')
   {
        searchKey = $("#suggesstion_patient_id").val();
   }
  
  var birthdateKey = $(this).val();
  if(searchKey=='' && birthdateKey ==''){
    $("#suggesstion-box-patient").empty();
  }else{

    $.ajax({
      type: "GET",
      url: ADMINURL + "/dashboard/patients",
      data: 'keyword=' + searchKey+'&popup=1&birthdateKey='+birthdateKey, 
      success: function (response) {

        var len = 0;
        if (response['data'] != null) {
          len = response['data'].length;
          var data = response['data'];
        }
        if (len > 0) {
          for (var i = 0; i < len; i++) {
            var patient_name = response['data'][i].first_name;
            var lname = response['data'][i].family_name;

            if (lname != null) {
              patient_name += "-" + lname;
            }
          }
          $("#suggesstion-box-patient").show();
          $("#suggesstion-box-patient").html(response['data']);
          $("#suggesstion_patient_id").css("background", "#FFF");

          document.getElementById("suggesstion_patient_id").addEventListener("search", function(event) {
            $("#suggesstion-box-patient").empty();
          });
        }else{
           $("#suggesstion-box-patient").empty();
        }
      }
    });
  }
});


// submitting form after validation
$('#frmAppointment').validator().on('submit', function (e) {
    if (!e.isDefaultPrevented()) {

        const $this = $(this);
        const action = $this.attr('action');
        const formData = new FormData($this[0]);
        // added by vijay 10/7/2024
        var submitButton = document.getElementById("appointmentSubmitButton");
        submitButton.disabled = true;
        // end
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
                   submitButton.disabled = false;
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
                submitButton.disabled = false;
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

function assignValueToText()
{  
  $("#time_frame1").val($("#time_frame").val());
  var time_frame_id = $('#time_frame option:selected').attr('lang');
  var time_frame_id_old = $('#roster_time_frame_id_old').val();

  if(time_frame_id_old == 'undefined')
  {
    var time_frame_id_old = '';
  }
    if(time_frame_id)
    {
      $.ajax({
        type: "POST",
        headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: ADMINURL + '/appointment/selectTimeFrame',
        data: 'time_frame_id=' + time_frame_id+'&time_frame_id_old='+time_frame_id_old, 
        success: function (response) 
        {
          $('#roster_time_frame_id').val(time_frame_id);
          $('#roster_time_frame_id_old').val(time_frame_id);
        }          
      }); 
    }
}


function getDoctorTimeFrames() {
  
    var patient_id = $("#patient_id").val();
    var doctor_id = $("#doctor_id").val();
    var doctor_status = $('#doctor_id option:selected').attr('lang');
    var appointment_type_id = $("#appointment_type_id").val();
    var appointment_date = $("#appointment_date").val();
    
    if(appointment_type_id != "")
    {
      var a_id     =  $("#a_id").val();
      GetServices(appointment_type_id,patient_id,a_id);
    }

    if(doctor_status == 0)
    {
      $(".active_status").hide();
      $(".inactive_status").show();
      $("#time_slot").removeAttr('required'); $("#time_slot").removeAttr('data-error');
      return false;
    }else
    {
      $(".active_status").show();
      $(".inactive_status").hide();
    }
    // console.log(patient_id);
    // console.log(doctor_id);
    // console.log(appointment_type_id);
    // console.log(appointment_date);

    //$("#appointment_date").blur();
    //return false;
    if (doctor_id != "" && appointment_type_id != "" && appointment_date != "") {
        var action = ADMINURL + '/appointment/getDoctorTimeFrames';
        $('.card-body').LoadingOverlay("show", {
            background: "rgba(165, 190, 100, 0.4)",
        });
        axios.post(action, {
                patient_id: patient_id,
                doctor_id: doctor_id,
                appointment_type_id: appointment_type_id,
                appointment_date: appointment_date,
                sel_time_frame: sel_time_frame
            })
            .then(response => {
                $('.card-body').LoadingOverlay("hide");
                $("#time_frame").empty();
                $("#time_frame").html(response.data.html);
                if (response.data.msg) {
                    toastr.error(response.data.msg);
                }
                /*plan_options=response.data.html;
                $("#material_"+index).html(response.data.html); 
                */
            })
            .catch(error => {
                $('.card-body').LoadingOverlay("hide");
            })
    }

    return false;
}

function GetServices(appointment_type_id,patient_id,a_id)
  {  
    
    $("#appointmentSubmitButton").prop("disabled", true);//added on 15-jan-26

    $.ajax({
      type: "POST",
      headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      url: ADMINURL + '/appointment/getServices',
      data: 'appointment_type_id=' + appointment_type_id+'&patient_id='+patient_id+'&a_id='+a_id, 
      success: function (response) 
      {
        fetchServices();
        $("#appointment_type_services").html(response.services);
        $("#appointmentSubmitButton").prop("disabled", false);//added on 15-jan-26

      }          
    });  
  }

  /*Code Added by Shyam 22-02-22 */
  function fetchServices()
  {
    
   // console.log('in fetchServices function appointment section....');

    var birth_date = $('#birth_date').val();
    if(birth_date)
    {
       birth_date = birth_date.split("-").reverse().join("-");
    }
    var appointment_type_id = $('#appointment_type_id').val();

   // console.log(birth_date);
    //console.log($('#new_patient_chkbox').prop("checked"));

    if(birth_date != '' && appointment_type_id != '' && $('#new_patient_chkbox').prop("checked") == true)
    {
      //console.log('innnnnnnnnnnnnnnnnnnnn....');


      setTimeout(function() {
        $.ajax({
          type: "POST",
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          url: ADMINURL + '/appointment/getExtraServices',
          data: 'appointment_type_id=' + appointment_type_id+'&birth_date='+birth_date,
          success: function (response)
          {
            $(".appointment_type_services").append(response.services);
          }
        });
      }, 1000);
    }
  }
  /*Code Added by Shyam 22-02-22 */
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