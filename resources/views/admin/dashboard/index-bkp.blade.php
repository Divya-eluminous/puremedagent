@extends('admin.layout.master')
@section('title')
{{ $moduleAction ?? 'Manage Dashboard' }} 
@endsection
@section('style')
<!-- fullCalendar --> 

<link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/fullcalendar/main.min.css') }}">
<!-- <link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/fullcalendar-daygrid/main.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/fullcalendar-timegrid/main.min.css') }}"> -->
<!-- <link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/fullcalendar-bootstrap/main.min.css') }}"> -->
<link rel="stylesheet" href="{{ asset('assets/plugins/qtip2/jquery.qtip.min.css') }}">
<link href="{{ asset('assets/admin-lte/plugins/fullcalendar-scheduler/main.css') }}" rel='stylesheet' />
<style type="text/css">
  a.fc-timegrid-event {
    line-height: 15px;
    height: 20px;
    overflow: auto;
    color: #fff;
    font-size: 15px!important;
    font-weight: 500;
  }
  .fc-timegrid-slots table
  {
    height: 1500px !important;
  }
  .fc-timegrid-event-condensed .fc-event-main-frame {
    flex-wrap: nowrap;
  }
  
</style>
@endsection
@section('content')

<!-- Main content -->
<section class="content">
 <!--  {{ 'Current Database Name: '.$databaseName }} -->
   <!-- <div id='loader'>Loading</div> -->
   <div class="container-fluid">
      <div class="row">

         <div class="col-lg-4">
            <label class="theme-blue"> @lang('admin.TITLE_PATIENT_TEXT')</label> 
            <!--  <select class="form-control" 
               id="patient-id"
               name="patient_id" 
               > 
               <option value="">Select Patient</option>  
               <option value=""><input type="text" name="patient"></option>  -->      
            <!--  -->
            <!-- </select> -->
            <!-- <input id="patient-id" list="patient-data-list" class="form-control">
               <datalist id="patient-data-list">
                 <option>Select Patient</option>
               </datalist> -->
            <!--  <input type="text" class="form-control"><br><br><br><br><br> -->
            <!-- My Example -->
           <!--  <div class="form-group">
                <select class="form-control select2" id="patient-id" style="width: 100%;">
                  
              
                </select>
              </div> -->
            <div class="frmSearch">   
               <input type="search" placeholder="@lang('admin.TITLE_SEARCH_TEXT')" id="patient-id" class="form-control" autocomplete="off">
               <div id="suggesstion-box" style="margin-top: 2%"></div>
            </div>
            
         </div>
        <!--  <div class="col-lg-3"> 
          <label class="theme-blue">@lang('admin.TITLE_DOCTORS_TEXT')</label>
            <div class="frmSearch">
               <input type="search" placeholder="@lang('admin.TITLE_SEARCH_TEXT')" id="doctor-id" class="form-control" autocomplete="off">
               <div id="suggesstion-box1" style="margin-top: 2%"></div>
            </div>
         </div> -->
         <div class="col-sm-2">
             <div class="form-group">
                <label class="theme-blue"> 
                @lang('admin.TITLE_APPOINTMENT_DATE') <span class="required">*</span></label>
                <input 
                   type="text" 
                   name="date" 
                   class="form-control"
                   id="goto_date"  
                   autocomplete="off"
                   onchange ="gotoDate(this)" 
                   >
             </div>
          </div>
         <div class="col-lg-3" style="margin-top: 3%"> 
            <button type="button" id="addAppbutton" class="btn fc-button-primary" data-toggle="modal" data-target="#addAppointmentModal" >
            @lang('admin.ADD_APPOINTMENT')
            </button>
            <!-- <button type="button" id="doctorAvailButton" class="btn fc-button-primary" data-toggle="modal" data-target="#doctorAvailabilityModal" >
            @lang('admin.TITLE_DOCTOR_AVAILABILITY')
            </button> -->
          </div>
      </div>
      <div class="row">
         <div class="col-md-12">
            <div id="calendar">              
            </div>

            <!-- Start The Modal -->
            <div id="appointmentModal" class="modal" role="dialog">
               <div class="modal-dialog">
                 <div class="modal-content">  
                   <div class="modal-header">
                     <h3 class="modal-title">@lang('admin.APPOINTMENT_DETAILS')
                        <i class="fa fa-edit" data-toggle="modal" id="editAppointmentModal" data-id="" data-toggle="modal" data-target="#editAppointmentDataModal" title="Edit Appointment"></i>
                        <i class="fa fa-trash"  id="deleteAppointmentModal" data-id="" title="Delete Appointment"></i>
                        <i class="fas fa-user-injured" data-toggle="modal" id="redirectToPatient" data-id="" data-toggle="modal" title="Edit Patient"></i>
                      </h3>
                        <button type="button" class="close btnClosePopup" data-dismiss="modal" aria-label="Close">
                       <span aria-hidden="true">&times;</span></button>
                   </div>
                  <div class="modal-body border-0 col-md-12">
                    <div class="col-md-8" id="popup_description">
                    </div>
                    <div class="col-md-4" id="qr_code">
                    </div>
                  </div>
                  <div class="modal-footer">
                  </div>
                 </div>
                 <!-- /.modal-content -->
               </div>
               <!-- /.modal-dialog -->
              </div>
             <!-- End The Modal -->
             
         </div>
      </div>
      <div class="modal fade" id="addAppointmentModal" style="position:fixed;">
         <div class="modal-dialog modal-dialog-scrollable">
          <form id="frmAppointment" role="form" data-toggle="validator" action="{{ route($modulePath.'store') }}">
            <div class="modal-content">
               <div class="modal-header">
                  <h3 class="card-title">@lang('admin.APPOINTMENT_INFORMATION')</h3>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span></button>                
               </div>               
                  <div class="modal-body">
                     <div class="row">
                        <div class="col-sm-4">
                           <div class="form-group">
                              <div class="form-check"> 
                                 <input type="checkbox" class="form-check-input" id="new_patient_chkbox"
                                    name="new_patient_chkbox" value="1" 
                                    >
                                 <label class="form-check-label" for="new_patient_chkbox">Termin für neue Patientin anlegen</label>
                              </div>
                           </div>
                        </div>
                        <div class="col-sm-4 patient_details" style="display: none;"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_COUNTRY_CODE') <span class="required">*</span></label> 
                                     <div class="select-editable">
                                      <select 
                                        class="form-control my-select"
                                        name="country_code"
                                        id="country_code"
                                        maxlength="5" 
                                        data-error="@lang('admin.ERR_COUNTRY_CODE_REQUIRED')"  onchange="this.nextElementSibling.value=this.value">
                                        <!-- required -->
                                        <option value="43">+43</option>
                                        <option value="0043">0043</option>
                                        <option value="0">0</option>
                                    </select>
                                     <input  
                                        type="text" 
                                        name="format"
                                        id="format"  
                                        class="form-control"
                                        placeholder="@lang('admin.TITLE_PATIENT_COUNTRY_CODE')"   
                                        required
                                         value='+43'
                                        maxlength="5" 
                                        pattern="(\+[0-9]+|0[0-9]+|00[0-9]+)"
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
                                        maxlength="17" 
                                        data-error="@lang('admin.ERR_MOBILE_NO_REQUIRED')" 
                                    >
                                       <!--  required -->
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
                                        <!-- required -->
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
                                        <!-- required -->
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
                                    @lang('admin.TITLE_PATIENT_EMAIL')</label>
                                    <input 
                                        type="email" 
                                        name="email" 
                                        class="form-control" 
                                        maxlength="250" 
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
                                        data-error="@lang('admin.ERR_BIRTH_DATE_REQUIRED')"
                                        required  
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

                            <div class="col-sm-5" id="suggesstion_patient_div_id">
                               <div class="form-group">
                                  <label>@lang('admin.TITLE_APPOINTMENT_PATIENT')</label>  
                                  <div class="frmSearch">   
                                     <input type="search" placeholder="@lang('admin.TITLE_SEARCH_TEXT')" id="suggesstion_patient_id" class="form-control" autocomplete="off">
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
                                      autocomplete="off" 
                                        class="form-control"
                                        id="search_birth_date"  
                                        maxlength="250"                                        
                                    >
                              </div>
                            </div>
                       
                        @if (Auth::user()->hasRole('super-admin') || (Auth::user()->hasRole('Assistant')) || (Auth::user()->hasRole('Doctor')) )
                        <div class="col-sm-6">
                           <div class="form-group">
                              <label>@lang('admin.TITLE_APPOINTMENT_DOCTOR')</label> 
                              <select 
                                 name="doctor_id" 
                                 id="doctor_id"  
                                 required
                                 data-error="@lang('admin.ERR_APPOINTMENT_DOCTOR_REQUIRED')"
                                 class="form-control select2" 
                                 onchange ="getDoctorTimeFrames()" 
                                 >
                                 <option value="">@lang('admin.TITLE_SELECT_DOCTOR')</option>
                                 @foreach($user as $users)
                                 <option value="{{ $users->id }}" lang="{{ $users->status }}">{{ $users->first_name .' '. $users->last_name}}</option>
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
                              <label>@lang('admin.TITLE_APPOINTMENT_TYPE')</label>  
                              <select 
                                 name="appointment_type_id" 
                                 id="appointment_type_id"  
                                 required
                                 data-error="@lang('admin.ERR_APPOINTMENT_TYPE_REQUIRED')"
                                 class="form-control select2" 
                                 onchange ="getDoctorTimeFrames()" 
                                 >
                                 <option value="">@lang('admin.TITLE_ROSTER_SELECT_APPOINTMENT_TYPE')</option>
                                 @foreach($appointment_type as $appointment_types)
                                 <option value="{{ $appointment_types->id }}">{{ $appointment_types->name}} ({{ $appointment_types->duration }})</option>
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
                              <input 
                                 type="text" 
                                 name="date" 
                                 class="form-control"
                                 id="appointment_date"  
                                 autocomplete="off"
                                 required
                                 onchange ="getDoctorTimeFrames()" 
                                 data-error="@lang('admin.ERR_APPOINTMENT_DATE_REQUIRED')." 
                                 >
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
                              <select 
                                 name="time_frame"
                                 id="time_frame"
                                 class="form-control active_status" 
                                 data-placeholder="@lang('admin.TITLE_SELECT_TEXT') @lang('admin.TITLE_ROSTER_TIME_FRAME')"
                                 onchange="assignValueToText()" 
                                 style="width: 100%;"
                                 >
                                 <option value="">@lang('admin.TITLE_SELECT_TIME_FRAME_TEXT')</option>
                              </select>
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
                                       value=""
                                       style="display: none" 
                                       />
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
                        <div class="col-sm-6">
                           <div class="form-group">
                              <label class="theme-blue"> 
                              @lang('admin.TITLE_APPOINTMENT_STATUS')</label>
                              <div class="form-check"> 
                                 <input type="checkbox" class="form-check-input" id="status"
                                    name="status" value="1" checked
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
                  </div>               
               <div class="modal-footer">
                  <button type="submit" class="btn btn-success" id="s_button">@lang('admin.TITLE_SAVE_BUTTON')</button>
                  <button type="reset" class="btn btn-danger" id="app_reset">@lang('admin.TITLE_RESET_BUTTON')</button>
               </div>
            </div>
            </form>
            <!-- /.modal-content -->
         </div>
         <!-- /.modal-dialog -->
      </div>

      <div class="modal fade" id="doctorAvailabilityModal" style="position:fixed;">
         <div class="modal-dialog modal-dialog-scrollable" style="max-width: 800px;">
          <form id="" role="form" data-toggle="validator" action="" style="width: 100%;">
            <div class="modal-content">
               <div class="modal-header">
                  <h3 class="card-title">@lang('admin.TITLE_DOCTOR_AVAILABILITY')</h3>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span></button>                
               </div>               
                  <div class="modal-body">
                     <div class="row">
                        <div class="col-sm-6">
                           <div class="form-group">
                              <label>@lang('admin.TITLE_APPOINTMENT_DOCTOR')</label> 
                              <select 
                                 name="doctor_availability_id" 
                                 id="doctor_availability_id"  
                                 required
                                 data-error="@lang('admin.ERR_APPOINTMENT_DOCTOR_REQUIRED')"
                                 class="form-control select2" 
                                 onchange ="getDoctorDates()" 
                                 >
                                 <option value="">@lang('admin.TITLE_SELECT_DOCTOR')</option>
                                 @foreach($user as $users)
                                 <option value="{{ $users->id }}">{{ $users->first_name .' '. $users->last_name}}</option>
                                 @endforeach
                              </select>
                              <span class="help-block invalid-feedback with-errors">
                                 <ul class="list-unstyled">
                                    <li class="err_doctor_availability_id"></li>
                                 </ul>
                              </span>
                           </div>
                        </div>
                        <div class="col-sm-6">
                          <div class="form-group">
                              <label>@lang('admin.TITLE_ROSTER_DATE')</label> 
                              <select 
                                 name="doctor_dates_id" 
                                 id="doctor_dates_id"  
                                 required
                                 data-error="@lang('admin.ERR_APPOINTMENT_DATE_REQUIRED')"
                                 class="form-control" 
                                 onchange ="getDoctorDutyRoster()" 
                                 >
                              </select>
                              <span class="help-block invalid-feedback with-errors">
                                 <ul class="list-unstyled">
                                    <li class="err_doctor_dates_id"></li>
                                 </ul>
                              </span>
                           </div>
                        </div>
                     </div>
                    <table class="table table-bordered">
                      <thead>
                          <tr>
                              <th width="20px">@lang('admin.TITLE_ROSTER_DATE')</th>
                              <th>@lang('admin.TITLE_ROSTER_TIME_FROM')</th>
                              <th>@lang('admin.TITLE_ROSTER_TIME_TO')</th>
                              <th>@lang('admin.TITLE_ROSTER_TIME_FRAME')</th>
                              <th width="20px">Booked Slots</th>
                          </tr>
                      </thead>
                      <tbody id="doctorRosterData">
                        <!-- <tr>
                          <td>2020-09-15</td>
                          <td>2020-09-15</td>
                          <td>2020-09-15</td>
                        </tr> -->
                      </tbody>
                  </table>
                  </div>               
               <div class="modal-footer">
                  
               </div>
            </div>
            </form>
            <!-- /.modal-content -->
         </div>
         <!-- /.modal-dialog -->
      </div>

      <!-- The Modal -->
      <div class="modal" id="editAppointmentDataModal">
          <div class="modal-dialog modal-dialog-scrollable">
             <div class="modal-content">
                <div class="modal-header">
                   <h3 class="modal-title">
                    @lang('admin.EDIT_APPOINTMENT')
                    <i class="fa fa-edit" data-toggle="modal" id="editAppointmentModal1" data-id=""></i>
                   </h3>
                   <button type="button" class="close btnClosePopup" data-dismiss="modal">×</button>
                </div>                        
                <div class="modal-body">
                  <form id="frmAppointmentEdit" role="form" data-toggle="validator" action="">  

                  </form>
                </div>
             </div>
          </div>
      </div>
      <!-- The Modal -->


      </div>  
      <!-- /.row -->
   </div>
   <!-- /.container-fluid -->
</section>
@endsection
@section('scripts')
<script type="text/javascript">
   var sel_time_frame = "";
</script>
<!-- <script type="text/javascript">
   var settimeout = false; 
   </script> -->
<!-- fullCalendar 2.2.5 --> 
<script src="{{ asset('assets/admin-lte/plugins/moment/moment.min.js') }}"></script>
<!--<script src="{{ asset('assets/admin-lte/plugins/fullcalendar/main.min.js') }}"></script>
 <script src="{{ asset('assets/admin-lte/plugins/fullcalendar/locales-all.min.js') }}"></script>
<script src="{{ asset('assets/admin-lte/plugins/fullcalendar-daygrid/main.min.js') }}"></script>
<script src="{{ asset('assets/admin-lte/plugins/fullcalendar-timegrid/main.min.js') }}"></script>
 -->
<!-- <script src="{{ asset('assets/admin-lte/plugins/fullcalendar-bootstrap/main.min.js') }}"></script> -->
<!-- <script src="https://cdn.jsdelivr.net/npm/fullcalendar@3.10.2/dist/locale-all.js"></script> -->
<script src="{{ asset('assets/admin-lte/plugins/fullcalendar-scheduler/main.js') }}"></script>

<script src="{{ asset('assets/admin-lte/plugins/fullcalendar-scheduler/locales-all.min.js') }}"></script>
<script src="{{ asset('assets/plugins/qtip2/jquery.qtip.min.js') }}"></script>
<script type="text/javascript" src="{{ url('assets/admin/js/dashboard/qrcode.js') }}"></script>
<script type="text/javascript" src="{{ url('assets/admin/js/dashboard/index.js') }}"></script>

@endsection