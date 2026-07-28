$(document).ready(function () {
    // adding focus event to first field
    // $('input[name="name"]').focus();
//   $('#start_date').mask("99/99/9999"); 
//   $('#end_date').mask("99/99/9999"); 

    $("#toggle_icon").on("click",function(){
       $(".toggle_text").slideToggle();
    });

    $('[data-toggle="collapse"]').click(function() {
        $(this).toggleClass( "active" );
        if ($(this).hasClass("active")) {
          $(this).html("Details ausblenden <i class='fa fa-arrow-up'></i>");
        } else {
          $(this).html("Detailsuche <i class='fa fa-arrow-down'></i> ");
        }
    });
  
    //commneted below line on 30-aug-22
    //$('.chk').datepicker({ setDate:new Date(),dateFormat: 'dd/mm/yy',minDate:0}), $.datepicker.regional['de'];
   
    var today =  new Date();
    console.log(today);
    var end_date = new Date(today.getFullYear(),today.getMonth(),today.getDate()+ 7);

    //$("#start_date").datepicker("setDate", today);
    //$("#end_date").datepicker("setDate", end_date);

    // $("#start_date").datepicker().on("change", function(dateText) {
    //     console.log(dateText);
    //     alert("ssssSelected date: " + dateText + ", Current Selected Value= " + this.value);
    //   });

     //commented below line on 30-aaug-22
    /*$('#start_date').on("change", function(date) {
       
        selectedDate = $(this).val();      
        if (selectedDate != '')
        {
            console.log(selectedDate);
            selectedDate = selectedDate.split("/");
            selectedDate = selectedDate[1]+"/"+selectedDate[0]+"/"+selectedDate[2];
            var minDate2 = new Date(selectedDate);  
          
            $("#end_date").datepicker("option", "minDate", minDate2);  
            //$("#end_date").datepicker("setStartDate", minDate2); 
            var end_date = new Date(minDate2.getFullYear(),minDate2.getMonth(),minDate2.getDate() + 7);
           
            $("#end_date").datepicker("option", "minDate", end_date);  
            
        }
    });*/

    // $('#end_date').datepicker({
    //     // changeMonth: true,
    //     // changeYear: true,
    //     format: 'dd-mm-yyyy',
    //     orientation: "bottom",
    //     autoclose: true,
    //     todayHighlight: true,
    //     startDate: new Date()
    // });

    $('.timepicker').timepicker({
        // minuteStep: 1, 
        defaultTime:'',
        timeFormat: 'H:i',
        showInputs: false,
        showMeridian: false //24hr mode 
    });

    
})


 
function assignValueToText(id)
{  
    
    var fram_val = "time_slot_"+id;
   
  // $("#time_frame").val($("#time_frame_"+id).val());
    var time_frame_id = $('#'+fram_val+' option:selected').attr('lang');
    console.log(time_frame_id);
    if(time_frame_id)
    {
      $.ajax({
        type: "POST",
        headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: WEBURL + '/online-appointments/selectTimeFrame',
        data: 'time_frame_id=' + time_frame_id, 
        success: function (response) 
        {
          $('#time_fram_hd_id').val(time_frame_id);
        }          
      }); 
    }
}

function getDoctorTimeFrames_renamedon_9_aug_24() {
    // console.log("getDoctorTimeFrames");

     //added on 10-nov-23 for search click scroll to bottom
      $("html, body").animate({
          scrollTop: $("#doctor_duty_rosters").offset().top+ 200
      }, 1000); // 1000 milliseconds (1 second) for the animation 
    
    //var patient_id = $("#patient_id").val();
    var doctor_id = $("#doctor_id").val();
    var fastest_appoitment = $("#fastest_appoitment").val();
    var appointment_type_id = $("#appointment_type_id").val();
    var week_day_id = $("#week_day_id").val();
    var start_date = $("#start_date").val();
    var end_date = $("#end_date").val();
    var from_time = $("#from_time").val();
    var to_time = $("#to_time").val();
    var hidden_patient_id = $("#hidden_patient_id").val();
    // console.log(doctor_id);
    // console.log(appointment_type_id);
    // console.log(appointment_date);

    //$("#appointment_date").blur();
    //return false;
    console.log(fastest_appoitment,doctor_id);
    if(fastest_appoitment == 0 && doctor_id == "")
    {console.log('if');
        toastr.error('Bitte füllen Sie die erforderlichen Felder aus');
    }else
    {console.log('else');
       if (appointment_type_id != "" && week_day_id != "" && start_date != "" && end_date != "" && from_time != "" && to_time != "" && doctor_id !="") {
        if(doctor_id)
            var action = WEBURL + '/get-doctor-slots';
        else
            var action = WEBURL + '/get-all-doctor-slots';
        $('.card-body').LoadingOverlay("show", {
            background: "rgba(165, 190, 100, 0.4)",
        });
        axios.post(action, {
                doctor_id: doctor_id,
                appointment_type_id: appointment_type_id,
                week_day_id: week_day_id,
                start_date: start_date,
                end_date: end_date,
                from_time: from_time,
                to_time: to_time,
            })
            .then(response => {
                $('.card-body').LoadingOverlay("hide");
                $("#doctor_duty_rosters").empty();
                $("#doctor_duty_rosters").html(response.data.html);
                /*if (response.data.msg) {
                    toastr.error(response.data.msg);
                }*/
               
            })
            .catch(error => {
                $('.card-body').LoadingOverlay("hide");
            })
        }else{
            console.log('elseif');
            toastr.error('Bitte füllen Sie die erforderlichen Felder aus');
        }
    }

    return false;
}//

//added code on 9-aug-24
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

//changed below code on 9-aug-24
function getDoctorTimeFrames() {
    // console.log("getDoctorTimeFrames");

     //added on 10-nov-23 for search click scroll to bottom
      $("html, body").animate({
          scrollTop: $("#doctor_duty_rosters").offset().top+ 200
      }, 1000); // 1000 milliseconds (1 second) for the animation 
     
    //var patient_id = $("#patient_id").val();
    var doctor_id = $("#doctor_id").val();
    var fastest_appoitment = $("#fastest_appoitment").val();
    var appointment_type_id = $("#appointment_type_id").val();
    var week_day_id = $("#week_day_id").val();
    var start_date = $("#start_date").val();
    var end_date = $("#end_date").val();
    var from_time = $("#from_time").val();
    var to_time = $("#to_time").val();
    var hidden_patient_id = $("#hidden_patient_id").val();
    // console.log(doctor_id);
    // console.log(appointment_type_id);
    // console.log(appointment_date);

    //$("#appointment_date").blur();
    //return false;
    console.log(fastest_appoitment,doctor_id);
    if(fastest_appoitment == 0 && doctor_id == "")
    {console.log('if');
        toastr.error('Bitte füllen Sie die erforderlichen Felder aus');
    }else
    {console.log('else');

     /****************************************************/
        var is_already_registered = $("#is_already_registered").val();
        if(doctor_id == "")
        {    console.log('if doctor_id empty err');
            $("#appointment_type_id").val('');
            toastr.error('Bitte wählen Sie Arzt');
        }
        else
        {
            $('.card-body').LoadingOverlay("show", {
                background: "rgba(165, 190, 100, 0.4)",
            });

            $.ajax({
                url: WEBURL +"/online-appointment/getWebAppointmentStartDate",
                type: "POST",
                data: {
                    "doctor_id": doctor_id,
                    // _token: '{{csrf_token()}}',
                    "patient_id": hidden_patient_id,
                    "is_already_registered": is_already_registered,
                    "appoinmant_type_id": appointment_type_id,
                },
                success: function(response) {
                    console.log(response);
                    if (response.count == 1) {
                        $('.card-body').LoadingOverlay("hide");

                        /**************added below code here*on 9-aug-24******************************/
                         if (appointment_type_id != "" && week_day_id != "" && start_date != "" && end_date != "" && from_time != "" && to_time != "" && doctor_id !="") 
                           {
                            if(doctor_id)
                                var action = WEBURL + '/get-doctor-slots';
                            else
                                var action = WEBURL + '/get-all-doctor-slots';
                            $('.card-body').LoadingOverlay("show", {
                                background: "rgba(165, 190, 100, 0.4)",
                            });
                            axios.post(action, {
                                    doctor_id: doctor_id,
                                    appointment_type_id: appointment_type_id,
                                    week_day_id: week_day_id,
                                    start_date: start_date,
                                    end_date: end_date,
                                    from_time: from_time,
                                    to_time: to_time,
                                    patient_id: hidden_patient_id,//added on 8-aug-25
                                    is_already_registered: is_already_registered, //added on 8-aug-25
                                })
                                .then(response => {
                                    $('.card-body').LoadingOverlay("hide");
                                    $("#doctor_duty_rosters").empty();
                                    $("#doctor_duty_rosters").html(response.data.html);
                                    /*if (response.data.msg) {
                                        toastr.error(response.data.msg);
                                    }*/

                                    //start added on 8-aug-25
                                    /*if (response.data.errmsg) {
                                      $("#doctor_duty_rosters").empty();
                                        toastr.error(response.data.errmsg);
                                    }*/
                                    //end
                                   
                                })
                                .catch(error => {
                                    $('.card-body').LoadingOverlay("hide");
                                })
                            }else{
                                console.log('elseif');
                                toastr.error('Bitte füllen Sie die erforderlichen Felder aus');
                            }
                           /**************added above code here*on 9-aug-24******************************/


                    } else {
                        $('.card-body').LoadingOverlay("hide");

                        $("#doctor_duty_rosters").empty();
                        $("#doctor_duty_rosters").html(" ");
                        //Roshani commented the line for 144(b) on 12-07-2024
                        // $(".chk").datepicker("destroy");
                        // $(".chk").val("");

                        // $(".chk_enddate").datepicker("destroy");
                        // $(".chk_enddate").val("");
                        //Roshani commented the line for 144(b) on 12-07-2024
                        var msg = Doctor_No_Available;
                        // toastr.error(msg);
                        $("#doctor_duty_rosters").html(`
                            <table id="customers">
                                <thead>
                                    <tr>
                                        <td colspan="3" style="text-align: center;">
                                           ${msg}
                                        </td>
                                    </tr>
                                </thead>
                            </table>
                        `);
                        // $('.ui-datepicker-calendar').css("display", "none");//Roshani commented the line for 144(b) on 12-07-2024
                    }
                }
            });
        }


    /*****************************************************/


     /*************commented below code on 9-aug-24*************************************/
        
       // if (appointment_type_id != "" && week_day_id != "" && start_date != "" && end_date != "" && from_time != "" && to_time != "" && doctor_id !="") 
       // {
       //  if(doctor_id)
       //      var action = WEBURL + '/get-doctor-slots';
       //  else
       //      var action = WEBURL + '/get-all-doctor-slots';
       //  $('.card-body').LoadingOverlay("show", {
       //      background: "rgba(165, 190, 100, 0.4)",
       //  });
       //  axios.post(action, {
       //          doctor_id: doctor_id,
       //          appointment_type_id: appointment_type_id,
       //          week_day_id: week_day_id,
       //          start_date: start_date,
       //          end_date: end_date,
       //          from_time: from_time,
       //          to_time: to_time,
       //      })
       //      .then(response => {
       //          $('.card-body').LoadingOverlay("hide");
       //          $("#doctor_duty_rosters").empty();
       //          $("#doctor_duty_rosters").html(response.data.html);
       //          /*if (response.data.msg) {
       //              toastr.error(response.data.msg);
       //          }*/
               
       //      })
       //      .catch(error => {
       //          $('.card-body').LoadingOverlay("hide");
       //      })
       //  }else{
       //      console.log('elseif');
       //      toastr.error('Bitte füllen Sie die erforderlichen Felder aus');
       //  }
       /****************commented below code on 9-aug-24***********************************/


    }

    return false;
}


function arrangeTimeSlot(element,index) {
    // console.log("arrangeTimeSlot",index);
    // console.log("roster_date",$(element).attr('roster_date'));
    // console.log("roster_time_slot",$("#time_slot_"+index).val());
    

    //var patient_id = $("#patient_id").val();
    var doctor_id = $("#doctor_id").val();
    var appointment_type_id = $("#appointment_type_id").val();
    var roster_date = $(element).attr('roster_date');
    var roster_time_slot = $("#time_slot_"+index).val();
    var roster_time_slot_hd_id = $('#time_slot_'+index+' option:selected').attr('lang');

    var is_already_registered = $("#is_already_registered").val();
    console.log('is_already_registered===>'+is_already_registered);

    var submitButtons = document.querySelectorAll("#roster_date");
    submitButtons.forEach(function (button) {
        button.disabled = true;
    });

    //var roster_time_slot_hd_id = $("#time_fram_hd_id").val();
    // console.log(doctor_id);
    // console.log(appointment_type_id);
    // console.log(roster_date);
    // console.log(roster_time_slot);
    // return false;

    //$("#appointment_date").blur();
    //return false;
    if (doctor_id != "" && appointment_type_id != "" && roster_date != "" && roster_time_slot != "") {
        var action = WEBURL + '/online-appointments/arrange';
        $('.card-body').LoadingOverlay("show", {
            background: "rgba(165, 190, 100, 0.4)",
        });
        axios.post(action, {
                doctor_id: doctor_id,
                appointment_type_id: appointment_type_id,
                roster_date: roster_date,
                roster_time_slot: roster_time_slot,
                roster_time_slot_hd_id :roster_time_slot_hd_id,
                is_already_registered:is_already_registered
            })
            .then(response => {
                const resp = response.data;

                if (resp.status == 'success') {

                    //start added on 30-dec-25
                    if (resp.msg && resp.msg.trim() !== '') {
                      toastr.success(resp.msg);
                    }
                    //end added on 30-dec-25

                    setTimeout(function () {
                        window.location.href = resp.url;
                    }, 1000)
                }

                if (resp.status == 'error') {
                    submitButtons.forEach(function (button) {
                        button.disabled = false;
                    });
                    $('.card-body').LoadingOverlay("hide");
                    toastr.error(resp.msg);
                }

                $('.card-body').LoadingOverlay("hide");
               
            })
            .catch(error => {
                submitButtons.forEach(function (button) {
                    button.disabled = false;
                });
                $('.card-body').LoadingOverlay("hide");
            })
    }

    return false;
}


function arrangeAllDoctorTimeSlot(element,index) {
    // console.log("arrangeTimeSlot",index);
    // console.log("roster_date",$(element).attr('roster_date'));
    // console.log("roster_time_slot",$("#time_slot_"+index).val());
    

    //var patient_id = $("#patient_id").val();
    var doctor_id = 0;
    var appointment_type_id = $("#appointment_type_id").val();
     var hidden_week_day = $("#hidden_week_day").val();
    var roster_date = $(element).attr('roster_date');
    var roster_time_slot = $("#time_slot_"+index).val();

    // console.log(doctor_id);
    // console.log(appointment_type_id);
    // console.log(roster_date);
    // console.log(roster_time_slot);
    // return false;

    //$("#appointment_date").blur();
    //return false;
    if (appointment_type_id != "" && roster_date != "" && roster_time_slot != "") {
        var action = WEBURL + '/online-appointments/arrange';
        $('.card-body').LoadingOverlay("show", {
            background: "rgba(165, 190, 100, 0.4)",
        });
        axios.post(action, {
                doctor_id: doctor_id,
                appointment_type_id: appointment_type_id,
                roster_date: roster_date,
                roster_time_slot: roster_time_slot,
                hidden_week_day: hidden_week_day,
            })
            .then(response => {
                const resp = response.data;

                if (resp.status == 'success') {
                    setTimeout(function () {
                        window.location.href = resp.url;
                    }, 1000)
                }

                if (resp.status == 'error') {
                    $('.card-body').LoadingOverlay("hide");
                    toastr.error(resp.msg);
                }

                $('.card-body').LoadingOverlay("hide");
               
            })
            .catch(error => {
                $('.card-body').LoadingOverlay("hide");
            })
    }

    return false;
}

/******************** Roshani added this script ***************************/
function confirmedAppointment(confirmation, app_id) {  
    if (confirmation) {
        //  swal({
        //      title: "Möchten Sie diesen Termin wirklich bestätigen?",
        //     // text: "This action cannot be undone!",
        //     type: "warning",
        //     showCancelButton: true,
        //     cancelButtonText:'Stornieren',
        //     confirmButtonText: 'Bestätigen',
        //     confirmButtonClass: "btn-success",
        //     closeOnConfirm: false,
        //     showLoaderOnConfirm: true

        // },
        //  function () {
                $.ajax({
                    url: WEBURL + '/online-appointment/app-confirm-or-not',
                    type: "POST", // Specify the HTTP method as POST
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        'confirmation': confirmation,
                        'app_id': app_id
                    },
                    success: function (response) {
                        const resp = response;
                       if (resp.status == 'success') {
                            $('.card-body').LoadingOverlay("hide");
                            toastr.success(resp.msg);
                            setTimeout(function () {
                                window.location.href = resp.url;
                            }, 1000)
                        }
                    },
                    error: function(xhr, status, error) {
                        // Handle error response here if needed
                    }
                });
        // }); 
    }
}
