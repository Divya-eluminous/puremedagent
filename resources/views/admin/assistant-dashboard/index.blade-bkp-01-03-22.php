@extends('admin.layout.master')
@section('title')
{{ $moduleAction ?? 'Manage Dashboard' }}
@endsection
@section('style')
<!-- fullCalendar --> 
<link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/fullcalendar/main.min.css') }}">
<!-- <link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/fullcalendar-daygrid/main.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/fullcalendar-timegrid/main.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/fullcalendar-bootstrap/main.min.css') }}"> -->
<link rel="stylesheet" href="{{ asset('assets/plugins/qtip2/jquery.qtip.min.css') }}">
<link href="{{ asset('assets/admin-lte/plugins/fullcalendar-scheduler/main.css') }}" rel='stylesheet' />
<style type="text/css">
   /*.modal {
   position: absolute;   
   }*/
   .fc-timegrid-event .fc-event-time {
    white-space: nowrap;
    font-size: .85em;
    font-size: var(--fc-small-font-size, .85em);
    margin-bottom: 1px;
    display: none;
}
  .table td, .table th {
    padding: 0.30rem;
    vertical-align: top;
    border-top: 1px solid #dee2e6;
}

    .fc-axis{
      display: none !important;
    }
    .tableBorder{
    border: none!important;
  }
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
    .fixed  
  { 
    position: fixed;  
    width: 100%;  
    left: unset;  
  } 
/*15-01-2021*/  
/* End */ 
@media (max-width: 768px) {   
    .fixed {  
    position: fixed;  
    width: 100%;  
    left: 0;  
    } 
    table.old-appoinmant.table.table-bordered.tableBorder {
    width: 100% !important;
}

.tableBorder td {
    width: 100% !important;
    padding: 5px 0;
    border: 0 !important;
}

tr.tableBorder {
    display: flex;
    flex-direction: column;
    width: 100% !important;
    flex: 0 0 100%;
    / background: #f1f1f1; /
    border-bottom: 1px solid #000;
}
 

table.old-appoinmant.table.table-bordered.tableBorder tbody {
    display: flex;
    flex-direction: column;
}

.tableBorder td:last-child {
    padding-bottom: 15px;
}

.tableBorder td:first-child {
    border-top: 1px solid #d8d8d8 !important;
    padding-top: 15px;
}
} 

.loadingoverlay {
    background: rgba(255, 255, 255, 0) !important;
}
</style>
@endsection
@section('content')

<!-- Main content -->
  
<div class="container my-4">
  <div class="row">
    <!-- Grid column -->
    <div class="col-xl-12 mb-3 mb-xl-0">
      <!-- Section: Live preview -->
       <section>

        <div class="row flex-lg-row-reverse">
          <div class="col-xl-3 d-flex justify-content-center justify-content-xl-end mb-3 mb-xl-0">
               <div class="nav-item waves-effect waves-light new_window_btn">
                @php
                 $url = url('admin/assistant-dashboard');
                 @endphp
                 <a href="javascript:void(0)" class="btn btn-primary float-right" onclick="openMe()">
                 @lang('admin.TITLE_NEW_WINDOW')</a>
                 
                </a>
              </div>
          </div> 
          <div class="col-xl-9">
            <ul class="nav nav-tabs sticky button_add" id="myTab" role="tablist">
              <li class="nav-item  waves-effect waves-light">
                <a class="nav-link active" id="appoinmant_list-tab" data-toggle="tab" href="#appoinmant_list" role="tab" aria-controls="appoinmant_list" aria-selected="false">
                 <!--  <button type="button" class="btn btn-primary btn-cnt">{{count($getDismissalHasPatients)}}</button> -->
                @lang('admin.TITLE_ASSISTANT_DASHBOARD_APPOINMENT') </a>
              </li>
              <li class="nav-item waves-effect waves-light">
                <a class="nav-link refreshclass disabled" id="totdoList-tab" data-toggle="tab" href="#totdoList" role="tab" aria-controls="totdoList" aria-selected="false">
                  <button id="btn_todoList_cnt" type="button" class="btn btn-primary btn-cnt">{{$patient_cnt}}</button>
                @lang('admin.TITLE_ASSISTANT_DASHBOARD_TODO_LIST') </a>
              </li>
              
              
              <li class="nav-item waves-effect waves-light">
                <a class="nav-link waitingCls disabled" id="waiting_list-tab" data-toggle="tab" href="#waiting_list" role="tab" aria-controls="waiting_list" aria-selected="true">
                  <button type="button" class="btn btn-primary btn-cnt">{{count($waiting_list)}}</button>
                @lang('admin.TITLE_ASSISTANT_DASHBOARD_WAITING') </a>
              </li>
              <li class="nav-item waves-effect waves-light">
                <a class="nav-link dismissalCls disabled" id="dismissal-tab" data-toggle="tab" href="#dismissal_list" role="tab" aria-controls="dismissal_list" aria-selected="true">
                  <button id="btn_dismissal_cnt" type="button" class="btn btn-primary btn-cnt">{{count($getDismissalHasPatients)}}</button>
                  @lang('admin.TITLE_ASSISTANT_DASHBOARD_DISMISSAL') 
                </a>
              </li>
               <li class="nav-item waves-effect waves-light">
                <a class="nav-link duplicateCls disabled" id="duplicate-tab" data-toggle="tab" href="#duplicate_list" role="tab" aria-controls="duplicate_list" aria-selected="true">
                  <button id="btn_duplicate_cnt" type="button" class="btn btn-primary btn-cnt">{{ count($duplicateRecord)}}</button>

                  @lang('admin.TITLE_ASSISTANT_DASHBOARD_DUPLICATE') 
                </a>
              </li>
            </ul>
          </div>
        </div>

        <div class="tab-content" id="myTabContent">
          <!-- TODO LIST -->
          <div class="tab-pane fade" id="totdoList" role="tabpanel" aria-labelledby="totdoList-tab">
            <div class="row">
                <div class="col-12">
                    <div id="content" class="card">
                        <!-- /.card-header -->
                        <div id="results" class="card-body list-wrappper" style="padding-top: 15px!important;padding-bottom: inherit!important;">

                        </div> 
                    </div>
                </div>
            </div>
          </div>
          <!-- TODO LIST END -->

          <!-- Appoinmant tab -->
          <div class="tab-pane fade active show" id="appoinmant_list" role="tabpanel" aria-labelledby="appoinmant_list-tab">
            <section class="content">

              <!-- <div id='loader'>Loading</div> -->
              <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-10"></div>
                    <div class="col-lg-2" style="margin-top: 2.5%;padding-left: 22px;"> 
                      <button type="button" id="addAppbutton" class="btn fc-button-primary" data-toggle="modal" data-target="#addAppointmentModal" >
                      @lang('admin.ADD_APPOINTMENT')
                      </button>
                      <button type="button" id="doctorAvailButton" class="btn fc-button-primary" data-toggle="modal" data-target="#doctorAvailabilityModal" >
                      @lang('admin.TITLE_DOCTOR_AVAILABILITY')
                      </button>
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
                                <!-- Change here for edit -->
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
                  
                    <form id="frmAppointment" role="form" data-toggle="validator" action="{{ url('admin/assistant-dashboard/adashboardstore') }}">
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
               <!-- /.container-fluid -->
            </section>
          </div>
          <!-- APPOINMANT LIST END -->

          <!-- WAITING LIST -->
          <div class="tab-pane fade" id="waiting_list" role="tabpanel" aria-labelledby="waiting_list-tab">
              <div class="row">
                <div class="col-12">
                    <div class="card">
                        <!-- /.card-header -->
                        <div class="card-body">         
                            <table id="listingTable" class="table table-bordered table-striped" style="width:100%" >   
                                <thead class="">    
                                    <tr> 
                                        <th style="visibility: hidden;"></th>
                                        <th class="w-140-px">@lang('admin.TITLE_PATIENT_NAME')</th>
                                        <th class="w-200-px">@lang('admin.TITLE_WAITING_PATIENT_QUEUE_NUMBER')</th>
                                        <th class="w-200-px">@lang('admin.TITLE_WAITING_TYPE')</th>
                                        <th class="w-200-px">@lang('admin.TITLE_WAITING_PATIENT_SCAN_TIME')</th>
                                        <th class="text-center w-130-px">

                                        @lang('admin.TITLE_ACTIONS_TEXT')</th>

                                    </tr>
                                </thead>
                                <tbody>
                                </tbody> 
                            </table>
                        </div>
                    </div>
                </div>
            </div>
          </div>
          <!-- WATING LIST END -->

          <!-- DISMISSAL LIST -->
          <div class="tab-pane fade" id="dismissal_list" role="tabpanel" aria-labelledby="dismissal-tab">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <!-- /.card-header -->
                        <div class="card-body">  
                        <input type="hidden" name="hd_dismissal_cnt" id="hd_dismissal_cnt" value="{{$getTotalDismissal}}">
                        @if(!empty($getDismissalHasPatients) && count($getDismissalHasPatients)>0)
                          @foreach($getDismissalHasPatients as $key => $val)
                               @if((!empty($val['patient']['appoinmant']) && sizeof($val['patient']['appoinmant'])>0))
                              <form id="frm_{{$val['patient']['p_id']}}" class="dismissal_frm" method="post"> 

                                <div class="row">
                                  <div class="col-sm-3"> 
                                    <div class="form-group">
                                      <input type="hidden" name="p_id[]" value="{{$val['patient']['p_id']}}">
                                      <label class="theme-blue" style="font-weight: 500!important;font-size: 18px;">
                                        <p style="font-weight: 600;font-size: 20px;">{{$val['patient']['full_name']}}</p>
                                      </label>
                                    </div>
                                  </div>
                                 
                                      <div class="col-sm-9"> 
                                        <div class="p-0 form-group"> 
                                          <button onclick="dismissalDone('{{$val['patient']['p_id'] }}')" type="button" lang="{{$val['patient']['p_id'] }}" class="btn btn-primary dismissal_done">@lang('admin.TITLE_DISMISSAL_BUTTON')</button> 
                                        </div>
                                      </div>
                                    </div>
                                    <!-- Dismissal -->
                                    @if(!empty($val['patient']['appoinmant']['dismissal'])>0 && sizeof($val['patient']['appoinmant']['dismissal'])>0)
                                      
                                          <div class="row">
                                              <div class="col-sm-12"> 
                                                  <div class="form-group">
                                                    
                                                   
                                                      <p style="font-weight: 600;">
                                                        Appoitment : {{$val['patient']['appointment_date']}}
                                                      </p>
                                                   
                                                  </div>
                                              </div>
                                          </div>

                                          <div class="row">
                                              <div class="col-sm-12"> 
                                                  <div class="form-group">
                                                   
                                                      <p style="font-weight: 600;">@lang('admin.TITLE_ASSISTANT_DASHBOARD_DISMISSAL')
                                                       
                                                      </p>
                                                    
                                                  </div>
                                              </div>
                                              
                                              @foreach($val['patient']['appoinmant']['dismissal'] as $ad_key => $ad_val)
                                              <div class="col-sm-3">
                                                <div class="form-group">
                                                  <div class="form-check"> 

                                                    <input type="checkbox" class="form-check-input"
                                                          name="dismissal[{{$ad_val['appointment_id']}}][]" value="{{$ad_val['id']}}" 
                                                          >
                                                    <label class="form-check-label" for="new_patient_chkbox">{{$ad_val['name']}}</label>
                                                  </div>
                                                </div>
                                              </div>
                                              @endforeach
                                          </div> 
                                      <!-- Examination -->
                                   
                                    @endif 

                                    @if(!empty($val['patient']['appoinmant']['reminder'])>0 && sizeof($val['patient']['appoinmant']['reminder'])>0)

                                        <input type="hidden" name="hd_examinaton_cnt" id="hd_examinaton_cnt" value="{{count($val['patient']['appoinmant']['reminder'])}}">
                                            <div class="row">
                                              <div class="col-sm-12"> 
                                                  <div class="form-group">
                                                      <p style="font-weight: 600;">@lang('admin.TITLE_REMINDER')
                                                      </p>
                                                  </div>
                                              </div>
                                              @foreach($val['patient']['appoinmant']['reminder'] as $e_key => $e_val)
                                              <div class="col-md-3 col-sm-6">
                                                <div class="form-group">
                                                  <div class="form-check"> 
                                                    <input  type="checkbox" class="form-check-input"
                                                          name="dismissal[{{ $e_val['appointment_id']}}][]" value="{{$e_val['id']}}" 
                                                          >
                                                    <label class="form-check-label" for="new_patient_chkbox">{{$e_val['name']}}   ({{$e_val['control_interval']}})</label>
                                                  </div>
                                                </div>
                                              </div>
                                              @endforeach
                                            </div> 
                                    @endif  

                                    @if(!empty($val['patient']['appoinmant']['examination'])>0 && sizeof($val['patient']['appoinmant']['examination'])>0)

                                        <input type="hidden" name="hd_examinaton_cnt" id="hd_examinaton_cnt" value="{{count($val['patient']['appoinmant']['examination'])}}">
                                            <div class="row">
                                              <div class="col-sm-12"> 
                                                  <div class="form-group">
                                                      <p style="font-weight: 600;">@lang('admin.TITLE_EXAMINATIONS_TEXT')
                                                      </p>
                                                  </div>
                                              </div>
                                              @foreach($val['patient']['appoinmant']['examination'] as $e_key => $e_val)
                                              <div class="col-sm-3">
                                                <div class="form-group">
                                                  <div class="form-check"> 
                                                    <input  type="checkbox" class="form-check-input"
                                                          name="examination[{{ $e_val['appointment_id']}}][]" value="{{$e_val['id']}}" 
                                                          >
                                                    <label class="form-check-label" for="new_patient_chkbox">{{$e_val['name']}}</label>
                                                  </div>
                                                </div>
                                              </div>
                                              @endforeach
                                            </div> 
                                    @endif 

                                 
                               
                                @endif 

                                  
                                
                              </form>
                            <hr>
                          @endforeach
                        @else
                              <div class="row">
                              <div class="col-sm-12"> 
                                <div class="form-group" style="margin-left: 300px;font-size: 20px;">
                                  <label class="theme-blue">
                                    <p>@lang('admin.ERR_DISMISSAL_REQUIRED_NOT_EXIST')</p>
                                  </label>
                                </div>
                              </div>
                            </div>
                        @endif  
                        </div>
                    </div>
                </div>
            </div>
          </div>
          <!-- DISMISSAL LIST END -->

          <!-- DUPLICATE LIST -->
          <div class="tab-pane fade" id="duplicate_list" role="tabpanel" aria-labelledby="duplicate-tab">
            <div class="row">
              <div class="col-12">
                <div class="card">               
                  <div class="card-body">  
                    @foreach($duplicateRecord as $key=>$value)
                      <div class="card  collapsed-card 23662_main_div">
                        <div class="card-header " >
                          <h3 class="card-title" data-card-widget="collapse" style="width:70%">{{ $value->first_name }} {{ $value->family_name }} </h3> 

                        </div>
                        <div class="card-body 23662_sub">
                          <div class="col-md-12">
                              @foreach ($value->link_ids as $key => $id) 
                              <a href="{{ route('admin.patients.edit', [ base64_encode(base64_encode($id))]) }}" target="_blank" title="{{ $value->first_name }} {{ $value->family_name }}">{{ route('admin.patients.edit', [ base64_encode(base64_encode($id))]) }}</a><br/>
                              @endforeach
                          </div>
                        </div>
                      </div>
                    @endforeach
                  </div>
                </div>
              </div> 
            </div>
          </div>
          <!-- DUPLICATE LIST END -->
      </section>
      <!--Edit carpark clietn id --> 
      <a id="btn-import-finding" style="display: none;" type="button" data-toggle="modal" data-target="#modal-default-upgrade"  class="btn btn btn-primary" href="javascript:void(0)" ></a>
      <div class="modal fade" id="modal-default-upgrade">
          <div class="modal-dialog">
              <div class="modal-content">
                  <div class="modal-header">
                     <h4 class="modal-title">@lang('admin.TITLE_TODO_LIST_IMPORT_FINDING')</h4>
                     <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                     <span aria-hidden="true">&times;</span></button>
                     
                  </div>
                  <div class="modal-body">
                      <form  method="POST" enctype="multipart/form-data"  role="form"  action="{{ url('admin/assistant-dashboard/importFinding') }}">
                        <input type="hidden" name="_token" value="{{csrf_token()}}">
                        <input type="hidden" class="form-control" id="old_date_id" name="old_date_id" value="">
                        <input type="hidden" class="form-control" id="hd_patient_id" name="hd_patient_id" value="">
                        <input type="hidden" class="form-control" id="hd_date" name="hd_date" value="">
                        <div class="box-body">
                            <div class="box-body" id="popup_div"> 
                              <div class="card-body">
                                <div class="row">
                                    <div class="col-sm-12"> 
                                        <div class="form-group">
                                            <label class="theme-blue"> 
                                            @lang('admin.TITLE_FINDING_SERVICES_TYPE') <!-- <span class="required">*</span> --></label>
                                            <select required class="form-control" name="type" id="type" data-error="@lang('admin.ERR_TODO_LIST_IMP_TYPE')">
                                              <option value="">@lang('admin.TITLE_TOTO_SELECT_FINDING_TYPE')</option>
                                              @if(!empty($finding_type) && sizeof($finding_type)>0)
                                              @foreach($finding_type as $t_key =>$t_val)
                                              <option value="{{$t_val['id']}}">{{$t_val['name']}}</option>
                                              @endforeach
                                              @endif
                                            </select> 
                                            <span class="help-block invalid-feedback with-errors">
                                                <ul class="list-unstyled">
                                                    <li class="err_type"></li>
                                                </ul>
                                            </span>
                                        </div>
                                    </div>
                                   <!--  <div class="col-sm-12"> 
                                        <div class="form-group">
                                            <label class="theme-blue"> 
                                            @lang('admin.TITLE_FINDING_DOCUMANT_NAME')</label>
                                            <input required type="text" name="document_name" class="form-control"  data-error="@lang('admin.ERR_TODO_LIST_IMP_DOCUMENT_NAME')">
                                            <span class="help-block invalid-feedback with-errors">
                                                <ul class="list-unstyled">
                                                    <li class="err_document_name"></li>
                                                </ul>
                                            </span>
                                        </div>
                                    </div> -->
                                    <div class="col-sm-12"> 
                                        <div class="form-group">
                                            <label class="theme-blue"> 
                                            @lang('admin.TITLE_TODO_LIST_IMPORT')</label>
                                            <input  multiple required type="file" name="import[]" class="form-control"  data-error="@lang('admin.ERR_TODO_LIST_IMP')">
                                            <span class="help-block invalid-feedback with-errors">
                                                <ul class="list-unstyled">
                                                    <li class="err_import"></li>
                                                </ul>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                        
                              </div>
                            </div>  
                            <div class="box-footer">
                                <div class="col-md-12 align-right">
                                    <button type="submit" class="btn btn-primary btn_submit" id="btn-update-permission-submit">Save</button>&nbsp;
                                    <button id="close" type="button" class="btn btn-danger close-div" data-dismiss="modal" aria-label="Close">Cancel</button>
                                </div>
                            </div>
                        </div>
                      </form>
                  </div>
              </div>
              <!-- /.modal-content -->
          </div>
        <!-- /.modal-dialog -->
      </div>
      <!-- Section: Live preview -->

      <!--If Setting Is ON -->
      <a style="display: none" id="btn-send-finding-via-email"  type="button" data-toggle="modal" data-target="#modal-default-send-finding-via-email"  class="btn btn btn-primary" href="javascript:void(0)" ></a>
      <div class="modal fade" id="modal-default-send-finding-via-email">
          <div class="modal-dialog">
              <div class="modal-content">
                  <div class="modal-header">
                     <h4 class="modal-title">@lang('admin.TITLE_TODO_LIST_SEND_FINDING')</h4>
                     <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                     <span aria-hidden="true">&times;</span></button>
                     
                  </div>
                  <div class="modal-body">
                      <form id="frm_send_findings"  method="POST" enctype="multipart/form-data"  role="form"  action="{{ url('admin/assistant-dashboard/sendFindingEmail') }}">
                        <input type="hidden" name="_token" value="{{csrf_token()}}">
                        <input type="hidden" class="form-control" id="hd_finding_patient_id" name="hd_finding_patient_id" value="">
                        <input type="hidden" class="form-control" id="hd_finding_old_id" name="hd_finding_old_id" value="">
                        <div class="box-body">
                            <div class="box-body" id="popup_div"> 
                              <div class="col-sm-12"> 
                                        <div class="form-group">
                                            <p class="theme-blue" style="font-size: 19px!important;"> 
                                            {{$msg_finding_via_mail}}</p>
                                            <!-- <input type="text" id="patient_name" name="patient_name" class="form-control" readonly> -->
                                        </div>
                                    </div>
                              <div class="card-body">
                                <div class="row">
                                    <div class="col-sm-4"> Befunddatum :</div>
                                    <div class="col-sm-8" id="old_appoinment_date"> </div>

                                    <div class="col-sm-12"> 
                                        <div class="form-group">
                                            <label class="theme-blue"> 
                                            @lang('admin.TITLE_PATIENT_NAME') : </label>
                                            <input type="text" id="patient_name" name="patient_name" class="form-control" readonly>
                                        </div>
                                    </div>
                                    <div class="col-sm-12"> 
                                        <div class="form-group">
                                            <label class="theme-blue"> 
                                            @lang('admin.SEND_MAIL_TO') : </label>
                                            <input required type="text" id="to" name="to" class="form-control" >
                                        </div>
                                    </div>
                                    <div id="hd_noties_div" class="col-sm-12" style="display: none;"> 
                                        <div class="form-group">
                                            <label class="theme-blue"> 
                                            @lang('admin.TITLE_APPOINTMENT_NOTE') : </label>
                                            <textarea type="text" name="hd_notes" id="hd_notes" class="form-control"></textarea>
                                        </div>
                                    </div>
                                </div>
                              </div>
                            </div>  
                            <div class="box-footer">
                                <div class="col-md-12 align-right">
                                    <button type="button" class="btn btn-primary btn_submit" id="send_findings">Save</button>&nbsp;
                                    <button id="close" type="button" class="btn btn-danger close-div" data-dismiss="modal" aria-label="Close">Cancel</button>
                                </div>
                            </div>
                        </div>
                      </form>
                  </div>
              </div>
              <!-- /.modal-content -->
          </div>
        <!-- /.modal-dialog -->
      </div>
      <!-- Section: Live preview -->
    </div>
  </div>
</div>

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
<!-- <script src="{{ asset('assets/admin-lte/plugins/fullcalendar/main.min.js') }}"></script>
<script src="{{ asset('assets/admin-lte/plugins/fullcalendar/locales-all.min.js') }}"></script> -->
<!-- <script src="{{ asset('assets/admin-lte/plugins/fullcalendar-daygrid/main.min.js') }}"></script>
<script src="{{ asset('assets/admin-lte/plugins/fullcalendar-timegrid/main.min.js') }}"></script>
<script src="{{ asset('assets/admin-lte/plugins/fullcalendar-interaction/main.min.js') }}"></script>
<script src="{{ asset('assets/admin-lte/plugins/fullcalendar-bootstrap/main.min.js') }}"></script> -->
<script src="{{ asset('assets/admin-lte/plugins/fullcalendar-scheduler/main.js') }}"></script>
<script src="{{ asset('assets/admin-lte/plugins/fullcalendar-scheduler/locales-all.min.js') }}"></script>
<script src="{{ asset('assets/plugins/qtip2/jquery.qtip.min.js') }}"></script>
<script type="text/javascript" src="{{ url('assets/admin/js/assistant-dashboard/qrcode.js') }}"></script>
<script type="text/javascript" src="{{ url('assets/admin/js/assistant-dashboard/index.js?ver=0.2') }}"></script>
<!-- <script type="text/javascript" src="{{ url('assets/admin/js/dashboard/index.js') }}"></script> -->
<script type="text/javascript" src="{{ url('assets/admin/js/assistant-dashboard/waiting_index.js') }}"></script>
<script type="text/javascript">
  var success_msg = '{{$success_msg}}';
  var error_msg   = '{{$error_msg}}';
  var copy_success_msg = '{{$copy_success_msg}}';
  var copy_error_msg   = '{{$copy_error_msg}}';
  var warning_mesg     = '{{$warning_todo_list}}';
  var warning_yes      = '{{$todo_list_confirmation}}';
  var title_todo_warning = '{{$title_todo_warning}}';
  var completed_msg = '{{$completed_msg}}';
  var completed_not_msg = '{{$completed_not_msg}}';
  var imp_patient_id = '{{$patient_id}}'//after import finding get patient id
  var finding_imp_suc = '{{$finding_imp_suc}}';
  var title_warning = '{{$title_warning}}';
  var msg_finding_via_mail = '{{$msg_finding_via_mail}}';
  var msg_msg_finding_push_notification = '{{$msg_msg_finding_push_notification}}';
  var err_something_wrong = '{{$err_something_wrong}}';
  var todolist_title = '{{$todolist_title}}';
 
</script>
<script>
  function openMe()
  {
    var width = `{{ $width }}`;
    var height = `{{ $height }}`;
    top_position = 0;
    left_position = 0;
  
    switch(`{{ $position }}`)
    {   
        case 'top_right':                      
                   left_position = screen.width;
        break;

        case 'bottom_left':
                   top_position = screen.height;
        break;

        case 'bottom_right':
                   top_position = screen.height;
                   left_position = screen.width;
        break;

        case 'center':          
                   left_position = (screen.width /2) - (width/2);
                   top_position = (screen.height /2) - (height/2);
        break;
    }

    window.open('{{ $url }}', '_blank', 'menubar=no,status=no,titlebar=yes,resizable=yes,scrollbars=yes,toolbar=no,width={{ $width }},height={{ $width }},top='+top_position+',left='+left_position); 
   }
 </script>

<script>

$( document ).ready(function()
{
  $('body').LoadingOverlay("show");
  var getUrl = "{{ Request::get('tab') }}";
  if(getUrl === 'todoList') {
    $('#appoinmant_list-tab').removeClass('active');
    $('#appoinmant_list').removeClass('active show');
    setTimeout(function(){
      $('#totdoList-tab').addClass('active');
      $('#totdoList').addClass('active show');
      displayRecords(200, 200);
    },1000);
  }
  setTimeout(function(){
    $('body').LoadingOverlay("hide");
  },3000);
});

</script>

@endsection