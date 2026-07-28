$(document).ready(function () {


    // adding focus event to first field
    $('input[name="name"]').focus();

    // $('#birth_date').datepicker({ 
    //     format: 'dd-mm-yyyy',       
    // });

    // $('#appointment_date').datepicker({
    //     // changeMonth: true,
    //     // changeYear: true,
    //     format: 'yyyy-mm-dd',
    //     orientation: "bottom",
    //     autoclose: true,
    //     todayHighlight: true,
    //     startDate: new Date()
    // });

    // $('#search_birth_date').datepicker({ 
    //   format: 'dd-mm-yyyy',       
    // });
    // $("#appointment_date").datepicker("setDate", new Date());
})
$("#suggesstion_patient_id").keyup(function () {    
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
                  console.log('ddddddddddd');
                  $("#suggesstion-box-patient").on('change','#patient_id',function()
                  {
                    $("#search_birth_date").val($("#patient_id option:selected").attr('title'));
                  })
                  $('#search_birth_date').datepicker({ 
                    dateFormat: 'dd-mm-yy',       
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
        });
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
         // CLEAR OLD ERRORS HERE (//Added above code for hide error if value is correct on submit button)
        $('[class^="err_"]').each(function () {
            // $(this).text('').hide();
            $(this).closest('.form-group').removeClass('has-error has-danger');
        });
        //Added above code for hide error if value is correct on submit button 
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
})

function assignValueToText()
{  
  $("#time_frame1").val($("#time_frame").val());
}


function getDoctorTimeFrames() {
  
    var patient_id = $("#patient_id").val();
    var doctor_id = $("#doctor_id").val();
    var doctor_status = $('#doctor_id option:selected').attr('lang');
    var appointment_type_id = $("#appointment_type_id").val();
    var appointment_date = $("#appointment_date").val();


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