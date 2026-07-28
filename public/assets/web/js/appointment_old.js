$(document).ready(function () {
    // adding focus event to first field
    // $('input[name="name"]').focus();
  $('#start_date').mask("99/99/9999"); 
  $('#end_date').mask("99/99/9999"); 

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
  

    $('.chk').datepicker({ setDate:new Date(),dateFormat: 'dd/mm/yy',minDate:0}), $.datepicker.regional['de'];
   
    var today =  new Date();
    console.log(today);
    var end_date = new Date(today.getFullYear(),today.getMonth(),today.getDate()+ 7);

    //$("#start_date").datepicker("setDate", today);
    //$("#end_date").datepicker("setDate", end_date);

    // $("#start_date").datepicker().on("change", function(dateText) {
    //     console.log(dateText);
    //     alert("ssssSelected date: " + dateText + ", Current Selected Value= " + this.value);
    //   });

    $('#start_date').on("change", function(date) {
       
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
    });

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

function getDoctorTimeFrames() {
    // console.log("getDoctorTimeFrames");

    //var patient_id = $("#patient_id").val();
    var doctor_id = $("#doctor_id").val();
    var fastest_appoitment = $("#fastest_appoitment").val();
    var appointment_type_id = $("#appointment_type_id").val();
    var week_day_id = $("#week_day_id").val();
    var start_date = $("#start_date").val();
    var end_date = $("#end_date").val();
    var from_time = $("#from_time").val();
    var to_time = $("#to_time").val();

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
       if (appointment_type_id != "" && week_day_id != "" && start_date != "" && end_date != "" && from_time != "" && to_time != "") {
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
