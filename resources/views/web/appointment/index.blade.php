@extends('web.layout.master')
@section('title')
{{ $moduleAction ?? '' }}
@endsection
@section('style') 
<link rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/timepicker/bootstrap-timepicker.min.css') }}">
@endsection
@section('content')

<meta name="csrf-token" content="{{ csrf_token() }}">



<div class="container">
  <div class="row">
    <div class="main_content book_data">
        <!-- jquery validation -->
            <div class="card card-primary">   
                <div class="card-header">
                    <h3 class="card-title">Online Terminvereinbarung</h3>
                </div>
            

                <form id="frmAppointment" role="form" data-toggle="validator" action="">
                    <!-- Hide the some valus which we want to next page -->
                    @if(session()->has('mobile_no') && session()->has('birth_date'))
                        <input type="hidden" name="hid_mobile_no" id="hid_mobile_no" value="{{ session('mobile_no') }}">
                        <input type="hidden" name="hid_birth_date" id="hid_birth_date" value="{{ session('birth_date') }}">
                        <input type="hidden" name="hid_format" id="hid_format" value="{{ session('format') }}">

                    @endif

                    <!-- <input type="text" name="appointment_type_id_hidden" id="appointment_type_id_hidden" value=""> -->

                    <input type="hidden" name="is_already_registered" id="is_already_registered" value="@if(isset($is_already_registered)) {{ $is_already_registered }} @endif">

                     <input type="hidden" name="hidden_patient_id" id="hidden_patient_id" value="@if(isset($appointment['patient_id'])) {{ $appointment['patient_id'] }} @endif">

                     <input type="hidden" name="quarter_setting_val" id="quarter_setting_val" value="@if(isset($quarter_setting_val)) {{ $quarter_setting_val }} @endif">

                    <!--------29-may-24--added for showing type in case inactive------->
                    
                    <input type="hidden" name="hidenApptypeId" id="hidenApptypeId" value="@if(isset($hidenApptypeId)) {{ $hidenApptypeId }} @endif">

                     <!--------29-may-24--added for showing type in case inactive------->
                     

                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>@lang('front.TITLE_APPOINTMENT_DOCTOR')
                                        <span class="required">*</span></label> 
                                       <!--  @if($doctor_id) <span class="required">*</span>@endif</label>  -->
                                        @if($doctor_id)
                                        <input type="hidden" name="fastest_appoitment" id="fastest_appoitment" value="0" />
                                        <select 
                                        name="doctor_id" 
                                        id="doctor_id"  
                                        required
                                        data-error="@lang('front.ERR_APPOINTMENT_DOCTOR_REQUIRED')"
                                        class="form-control select2"

                                        @if(!empty($doctor_id)) disabled @endif
                                        >
                                        @else
                                         <input type="hidden" name="fastest_appoitment" id="fastest_appoitment" value="1" />
                                        <select 
                                        name="doctor_id" 
                                        id="doctor_id"  
                                        class="form-control select2"
                                        >
                                        @endif

                                   <!--  <select 
                                        name="doctor_id" 
                                        id="doctor_id"  
                                        required
                                        data-error="@lang('front.ERR_APPOINTMENT_DOCTOR_REQUIRED')"
                                        class="form-control select2"
                                        > -->
                                        <option value="">@lang('front.TITLE_SELECT_DOCTOR')</option>
                                        @foreach($doctors as $doctor)
                                        <option value="{{ $doctor->id }}" @if(!empty($doctor_id) && $doctor_id==$doctor->id) selected @endif>{{ $doctor->first_name .' '. $doctor->last_name}}</option>
                                        @endforeach
                                    </select> 
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_user_id"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label>@lang('front.TITLE_APPOINTMENT_TYPE') <span class="required">*</span></label>  
                                    <select 
                                        name="appointment_type_id" 
                                        id="appointment_type_id"  
                                        required
                                        data-error="@lang('front.ERR_APPOINTMENT_TYPE_REQUIRED')"
                                        class="form-control select2" 
                                        @if(!empty($hidenApptypeId)) disabled @endif 
                                        ><option value="">@lang('front.TITLE_ROSTER_SELECT_APPOINTMENT_TYPE')</option>
                                        @foreach($appointment_type as $appointment_types)
                                        <option value="{{ $appointment_types->id }}" @if(!empty($hidenApptypeId) && $hidenApptypeId==$appointment_types->id) selected @endif>{{ $appointment_types->name}}</option>
                                        @endforeach
                                    </select> 
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_appointment_type_id"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>

                            <!-- ############## Roshani Added this code (22/02/2024) C) User settings ################ -->
                            <input type="hidden" id="hidden_field_web" value="yes">
                            <input type="hidden" id="hidden_web" value="from_web">

                            <!-- ############## Roshani Added this code (22/02/2024) C) User settings ################ -->

                           <!--  <div class="col-sm-12">
                                <div class="green_btn right_arrow">
                                    <a href="javascript:void(0)" class="btn" id="toggle_icon" type="button" data-toggle="collapse">Detailsuche <i class="fa fa-arrow-down"></a></i>
                                </div>
                            </div> -->
                            <div class="col-sm-4 toggle_text"  style="display: block"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('front.TITLE_ROSTER_WEEKDAY') <span class="required">*</span></label>
                                    <select 
                                            class="form-control weekdays" 
                                            multiple="" 
                                            name="week_day_id"
                                            id="week_day_id"
                                            required
                                            data-placeholder="@lang('front.TITLE_SELECT_TEXT')"
                                            data-error="@lang('front.ERR_WEEKDAY_REQUIRED')" 
                                        >
                                            @if(!empty($weekdays) && sizeof($weekdays) > 0)
                                            @foreach($weekdays as $weekday)
                                             plan_options += `<option value="{{ $weekday->id }}" @if($weekday->id<=5) selected @endif>{{ $weekday->day }}</option>`;
                                            @endforeach
                                            @endif
                                        </select>
                                        <span class="help-block invalid-feedback with-errors">
                                            <ul class="list-unstyled">
                                                <li class="err_week_day_id"></li>
                                            </ul>
                                        </span>
                                </div>
                            </div>
                            <div class="col-sm-4 toggle_text" style="display: block"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('front.TITLE_ROSTER_STARTDATE') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="start_date" 
                                        class="form-control chk"
                                        id="start_date"  
                                        autocomplete="off"
                                        required
                                         value="{{ $appoinmentStartDate }}" 
                                        data-error="@lang('front.ERR_APPOINTMENT_DATE_REQUIRED')." 
                                    >

                                  

                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_start_date"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-4 toggle_text" style="display: block">
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('front.TITLE_ROSTER_ENDDATE') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="end_date" 
                                        class="form-control chk-renamed chk_enddate"
                                        id="end_date"  
                                        autocomplete="off"
                                        required
                                        value="{{ $appointmentEndDate }}" 
                                        data-error="@lang('front.ERR_APPOINTMENT_DATE_REQUIRED')." 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_end_date"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-4 toggle_text" style="display: block">
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('front.TITLE_ROSTER_TIME_FROM') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="from_time" 
                                        class="form-control timepicker"
                                        id="from_time"  
                                        autocomplete="off"
                                        required
                                        value="06:00:00" 
                                        data-error="@lang('front.ERR_APPOINTMENT_DATE_REQUIRED')." 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_from_time"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-4 toggle_text" style="display: block"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('front.TITLE_ROSTER_TIME_TO') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="to_time" 
                                        class="form-control timepicker"
                                        id="to_time"  
                                        autocomplete="off"
                                        value="21:00:00" 
                                        required
                                        data-error="@lang('front.ERR_APPOINTMENT_DATE_REQUIRED')." 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_to_time"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                           
                        </div> 
                    </div><!-- /.card-body -->
                    <div class="card-footer">
                        <button type="button" class="btn btn-success" onclick="getDoctorTimeFrames()">@lang('front.TITLE_SEARCH_TEXT')</button>
                    </div>

                    <div class="table-responsive table_bottom" id="doctor_duty_rosters">
                    </div>
                </form>
            </div>
        </div>
    </div>
   
</div>    
@endsection
@section('scripts') 
<script type="text/javascript" src="{{ asset('assets/plugins/timepicker/bootstrap-timepicker.min.js') }}"></script>
<!-- <script type="text/javascript" src="{{ asset('assets/plugins/datepicker/bootstrap-datepicker.de.js') }}"></script> -->
<!-- <script type="text/javascript"
        src="https://www.ubalt.edu/lib/jquery-ui-1.8.5.custom/development-bundle/ui/i18n/jquery.ui.datepicker-de.js">
</script> -->
<script src="https://cdn.jsdelivr.net/npm/jquery-ui@1.12.1/ui/i18n/datepicker-de.js"></script>

  <script>
//     function isValidDate(value) {
//     let parts = value.split("/");
//     if (parts.length !== 3) return false;
//     let day = parseInt(parts[0], 10);
//     let month = parseInt(parts[1], 10) - 1; // JS months 0-11
//     let year = parseInt(parts[2], 10);

//     let date = new Date(year, month, day);
//     return (
//         date.getFullYear() === year &&
//         date.getMonth() === month &&
//         date.getDate() === day
//     );
// }

// $('#start_date, #end_date').on("blur", function() {
//     if (!isValidDate(this.value)) {
//         // alert("Bitte gültiges Datum im Format dd/mm/yyyy eingeben!");
//         $("#doctor_duty_rosters").empty();
//             $("#doctor_duty_rosters").html(`
//                 <table id="customers">
//                     <thead>
//                         <tr>
//                             <td colspan="3" style="text-align: center;">
//                                 Bitte gültiges Datum im Format dd/mm/yyyy eingeben!.
//                             </td>
//                         </tr>
//                     </thead>
//                 </table>
//             `);
//         this.value = "";
//     }
// });
  $(function () {
      // Apply German defaults
      $.datepicker.setDefaults($.datepicker.regional["de"]);

      // Start date
      $(".chk").datepicker({
          minDate: "{{ $appoinmentStartDate }}",
          dateFormat: "dd/mm/yy"
      }).datepicker("setDate", "{{ $appoinmentStartDate }}");

      // End date
      $(".chk_enddate").datepicker({
          minDate: "{{ $appoinmentStartDate }}",
          dateFormat: "dd/mm/yy"
      }).datepicker("setDate", "{{ $appointmentEndDate }}");
  });
  </script>
<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/web/js/appointment.js') }}"></script>
<!-- ############## Roshani Added this code (22/02/2024) C) User settings ################ -->

<!----added on 18-june-24------->
<script type="text/javascript">
    var Doctor_No_Available = "{{ __('front.ERR_DOCTOR_NOT_AVALIABLE') }}"; 
</script>
<!----added on 18-june-24------->

<script type="text/javascript" src="{{ url('assets/admin/js/dashboard/commonJsForApp.js?ver=0.02') }}"></script>
<!-- ############## Roshani Added this code (22/02/2024) C) User settings ################ -->
<script type="text/javascript">

    // $('.chk').datepicker({ minDate:{{ $appoinmentStartDate }}, setDate:{{ $appoinmentStartDate }},dateFormat: 'dd/mm/yy'}), $.datepicker.regional['de'];
    // $('.chk_enddate').datepicker({ minDate:{{ $appoinmentStartDate }},setDate:{{ $appointmentEndDate }},dateFormat: 'dd/mm/yy'}), $.datepicker.regional['de'];

    //Appointment select date change by vijay 7/3/24

    function handleDoctorAppointmentChange() {
        let doctor_id = $('#doctor_id').val();
        var patient_id = $("#hidden_patient_id").val();
        var appoinmant_type_id = $("#appointment_type_id").val();
        console.log('appoinmant_type_id', appoinmant_type_id);
        console.log(doctor_id);
        var is_already_registered = $("#is_already_registered").val();

        //only if doctor id condition added on 26-apr-24 when we select app type first then it needs to show below error first
        if(doctor_id == "")
        {    
            console.log('if doctor_id empty err');
            $("#appointment_type_id").val('');
            toastr.error('Bitte wählen Sie Arzt');
            // $("#doctor_duty_rosters").empty();
            // $("#doctor_duty_rosters").html(`
            //     <table id="customers">
            //         <thead>
            //             <tr>
            //                 <td colspan="3" style="text-align: center;">
            //                     Bitte wählen Sie Arzt.
            //                 </td>
            //             </tr>
            //         </thead>
            //     </table>
            // `);
        }
        else
        {

            $('.card-body').LoadingOverlay("show", {
                background: "rgba(165, 190, 100, 0.4)",
            });

            $.ajax({
                url: "{{ route('getWebAppointmentStartDate') }}",
                type: "POST",
                data: {
                    "doctor_id": doctor_id,
                    _token: '{{csrf_token()}}',
                    "patient_id": patient_id,
                    "is_already_registered": is_already_registered,
                    "appoinmant_type_id": appoinmant_type_id,
                },
                success: function(response) {
                    console.log(response);
                    if (response.count == 1) {
                        $('.card-body').LoadingOverlay("hide");

                        $("#doctor_duty_rosters").empty();
                        $("#doctor_duty_rosters").html(" ");

                        $(".chk").datepicker("destroy");
                        $(".chk").val("");
                        $('.chk').datepicker({
                            dateFormat: 'dd/mm/yy',
                            minDate: response.avaliable_date,
                            setDate: response.avaliable_date
                        });
                        $(".chk").val(response.avaliable_date);

                        $(".chk_enddate").datepicker("destroy");
                        $(".chk_enddate").val("");
                        $('.chk_enddate').datepicker({
                            dateFormat: 'dd/mm/yy',
                            minDate: response.end_date,
                            setDate: response.end_date
                        });
                        $(".chk_enddate").val(response.end_date);


                    } else {
                        $('.card-body').LoadingOverlay("hide");

                        $("#doctor_duty_rosters").empty();
                        // $("#doctor_duty_rosters").html("{{ __('front.ERR_DOCTOR_NOT_AVALIABLE') }}");
                        var msg = "{{ __('front.ERR_DOCTOR_NOT_AVALIABLE') }}";
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

                        //Roshani commented the line for 144(b) on 12-07-2024
                        // $(".chk").datepicker("destroy");
                        // $(".chk").val("");

                        // $(".chk_enddate").datepicker("destroy");
                        // $(".chk_enddate").val("");
                        //Roshani commented the line for 144(b) on 12-07-2024
                        // var msg = "{{ __('front.ERR_DOCTOR_NOT_AVALIABLE') }}";
                        // toastr.error(msg);
                        // $('.ui-datepicker-calendar').css("display", "none");//Roshani commented the line for 144(b) on 12-07-2024
                    }
                }
            });
        }//else
    }

    $(document).ready(function() {
        // Event handler for doctor_id
        $('#doctor_id').on('change', handleDoctorAppointmentChange);


        
        // Event handler for appointment_type_id
        $('#appointment_type_id').on('change', handleDoctorAppointmentChange);

        //added below code on 25-apr-24 for when doctor selected then change appointment type
        // let doctor_id = $('#doctor_id').val();
        // if(doctor_id){
        //   $('#appointment_type_id').on('change', handleDoctorAppointmentChange);
        // }

    
    });
    // end change
   
    //Appointment select date
    // $('#doctor_id').on('change', function() {
    //     let doctor_id = this.value;
    //     var patient_id = $("#hidden_patient_id").val();
    //     console.log(doctor_id);
    //     var is_already_registered = $("#is_already_registered").val();

    //     $('.card-body').LoadingOverlay("show", {
    //         background: "rgba(165, 190, 100, 0.4)",
    //      });
       
    //      $.ajax({
    //         url:"{{ route('getWebAppointmentStartDate') }}",
    //         type:"POST",
    //         data:{"doctor_id":doctor_id, _token: '{{csrf_token()}}',"patient_id":patient_id,"is_already_registered":is_already_registered},
    //         success:function(response){
    //             console.log(response); 
    //             if(response.count==1)
    //             {
    //                  $('.card-body').LoadingOverlay("hide");

    //                  $("#doctor_duty_rosters").empty();
    //                  $("#doctor_duty_rosters").html(" ");

    //                 //commented below line in web/appointment.js file
    //                //$('.chk').datepicker({ setDate:new Date(),dateFormat: 'dd/mm/yy',minDate:0}), $.datepicker.regional['de'];     

    //                  //For showing start date
    //                  $(".chk" ).datepicker("destroy");
    //                  $(".chk").val("");
    //                  $('.chk').datepicker({
    //                             dateFormat:'dd/mm/yy',
    //                             minDate:response.avaliable_date,
    //                             setDate:response.avaliable_date
    //                         });
    //                  $(".chk").val(response.avaliable_date);

    //                  //For showing end date
    //                  $(".chk_enddate" ).datepicker("destroy");
    //                  $(".chk_enddate").val("");
    //                  $('.chk_enddate').datepicker({
    //                             dateFormat:'dd/mm/yy',
    //                             minDate:response.end_date,
    //                             setDate:response.end_date
    //                         });
    //                  $(".chk_enddate").val(response.end_date);
                     
                  
    //             }
    //             else
    //             {
    //                 $('.card-body').LoadingOverlay("hide");

    //                 $("#doctor_duty_rosters").empty();
    //                 $("#doctor_duty_rosters").html(" ");

    //                 $(".chk").datepicker("destroy");
    //                 $(".chk").val("");

    //                 $(".chk_enddate").datepicker("destroy");
    //                 $(".chk_enddate").val("");

    //                 var msg = "{{ __('admin.ERR_DOCTOR_NOT_AVALIABLE') }}";
    //                 toastr.error(msg);
    //                 $('.ui-datepicker-calendar').css("display","none");
    //             }
    //         }
    //     });
           
    // });
      //Appointment select date

// document.getElementById("start_date").addEventListener("input", function() {
//   console.log("Value changed:", this.value);
// });

let typingTimer;

const doneTypingInterval = 600; // milliseconds after user stops typing
 
$("#start_date").on("change", function (date) {
    console.log("date--------"+date);

    clearTimeout(typingTimer);

    let $this = $(this);
 
    typingTimer = setTimeout(function () {

        let value = $this.val();

        console.log("User finished typing date:", value);

        myCustomFunction(value);

    }, doneTypingInterval);

});
 
function myCustomFunction(dateValue) {
    let selectedDate = dateValue;   // ✅ use the value passed in

    if (selectedDate != '') {
        // let parts = selectedDate.split("/");
        // let inputDate = new Date(parts[2], parts[1] - 1, parts[0]); // yyyy, mm-1, dd

        // let today = new Date();
        // today.setHours(0,0,0,0);

        // if (inputDate < today) {
        //     // reset to today
        //     let dd   = String(today.getDate()).padStart(2, '0');
        //     let mm   = String(today.getMonth() + 1).padStart(2, '0');
        //     let yyyy = today.getFullYear();
        //     selectedDate = dd + "/" + mm + "/" + yyyy;  
        //     $("#start_date").val(selectedDate); // ✅ update field explicitly
        // }

        // convert to mdY format
        let formatted = selectedDate.split("/");
        formatted = formatted[1] + "/" + formatted[0] + "/" + formatted[2];

        $.ajax({
            url: "{{ route('getWebAppointmentEndDate') }}",
            type: "POST",
            data: { start_date: formatted, _token: '{{csrf_token()}}' },
            success: function(response) {
                $(".chk_enddate").datepicker("destroy").val("");
                $('.chk_enddate').datepicker({
                    dateFormat:'dd/mm/yy',
                    minDate: response.end_date,
                    setDate: response.end_date
                });
                $(".chk_enddate").val(response.end_date);
            }
        });
    }
}

 
 

// $('.chk').on("change", function(date) { 
//     // alert("hi");      
//     selectedDate = $(this).val();      
//     if (selectedDate != '')
//     {
//         // --- ✅ New logic: check if past date ---
//         let parts = selectedDate.split("/");
//         let inputDate = new Date(parts[2], parts[1] - 1, parts[0]); // yyyy, mm-1, dd

//         let today = new Date();
//         today.setHours(0,0,0,0);

//         if (inputDate < today) {
//             // reset to today
//             let dd   = String(today.getDate()).padStart(2, '0');
//             let mm   = String(today.getMonth() + 1).padStart(2, '0');
//             let yyyy = today.getFullYear();
//             selectedDate = dd + "/" + mm + "/" + yyyy;  
//             $(this).val(selectedDate); // update input field
//         }
//         // --- ✅ End new logic ---
//         selectedDate = selectedDate.split("/");
//         selectedDate = selectedDate[1]+"/"+selectedDate[0]+"/"+selectedDate[2]; //convert to mdY format
       
//          $.ajax({
//                 url:"{{ route('getWebAppointmentEndDate') }}",
//                 type:"POST",
//                 data:{"start_date":selectedDate, _token: '{{csrf_token()}}'},
//                 success:function(response){
                   
//                          //For showing end date
//                          $(".chk_enddate" ).datepicker("destroy");
//                          $(".chk_enddate").val("");
//                          $('.chk_enddate').datepicker({
//                                     dateFormat:'dd/mm/yy',
//                                     minDate:response.end_date,
//                                     setDate:response.end_date
//                                 });
//                          $(".chk_enddate").val(response.end_date);
                   
//                 }//
//             });
       
//     }//if selectedDate
// });

$( document ).ready(function() {
    var patient_id = $("#hidden_patient_id").val();
    var is_already_registered = $("#is_already_registered").val();
    var quarter_setting_val = $("#quarter_setting_val").val();
    
    var doctor_id = $("#doctor_id").val(); // added on 30-jan-23 for showing doctor selected on register page

         $('.card-body').LoadingOverlay("show", {
            background: "rgba(165, 190, 100, 0.4)",
         });

         $.ajax({
            url:"{{ route('getWebStartDate') }}",
            type:"POST",
            data:{_token: '{{csrf_token()}}',"patient_id":patient_id,"is_already_registered":is_already_registered,doctor_id:doctor_id},
            success:function(response){

                console.log('in response...');
                console.log(response);

                
                if(response.count==1 && response.quarter_setting==1)
                {
                    $('.card-body').LoadingOverlay("hide");

                     //For showing start date
                     $(".chk" ).datepicker("destroy");
                     $(".chk").val("");
                     $('.chk').datepicker({
                                dateFormat:'dd/mm/yy',
                                minDate:response.avaliable_date,
                                setDate:response.avaliable_date
                            });
                     $(".chk").val(response.avaliable_date);

                     //For showing end date
                     $(".chk_enddate" ).datepicker("destroy");
                     $(".chk_enddate").val("");
                     $('.chk_enddate').datepicker({
                                dateFormat:'dd/mm/yy',
                                minDate:response.end_date,
                                setDate:response.end_date
                            });
                     $(".chk_enddate").val(response.end_date);
                     
                  
                }
                else if(response.count==0 && response.quarter_setting==0)
                {
                    $('.card-body').LoadingOverlay("hide");

                    //For showing start date
                     $(".chk" ).datepicker("destroy");
                     $(".chk").val("");
                     $('.chk').datepicker({
                                dateFormat:'dd/mm/yy',
                                minDate:response.avaliable_date,
                                setDate:response.avaliable_date
                            });
                     $(".chk").val(response.avaliable_date);

                     //For showing end date
                     $(".chk_enddate" ).datepicker("destroy");
                     $(".chk_enddate").val("");
                     $('.chk_enddate').datepicker({
                                dateFormat:'dd/mm/yy',
                                minDate:response.end_date,
                                setDate:response.end_date
                            });
                     $(".chk_enddate").val(response.end_date);
                }
                 else if(response.count==0 && response.quarter_setting==1)
                {
                    $('.card-body').LoadingOverlay("hide");

                     //For showing start date
                     $(".chk" ).datepicker("destroy");
                     $(".chk").val("");
                     $('.chk').datepicker({
                                dateFormat:'dd/mm/yy',
                                minDate:response.avaliable_date,
                                setDate:response.avaliable_date
                            });
                     $(".chk").val(response.avaliable_date);

                     //For showing end date
                     $(".chk_enddate" ).datepicker("destroy");
                     $(".chk_enddate").val("");
                     $('.chk_enddate').datepicker({
                                dateFormat:'dd/mm/yy',
                                minDate:response.end_date,
                                setDate:response.end_date
                            });
                     $(".chk_enddate").val(response.end_date);
                     
                  
                }
                else
                {

                     //only if condition added on 2-may-24
                    // if(response.msg=="all quarters booked")
                    // {
                    //    $('.card-body').LoadingOverlay("hide");
                    // }
                    // else
                    // {


                        $('.card-body').LoadingOverlay("hide");

                        /*****commented on 30-aug-24**for datepicker blank*****/
                        // $(".chk").datepicker("destroy");
                        // $(".chk").val("");

                        // $(".chk_enddate").datepicker("destroy");
                        // $(".chk_enddate").val("");

                        /******commented on 30-aug-24**for datepicker blank****/

                        var msg = "{{ __('admin.ERR_DOCTOR_NOT_AVALIABLE') }}";
                        // toastr.error(msg);
                        $("#doctor_duty_rosters").empty();
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
                        //$('.ui-datepicker-calendar').css("display","none");//commented on 30-aug-24 for datepicker blank




                   // }

                }//else
            }
        });
     
           
});


</script>


<!----Added code on 28-aug-24----->
<!-- 
<script type="text/javascript">
    
$('#appointment_type_id').change(function()
{
    selectedAppointmentId = $('#appointment_type_id').val();
    if(selectedAppointmentId)
    {
        $('#appointment_type_id_hidden').val(selectedAppointmentId);
    }
    if ($('#doctor_id').val() != '') 
    {
 
        $('.appointment-loader').LoadingOverlay("show", {
         background: "rgba(165, 190, 100, 0.4)",
        });
        var appointmentTypeId = $(this).val();
        var hiddenFieldValue = $('#hidden_field_web').val();
        var url = hiddenFieldValue === 'yes' ? WEBURL : BASEURL;


        var fromWeb = $('#hidden_web').val();
        var fieldValue = fromWeb === 'from_web' ? 'from_web' : '';

        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: url + "/get-doctors-on-appointment-types",
            type: 'POST',
            data: {
                appointmentTypeId: appointmentTypeId,
                from : fieldValue,
            },
            success: function(response){
               
                $('.appointment-loader').LoadingOverlay("hide");

                var doctorTypeIds = response;
                console.log("=========>");
                console.log(doctorTypeIds);

                var selectedDoctorId = $('#doctor_id').val();

                var dropdown = $('#doctor_id');
                dropdown.empty();
                $.each(doctorTypeIds, function(index, item) {
                    console.log("===in doctor type ids==============>");
                    var option =$('<option>', { 
                        value: item.id, 
                        text: item.name
                       
                    });

                    if (item.id == selectedDoctorId) {
                        option.attr('selected', 'selected'); 
                    }
                    dropdown.append(option);
                    
                });
               
            },
            error: function(xhr, status, error) {
            }
        });
    }//if
});

</script> -->


@endsection