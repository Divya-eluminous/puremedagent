@extends('admin.layout.master')

@section('title')
   {{ $moduleTitle }}  
@endsection
@section('content') 
<!-- Main content -->        
<section class="content">
<div class="container-fluid">
    <div class="row appointment-loader-ap_create">
    <!-- left column -->
        <div class="col-md-12">
        <!-- jquery validation -->
            <div class="card card-primary">   
                <div class="card-header">
                    <h3 class="card-title">{{ $formTitle }}</h3>
                    <button class="btn btn-light float-right" onclick="window.history.back()">@lang('admin.TITLE_BACK_BUTTON')</button> 
                </div>
        
                <form id="frmAppointment" role="form" data-toggle="validator" action="{{ route($modulePath . 'store') }}">
                    <div class="card-body appointment-loader">
                        <div class="row">


                        <div class="col-sm-6">
                           <div class="form-group">
                              <div class="form-check"> 
                                 <input type="checkbox" class="form-check-input" id="new_patient_chkbox"
                                    name="new_patient_chkbox" value="1" 
                                    >
                                 <label class="form-check-label" for="new_patient_chkbox">Termin für neue Patientin anlegen</label>
                              </div>
                           </div>
                        </div>
                            <div class="col-sm-2 patient_details" style="display: none;"> 
                                <div class="form-group selector">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_COUNTRY_CODE') <span class="required">*</span></label> 
                                    <div class="select-editable">
                                        <select 
                                        class="form-control my-select"
                                        name="country_code"
                                        id="country_code"
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_COUNTRY_CODE_REQUIRED')"
                                         onchange="handleCountrySelect(this)">
                                        
                                        @php
                                            $selectedCode = old('country_code', $country_codes[0] ?? '');
                                            $showOther = $selectedCode !== '' && !in_array($selectedCode, $country_codes);
                                        @endphp
                                        @foreach($country_codes as $code)
                                            <option value="{{ $code }}" {{ !$showOther && $selectedCode == $code ? 'selected' : '' }}>{{ $code }}</option>
                                        @endforeach
                                        <option value="other" {{ $showOther ? 'selected' : '' }}>Weitere</option>
                                    </select>
                                    <input  
                                        type="text" 
                                        name="format"
                                        id="format"  
                                        class="form-control"
                                        placeholder="@lang('admin.TITLE_PATIENT_COUNTRY_CODE')"   
                                        required
                                         value='{{ $showOther ? $selectedCode : ($selectedCode ?? '') }}'
                                        maxlength="5" 
                                        pattern="^(\+[1-9][0-9]*|00[1-9][0-9]*)$"
                                        data-error="@lang('admin.ERR_COUNTRY_CODE_REQUIRED')"
                                        data-pattern-error ="@lang('admin.ERR_COUNTRY_CODE_WRONG')"  />
                                    </div>
                                     
                                    <span class="help-block invalid-feedback with-errors" >
                                        <ul class="list-unstyled">
                                            <li class="err_format"></li>
                                        </ul>
                                    </span>
                                </div> 
                            </div>
                            <div class="col-sm-4 patient_details" style="display: none;"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_MOBILE_NO') <span class="required">*</span></label> 
                                    <input  
                                        type="text" 
                                        name="mobile_no"
                                        id="mobile_no"  
                                        class="form-control"  
                                        maxlength="15" 
                                        pattern="^(?!0{2})0?[0-9]+$"
                                        data-error="@lang('admin.ERR_MOBILE_NO_REQUIRED')" 
                                        data-pattern-error="@lang('admin.ERR_MOBILE_NO_INVALID')"
                                    >
                                    <span id="validateNumber"></span>
                                    <span class="help-block invalid-feedback with-errors" >
                                        <ul class="list-unstyled">
                                            <li class="err_mobile_no"></li>
                                        </ul>
                                    </span>
                                </div> 
                            </div>
                        <div class="col-sm-6 patient_details" style="display: none;">  
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_FAMILY_NAME') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="family_name" 
                                        class="form-control"   
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_PATIENT_FAMILY_NAME_REQUIRED')" 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_family_name"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-6 patient_details" style="display: none;"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_FIRST_NAME') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="first_name" 
                                        class="form-control"  
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_FIRST_NAME_REQUIRED')" 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_first_name"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>

                            <div class="col-sm-6 patient_details" style="display: none;"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_EMAIL') <span class="required">*</span></label>
                                    <input 
                                        type="email" 
                                        name="email" 
                                        class="form-control" 
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_EMAIL_REQUIRED')"

                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_email"></li>
                                        </ul>
                                    </span> 
                                </div>
                            </div>

                            <div class="col-sm-6 patient_details" style="display: none;"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_BIRTH_DATE') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="birth_date" 
                                        class="form-control"
                                        id="birth_date"  
                                        maxlength="250"
                                        autocomplete="off" 
                                        placeholder="DD-MM-YYYY"
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_birth_date"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>

                            <div class="col-sm-6 patient_details" style="display: none;"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_ENSURANCE_NUMBER') </label>
                                    <input 
                                        type="text" 
                                        name="insurance_number" 
                                        class="form-control" 
                                        maxlength="250" 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_insurance_number"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>


                         

                             <div class="col-sm-3" id="suggesstion_patient_div_id">
                               <div class="form-group">
                                  <label>@lang('admin.TITLE_APPOINTMENT_PATIENT') <span class="required">*</span></label>  
                                  <div class="frmSearch">   
                                     <input type="search" placeholder="@lang('admin.TITLE_SEARCH_TEXT')" id="suggesstion_patient_id" name="suggesstion_patient_id" class="form-control" autocomplete="off"
                                    
                                        >
                                     <div id="suggesstion-box-patient" style="margin-top: 2%"></div>
                                  </div>
                                  <span class="help-block invalid-feedback with-errors">
                                     <ul class="list-unstyled">
                                        <li class="err_patient_id"></li>
                                     </ul>
                                  </span>
                               </div>
                            </div>
                             
                            <div class="col-sm-3" id="search_birth_date_div">
                              <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_BIRTH_DATE') </label>
                                    <input 
                                        type="text" 
                                        
                                        class="form-control"
                                        id="search_birth_date"  
                                        maxlength="250"  
                                        autocomplete="off"  placeholder="DD-MM-YYYY"                                    
                                    >
                              </div>
                            </div>
                            @if (Auth::user()->hasRole('super-admin') || (Auth::user()->hasRole('Assistant')) || (Auth::user()->hasRole('Doctor')) || (Auth::user()->hasRole('Lead-Assistant')))
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>@lang('admin.TITLE_APPOINTMENT_DOCTOR') <span class="required">*</span></label> 
                                    <select 
                                        name="doctor_id" 
                                        id="doctor_id"  
                                        data-error="@lang('admin.ERR_APPOINTMENT_DOCTOR_REQUIRED')"
                                        class="form-control select2" 
                                       
                                        >
                                        <option value="">@lang('admin.TITLE_SELECT_DOCTOR')</option>
                                        @foreach($user as $users)
                                        <option value="{{ $users->id }}" lang="{{ $users->status }}">{{ $users->first_name . ' ' . $users->last_name}}</option>
                                        @endforeach
                                    </select> 
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_doctor_id"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            @endif
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label>@lang('admin.TITLE_APPOINTMENT_TYPE') <span class="required" >*</span></label>  
                                    <select 
                                        name="appointment_type_id" 
                                        id="appointment_type_id"  
                                        data-error="@lang('admin.ERR_APPOINTMENT_TYPE_REQUIRED')"
                                        class="form-control select2" 
                                       
                                        ><option value="">@lang('admin.TITLE_ROSTER_SELECT_APPOINTMENT_TYPE')</option>
                                        @foreach($appointment_type as $appointment_types)
                                        <option value="{{ $appointment_types->id }}" data-optimal-appointment="{{ $appointment_types->optimal_appointment }}">{{ $appointment_types->name}} ({{ $appointment_types->duration }})</option>
                                        @endforeach
                                    </select> 
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_appointment_type_id"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-6" id="appointment_date_calender"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_APPOINTMENT_DATE') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        name="date" 
                                        class="form-control old_appointment_date_added"
                                        id="appointment_date"  
                                        autocomplete="off"                                      
                                       
                                        data-error="@lang('admin.ERR_APPOINTMENT_DATE_REQUIRED')."  readonly="readonly" style="background-color:white;"
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_date"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-6" id="appointment_time_slot"> 
                                <div class="form-group">
                                    <label>@lang('admin.TITLE_APPOINTMENT_TIME_FRAME') <span class="required">*</span></label>  
                                  <!--   <select 
                                        name="time_frame"
                                        id="time_frame"
                                        class="form-control active_status" 
                                        data-placeholder="@lang('admin.TITLE_SELECT_TEXT') @lang('admin.TITLE_ROSTER_TIME_FRAME')"
                                        onchange="assignValueToText()" 
                                        style="width: 100%;"
                                        >
                                     <option value="">@lang('admin.TITLE_SELECT_TIME_FRAME_TEXT')</option>
                                    </select>  -->
                                    
                                    <input type="hidden" 
                                        name="roster_time_frame_id"
                                        id="roster_time_frame_id"  
                                        class="form-control inactive_status timepicker"  
                                       value=""
                                       />
                                    <input type="time" 
                                        name="time_frame"
                                        id="time_frame1"  
                                        class="form-control inactive_status timepicker"  
                                        maxlength="12" 
                                       value=""
                                       
                                       />

                                     <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_time_frame"></li>
                                        </ul>
                                    </span>
                                    
                                </div> 
                            </div>
                             <!-- # Roshani Added this code #  -->
                             <div class="col-sm-6 patient_details" style="display: none;">
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_GENDER') <span class="required">*</span> </label>
                                    <select 
                                        class="form-control my-select"
                                        name="gender"                                    
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_PATIENT_GENDER_REQUIRED')" >
                                        <option value="" name="">@lang('admin.TITLE_SELECT_GENDER')</option>
                                        <option value="M">M</option>
                                        <option value="W">W</option>
                                        <option value="D">D</option>
                                    </select>
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_gender"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <!-- # Roshani Added this code #  -->
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_APPOINTMENT_NOTE')</label> 
                                    <textarea
                                        type="text" 
                                        name="notes" 
                                        class="form-control" 
                                    ></textarea>
                                    <!--  required
                                        data-error="@lang('admin.ERR_APPOINTMENT_NOTE_REQUIRED')"  -->
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_notes"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            <!-- # Roshani Added this code #  CR #102-->

                            <!-- <div class="col-sm-6 patient_details" style="display: none;" > 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_COUNTRY') <span class="required">*</span></label>
                                    <select 
                                        class="form-control my-select"
                                        name="country"
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_COUNTRY_REQUIRED')" >
                                        <option value="" name="">@lang('admin.TITLE_SELECT_COUNTRY')</option>
                                        <option value="Austria">@lang('admin.TITLE_COUNTRY_AUSTRIA')</option>
                                        <option value="Germany">@lang('admin.TITLE_COUNTRY_GERMANY')</option>
                                        <option value="Switzerland">@lang('admin.TITLE_COUNTRY_SWITZERLAND')</option>
                                    </select>
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_country"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div> -->
                            <!-- # Roshani Added this code #  CR #102-->
                           
                            <div class="col-sm-6">
                               <div class="form-group appointment_type_services" id="appointment_type_services">
                                  
                               </div>
                            </div>
                        </div> 

                     <!----start code added by swapnil 20-09-2022 --->
                     <div class="row" id="optimal_checkbox">
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
                                        value="1" 
                                        checked
                                        > 
                                        <label class="form-check-label" for="status">@lang('admin.TITLE_APPOINTMENT_STATUS_ACTIVE')</label>
                                    </div>
                                </div>
                            </div>
                        <div class="col-sm-6">
                           <div class="form-group mb-0">
                              <label class="theme-blue mb-0"> 
                              @lang('admin.TITLE_OPTIMAL_APPOINTMENT')</label>
                              <input type="checkbox" class="form-check-input" id="quarter_setting_check"
                                 name="quarter_setting_check" value="1" checked style="margin-left: 10px">
                           </div>
                        </div>
                     </div>
                     <!----start code added by swapnil 20-09-2022 --->
                     <!---------start code added by swapnil 15-09-2022 ------------->
                     <div class="row">
                        <div class="col-sm-12">
                           <div class="form-group">
                              <input type="hidden" id="dr_not_available" value="">
                           </div>
                        </div>
                     </div>
                     <div id="available_datetime">
                        <div class="row">
                           <div class="col-sm-6">
                              <div class="form-group">
                                 <label class="theme-blue">
                                 @lang('admin.TITTLE_FORM_DATE') <span class="required">*</span> </label>
                                 <input
                                    type="text"
                                    name=""
                                    class="form-control"
                                    onchange ="getfirstdate(elements)"
                                    id="appointment_from_date"
                                    autocomplete="off" readonly="readonly" style="background-color:white;">
                                 <input type="hidden"  id="time_frame" class="new_appointment_datetime_added">
                                 <input type="hidden" id="appointment_date_new" class="new_appointment_date_added">
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
                                    id="appointment_to_date"
                                    onchange ="getseconddate(elements)"
                                    autocomplete="off" readonly="readonly" style="background-color:white;">
                              </div>
                           </div>
                        </div>
                     </div>
                     <div class="table-responsive table_bottom" id="doctor_duty_rosters">
                     </div>
                     <div id="quarterSetting" data-quarter-setting="{{ $quarter_setting }}"></div>
                     <!-----------end code added by swapnil 15-09-2022 ----------------------------->

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
    var sel_time_frame = "";

    $('#new_patient_chkbox').click(function(){
          if($(this).prop("checked") == true){
              $(".patient_details").show();
              $("#suggesstion_patient_div_id").hide();
               $("#search_birth_date_div").hide();
          }
          else if($(this).prop("checked") == false){
              $(".patient_details").hide();
              $("#suggesstion_patient_div_id").show();
               $("#search_birth_date_div").show();
          }
      });
</script>

<!----------------Below all script added by swapnil--------------------------------------->
<script>

   var daterequiredaptcreate =  "{{ __('admin.ERR_APPOINTMENT_DATE_REQUIRED') }}";  

   $('#appointment_date').datepicker({
      // dateFormat: 'yy-mm-dd', //commented on 10jan23
       dateFormat: 'dd-mm-yy', //swapnil added on 10-jan-2022
      orientation: "bottom",
      autoclose: true,
      todayHighlight: true,
      startDate: new Date(),
    //  minDate: 0 //commented on 10jan23
   });
   
   $(function() {
   $("#appointment_date_calender").hide();
   $("#appointment_time_slot").hide();
   $("#doctor_duty_rosters").empty();
   $("#available_datetime").hide();
   });
   //Doctor Select
   $('#doctor_id').on('change', function() {
   $("#available_datetime").hide();
   $("#doctor_duty_rosters").hide();
    var doctor_id = this.value;
    var appointment_type_id = $('#appointment_type_id').find(":selected").val();
    if (doctor_id.length == 0 || appointment_type_id.length == 0) {
      return false;
    } else {
      commonFunctionDoctorAppointment(doctor_id, appointment_type_id);
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
            $("#quarter_setting_check").val(1);
            $("#quarter_setting_check").prop('checked', true);
        } else {
            $("#quarter_setting_check").val(0);
            $("#quarter_setting_check").prop('checked', false);
        }
        
    }

    // 
   $("#available_datetime").hide();
   $("#doctor_duty_rosters").hide();
   var appointment_type_id = this.value;
   var doctor_id = $('#doctor_id').find(":selected").val();
   var patient_id = $('#patient_id').find(":selected").val();
   if (doctor_id.length == 0 || appointment_type_id.length == 0) {
      return false;
   } else {
      commonFunctionDoctorAppointment(doctor_id, appointment_type_id);
   }

   if(appointment_type_id != "" ) 
   {
      var a_id = '';
      GetServices(appointment_type_id,patient_id,a_id);
   }
   });


   //function added by divya onchange of checkbox
   $('#quarter_setting_check').on('click', function() {

         var validUser = {{ $validUser }};
         console.log(validUser);   


         var quarter_setting_check = $("#quarter_setting_check").val();
         if(quarter_setting_check==0)
         {
            // $("#quarter_setting_check").val(1);
            // $("#quarter_setting_check").attr('checked', 'checked');
            // added by vijay 8/3/24
            if(validUser==0){
                toastr.error("{{ __('admin.ERR_ROLE_NOT_ALLOWED') }}");
                return false;
            }else{
               $("#quarter_setting_check").val(1);
               $("#quarter_setting_check").attr('checked', 'checked');
            }
         }else{

            //commented below code on 12-jan-24 (15-jan-24)    
            // $("#quarter_setting_check").val(0);
            // $("#quarter_setting_check").removeAttr('checked');

            //added if else condition on 12-jan-24 (15-jan-24)
             console.log("in===>"+validUser);
            if(validUser==1)
            {
             $("#quarter_setting_check").val(0);
             $("#quarter_setting_check").removeAttr('checked');
            }
            else
            {
                toastr.error("{{ __('admin.ERR_ROLE_NOT_ALLOWED') }}");
                return false;   
            }

         }  
   
         var doctor_id = $('#doctor_id').find(":selected").val(); 
         var appointment_type_id = $('#appointment_type_id').find(":selected").val();
         if (doctor_id.length == 0 || appointment_type_id.length == 0) {
            return false;
         } else {
            commonFunctionDoctorAppointment(doctor_id, appointment_type_id);
         }
   });//quarter setting check function 

   function commonFunctionDoctorAppointment(doctor_id, appointment_type_id) {
   //common code doctor select
   $("#time_frame").val('');
   $("#appointment_date_new").val('');
   $("#time_frame1").val('');
   var doctor_id = doctor_id;
   var doctor_status = $('#doctor_id option:selected').attr('lang');
   var patient_id = $('#patient_id').find(":selected").val();
   var appointment_type_id = appointment_type_id;
   var patient_id_value = $('#suggesstion_patient_id').val();
   var calender_search = 0;
   var appointment_from_date = $("#appointment_from_date").val();
   var appointment_to_date = $("#appointment_to_date").val();
   var quarter_setting_check = $("#quarter_setting_check").val();   // Added by divya on 19 sept 22
    if (doctor_status == 1) {
      $("#optimal_checkbox").show();
      $('.new_appointment_date_added').attr('name', 'date');
      $(".old_appointment_date_added").removeAttr('name');
      
      $('.new_appointment_datetime_added').attr('name', 'time_frame');
      $("#time_frame1").removeAttr('name');

      $("#appointment_date_calender").hide();
      $("#appointment_time_slot").hide();
      $("#available_datetime").show();
      //02-09-2022
      $('.appointment-loader-ap_create').LoadingOverlay("show", {
         background: "rgba(165, 190, 100, 0.4)",
      });
      //02-09-2022
      //Ajax Code 
      $("#available_datetime").hide();
      $("#doctor_duty_rosters").hide();   
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
               "quarter_setting_check":quarter_setting_check,
            },
            success: function(response) {
                if (response.count == 1) {
                  $('.appointment-loader-ap_create').LoadingOverlay("hide");
                  var data = response.data;
                  $("#appointment_from_date").val("");
                  $("#appointment_to_date").val("");
                  $("#dr_not_available").val("")
                  if (appointment_type_id.length == 0) {
                  $("#available_datetime").hide();
                  $("#doctor_duty_rosters").hide();
                  } else {
                  $("#available_datetime").show();
                  $("#doctor_duty_rosters").show();
                  }
                  $("#appointment_from_date").val(response.calender_date1);
                  $("#appointment_to_date").val(response.calender_date2);
                  //get doctor time frame
                  $("#doctor_duty_rosters").empty();
                  $("#doctor_duty_rosters").html(response.html);
                  //get doctor time frame
                  //Start code added by divya on 19sept22
                  $("#appointment_from_date" ).datepicker( "destroy");
                  $("#appointment_to_date" ).datepicker( "destroy");
                  //End code added by divya on 19sept22
                  $('#appointment_from_date').datepicker({
                    // dateFormat: 'yy-mm-dd', //swapnil commented on 10-jan-2023
                      dateFormat: 'dd-mm-yy', //swapnil added on 10-jan-2023
                     orientation: "bottom",
                     autoclose: true,
                     todayHighlight: true,
                    // minDate: response.hidedate     //Commented by divya on 19 sept 22
                     minDate: response.calender_date1   //Added by divya on 19 sept 22
                  });
                  $('#appointment_to_date').datepicker({
                    // dateFormat: 'yy-mm-dd', //swapnil commented on 10-jan-2023
                     dateFormat: 'dd-mm-yy', //swapnil added on 10-jan-2023
                     orientation: "bottom",
                     autoclose: true,
                     todayHighlight: true,
                     //minDate: response.hideenddate   //Commented by divya on 19 sept 22
                     minDate: response.calender_date2  //Added by divya on 19 sept 22
                  });

                  $('button[type="submit"]').click(function(){
                     var timedateframe = $("#appointment_date_new").val();
                     var doctor_status = $('#doctor_id option:selected').attr('lang');
                     if (doctor_status == 1)
                     {
                        if(timedateframe.length==0)
                        {
                           toastr.error(daterequiredaptcreate);
                        }
                     }
                  });
   
   
                } else {
                  $('.appointment-loader-ap_create').LoadingOverlay("hide");
                  $("#doctor_duty_rosters").empty();
                  $("#appointment_from_date").val(null);
                  $("#appointment_to_date").val(null);
                  $("#available_datetime").hide();
                  toastr.error("{{ __('admin.ERR_TIME_FRAME_NOT_FOUND') }}");
                  $("#dr_not_available").val('1');
                }
            }
        });
        //Ajax Code
    } else if (doctor_status == "undefined") {
      $("#appointment_date_calender").hide();
      $("#appointment_time_slot").hide();
      $("#doctor_duty_rosters").empty();
      $("#available_datetime").hide();
      $("#optimal_checkbox").hide(); // Added by divya on 19sept22
    } else if (doctor_id == "") {
      $("#appointment_date_calender").hide();
      $("#appointment_time_slot").hide();
      $("#doctor_duty_rosters").empty();
      $("#available_datetime").hide();
       $("#optimal_checkbox").hide(); // Added by divya on 19sept22
    } else {

      $('.old_appointment_date_added').attr('name', 'date');
      $(".new_appointment_date_added").removeAttr('name');

      $('#time_frame1').attr('name', 'time_frame');
      $(".new_appointment_datetime_added").removeAttr('name');

      $("#doctor_duty_rosters").empty();
      $("#appointment_date_calender").show();
      $("#appointment_time_slot").show();
      $("#available_datetime").hide();
      $("#dr_not_available").val("");
      $("#appointment_date").val('');
      $("#optimal_checkbox").hide(); // Added by divya on 19sept22
      $('#appointment_date').datepicker({
        // dateFormat: 'yy-mm-dd',  //swapnil commented code on 10-jan-2023
         dateFormat: 'dd-mm-yy', //swapnil added on 10-jan-2023
         orientation: "bottom",
         autoclose: true,
         todayHighlight: true,
         startDate: new Date(),
        // minDate: 0 //commented by swapnil on 10-jan-2023
        });
    }
    //common code doctor select
   }
   //doctor on change code
   
   function assignValueToText(id) {
   var WEBURL = "{{ url('/') }}";
   var fram_val = "time_slot_" + id;
   // $("#time_frame").val($("#time_frame_"+id).val());
   var time_frame_id = $('#' + fram_val + ' option:selected').attr('lang');
   console.log(time_frame_id);
   if (time_frame_id) {
     $.ajax({
         type: "POST",
         headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
         },
         url: WEBURL + '/online-appointments/selectTimeFrame',
         data: 'time_frame_id=' + time_frame_id,
         success: function(response) {
            $('#time_fram_hd_id').val(time_frame_id);
         }
     });
   }
}
   
   
   function getfirstdate(elements) {
   $("#time_frame").val('');
   $("#appointment_date_new").val('');
   $("#time_frame1").val('');
   var appointment_from_date = $("#appointment_from_date").val();
   var doctor_id = $('#doctor_id').find(":selected").val();
   var patient_id = $('#patient_id').find(":selected").val();
   var doctor_status = $('#doctor_id option:selected').attr('lang');
   var appointment_type_id = $('#appointment_type_id').find(":selected").val();
   $("#appointment_to_date").val('');
   var appointment_to_date = $("#appointment_to_date").val();
   var calender_search = 1;
   $("#doctor_duty_rosters").empty();
   var quarter_setting_check = $("#quarter_setting_check").val();   // Added by divya on 19 sept 22
   $('.appointment-loader-ap_create').LoadingOverlay("show", {
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
            "quarter_setting_check":quarter_setting_check,
        },
        success: function(response) {
            var minDate2 = new Date(appointment_from_date);
            var end_date = new Date(minDate2.getFullYear(), minDate2.getMonth(), minDate2.getDate() + response.no_of_days);
            $("#appointment_to_date").datepicker("option", "minDate", end_date);
            if (response.count == 1) {
               $('.appointment-loader-ap_create').LoadingOverlay("hide");
               var data = response.data;
               $("#dr_not_available").val("");
               $("#available_datetime").show();
               $("#appointment_from_date").val(response.calender_date1);
               $("#appointment_to_date").val(response.calender_date2);
               //get doctor time frame
               $("#doctor_duty_rosters").empty();
               $("#doctor_duty_rosters").html(response.html);
               //get doctor time frame

               //Start code added by swapnil on 10-jan-23
              $("#appointment_from_date" ).datepicker( "destroy");
              $("#appointment_to_date" ).datepicker( "destroy");
              //End code added by divya on 19sept22
              $('#appointment_from_date').datepicker({
               //  dateFormat: 'yy-mm-dd', //swapnil commented on 10-jan-23
                dateFormat: 'dd-mm-yy', //swapnil added on10-jan-23
                 orientation: "bottom",
                 autoclose: true,
                 todayHighlight: true,
                // minDate: response.hidedate     //Commented by divya on 19 sept 22
                 minDate: response.calender_date1   //Added by divya on 19 sept 22
              });
              $('#appointment_to_date').datepicker({
                // dateFormat: 'yy-mm-dd', //swapnil commented on 10-jan-23
                 dateFormat: 'dd-mm-yy', //swapnil added on 10-jan-23
                 orientation: "bottom",
                 autoclose: true,
                 todayHighlight: true,
                 //minDate: response.hideenddate   //Commented by divya on 19 sept 22
                 minDate: response.calender_date2  //Added by divya on 19 sept 22
              });
                //Start code added by swapnil on 10-jan-23



               $('button[type="submit"]').click(function(){
                  var timedateframe = $("#appointment_date_new").val();
                  var doctor_status = $('#doctor_id option:selected').attr('lang');
                   if (doctor_status == 1)
                   {
                      if(timedateframe.length==0)
                      {
                         toastr.error(daterequiredaptcreate);
                      }
                   }
               });



            } else {
               $('.appointment-loader-ap_create').LoadingOverlay("hide");
               $("#doctor_duty_rosters").empty();
               $("#appointment_from_date").val(null);
               $("#appointment_to_date").val(null);
               $("#available_datetime").hide();
               toastr.error("{{ __('admin.ERR_TIME_FRAME_NOT_FOUND') }}");
               $("#dr_not_available").val('1');
            }
        }
    });
   }
   
   
   function getseconddate(elements) {
   $("#time_frame").val('');
   $("#appointment_date_new").val('');
   $("#time_frame1").val('');
   var appointment_from_date = $("#appointment_from_date").val();
   var appointment_to_date = $("#appointment_to_date").val();
   var doctor_id = $('#doctor_id').find(":selected").val();
   var patient_id = $('#patient_id').find(":selected").val();
   var doctor_status = $('#doctor_id option:selected').attr('lang');
   var appointment_type_id = $('#appointment_type_id').find(":selected").val();
   var quarter_setting_check = $("#quarter_setting_check").val(); // Added by divya on 19 sept 22
   var calender_search = 2;
   $("#doctor_duty_rosters").empty();
   $('.appointment-loader-ap_create').LoadingOverlay("show", {
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
            "quarter_setting_check":quarter_setting_check,
        },
        success: function(response) {
            var minDate2 = new Date(appointment_from_date);
            var end_date = new Date(minDate2.getFullYear(), minDate2.getMonth(), minDate2.getDate() + response.no_of_days);
            $("#appointment_to_date").datepicker("option", "minDate", end_date);
            if (response.count == 1) {
               $('.appointment-loader-ap_create').LoadingOverlay("hide");
               var data = response.data;
               $("#appointment_from_date").val("");
               $("#appointment_to_date").val("");
               $("#dr_not_available").text("");
               $("#available_datetime").show();
               $("#appointment_from_date").val(appointment_from_date);
               $("#appointment_to_date").val(appointment_to_date);
               //get doctor time frame
               $("#doctor_duty_rosters").empty();
               $("#doctor_duty_rosters").html(response.html);
               //get doctor time frame
               $('button[type="submit"]').click(function(){
                  var timedateframe = $("#appointment_date_new").val();
                  var doctor_status = $('#doctor_id option:selected').attr('lang');
                   if (doctor_status == 1)
                   {
                      if(timedateframe.length==0)
                      {
                         toastr.error(daterequiredaptcreate);
                      }
                   }
               });


               //Start code added by swapnil on 10-jan-23
               $("#appointment_from_date" ).datepicker( "destroy");
               $("#appointment_to_date" ).datepicker( "destroy");
               //End code added by divya on 19sept22
               $('#appointment_from_date').datepicker({
               //  dateFormat: 'yy-mm-dd', //swapnil commented on 10-jan-23
                dateFormat: 'dd-mm-yy', //swapnil added on 10-jan-23
                 orientation: "bottom",
                 autoclose: true,
                 todayHighlight: true,
                // minDate: response.hidedate     //Commented by divya on 19 sept 22
                 minDate: response.calender_date1   //Added by divya on 19 sept 22
              });
              $('#appointment_to_date').datepicker({
                // dateFormat: 'yy-mm-dd', //swapnil commented on 10-jan-23
                 dateFormat: 'dd-mm-yy', //swapnil added on 10-jan-23
                 orientation: "bottom",
                 autoclose: true,
                 todayHighlight: true,
                 //minDate: response.hideenddate   //Commented by divya on 19 sept 22
                 minDate: response.getsecond_date  //Added by divya on 19 sept 22
              });
                //End code added by swapnil on 10-jan-23


            } else {
               $('.appointment-loader-ap_create').LoadingOverlay("hide");
               $("#doctor_duty_rosters").empty();
               $("#appointment_from_date").val(null);
               $("#appointment_to_date").val(null);
               $("#available_datetime").hide();
               toastr.error("{{ __('admin.ERR_TIME_FRAME_NOT_FOUND') }}");
               $("#dr_not_available").val('1');
            }
        }
    });
   }
   
   function gettimetoradiobutton_old_26_09_2022(element) 
   {
      var timeSlot = $("#time_slot_" + element).val();
      var rvalue = $('input[name="select_appointment"]:checked').attr('data_time_timeslotradio');
      $('#select_appointment_' + element).attr('data-select_appointment_timeslot', timeSlot);
      var data_time_timeslot = $("#time_slot_" + element).attr('data_time_timeslot');
      let data_time_timeslotradio = $('input[name="select_appointment"]:checked').attr('data_time_timeslotradio');
      var data_time_frame = $("#time_frame").attr('data_time_frame');
      var data_time_timeslot = $("#time_slot_" + element).attr('data_time_timeslot');
      var roster_time_frame_id = $('#time_slot_' + element + ' option:selected').attr('lang');
      var appointmentdatetime = $("#select_appointment_" + element).attr("data-select_appointment_timeslot");
      var appointmentdate = $("#select_appointment_" + element).attr("data-select_appointment_date");
      if (data_time_timeslotradio == data_time_timeslot) 
      {
         $("#time_frame").val(timeSlot);
         var roster_time_frame_id = $('#time_slot_' + element + ' option:selected').attr('lang');
         $("#roster_time_frame_id").val(roster_time_frame_id);
      }
      $("#appointment_date_new").val(appointmentdate);
      $("#time_frame").val(timeSlot);
      $("#roster_time_frame_id").val(roster_time_frame_id);
      $("#select_appointment_"+ element).prop("checked", true);
      $('button[type="submit"]').removeClass('disabled');
   }
   
   
   function gettimetoradiobutton(element) 
   {
      var timeSlot = $("#time_slot_" + element).val();
      var appointmentdate = $("#select_appointment_" + element).attr("data-select_appointment_date");
      var roster_time_frame_id = $('#time_slot_' + element + ' option:selected').attr('lang');
      $("#time_frame").val(timeSlot);
      $("#roster_time_frame_id").val(roster_time_frame_id);
      $("#appointment_date_new").val(appointmentdate);
      $("#roster_time_frame_id").val(roster_time_frame_id);
      $("#select_appointment_"+ element).prop("checked", true); 
      $('button[type="submit"]').removeClass('disabled');
      console.log("selected timeframe= "+timeSlot);
      console.log("selected date= "+appointmentdate);
      console.log("roster_time_frame_id= "+roster_time_frame_id);
   }
   
   function getradioselectdateTime(element) {
    if ($("#select_appointment_" + element).is(":checked")) {
        var appointmentdate = $("#select_appointment_" + element).attr("data-select_appointment_date");
        $('button[type="submit"]').removeClass('disabled');
        var appointmentdatetime = $("#select_appointment_" + element).attr("data-select_appointment_timeslot");
        if (appointmentdatetime == "undefined") {
         var appointmentdatetime = $("#select_appointment_" + element).attr("data-select_appointment_timeslot");
         var roster_time_frame_id = "";
        } else {
         var appointmentdatetime = $('#time_slot_' + element).find(":selected").val();
         var roster_time_frame_id = $('#time_slot_' + element + ' option:selected').attr('lang');
        }
        $("#appointment_date_new").val(appointmentdate);
        $("#time_frame").val(appointmentdatetime);
        $('#time_frame').attr('data_time_frame', element);
        $('#time_frame').attr('data_time_frame', element);
        $("#roster_time_frame_id").val(roster_time_frame_id);
        $(".old_appointment_date_added ").val(appointmentdate);
    } else {
      $('#time_frame').attr('data_time_frame', element);
    }
   }
   
   $("#suggesstion-box-patient").on('change', '#patient_id', function() {
   $('#doctor_id').val('').trigger('change');
   $('#appointment_type_id').val('').trigger('change');
   $("#appointment_date_calender").hide();
   $("#appointment_time_slot").hide();
   $("#appointment_date_calender").hide();
   $("#appointment_time_slot").hide();
   $("#doctor_duty_rosters").empty();
   $("#available_datetime").hide();
   $("#appointment_type_services").load(location.href + " #appointment_type_services");
   $("#time_frame").val('');
   $("#appointment_date_new").val('');
   $("#time_frame1").val('');
   });
   
   
   $("button[type='reset']").click(function(){
   $("#select2-doctor_id-container").html('');
   $("#select2-appointment_type_id-container").html('');
   $("#suggesstion-box-patient").html('');
   //Swapnil Added Code 21-09-2022
   $("#available_datetime").hide();
   $("#doctor_duty_rosters").hide();
   $("#appointment_date_calender").hide();
   $("#appointment_time_slot").hide();
   $("#quarter_setting_check").val(0);
   $("#quarter_setting_check").removeAttr('checked');
   $("#time_frame").val('');
   $("#appointment_date_new").val('');
   $("#time_frame1").val('');
   $("#appointment_type_services").html('');//Aishwarya added this code on 26-may-25
   });
</script>
<!--apppointment code by swapnil pawar 15-09-2022-->

<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<script type="text/javascript" src="{{ url('assets/admin/js/appointment/create-edit.js?ver=0.01') }}"></script>
@endsection



