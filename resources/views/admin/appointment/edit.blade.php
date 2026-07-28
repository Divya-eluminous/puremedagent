@extends('admin.layout.master')

@section('title')
   {{ $moduleTitle }}
@endsection
@section('content') 
<!-- Main content -->        

<?php 
   $pname = DB::table('patients')->select('first_name', 'family_name')->Where('id', $appointment->patient_id)->first();
$patient_name = $pname->first_name . ' ' . $pname->family_name;
?>  

<section class="content">
<div class="container-fluid">
    <div class="row">
        <!-- left column -->
        <div class="col-md-12">
            <!-- jquery validation -->
            <div class="card card-primary appointment-loader_edit">
                <div class="card-header">
                    <h3 class="card-title">{{ $formTitle }}</h3>
                    <button class="btn btn-light float-right" onclick="window.history.back()">@lang('admin.TITLE_BACK_BUTTON')</button>
                </div>
                
                <form id="frmAppointment" role="form" data-toggle="validator" action="{{ route($modulePath . 'update', [base64_encode(base64_encode($appointment->id))]) }}">
                    <input type="hidden" name="_method" value="PUT">
                    <input type="hidden" name="a_id" id="a_id" value="{{$appointment->id}}">
                    <div class="card-body appointment-loader">
                        <div class="row">
                           <!--  <div class="col-sm-6">  
                                <div class="form-group">
                                    <label>@lang('admin.TITLE_APPOINTMENT_PATIENT')</label> 
                                    <select 
                                        name="patient_id" 
                                        id="patient_id"  
                                        required
                                        data-error="@lang('admin.ERR_APPOINTMENT_PATIENT_REQUIRED')"
                                        class="form-control" 
                                        >
                                        <option value="">@lang('admin.TITLE_SELECT_PATIENT')</option>
                                        @foreach($patient as $patients)
                                        <option value="{{ $patients->id }}" @if($patients->id==$appointment->patient_id) selected @endif>{{ $patients->first_name .' '. $patients->family_name}}</option>
                                        @endforeach
                                    </select> 
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_patient_id"></li>
                                        </ul>
                                    </span> 
                                </div>
                            </div> -->


                          <!--------------Below patient field code added by swapnil on 3 oct 22------------>

                            <div class="col-sm-6">
                             <!-- patients dropdown-->
                               <div class="form-group">
                                  <label>@lang('admin.TITLE_APPOINTMENT_PATIENT')</label>  
                                  <div class="frmSearch">
                                     <input type="search" placeholder="@lang('admin.TITLE_SEARCH_TEXT')" id="suggesstion_patient_id" name="suggesstion_patient_id" class="form-control" value="{{ $patient_name }}" autocomplete="off">
                                     <div id="suggesstion-box-patient" style="margin-top: 2%"></div>
                                  </div>
                                  <span class="help-block invalid-feedback with-errors">
                                     <ul class="list-unstyled">
                                        <li class="err_patient_id"></li>
                                     </ul>
                                  </span>
                               </div>
                               <input  name="patient_id"  type="hidden" value="{{ $appointment->patient_id }}" id="newPatientsId">
                               <!--patients dropdown-->
                            </div> 



                            <div class="col-sm-6"> 
                                @if (Auth::user()->hasRole('super-admin') || (Auth::user()->hasRole('Assistant')) || (Auth::user()->hasRole('Doctor')) || (Auth::user()->hasRole('Lead-Assistant')))
                                <div class="form-group"> 
                                    <label>@lang('admin.TITLE_APPOINTMENT_DOCTOR')</label> 
                                    <select 
                                        name="doctor_id" 
                                        id="doctor_id"  
                                        required
                                        data-error="@lang('admin.ERR_APPOINTMENT_DOCTOR_REQUIRED')"
                                        class="form-control select2" 
                                        
                                        >
                                        <option value="">@lang('admin.TITLE_SELECT_DOCTOR')</option>
                                        @foreach($user as $users)
                                        <option value="{{ $users->id }}" lang="{{ $users->status }}"  @if($users->id == $appointment->doctor_id) selected @endif>{{ $users->first_name . ' ' . $users->last_name}}</option> 
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
                        </div>
                        <div class="row">
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label>@lang('admin.TITLE_APPOINTMENT_TYPE')</label>  
                                    <select 
                                        name="appointment_type_id" 
                                        id="appointment_type_id"  
                                        required
                                        data-error="@lang('admin.ERR_APPOINTMENT_TYPE_REQUIRED')"
                                        class="form-control select2" 
                                       
                                        >
                                        <option value="">@lang('admin.TITLE_ROSTER_SELECT_APPOINTMENT_TYPE')</option>
                                        @foreach($appointment_type as $appointment_types)
                                        <option value="{{ $appointment_types->id }}" data-optimal-appointment="{{ $appointment_types->optimal_appointment }}" @if($appointment_types->id == $appointment->appointment_type_id) selected @endif>{{ $appointment_types->name}} ({{ $appointment_types->duration }})</option>
                                        @endforeach
                                    </select> 
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_appointment_type_id"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>



                          <!--   <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_APPOINTMENT_DATE') <span class="required">*</span></label> 
                                    <input 
                                        type="text" 
                                        name="date" 
                                        class="form-control"
                                        id="appointment_date"  
                                        required
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_APPOINTMENT_DATE_REQUIRED')." 
                                        onchange ="getDoctorTimeFrames()" 
                                        value="{{ date('Y-m-d',strtotime($appointment->start_date)) }}"
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_date"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div> -->


                          <!--------------Below date field code added by swapnil on 3 oct 22------------>

                        <div class="col-sm-6">
                           <div class="form-group">
                              <label class="theme-blue"> 
                              @lang('admin.TITLE_APPOINTMENT_DATE') <span class="required">*</span></label> 
                              <!--changes made by swapnil 15-09-2022-->


                           <!--    <input 
                                 type="text" 
                                 class="form-control"
                                 id="appointment_date"  
                                 required
                                 maxlength="250" 
                                 data-error="@lang('admin.ERR_APPOINTMENT_DATE_REQUIRED')." 
                                 value="{{ date('Y-m-d',strtotime($appointment->start_date)) }}" readonly="readonly" style="background-color:white;">
                              <input 
                                 type="text" 
                                 class="form-control"
                                 id="appointment_date_new"  
                                 readonly 
                                 maxlength="250" 
                                 data-error="@lang('admin.ERR_APPOINTMENT_DATE_REQUIRED')." 
                                 value="{{ date('Y-m-d',strtotime($appointment->start_date)) }}"> -->

                                 <!--start changes made by swapnil 10-jan-23-->
                                <input 
                                 type="text" 
                                 class="form-control"
                                 id="appointment_date"  
                                 required
                                 maxlength="250" 
                                 data-error="@lang('admin.ERR_APPOINTMENT_DATE_REQUIRED')." 
                                 value="{{ date('d-m-Y', strtotime($appointment->start_date)) }}" readonly="readonly" style="background-color:white;">
                              <input 
                                 type="text" 
                                 class="form-control"
                                 id="appointment_date_new"  
                                 readonly 
                                 maxlength="250" 
                                 data-error="@lang('admin.ERR_APPOINTMENT_DATE_REQUIRED')." 
                                 value="{{ date('d-m-Y', strtotime($appointment->start_date)) }}">    
                                  <!--end changes made by swapnil 10-jan-23-->


                              <!--changes made by swapnil 15-09-2022-->
                              <span class="help-block invalid-feedback with-errors">
                                 <ul class="list-unstyled">
                                    <li class="err_date"></li>
                                 </ul>
                              </span>
                           </div>
                        </div>

                        </div> 
                        <div class="row">


                            <!--------------Below timeframe field code added by swapnil on 3 oct 22------------>

                           <!--  <div class="col-sm-6">
                                <div class="form-group">
                                    <label>@lang('admin.TITLE_APPOINTMENT_TIME_FRAME') <span class="required">*</span></label>  
                                    <select 
                                        
                                        name="time_frame"
                                        id="time_frame"
                                        class="form-control active_status" 
                                        data-placeholder="@lang('admin.TITLE_SELECT_TEXT') @lang('admin.TITLE_ROSTER_TIME_FRAME')"
                                        onchange="assignValueToText()" 
                                        style="width: 100%;"
                                        >
                                        <option selected>{{ date('H:i',strtotime($appointment->start_date)) }}</option>
                                       
                                    </select> 
                                    <input type="hidden" 
                                      name="roster_time_frame_id_old"
                                      id="roster_time_frame_id_old"  
                                      class=""  
                                     value="{{$time_frames_id}}"
                                    />
                                     <input type="hidden" 
                                      name="roster_time_frame_id"
                                      id="roster_time_frame_id"  
                                      class=""  
                                     value=""
                                     />
                                    <input type="time" 
                                        name="time_frame"
                                        id="time_frame1"  
                                        class="form-control inactive_status timepicker"  
                                        maxlength="12" 
                                       value="{{ date('H:i',strtotime($appointment->start_date)) }}"
                                       style="display: none" 
                                       />
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_time_frame"></li>
                                        </ul>
                                    </span>
                                </div> 
                            </div> -->

                            <div class="col-sm-6">
                               <div class="form-group">
                                  <label>@lang('admin.TITLE_APPOINTMENT_TIME_FRAME') <span class="required">*</span></label>  
                                  <!--changes commented by swapnil 15-09-2022-->
                                  <!--changes made by swapnil 15-09-2022-->
                                  <input  id="time_frame"
                                     class="form-control active_status" 
                                     data-placeholder="@lang('admin.TITLE_SELECT_TEXT') @lang('admin.TITLE_ROSTER_TIME_FRAME')"
                                     style="width: 100%;" value="{{ date('H:i', strtotime($appointment->start_date)) }}" readonly> 
                                  <!--changes made by swapnil 15-09-2022-->
                                  <input type="hidden" 
                                     name="roster_time_frame_id_old"
                                     id="roster_time_frame_id_old"  
                                     class=""  
                                     value="{{$time_frames_id}}">
                                  <input type="hidden" 
                                     name="roster_time_frame_id"
                                     id="roster_time_frame_id"  
                                     class=""  
                                     value="">
                                  <input type="time" 
                                     id="time_frame1"  
                                     class="form-control inactive_status timepicker"  
                                     maxlength="12" 
                                     value="{{ date('H:i', strtotime($appointment->start_date)) }}"
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
                        </div>   
                        <div class="row">
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_APPOINTMENT_STATUS')</label>
                                    <div class="form-check"> 
                                        <input 
                                            type="checkbox" 
                                            class="form-check-input" 
                                            id="status"
                                            name="status"  
                                            value="1" @if(!empty($appointment->status) && $appointment->status == 1) checked @endif
                                        >
                                        <label class="form-check-label" for="status">@lang('admin.TITLE_APPOINTMENT_STATUS_ACTIVE')</label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-6">
                               <div class="form-group appointment_type_services" id="appointment_type_services">
                                  
                               </div>
                            </div>
                        </div> 

                        <div class="row">
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
                        </div>

                        <br/>
                        <div class="row">
                            <span id="doctor_not_avaliable_msg" style="display: none"></span>
                        </div>

                          <!---------start code added by swapnil 03-oct-2022 ---------------------------->
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

                                            <input
                                            type="text"
                                            name=""
                                            class="form-control"
                                            onchange ="getfirstdate(elements)"
                                            id="appointment_from_date_edit"
                                            value="{{ date('d-m-Y', strtotime($appointment->start_date)) }}"
                                            autocomplete="off" readonly="readonly" style="background-color:white;">

                                    <!--changes made by swapnil 10-jan-23 above for d-m-Y only-->
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
                             <div class="table-responsive table_bottom doctor_duty_rosters_edit" id="doctor_duty_rosters">
                             </div>
                             <div id="quarterSetting" data-quarter-setting="{{ $quarter_setting }}"></div>
                        <!-----------end code added by swapnil 03-oct-2022 ---------------------------------->


                    </div><!-- /.card-body -->

                    <div class="card-footer">
                        <button type="submit" id="appointmentSubmitButton" class="btn btn-success">@lang('admin.TITLE_SAVE_BUTTON')</button>
                        <button type="reset" class="btn btn-danger">@lang('admin.TITLE_RESET_BUTTON')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>    
</section>
@endsection



@php 
    $validUser = 0;
 @endphp
 @if(auth()->user()->can('optimal-appointment'))
   @php 
    $validUser = 1;
   @endphp
 @endif


@section('scripts')
<!-- ############## Roshani Added this code (22/02/2024) C) User settings ################ -->
<script type="text/javascript" src="{{ url('assets/admin/js/dashboard/commonJsForApp.js') }}"></script>
<!-- ############## Roshani Added this code (22/02/2024) C) User settings ################ -->
<script type="text/javascript">
   // added by vijay 6/3/24
    $(document).ready(function(){
        $('#appointment_type_id').trigger('change');
    });
    var sel_time_frame = "{{ date('H:i', strtotime($appointment->start_date)) }}";
    $(function(){
    //     var appointment_type_id = '{{$appointment->doctor_id}}';
    //     var p_id = $("#patient_id").val();
    //     getDoctorTimeFrames();
    // });


      //03-10-2022
      var hd_edit_appointment_type_id = $('#appointment_type_id').find(":selected").val();
      var hd_patient_id = $('#newPatientsId').val();
      var hd_edit_appointment_id = $("#a_id").val();
      GetServices(hd_edit_appointment_type_id,hd_patient_id,hd_edit_appointment_id);
      //03-10-2022

      
      var patient_id = $('#newPatientsId').val();
      var calender_search = 0;
      var appointment_from_date = $("#appointment_from_date_edit").val();
      var appointment_to_date = $("#appointment_to_date_edit").val();
      var doctor_id = $('#doctor_id option:selected').val();
      var doctor_status = $('#doctor_id option:selected').attr('lang');
      var appointment_type_id = $('#appointment_type_id').find(":selected").val();
      if(doctor_status=='1')
      { 
         $("#optimal_checkbox_div").show(); // Added by divya on 19sept22
         //Not Emergency Doctor
         $(".available_datetime_edit").show();
         $(".doctor_duty_rosters_edit").show();
         $("#time_frame1").hide();
         $("#time_frame").show();
         $("#appointment_date_new").show();
         $("#appointment_date").hide();

        
         $('#appointment_date_new').attr('name', 'date');
         $("#appointment_date").removeAttr('name');

         $('#time_frame').attr('name', 'time_frame');
         $("#time_frame1").removeAttr('name');

         if (doctor_id.length == 0 || appointment_type_id.length == 0) {
            return false;
         } else {
            editAppointmentFunction(doctor_id, appointment_type_id,1);
         }
      }
      else
      {
         $("#optimal_checkbox_div").hide(); // Added by divya on 19sept22
         //Emergency Doctor
         $(".available_datetime_edit").hide();
         $(".doctor_duty_rosters_edit").hide();
         $("#appointment_from_date_edit").val('');
         $("#time_frame1").show();
         $("#time_frame").hide();
         $("#appointment_date_new").hide();
         $("#appointment_date").show();

         $('#appointment_date').attr('name', 'date');
         $("#appointment_date_new").removeAttr('name');
         $('#time_frame1').attr('name', 'time_frame');
         $("#time_frame").removeAttr('name');

         $("#doctor_not_avaliable_msg").hide(); // Added by divya on 3oct22
      }
   });
   
   //Doctor Select
      $('#doctor_id').on('change', function() {
         $(".available_datetime_edit").hide();
         $(".doctor_duty_rosters_edit").hide();
         var doctor_id = this.value;
         var appointment_type_id = $('#appointment_type_id').find(":selected").val();
         if (doctor_id.length == 0 || appointment_type_id.length == 0) {
            return false;
         } else {
            editAppointmentFunction(doctor_id, appointment_type_id,0);
         }
      });
   
      //Appointment Type Select
      $('#appointment_type_id').on('change', function() {
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
         var doctor_id = $('#doctor_id option:selected').val();
         var patient_id = $('#newPatientsId').val();
         if (doctor_id.length == 0 || appointment_type_id.length == 0) {
            return false;
         } else {
            editAppointmentFunction(doctor_id, appointment_type_id,0);
         }
         if(appointment_type_id != "" ) 
         {
            var a_id = '';
            var edit_appointment_id = $("#a_id").val();//added on 13-feb-26
            // GetServices(appointment_type_id,patient_id,a_id); //commented on 13-feb-26
            GetServices(appointment_type_id,patient_id,edit_appointment_id);//added on 13-feb-26 for edit pap

         }
      });
   
    
   
      //function added by divya onchange of checkbox
       $('#quarter_setting_check_val').on('click', function() {

            var validUser = {{ $validUser }};
            console.log(validUser);

            var quarter_setting_check_val = $("#quarter_setting_check_val").val();
            if(quarter_setting_check_val==0)
            {
               // $("#quarter_setting_check_val").val(1);
               // $("#quarter_setting_check_val").attr('checked', 'checked');
               // added by vijay 8/3/24
               if(validUser==0){
                  toastr.error("{{ __('admin.ERR_ROLE_NOT_ALLOWED') }}");
                  return false;
               }else{
                  $("#quarter_setting_check_val").val(1);
                  $("#quarter_setting_check_val").attr('checked', 'checked');
               }
            }else{

                //commented code on 12-jan-24 (15-jan-24)     
               // $("#quarter_setting_check_val").val(0);
               // $("#quarter_setting_check_val").removeAttr('checked');

                 //added code on 12-jan-24 (15-jan-24)
                 console.log("in===>"+validUser);
                if(validUser==1)
                {
                  $("#quarter_setting_check_val").val(0);
                  $("#quarter_setting_check_val").removeAttr('checked');
                }
                else{
                   toastr.error("{{ __('admin.ERR_ROLE_NOT_ALLOWED') }}");
                   return false;  
                }

            }  
            var doctor_id = $('#doctor_id').find(":selected").val();
            var appointment_type_id = $('#appointment_type_id').find(":selected").val();
            if (doctor_id.length == 0 || appointment_type_id.length == 0) {
               return false;
            } else {
               $("#appointment_from_date_edit").val("");
               $("#appointment_to_date_edit").val("");
               editAppointmentFunction(doctor_id, appointment_type_id,0);
            }
       });//quarter setting check function 
   
       function editAppointmentFunction(doctor_id, appointment_type_id,ondocumentload) {
         //common code doctor select
         var doctor_id = doctor_id;
         var appointment_type_id = appointment_type_id;
         var doctor_status = $('#doctor_id option:selected').attr('lang');
         var quarter_setting_check = $("#quarter_setting_check_val").val(); // Added by divya on 19 sept 22
         var patient_id = $('#newPatientsId').val();
         var calender_search = 0;
         var appointment_from_date = $("#appointment_from_date_edit").val();
         var appointment_to_date = $("#appointment_to_date_edit").val();
         if (doctor_status == 1) {

               $('#appointment_date_new').attr('name', 'date');
               $("#appointment_date").removeAttr('name');

               $('#time_frame').attr('name', 'time_frame');
               $("#time_frame1").removeAttr('name');

               $("#appointment_date").hide();
               $("#time_frame1").hide();
               $("#appointment_date_new").show();
               $("#time_frame").show();
               $("#optimal_checkbox_div").show(); // Added by divya on 19sept22
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
                  async: true,
                  cache: false,
                  data: {
                     "doctor_id": doctor_id,
                     "appointment_type_id": appointment_type_id,
                     "calender_search": calender_search,
                     "appointment_from_date": appointment_from_date,
                     "appointment_to_date": appointment_to_date,
                     "patient_id": patient_id,
                     "doctor_status": doctor_status,
                     "quarter_setting_check":quarter_setting_check,
                  },
                  success: function(response) {
                      if (response.count == 1) {

                        //Added below code for hide onload err msg
                         if(ondocumentload==0)
                        {
                          $("#doctor_not_avaliable_msg").hide();
                          $("#doctor_not_avaliable_msg").html(" ");
                        }

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
                        //Start code added by divya on 19sept22
                        $("#appointment_from_date_edit" ).datepicker( "destroy");
                        $("#appointment_to_date_edit" ).datepicker( "destroy");
                        //End code added by divya on 19sept22
                        $('#appointment_from_date_edit').datepicker({
                           //dateFormat: 'yy-mm-dd', //commented on 10-jan-23
                            dateFormat: 'dd-mm-yy', //swapnil added on 10-jan-23
                           orientation: "bottom",
                           autoclose: true,
                           todayHighlight: true,
                          // minDate: response.hidedate     //Commented by divya on 19 sept 22
                           minDate: response.calender_date1   //Added by divya on 19 sept 22
                        });
                        $('#appointment_to_date_edit').datepicker({
                          // dateFormat: 'yy-mm-dd', //commented on 10-jan-23
                           dateFormat: 'dd-mm-yy', //swapnil added on 10-jan23
                           orientation: "bottom",
                           autoclose: true,
                           todayHighlight: true,
                           //minDate: response.hideenddate   //Commented by divya on 19 sept 22
                           minDate: response.calender_date2  //Added by divya on 19 sept 22
                        });
                        //get doctor time frame
                        $(".doctor_duty_rosters_edit").empty();
                        $(".doctor_duty_rosters_edit").html(response.html);
                        //get doctor time frame
                     } else {
                        $('.appointment-loader_edit').LoadingOverlay("hide");
                        $(".doctor_duty_rosters_edit").empty();
                        $(".appointment_from_date_edit").val(null);
                        $(".appointment_to_date_edit").val(null);
                        $(".available_datetime_edit").hide();
                       // toastr.error("{{ __('admin.ERR_TIME_FRAME_NOT_FOUND') }}"); //Commented on 3oct22

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
                        }


                        $("#dr_not_available_edit").val('1');
                     }
                  }
              });
            //Ajax Code
          } else if (doctor_status == "undefined") {
            $("#time_frame").hide();
            $(".doctor_duty_rosters_edit").empty();
            $(".available_datetime_edit").hide();
            $("#appointment_date").show();
            $("#appointment_date_new").hide();
            $("#time_frame1").show();
            $("#time_frame").hide();
            $("#optimal_checkbox_div").hide(); // Added by divya on 19sept22
          } else if (doctor_id == "") {
            $("#time_frame").hide();
            $(".doctor_duty_rosters_edit").empty();
            $(".available_datetime_edit").hide();
            $("#appointment_date").show();
            $("#appointment_date_new").hide();
            $("#time_frame1").show();
            $("#time_frame").hide();
            $("#optimal_checkbox_div").hide(); // Added by divya on 19sept22
          } else {
            $('#appointment_date').attr('name', 'date');
            $("#appointment_date_new").removeAttr('name');
            $('#time_frame1').attr('name', 'time_frame');
            $("#time_frame").removeAttr('name');

            $(".doctor_duty_rosters_edit").empty();
            $("#appointment_date").show();
            $("#appointment_date_new").hide();
            $("#time_frame1").show();
            $("#time_frame").hide();
            $(".available_datetime_edit").hide();
            $("#dr_not_available_edit").val("");
            $("#appointment_date_edit").val('');
            $("#optimal_checkbox_div").hide(); // Added by divya on 19sept22
            $('#appointment_dateedit').datepicker({
              // dateFormat: 'yy-mm-dd', //commented on 10-jan23
              dateFormat: 'dd-mm-yy', //swapnil added on 10-jan-23
               orientation: "bottom",
               autoclose: true,
               todayHighlight: true,
               startDate: new Date(),
             //  minDate: 0  //commented on 10-jan23
            });

            $("#doctor_not_avaliable_msg").hide();  // Added by divya on 3oct22
            $("#doctor_not_avaliable_msg").html(" "); // Added by divya on 3oct22

          }
         //common code doctor select
      }
   //doctor on change code
   //from date select
   function getfirstdate(elements) {
   var appointment_from_date = $("#appointment_from_date_edit").val();
   var doctor_id = $('#doctor_id option:selected').val();   
   var patient_id = $('#newPatientsId').val();
   var doctor_status = $('#doctor_id option:selected').attr('lang');
   var appointment_type_id = $('#appointment_type_id').find(":selected").val();
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
      async: true,
      cache: false,
      data: {
         "doctor_id": doctor_id,
         "appointment_type_id": appointment_type_id,
         "calender_search": calender_search,
         "appointment_from_date": appointment_from_date,
         "appointment_to_date": appointment_to_date,
         "patient_id": patient_id,
         "doctor_status": doctor_status,
         "quarter_setting_check":quarter_setting_check,
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

         //swapnil added on 10-jan-23
         $("#appointment_from_date_edit" ).datepicker( "destroy" );
         $("#appointment_to_date_edit" ).datepicker( "destroy" );
         //swapnil added on 10-jan-23


           $('#appointment_from_date_edit').datepicker({
           // dateFormat: 'yy-mm-dd', //swapnil commented on 10-jan-23
            dateFormat: 'dd-mm-yy', //swapnil added on 10-jan-23
            orientation: "bottom",
            autoclose: true,
            todayHighlight: true,
           // minDate: response.hidedate
            minDate: response.calender_date1  // added by swapnil on 10-jan-23
           });

           $('#appointment_to_date_edit').datepicker({
           // dateFormat: 'yy-mm-dd', /swapnil commented on 10-jan-23
            dateFormat: 'dd-mm-yy', //swapnil added on 10-jan-23
            orientation: "bottom",
            autoclose: true,
            todayHighlight: true,
           // minDate: response.hideenddate /swapnil commented on 10-jan-23
            minDate: response.calender_date2  // added by swapnil on 10-jan-23
           });
         //get doctor time frame
         $(".doctor_duty_rosters_edit").empty();
         $(".doctor_duty_rosters_edit").html(response.html);
         //get doctor time frame
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
   var patient_id = $('#newPatientsId').val();
   var doctor_id = $('#doctor_id option:selected').val();
   var doctor_status = $('#doctor_id option:selected').attr('lang');
   var appointment_type_id = $('#appointment_type_id').find(":selected").val();
    var appointment_from_date = $("#appointment_from_date_edit").val();
    var appointment_to_date = $("#appointment_to_date_edit").val();
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
        async: true,
        cache: false,
        data: {
         "doctor_id": doctor_id,
         "appointment_type_id": appointment_type_id,
         "calender_search": calender_search,
         "appointment_from_date": appointment_from_date,
         "appointment_to_date": appointment_to_date,
         "patient_id": patient_id,
         "doctor_status": doctor_status,
         "quarter_setting_check":quarter_setting_check,
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

            //Start code added by swapnil on 10-jan23
            $("#appointment_from_date_edit" ).datepicker( "destroy");
            $("#appointment_to_date_edit" ).datepicker( "destroy");
            //End code added by divya on 19sept22
            $('#appointment_from_date_edit').datepicker({

              // dateFormat: 'yy-mm-dd', //commented on 10-jan23
               dateFormat: 'dd-mm-yy', //swapnil added on 10-jan23

               orientation: "bottom",
               autoclose: true,
               todayHighlight: true,
              // minDate: response.hidedate     //Commented by swapnil on 15-nov-22
               minDate: response.calender_date1   //Added by swapnil on 15-nov-22
            });
            $('#appointment_to_date_edit').datepicker({
              // dateFormat: 'yy-mm-dd', //commented by swapnil on 10-jan23
               dateFormat: 'dd-mm-yy', //swapnil added on 10-jan23

               orientation: "bottom",
               autoclose: true,
               todayHighlight: true,
               //minDate: response.hideenddate   //Commented by swapnil on 10-jan23
               minDate: response.getsecond_date  //Added by swapnil on 10-jan23
            });

            //End code added by swapnil on 10-jan23



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
   function gettimetoradiobutton(element) 
   {
      var timeSlot = $("#time_slot_" + element).val();
      var appointmentdate = $("#select_appointment_" + element).attr("data-select_appointment_date");
      var roster_time_frame_id = $('#time_slot_' + element + ' option:selected').attr('lang');
      $("#appointment_date_new").val(appointmentdate);
      $("#time_frame").val(timeSlot);
      $("#roster_time_frame_id").val(roster_time_frame_id);
      $("#select_appointment_"+ element).prop("checked", true);
      $('button[type="submit"]').removeClass('disabled');
      console.log("selected timeframe= "+timeSlot);
      console.log("selected date= "+appointmentdate);
      console.log("roster_time_frame_id= "+roster_time_frame_id);
   }
   
   function getradioselectdateTime(element) 
   {
      if ($("#select_appointment_" + element).is(":checked")) 
      {
         var appointmentdate = $("#select_appointment_" + element).attr("data-select_appointment_date");
         var appointmentdatetime = $("#select_appointment_" + element).attr("data-select_appointment_timeslot");
         if (appointmentdatetime == "undefined") 
         {
            var appointmentdatetime = $("#select_appointment_" + element).attr("data-select_appointment_timeslot");
            var roster_time_frame_id = "";
         } 
         else 
         {
            var appointmentdatetime = $('#time_slot_' + element).find(":selected").val();
            var roster_time_frame_id = $('#time_slot_' + element + ' option:selected').attr('lang');
         }
         $("#appointment_date_new").val(appointmentdate);
         $("#time_frame").val(appointmentdatetime);
       } 
      else 
      {
         $("#appointment_date_new").val('');
         $("#time_frame").val('');
      }
   }
   
   $("#suggesstion-box-patient").on('change', '#patient_id',function(){   
      var pid = this.value;
      $('#newPatientsId').val(pid);
      $('#doctor_id').val('').trigger('change');
      $('#appointment_type_id').val('').trigger('change');
      $(".doctor_duty_rosters_edit").empty();
      $(".available_datetime_edit").hide();
   });
//swapnil code here 15-09-2022 
</script>
<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<script type="text/javascript" src="{{ url('assets/admin/js/appointment/create-edit.js?ver=0.01') }}"></script>
@endsection