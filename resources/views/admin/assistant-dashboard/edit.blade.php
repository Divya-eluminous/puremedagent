<link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/select2/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/toastr/toastr.min.css') }}">
<?php 
if($result == "found" ) { ?>
<!-- <form id="frmAppointmentEdit" role="form" data-toggle="validator" action="">   --> 
   <div class="row">
      <div class="col-sm-6">
         <div class="form-group">
            <input type="hidden" name="encID" id="edit_appointment_id" value="{{ base64_encode(base64_encode($encID)) }}">
            <input type="hidden" name="hd_edit_appointment_id" id="hd_edit_appointment_id" value="{{ $encID }}"> 
            <input type="hidden" name="hd_edit_patient_id" id="hd_edit_patient_id" value="{{ $appointment->patient_id }}"> 
            <input type="hidden" name="hd_edit_appointment_type_id" id="hd_edit_appointment_type_id" value="{{ $appointment->appointment_type_id }}">
            <label>@lang('admin.TITLE_APPOINTMENT_PATIENT')</label> 
            <div class="frmSearch">   
               <input type="search" placeholder="@lang('admin.TITLE_SEARCH_TEXT')" id="suggesstion_edit_patient_id" class="form-control" autocomplete="off">
               <div id="suggesstion-box-edit-patient" style="margin-top: 2%">
                 <select 
                   name="patient_id" 
                   id="patient_idedit"  
                   required
                   data-error="@lang('admin.ERR_APPOINTMENT_PATIENT_REQUIRED')"
                   class="form-control" 
                   >
                   <option value="">@lang('admin.TITLE_SELECT_PATIENT')</option>
                   @foreach($patient as $patients)
                   <option value="{{ $patients->id }}" @if($patients->id==$appointment->patient_id) selected @endif>{{ $patients->first_name .' '. $patients->family_name}}</option>
                   @endforeach
                </select>
               </div>
            </div>
            
            <span class="help-block invalid-feedback with-errors">
               <ul class="list-unstyled">
                  <li class="err_patient_id"></li>
               </ul>
            </span>
         </div>
      </div>
      <div class="col-sm-6">
         @if (Auth::user()->hasRole('super-admin') || (Auth::user()->hasRole('Assistant')) || (Auth::user()->hasRole('Doctor')) || (Auth::user()->hasRole('Lead-Assistant')) )
         <div class="form-group">
            <label>@lang('admin.TITLE_APPOINTMENT_DOCTOR')</label> 
            <select 
               name="doctor_id" 
               id="doctor_idedit"  
               required
               data-error="@lang('admin.ERR_APPOINTMENT_DOCTOR_REQUIRED')"
               class="form-control select2" 
               >
               <option value="">@lang('admin.TITLE_SELECT_DOCTOR')</option>
               @foreach($user as $users)
               <option value="{{ $users->id }}" lang="{{ $users->status }}" @if($users->id==$appointment->doctor_id) selected @endif >{{ $users->first_name .' '. $users->last_name}}</option>
               @endforeach
            </select>
            <span class="help-block invalid-feedback with-errors">
               <ul class="list-unstyled">
                  <li class="err_doctor_id"></li>
               </ul>
            </span>
         </div>
         @endif
      </div>
      <div class="col-sm-6">
         <div class="form-group">
            <label>@lang('admin.TITLE_APPOINTMENT_TYPE')</label>  
            <select 
               name="appointment_type_id" 
               id="appointment_type_idedit"  
               required
               data-error="@lang('admin.ERR_APPOINTMENT_TYPE_REQUIRED')"
               class="form-control select2" 
               >
               <option value="">@lang('admin.TITLE_ROSTER_SELECT_APPOINTMENT_TYPE')</option>
               @foreach($appointment_type as $appointment_types)
               <option value="{{ $appointment_types->id }}" data-optimal-appointment="{{ $appointment_types->optimal_appointment }}" @if($appointment_types->id==$appointment->appointment_type_id) selected @endif>{{ $appointment_types->name}} ({{ $appointment_types->duration }})</option>
               @endforeach
            </select>
            <span class="help-block invalid-feedback with-errors">
               <ul class="list-unstyled">
                  <li class="err_appointment_type_id"></li>
               </ul>
            </span>
         </div>
      </div>
      <div class="col-sm-6">
         <div class="form-group">
            <label class="theme-blue"> 
            @lang('admin.TITLE_APPOINTMENT_DATE') <span class="required">*</span></label> 
           <!--  <input 
               type="text" 
               name="date" 
               class="form-control"
               id="appointment_dateedit"  
               required
               maxlength="250" 
               data-error="@lang('admin.ERR_APPOINTMENT_DATE_REQUIRED')." 
               value="{{ date('Y-m-d',strtotime($appointment->start_date)) }}" readonly="readonly" style="background-color:white;"
               > -->

              <!--swapnil added on 10-jan-23-->  
             <input 
               type="text" 
               name="date" 
               class="form-control"
               id="appointment_dateedit"  
               required
               maxlength="250" 
               data-error="@lang('admin.ERR_APPOINTMENT_DATE_REQUIRED')." 
               value="{{ date('d-m-Y',strtotime($appointment->start_date)) }}" readonly="readonly" style="background-color:white;"
               >   



               <!--new code added swapnil 16-09-2022-->


              <!-- <input 
              type="text" 
              class="form-control"
              id="not_emgr_doc_appointment_dateedit"  
              readonly 
              value="{{ date('Y-m-d',strtotime($appointment->start_date)) }}"> -->

               <!--new code added swapnil 10-jan-23-->
              <input 
              type="text" 
              class="form-control"
              id="not_emgr_doc_appointment_dateedit"  
              readonly 
              value="{{ date('d-m-Y',strtotime($appointment->start_date)) }}">



             <!--new code added swapnil 16-09-2022-->
             <!--code changes by swapnil 16-09-2022-->
            <span class="help-block invalid-feedback with-errors">
               <ul class="list-unstyled">
                  <li class="err_date"></li>
               </ul>
            </span>
         </div>
      </div>
      <div class="col-sm-6">
      <div class="form-group">
         <label>@lang('admin.TITLE_APPOINTMENT_TIME_FRAME') <span class="required">*</span></label>  
         <input required
            id="time_frameedit"
            class="form-control active_status"
            data-placeholder="@lang('admin.TITLE_SELECT_TEXT') @lang('admin.TITLE_ROSTER_TIME_FRAME')"
            value="{{ date('H:i',strtotime($appointment->start_date)) }}" 
            style="width: 100%;" readonly>

         <input type="hidden" 
            name="roster_time_frame_id_old"
            id="roster_time_frame_id_old"  
            class=""  
            value="{{$time_frames_id}}">
         <input type="hidden" 
            name="roster_time_frame_id1"
            id="roster_time_frame_id1"  
            class=""  
            value="">
         <input type="time" 
            
            id="time_frame2"  
            class="form-control inactive_status timepicker"  
            maxlength="12" 
            value="{{ date('H:i',strtotime($appointment->start_date)) }}"
            style="display: none">
         <span class="help-block invalid-feedback with-errors">
            <ul class="list-unstyled">
               <li class="err_time_frame"></li>
            </ul>
         </span>
      </div>
   </div>
      <div class="col-sm-6">
         <div class="form-group">
            <label class="theme-blue"> 
            @lang('admin.TITLE_APPOINTMENT_NOTE')</label> 
            <textarea
               type="text" 
               name="notes" 
               class="form-control" 
               >{{ $appointment->notes }}</textarea>
            <!--  required
               data-error="@lang('admin.ERR_APPOINTMENT_NOTE_REQUIRED')"  -->
            <span class="help-block invalid-feedback with-errors">
               <ul class="list-unstyled">
                  <li class="err_notes"></li>
               </ul>
            </span>
         </div>
      </div>
      <div class="col-sm-6">
         <div class="form-group">
            <label class="theme-blue"> 
            @lang('admin.TITLE_APPOINTMENT_STATUS')</label>
            <div class="form-check"> 
               <input 
               type="checkbox" 
               class="form-check-input" 
               id="statusedit"
               name="status"  
               value="1" @if(!empty($appointment->status) && $appointment->status==1) checked @endif
               >
               <label class="form-check-label" for="status">@lang('admin.TITLE_APPOINTMENT_STATUS_ACTIVE')</label>
            </div>
         </div>
      </div>
      <div class="col-sm-6"> 
         <div class="form-group appointment_type_services" id="appointment_type_services">  
              
         </div> 
      </div>

      <input type="hidden" name="update_url" id="update_url" value="{{ $modulePath }}">
      <input type="hidden" name="sel_time_frame" id="sel_time_frame" value="{{ date('H:i',strtotime($appointment->start_date)) }}">
   </div>
   <!--------------Start--Checkbox added by divya--19-sept-22---------------->

  <div class="col-sm-6 p-0" id="optimal_checkbox_div" style="margin-top: -8px;margin-bottom: -8px;">
      <div class="form-group mb-0">
         <label class="theme-blue mb-0"> 
         @lang('admin.TITLE_OPTIMAL_APPOINTMENT')</label>
       <!--   <div class="form-check">  -->
            <input type="checkbox" class="form-check-input" id="quarter_setting_check_val"
               name="quarter_setting_check_val" value="1" checked style="margin-left: 10px">
           
        <!--  </div> -->
      </div>
   </div>

<!--------------End-Checkbox added by divya--19-sept-22--------------->

<!--swapnil added 04-oct-2022-->
  <div class="row">
   <span id="doctor_not_avaliable_msg" style="display: none;padding-top: 12px;"></span>
  </div>
<!--swapnil added 04-oct-2022-->



<!---------start code added by swapnil 16-09-2022 ----------------------------------------->
<div class="row">
   <div class="col-sm-12">
      <div class="form-group">
         <input type="hidden" id="dr_not_available_edit" value="">
      </div>
   </div>
</div>
<div class="available_datetime_edit">
   <div class="row">
      <div class="col-sm-6">
         <div class="form-group">
            <label class="theme-blue">
            @lang('admin.TITTLE_FORM_DATE') <span class="required">*</span> </label>

            <!-- <input
               type="text"
               name=""
               class="form-control"
               onchange ="getfirstdate(elements)"
               id="appointment_from_date_edit"
               value="{{ date('Y-m-d',strtotime($appointment->start_date)) }}"
               autocomplete="off" readonly="readonly" style="background-color:white;"> -->

               <!--swapnil added 10-jan-23-->

               <input
               type="text"
               name=""
               class="form-control"
               onchange ="getfirstdate(elements)"
               id="appointment_from_date_edit"
               value="{{ date('d-m-Y',strtotime($appointment->start_date)) }}"
               autocomplete="off" readonly="readonly" style="background-color:white;">



         </div>
      </div>
      <div class="col-sm-6">
         <div class="form-group">
            <label class="theme-blue">
            @lang('admin.TITTLE_TO_DATE') <span class="required">*</span> </label>
            <input
               type="text"
               name=""
               class="form-control"
               id="appointment_to_date_edit"
               onchange ="getseconddate(elements)"
               autocomplete="off" readonly="readonly" style="background-color:white;">
         </div>
      </div>
   </div>
</div>
<div class="table-responsive table_bottom doctor_duty_rosters_edit" id="doctor_duty_rosters"></div>
<div id="quarterSetting" data-quarter-setting="{{ $quarter_setting }}"></div>
<!-----------end code added by swapnil 16-09-2022 ---------------------------------------->
   <div class="modal-footer">
      <button type="submit" class="btn btn-success" id="s_button">@lang('admin.TITLE_SAVE_BUTTON')</button>
   </div>
<!-- </form> -->
<?php } else { ?>
   <div class="row">
      <div class="col-sm-6">
         <label>@lang('admin.NO_RESULT_FOUND')</label>
      </div>
   </div>
   <input type="hidden" name="sel_time_frame" id="sel_time_frame" value="">
<?php } ?>
<!-- ############## Roshani Added this code (22/02/2024) C) User settings ################ -->
<script type="text/javascript" src="{{ url('assets/admin/js/dashboard/commonJsForApp.js') }}"></script>
<!-- ############## Roshani Added this code (22/02/2024) C) User settings ################ -->
<script src="{{ asset('assets/admin-lte/plugins/jquery/jquery.min.js') }}"></script>
<!-- jQuery UI 1.11.4 -->
<script src="{{ asset('assets/admin-lte/plugins/jquery-ui/jquery-ui.min.js') }}"></script>
<script src="{{ asset('assets/admin-lte/plugins/select2/js/select2.full.min.js') }}"></script>
<script src="{{ asset('assets/admin-lte/plugins/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"></script>
<script src="{{ asset('assets/plugins/lodingoverlay/loadingoverlay.min.js') }}"></script>
<script src="{{ asset('assets/plugins/toastr/toastr.min.js') }}"></script>
<script src="{{ asset('assets/plugins/toastr/toastr.options.js') }}"></script>
<!-- overlayScrollbars -->
<script src="{{ asset('assets/admin-lte/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>


@php 
    $validUser = 0;
 @endphp
 @if(auth()->user()->can('optimal-appointment'))
   @php 
    $validUser = 1;
   @endphp
 @endif

<script type="text/javascript">
   $(document).ready(function () {
      // added by vijay 19/3/24
    $(document).ready(function(){
        $('#appointment_type_idedit').trigger('change');
    });
   var hd_patient_id = $("#hd_edit_patient_id").val(); 
   var hd_edit_appointment_id = $("#hd_edit_appointment_id").val();  
   var hd_edit_appointment_type_id = $("#hd_edit_appointment_type_id").val();  
   GetServices(hd_edit_appointment_type_id,hd_patient_id,hd_edit_appointment_id);
   
   if($('#doctor_idedit option:selected').attr('lang') == 0)
   {
      $(".active_status").hide();
      $(".inactive_status").show(); 
   }
   //Initialize Select2 Elements
   $('.select2').select2()
   $('input[name="name"]').focus();
   $('#appointment_dateedit').datepicker({
   // changeMonth: true,
   // changeYear: true,
     // format: 'yyyy-mm-dd', //swapnil commented on 10-jan-23
     format: 'dd-mm-yyyy', //swapnil added on 10-jan-23
      orientation: "bottom",
      autoclose: true,
      todayHighlight: true,
      // startDate: new Date() //commented on 10-jan-23 for past date to show for emergency doctor
      });

      //Appointment new updated code by swapnil pawar 13-09-2022
      var doctor_id = $('#doctor_idedit option:selected').val();
      var doctor_status = $('#doctor_idedit option:selected').attr('lang');
      if(doctor_status=='1')
      {
         $("#optimal_checkbox_div").show(); // Added by divya on 19sept22
         $("#not_emgr_doc_appointment_dateedit").show();
         $("#appointment_dateedit").hide();
         $(".available_datetime_edit").show();
         $(".doctor_duty_rosters_edit").show();
         $("#appointment_dateedit").removeAttr('name');
         $('#not_emgr_doc_appointment_dateedit').attr('name', 'date');
         $('#time_frameedit').attr('name', 'time_frame');
         $("#time_frame2").removeAttr('name');
         var doctor_id = doctor_id;
         var appointment_type_id = $('#appointment_type_idedit').find(":selected").val();
         if (doctor_id.length == 0 || appointment_type_id.length == 0) {
         return false;
         } else {
         doctorAppointmentCommonFunction(doctor_id, appointment_type_id,1);
         var editdate = $("#not_emgr_doc_appointment_dateedit").val();
         $("#appointment_from_date_edit").val(editdate);
         }
      }
      else
      {
          $("#optimal_checkbox_div").hide(); // Added by divya on 19sept22
         $("#not_emgr_doc_appointment_dateedit").hide();
         $("#appointment_dateedit").show();
         $("#not_emgr_doc_appointment_dateedit").removeAttr('name');
         $('#appointment_dateedit').attr('name', 'date');
         $(".available_datetime_edit").hide();
         $(".doctor_duty_rosters_edit").hide();
         $("#appointment_from_date_edit").val('');
         $('#time_frame2').attr('name', 'time_frame');
         $("#time_frameedit").removeAttr('name');

         $("#doctor_not_avaliable_msg").hide(); // Added by swapnil on 4oct22
      }
      //Appointment new updated code by swapnil pawar 13-09-2022
   
   
      var hd_patient_id = $("#hd_edit_patient_id").val();
      var hd_edit_appointment_id = $("#hd_edit_appointment_id").val();
      var hd_edit_appointment_type_id = $("#hd_edit_appointment_type_id").val();
      GetServices(hd_edit_appointment_type_id,hd_patient_id,hd_edit_appointment_id);
      if($('#doctor_idedit option:selected').attr('lang') == 0)
      {
         $(".active_status").hide();
         $(".inactive_status").show();  
      }
       //Initialize Select2 Elements
      $('.select2').select2()
      $('input[name="name"]').focus();
      $('#appointment_dateedit').datepicker({
         // changeMonth: true,
         // changeYear: true,
         //format: 'yyyy-mm-dd', //commented on 10-jan-23
         format: 'dd-mm-yyyy', //swapnil added on 10-jan-23
         orientation: "bottom",
         autoclose: true,
         todayHighlight: true,
         startDate: new Date()
      });
   }); //end document ready function
   
   //swapnil code here 13-09-2022
      //Doctor Select
      $('#doctor_idedit').on('change', function() {
          $(".available_datetime_edit").hide();
          $(".doctor_duty_rosters_edit").hide();
          var doctor_id = this.value;
          var appointment_type_id = $('#appointment_type_idedit').find(":selected").val();
          if (doctor_id.length == 0 || appointment_type_id.length == 0) {
            return false;
          } else {
            doctorAppointmentCommonFunction(doctor_id, appointment_type_id,0);
          }
      });
   
      //Appointment Type Select
      $('#appointment_type_idedit').on('change', function() {
         // added by vijay 6-3-24
         var appointmentType = $(this).val();
         if(appointmentType) {
               var optimalAppointment = $('option:selected', this).data('optimal-appointment');
               var quarterSettingValue = document.getElementById('quarterSetting').getAttribute('data-quarter-setting');
               if(optimalAppointment == 1 && (quarterSettingValue == 1 || quarterSettingValue==0)) {
                  $("#quarter_setting_check_val").val(1);
                  $("#quarter_setting_check_val").prop('checked', true);
               } else {
                  $("#quarter_setting_check_val").val(0);
                  $("#quarter_setting_check_val").prop('checked', false);
               }
               
         }
         // 
          $(".available_datetime_edit").hide();
          $(".doctor_duty_rosters_edit").hide();
          var appointment_type_id = this.value;
          var doctor_id = $('#doctor_idedit').find(":selected").val();
           var patient_id = $('#patient_idedit').find(":selected").val();
          if (doctor_id.length == 0 || appointment_type_id.length == 0) {
            return false;
          } else {
            doctorAppointmentCommonFunction(doctor_id, appointment_type_id,0);
          }

         if(appointment_type_id != "" ) 
         {
             var a_id = '';
             var hd_edit_appointment_id_val = $("#hd_edit_appointment_id").val();//added on 13-feb-26 for edit pap
            // GetServices(appointment_type_id,patient_id,a_id); //commented on 13-feb-26
            GetServices(appointment_type_id,patient_id,hd_edit_appointment_id_val);//added on 13-feb-26 for edit pap
         }


      });

       //function added by divya onchange of checkbox
       $('#quarter_setting_check_val').on('click', function() {

             var validUser = {{ $validUser }};
             console.log(validUser);

             var quarter_setting_check_val = $("#quarter_setting_check_val").val();
             if(quarter_setting_check_val==0)
             {
               //  $("#quarter_setting_check_val").val(1);
               //  $("#quarter_setting_check_val").attr('checked', 'checked');
               // added by vijay 8/3/24
               if(validUser==0){
                  toastr.error("{{ __('admin.ERR_ROLE_NOT_ALLOWED') }}");
                  return false;
               }else{
                  $("#quarter_setting_check_val").val(1);
                  $("#quarter_setting_check_val").attr('checked', 'checked');
               }
             }else{
                  //commented below code on 12-jan-24 (15-jan-24)
                  // $("#quarter_setting_check_val").val(0);
                  // $("#quarter_setting_check_val").removeAttr('checked');

                  //added if else condition on 12-jan-24 (15-jan-24)
                  console.log("in===>"+validUser);
                  if(validUser==1)
                  {
                   $("#quarter_setting_check_val").val(0);
                   $("#quarter_setting_check_val").removeAttr('checked');
                  }
                  else
                  {
                       toastr.error("{{ __('admin.ERR_ROLE_NOT_ALLOWED') }}");
                       return false;
                  }


             }  

            var doctor_id = $('#doctor_idedit').find(":selected").val();
            var appointment_type_id = $('#appointment_type_idedit').find(":selected").val();
            if (doctor_id.length == 0 || appointment_type_id.length == 0) {
                return false;
            } else {
                doctorAppointmentCommonFunction(doctor_id, appointment_type_id,0);
            }
       });//quarter setting check function 

   
   
      function doctorAppointmentCommonFunction(doctor_id, appointment_type_id,ondocumentload) {
          //common code doctor select
          var doctor_id = doctor_id;
          var doctor_status = $('#doctor_idedit option:selected').attr('lang');
          var patient_id = $('#patient_idedit').find(":selected").val();
          var appointment_type_id = appointment_type_id;
          var patient_id_value = $('#suggesstion_edit_patient_id').val();
          var calender_search = 0;
          var appointment_from_date = $("#appointment_from_date_edit").val();
          var appointment_to_date = $("#appointment_to_date_edit").val();

          var quarter_setting_check = $("#quarter_setting_check_val").val(); // Added by divya on 19 sept 22


          if (doctor_status == 1) {
            $("#optimal_checkbox_div").show(); // Added by divya on 19sept22
            $('#time_frameedit').attr('name', 'time_frame');
            $("#time_frame2").removeAttr('name');
            $('#not_emgr_doc_appointment_dateedit').attr('name', 'date');
            $("#appointment_dateedit").removeAttr('name');
            $("#appointment_dateedit").hide();
            $("#time_frame2").hide();
            $("#not_emgr_doc_appointment_dateedit").show();
            $("#time_frameedit").show();
            //02-09-2022
            $('.appointment-loader_edit').LoadingOverlay("show", {
               background: "rgba(165, 190, 100, 0.4)",
            });
              //02-09-2022
              //Ajax Code 
              $(".available_datetime_edit").hide();
              $(".doctor_duty_rosters_edit").hide();   
              $.ajax({
                  type: "POST",
                  headers: {
                     'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                  },
                  url: BASEURL + "/selectedDate",
                  data: {
                     "doctor_id": doctor_id,
                     "appointment_type_id": appointment_type_id,
                     "calender_search": calender_search,
                     "appointment_from_date": appointment_from_date,
                     "appointment_to_date": appointment_to_date,
                     "patient_id": patient_id,
                     "doctor_status": doctor_status,
                     "quarter_setting_check":quarter_setting_check
                  },
                  success: function(response) {
                      if (response.count == 1) {

                         //<!--swapnil added 04-oct-2022-->
                         if(ondocumentload==0)
                         {
                          $("#doctor_not_avaliable_msg").hide();
                          $("#doctor_not_avaliable_msg").html(" ");
                         }
                         //<!--swapnil added 04-oct-2022-->


                        $('.appointment-loader_edit').LoadingOverlay("hide");
                        var data = response.data;
                        $("#appointment_from_date_edit").val("");
                        $("#appointment_to_date_edit").val("");
                        $("#dr_not_available_edit").val("")
                        if (appointment_type_id.length == 0) {
                           $(".available_datetime_edit").hide();
                           $(".doctor_duty_rosters_edit").hide();
                        } else {
                           $(".available_datetime_edit").show();
                           $(".doctor_duty_rosters_edit").show();
                          }
                          $("#appointment_from_date_edit").val(response.calender_date1);
                          $("#appointment_to_date_edit").val(response.calender_date2);
                          //get doctor time frame
                          $(".doctor_duty_rosters_edit").empty();
                          $(".doctor_duty_rosters_edit").html(response.html);
                          //get doctor time frame

                           //Start Below lines added by divya to solve datepicker date not set issue on 19 sept 22 added on 10-jan-23
                           $("#appointment_from_date_edit" ).datepicker( "destroy" );
                           $("#appointment_to_date_edit" ).datepicker( "destroy" );
                          //End Below lines added by divya to solve datepicker date not set issue on 19 sept 22


                          $('#appointment_from_date_edit').datepicker({
                             // format: 'yyyy-mm-dd', //commented on 10-jan-23
                              format: 'dd-mm-yyyy', //swapnil added on 10-jan-23
                              orientation: "bottom",
                              autoclose: true,
                              todayHighlight: true,
                            //  minDate: response.hidedate //commented on 10-jan-23
                              startDate: response.calender_date1,  // Added by swapnil on 10-jan-23
                              minDate:0 // Added by swapnil on 10-jan-23
   
                          });
                          $('#appointment_to_date_edit').datepicker({
                             // format: 'yyyy-mm-dd', //commented on 10-jan-23
                              format: 'dd-mm-yyyy', //swapnil added on 10-jan-23
                              orientation: "bottom",
                              autoclose: true,
                              todayHighlight: true,
                             // minDate: response.hideenddate //commented on 10-jan-23
                              startDate: response.calender_date2,  // Added by swapnil on 10-jan-23
                              minDate:0 // Added by swapnil on 10-jan-23
                          });
                      } else {
                        $('.appointment-loader_edit').LoadingOverlay("hide");
                        $(".doctor_duty_rosters_edit").empty();
                        $(".appointment_from_date_edit").val(null);
                        $(".appointment_to_date_edit").val(null);
                        $(".available_datetime_edit").hide();
                        //toastr.error("{{ __('admin.ERR_TIME_FRAME_NOT_FOUND') }}");
                        //$("#dr_not_available_edit").val('1');
                          //swapnil added 04-oct-2022
                          if(ondocumentload==1)
                          {
                            $("#doctor_not_avaliable_msg").show();
                            $("#doctor_not_avaliable_msg").html("{{ __('admin.ERR_DOCTOR_ROSTER_NOT_AVALIABLE') }}");
                          }
                          else
                          {
                            $("#doctor_not_avaliable_msg").hide();
                            $("#doctor_not_avaliable_msg").html("");
                            toastr.error("{{ __('admin.ERR_TIME_FRAME_NOT_FOUND') }}");
                            $("#dr_not_available_edit").val('1');
                          }
                      }//else
                  }
              });
              //Ajax Code
          } else if (doctor_status == "undefined") {
              //$("#appointment_date_calender").hide();
              $("#time_frameedit").hide();
              $(".doctor_duty_rosters_edit").empty();
              $(".available_datetime_edit").hide();
              $("#appointment_dateedit").show();
              $("#not_emgr_doc_appointment_dateedit").hide();
              $("#time_frame2").show();
              $("#time_frameedit").hide();

               $("#optimal_checkbox_div").hide(); // Added by divya on 19sept22
   
          } else if (doctor_id == "") {
              //$("#appointment_date_calender").hide();
              $("#time_frameedit").hide();
              $(".doctor_duty_rosters_edit").empty();
              $(".available_datetime_edit").hide();
              $("#appointment_dateedit").show();
              $("#not_emgr_doc_appointment_dateedit").hide();
              $("#time_frame2").show();
              $("#time_frameedit").hide();

               $("#optimal_checkbox_div").hide(); // Added by divya on 19sept22
   
          } else {
              $('#time_frame2').attr('name', 'time_frame');
              $("#time_frameedit").removeAttr('name');
              $('#appointment_dateedit').attr('name', 'date');
              $("#not_emgr_doc_appointment_dateedit").removeAttr('name');
              $(".doctor_duty_rosters_edit").empty();
              $("#appointment_dateedit").show();
              $("#not_emgr_doc_appointment_dateedit").hide();
              $("#time_frame2").show();
              $("#time_frameedit").hide();
              $(".available_datetime_edit").hide();
              $("#dr_not_available_edit").val("");
              $("#appointment_date_edit").val('');
              $('#appointment_dateedit').datepicker({
                 // format: 'yyyy-mm-dd', //commented on 10-jan-23
                 format: 'dd-mm-yyyy', //swapnil added on 10-jan-23
                  orientation: "bottom",
                  autoclose: true,
                  todayHighlight: true,
                  //startDate: new Date(), //commented on 10-jan-23
                 // minDate: 0 //commented on 10-jan-23 for past date to show for emergency doctor
              });

              $("#optimal_checkbox_div").hide(); // Added by divya on 19sept22
              $("#doctor_not_avaliable_msg").hide();  // Added by swapnil on 4oct22
              $("#doctor_not_avaliable_msg").html(" "); // Added by swapnil on 4oct22
          }
          //common code doctor select
      }
   //doctor on change code
   
   
   //from date select
   function getfirstdate(elements) {
          var appointment_from_date = $("#appointment_from_date_edit").val();
          var doctor_id = $('#doctor_idedit').find(":selected").val();
          var patient_id = $('#patient_idedit').find(":selected").val();
          var doctor_status = $('#doctor_idedit option:selected').attr('lang');
          var appointment_type_id = $('#appointment_type_idedit').find(":selected").val();
          $("#appointment_to_date_edit").val('');
          var appointment_to_date = $("#appointment_to_date_edit").val();
          var calender_search = 1;
          $(".doctor_duty_rosters_edit").empty();

         var quarter_setting_check = $("#quarter_setting_check_val").val();  // Added by divya on 19 sept 22



          $('.appointment-loader_edit').LoadingOverlay("show", {
              background: "rgba(165, 190, 100, 0.4)",
          });
          $.ajax({
             type: "POST",
              headers: {
                  'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
              },
              url: BASEURL + "/selectedDate",
              data: {
                  "doctor_id": doctor_id,
                  "appointment_type_id": appointment_type_id,
                  "calender_search": calender_search,
                  "appointment_from_date": appointment_from_date,
                  "appointment_to_date": appointment_to_date,
                  "patient_id": patient_id,
                  "doctor_status": doctor_status,
                  "quarter_setting_check":quarter_setting_check
              },
              success: function(response) {
                  var minDate2 = new Date(appointment_from_date);
                  var end_date = new Date(minDate2.getFullYear(), minDate2.getMonth(), minDate2.getDate() + response.no_of_days);
                  $("#appointment_to_date_edit").datepicker("option", "minDate", end_date);
                  if (response.count == 1) {
                     $('.appointment-loader_edit').LoadingOverlay("hide");
                     var data = response.data;
                     $("#dr_not_available_edit").val("");
                     $(".available_datetime_edit").show();
                     $("#appointment_from_date_edit").val(response.calender_date1);
                     $("#appointment_to_date_edit").val(response.calender_date2);
                     //get doctor time frame
                     $(".doctor_duty_rosters_edit").empty();
                     $(".doctor_duty_rosters_edit").html(response.html);
                     //get doctor time frame

                       //Start Below lines added by divya to solve datepicker date not set issue on 19 sept 22 added on 10-jan-23
                      $("#appointment_from_date_edit" ).datepicker( "destroy" );
                      $("#appointment_to_date_edit" ).datepicker( "destroy" );
                      //End Below lines added by divya to solve datepicker date not set issue on 19 sept 22 added on 10-jan-23


                      // Start swapnil code on 10-jan-23
                        $('#appointment_from_date_edit').datepicker({
                        //format: 'yyyy-mm-dd', //swapnil commented 10-jan-23
                        format: 'dd-mm-yyyy', //swapnil added on 10-jan-23
                        orientation: "bottom",
                        autoclose: true,
                        todayHighlight: true,
                        // minDate: response.hidedate     // commented by divya on 19 sep 22
                        //minDate: response.calender_date1  // commented by swapnil on 10-jan-23
                         startDate: response.calender_date1,  // Added by swapnil on 10-jan-23
                         minDate:0 // Added by swapnil on 10-jan-23

                      });
                      $('#appointment_to_date_edit').datepicker({
                        //format: 'yyyy-mm-dd', //swapnil commented 10-jan-23
                        format: 'dd-mm-yyyy', //swapnil added on 10-jan-23
                        orientation: "bottom",
                        autoclose: true,
                        todayHighlight: true,
                        // minDate: response.hideenddate  // commented by divya on 19 sep 22
                        //  minDate: response.calender_date2  // Commented by swapnil on 10-jan-23
                        startDate: response.calender_date2,  // Added by swapnil on 10-jan-23
                        minDate:0 // Added by swapnil on 10-jan-23
                      });

                      // End swapnil code on 10-jan-23



                  } else {
                     $('.appointment-loader_edit').LoadingOverlay("hide");
                     $(".doctor_duty_rosters_edit").empty();
                     $("#appointment_from_date_edit").val(null);
                     $("#appointment_to_date_edit").val(null);
                     $(".available_datetime_edit").hide();
                     toastr.error("{{ __('admin.ERR_TIME_FRAME_NOT_FOUND') }}");
                     $("#dr_not_available_edit").val('1');
                  }
              }
          });
      }
      //from date select
   
   
      //to date select
      function getseconddate(elements) {
       var appointment_from_date = $("#appointment_from_date_edit").val();
       var appointment_to_date = $("#appointment_to_date_edit").val();
       var doctor_id = $('#doctor_idedit').find(":selected").val();
       var patient_id = $('#patient_idedit').find(":selected").val();
       var doctor_status = $('#doctor_idedit option:selected').attr('lang');
       var appointment_type_id = $('#appointment_type_idedit').find(":selected").val();
       var calender_search = 2;
       $(".doctor_duty_rosters_edit").empty();

        var quarter_setting_check = $("#quarter_setting_check_val").val();    // Added by divya on 19 sept 22


       $('.appointment-loader_edit').LoadingOverlay("show", {
           background: "rgba(165, 190, 100, 0.4)",
       });
       $.ajax({
         type: "POST",
           headers: {
               'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
           },
           url: BASEURL + "/selectedDate",
           data: {
               "doctor_id": doctor_id,
               "appointment_type_id": appointment_type_id,
               "calender_search": calender_search,
               "appointment_from_date": appointment_from_date,
               "appointment_to_date": appointment_to_date,
               "patient_id": patient_id,
               "doctor_status": doctor_status,
               "quarter_setting_check":quarter_setting_check
           },
           success: function(response) {
               var minDate2 = new Date(appointment_from_date);
               var end_date = new Date(minDate2.getFullYear(), minDate2.getMonth(), minDate2.getDate() + response.no_of_days);
               $("#appointment_to_date_edit").datepicker("option", "minDate", end_date);
               if (response.count == 1) {
                  $('.appointment-loader_edit').LoadingOverlay("hide");
                  var data = response.data;
                  $("#appointment_from_date_edit").val("");
                  $("#appointment_to_date_edit").val("");
                  $("#dr_not_available_edit").text("");
                  $(".available_datetime_edit").show();
                  $("#appointment_from_date_edit").val(appointment_from_date);
                  $("#appointment_to_date_edit").val(appointment_to_date);
                  //get doctor time frame
                  $(".doctor_duty_rosters_edit").empty();
                  $(".doctor_duty_rosters_edit").html(response.html);
                  //get doctor time frame

                  //Start code aded by swapnil on 10-jan-23
                   $("#appointment_from_date_edit" ).datepicker( "destroy" );
                   $("#appointment_to_date_edit" ).datepicker( "destroy" );
                   //End Below lines added by divya to solve datepicker date not set issue on 19 sept 22

                    $('#appointment_from_date_edit').datepicker({
                     //format: 'yyyy-mm-dd', //swapnil commented 10-jan-23
                     format: 'dd-mm-yyyy', //swapnil added on 10-jan-23
                     orientation: "bottom",
                     autoclose: true,
                     todayHighlight: true,
                     //minDate: response.calender_date1  // commented by swapnil on 10-jan-23
                      startDate: response.calender_date1,  // Added by swapnil on 10-jan-23
                      minDate:0 // Added by swapnil on 10-jan-23

                   });
                   $('#appointment_to_date_edit').datepicker({
                     //format: 'yyyy-mm-dd', //swapnil commented 10-jan-23
                     format: 'dd-mm-yyyy', //swapnil added on 10-jan-23
                     orientation: "bottom",
                     autoclose: true,
                     todayHighlight: true,
                     //  minDate: response.calender_date2  // Commented by swapnil on 10-jan-23
                     startDate: response.getsecond_date,  // Added by swapnil on 10-jan-23
                     minDate:0 // Added by swapnil on 10-jan-23
                   });
                     //End code aded by swapnil on 10-jan-23



               } else {
                  $('.appointment-loader_edit').LoadingOverlay("hide");
                  $(".doctor_duty_rosters_edit").empty();
                  $("#appointment_from_date_edit").val(null);
                  $("#appointment_to_date_edit").val(null);
                  $(".available_datetime_edit").hide();
                  toastr.error("{{ __('admin.ERR_TIME_FRAME_NOT_FOUND') }}");
                  $("#dr_not_available_edit").val('1');
               }
           }
       });
   }
   //to date select

   // function gettimetoradiobutton(element) {
   //  var timeSlot = $("#time_slot_" + element).val();
   //  $('#select_appointment_' + element).attr('data-select_appointment_timeslot', timeSlot);
   //  var data_time_timeslot = $("#time_slot_" + element).attr('data_time_timeslot');
   //  let data_time_timeslotradio = $('input[name="select_appointment"]:checked').attr('data_time_timeslotradio');
   //  var data_time_frame = $("#time_frame").attr('data_time_frame');
   //  var data_time_timeslot = $("#time_slot_" + element).attr('data_time_timeslot');
   //  if (data_time_timeslotradio == data_time_timeslot) {
   //    $("#time_frame").val(timeSlot);
   //    var roster_time_frame_id = $('#time_slot_' + element + ' option:selected').attr('lang');
   //    $("#roster_time_frame_id").val(roster_time_frame_id);
   //   }
   // }//
   

    //Below function added by swapnil on 21 sept 22 
    //  function gettimetoradiobutton(element) 
    // {
    //      var timeSlot = $("#time_slot_" + element).val();
    //      var rvalue = $('input[name="select_appointment"]:checked').attr('data_time_timeslotradio');
    //      $('#select_appointment_' + element).attr('data-select_appointment_timeslot', timeSlot);
    //      var data_time_timeslot = $("#time_slot_" + element).attr('data_time_timeslot');
    //      let data_time_timeslotradio = $('input[name="select_appointment"]:checked').attr('data_time_timeslotradio');
    //      var data_time_frame = $("#time_frame").attr('data_time_frame');
    //      var data_time_timeslot = $("#time_slot_" + element).attr('data_time_timeslot');
    //      var roster_time_frame_id = $('#time_slot_' + element + ' option:selected').attr('lang');
    //      var appointmentdatetime = $("#select_appointment_" + element).attr("data-select_appointment_timeslot");
    //      var appointmentdate = $("#select_appointment_" + element).attr("data-select_appointment_date");
    //      if (data_time_timeslotradio == data_time_timeslot) 
    //      {
    //        $("#time_frameedit").val(timeSlot);
    //        var roster_time_frame_id = $('#time_slot_' + element + ' option:selected').attr('lang');
    //        $("#roster_time_frame_id").val(roster_time_frame_id);
    //      }
    //      if(rvalue==element)
    //      {
    //        $("#time_frameedit").val(timeSlot);
    //        var roster_time_frame_id = $('#time_slot_' + element + ' option:selected').attr('lang');
    //        $("#roster_time_frame_id1").val(roster_time_frame_id);
    //      }
    //      $("#not_emgr_doc_appointment_dateedit").val(appointmentdate);
    //      $("#time_frameedit").val(timeSlot);
    //      $("#roster_time_frame_id1").val(roster_time_frame_id);
    //      $("#select_appointment_"+ element).prop("checked", true);
    //  }

   function gettimetoradiobutton(element)
   {
    var timeSlot = $("#time_slot_" + element).val();
    var appointmentdate = $("#select_appointment_" + element).attr("data-select_appointment_date");
    var roster_time_frame_id = $('#time_slot_' + element + ' option:selected').attr('lang');
    $("#time_frameedit").val(timeSlot);
    $("#not_emgr_doc_appointment_dateedit").val(appointmentdate);
    $("#roster_time_frame_id1").val(roster_time_frame_id);
    $("#select_appointment_"+ element).prop("checked", true);
     console.log("selected timeframe= "+timeSlot);
     console.log("selected date= "+appointmentdate);
     console.log("roster_time_frame_id= "+roster_time_frame_id);
   }
   
   function getradioselectdateTime(element) {
      if ($("#select_appointment_" + element).is(":checked")) {
         var appointmentdate = $("#select_appointment_" + element).attr("data-select_appointment_date");
         var appointmentdatetime = $("#select_appointment_" + element).attr("data-select_appointment_timeslot");
         if (appointmentdatetime == "undefined") {
            var appointmentdatetime = $("#select_appointment_" + element).attr("data-select_appointment_timeslot");
            var roster_time_frame_id = "";
         } else {
            var appointmentdatetime = $('#time_slot_' + element).find(":selected").val();
            var roster_time_frame_id = $('#time_slot_' + element + ' option:selected').attr('lang');
         }
         $("#not_emgr_doc_appointment_dateedit").val(appointmentdate);
         $("#time_frameedit").val(appointmentdatetime);
       } else {
         $("#not_emgr_doc_appointment_dateedit").val('');
         $("#time_frameedit").val('');
       }
   }
   
   $("#suggesstion-box-edit-patient").on('change', '#patient_idedit', function() {
      $('#doctor_idedit').val('').trigger('change');
      $('#appointment_type_idedit').val('').trigger('change');
      $(".doctor_duty_rosters_edit").empty();
      $(".available_datetime_edit").hide();
      $("#appointment_type_services").load(location.href + " #appointment_type_services");
   });
   //swapnil code here 13-09-2022
   
</script>
<!-- Swapnil Add Appoinment Code Here 16-09-2022 -->
<script>
   function assignValueToTextEdit()
   {  
     $("#time_frame2").val($("#time_frameedit option:selected").val());
     var time_frame_id = $('#time_frameedit option:selected').attr('lang');
     var time_frame_id_old = $('#roster_time_frame_id_old').val();
   
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
             $('#roster_time_frame_id1').val(time_frame_id);
           }          
         }); 
       }
   }

   function getDoctorTimeFramesEdit() {

       console.log("getDoctorTimeFramesEdit");
       var patient_id = $("#patient_idedit").val();
       var doctor_id = $("#doctor_idedit").val();
       var appointment_type_id = $("#appointment_type_idedit").val();
       var doctor_status = $('#doctor_idedit option:selected').attr('lang');
       var appointment_date = $("#appointment_dateedit").val();
       var sel_time_frame = $("#sel_time_frame").val();
       var edit_appointment_id = $("#edit_appointment_id").val();

       if (appointment_type_id != "" )   
         { 

            var a_id = '';  
            GetServices(appointment_type_id,patient_id,a_id); 
         }
       if(doctor_status == 0)
       {
         $(".active_status").hide();
         $(".inactive_status").show();
       
         return false;
       }else
       {
         $(".active_status").show();
         $(".inactive_status").hide();
       }
       //return false;
       if (doctor_id != "" && appointment_type_id != "" && appointment_date != "") {
         var action = ADMINURL + '/appointment/getDoctorTimeFrames';
         $('.modal-content').LoadingOverlay("show", {
           background: "rgba(165, 190, 100, 0.4)",
         });
   
         axios.post(action, {
             patient_id: patient_id,
             doctor_id: doctor_id,
             appointment_type_id: appointment_type_id,
             appointment_date: appointment_date,
             sel_time_frame: sel_time_frame,
             edit_appointment_id: edit_appointment_id,
           })
           .then(response => {
             $('.modal-content').LoadingOverlay("hide");
             $("#time_frameedit").empty();
             $("#time_frameedit").html(response.data.html);
             if (response.data.msg) {
               toastr.error(response.data.msg);
            }
             
           })
           .catch(error => {
            $('.modal-content').LoadingOverlay("hide");
           })
       }
       return false;
     }

     function GetServices(appointment_type_id,patient_id,edit_appointment_id)
     {  
       $.ajax({
         type: "POST",
         headers: {
         'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
         },
         url: ADMINURL + '/appointment/getServices',
         data: 'appointment_type_id=' + appointment_type_id+'&patient_id='+patient_id+'&a_id='+edit_appointment_id, 
         success: function (response) 
         {
           $(".appointment_type_services").html(response.services);
         }          
       });  
     }
</script>