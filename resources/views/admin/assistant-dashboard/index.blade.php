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
<link rel="stylesheet" href="{{ asset('assets/admin/css/dashabord-datepicker-model.css') }}">
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
   /* height: 20px;
    overflow: auto;*/  /* commented both lines on 19-nov-24 for 225 CR */
    color: #fff;
    font-size: 15px!important;
    font-weight: 500;
     min-height: 17px;  /* added on 19-nov-24 for 225 CR */
    overflow: hidden;  /* added on 19-nov-24 for 225 CR */
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
    table.old-appoinmant.table.table-bordered.tableBorder,table.new-patients.table.table-bordered.tableBorder {
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
table.new-patients.table.table-bordered.tableBorder tbody {
    display: flex;
    flex-direction: column;
}

.tableBorder td:last-child {
    padding-bottom: 15px;
}

.tableBorder td:first-child {
    border-top: 1px solid #d8d8d8 !important;
    padding-top: 15px;
}   b
}

.loadingoverlay {
    background: rgba(255, 255, 255, 0) !important;
}

 .fc-timegrid-slots table
  {
    height: 1500px !important;
  }
  .fc-timegrid-slots tr {
    height: auto;
   }

   /******commented for 225 CR********************/

   .fc-timegrid-slots td {
      padding-top: 12px;
      padding-bottom: 12px;
   }

  .fc-timegrid-event-condensed .fc-event-main-frame {
    flex-wrap: nowrap;
  }


/**************12-apr-24*for*app**************************/
  .qr_code{ float:left; text-align: center;}
  .qr_code > a.btn {
    background: #212529;
    color: #fff;
    width: 100px;
    font-weight: 100;
    margin: 20px auto 0 auto;
    }
    .qr_code #qr_code {
        float: none;
        /* margin: 0 auto; */
        text-align: center;
    }

    div#profileModal.fade:not(.show) {
        opacity: 1;
        margin-top: 0;
        z-index: 9999;
    }

    div#profileModal.fade:not(.show) .modal-header {
        display: block;
    }

    div#profileModal.fade:not(.show) .modal-dialog {
        height: auto;
        justify-content: center;
        display: flex;
        margin-top: 80px;
    }

    div#profileModal.fade:not(.show):before {
        content: "";
        background: rgba(0,0,0,0.5);
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        width: 100%;
        z-index: -1;
    }


    .modal-dialog .select-editable select{
        border-bottom: 1px solid #ced4da !important;
    }
    .select-editable input{
        height: 34px;
    }

/**********12-apr-24*for*app**********************/

 /*---------start-added on 6-nov-24---for #225----*/

   /* .heightdropdown {
        position: relative;
    }

    .heightdropdown select {
       position: absolute;
       width: 100px;
       right: 210px;
       top: 59px;
       border-radius: 5px;
       z-index: 99;
    }*/
    /*---------end-added on 6-nov-24---for #225----*/


</style>
@endsection
@section('content')

   <!---------start-added on 5-nov-24--for #225---------------------->


   @php
   $default_height = 3;

   @endphp

   @if(isset($default_height) && $default_height == 3)
   <!-- <style type="text/css">
       .fc-timegrid-slots td {
        padding-top: 10px;
        padding-bottom: 10px;
      }
   </style> -->

   @elseif(isset($default_height) && $default_height == 5)
   <!-- <style type="text/css">
       .fc-timegrid-slots td {
        padding-top: 20px;
        padding-bottom: 20px;
      }
   </style>
    -->
   @elseif(isset($default_height) && $default_height == 10)
   <!-- <style type="text/css">
       .fc-timegrid-slots td {
        padding-top: 30px;
        padding-bottom: 30px;
      }
   </style> -->

   @else
   <!-- <style type="text/css">
       .fc-timegrid-slots td {
        padding-top: 10px;
        padding-bottom: 10px;
      }
   </style> -->
   @endif
   <!---------end-added on 5-nov-24--for #225---------------------->


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
                     @lang('admin.TITLE_ASSISTANT_DASHBOARD_APPOINMENT')
                   </a>
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
                 <li class="nav-item waves-effect waves-light" style="display: none;">
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
                        <!-- <button type="button" id="doctorAvailButton" class="btn fc-button-primary" data-toggle="modal" data-target="#doctorAvailabilityModal" >
                          @lang('admin.TITLE_DOCTOR_AVAILABILITY')
                          </button> -->
                     </div>

                     <!---------start-added on 5-nov-24--for #225-CR---------------------->


                     <!--  <div class="heightdropdown" style="margin-top: 2%">
                        <select
                           class="form-control my-select"
                           name="height"
                           id="height"
                           maxlength="5"
                          >
                           <option value="3" @if(isset($default_height) && $default_height==3) selected @endif>3 Mins</option>
                           <option value="5"@if(isset($default_height) && $default_height==5) selected @endif>5 Mins</option>
                           <option value="10"@if(isset($default_height) && $default_height==10) selected @endif>10 Mins</option>
                        </select>
                      </div> -->

                      <!---------end-added on 5-nov-24--for #225-CR--------------------->


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
                                 <h3 class="modal-title">
                                   @lang('admin.APPOINTMENT_DETAILS')
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

                                 <!------commented on 12-march-24--------------->
                                 <!-- <div class="col-md-4" id="qr_code">
                                 </div> -->

                                 <!--------added on 12-apr-24 for #3app-------------------------->
                                  <div class="col-md-4 qr_code">
                                     <div id="qr_code"></div>

                                     <a href="#" class="btn openProfileModal" data-id="">{{ __('admin.LABEL_PROFILE') }}</a>
                                  </div>

                                  <!--------added on 12-apr-24 for #3app-------------------------->

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
                   <!--Start Appointment Modal Form code by swapnil pawar 12-09-2022 -->
                   <div class="modal fade" id="addAppointmentModal" style="position:fixed;">
                     <div class="modal-dialog modal-dialog-scrollable">
                        <form id="frmAppointment" role="form" data-toggle="validator" action="{{ url('admin/assistant-dashboard/adashboardstore') }}">
                          <div class="modal-content">
                            <div class="modal-header">
                              <h3 class="card-title">@lang('admin.APPOINTMENT_INFORMATION')</h3>
                              <button type="button" class="close addBtnClosePopup" data-dismiss="modal" aria-label="Close">
                              <span aria-hidden="true">&times;</span></button>
                            </div>
                            <div class="modal-body appointment-loader">
                              <div class="row">
                                 <div class="col-sm-4">
                                   <div class="form-group">
                                     <div class="form-check">
                                       <input type="checkbox" class="form-check-input" id="new_patient_chkbox"
                                          name="new_patient_chkbox" value="1"
                                          >
                                       <label class="form-check-label" for="new_patient_chkbox">@lang('admin.TITLE_PATIENT_CREATE')</label>
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
                                          data-error="@lang('admin.ERR_COUNTRY_CODE_REQUIRED')"   onchange="handleCountrySelect(this)">                                        
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
                                     @lang('admin.TITLE_PATIENT_EMAIL') <span class="required">*</span></label>
                                     <input
                                       type="email"
                                       name="email"
                                       class="form-control"
                                       maxlength="250"
                                       required
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
                                       autocomplete="off"
                                       id="birth_date"
                                       maxlength="250"
                                       data-error="@lang('admin.ERR_BIRTH_DATE_REQUIRED')"
                                       required placeholder="DD-MM-YYYY">
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
                                       maxlength="250" placeholder="DD-MM-YYYY"
                                       >
                                   </div>
                                 </div>
                                 @if (Auth::user()->hasRole('super-admin') || (Auth::user()->hasRole('Assistant')) || (Auth::user()->hasRole('Doctor')) || (Auth::user()->hasRole('Lead-Assistant')))
                                 <div class="col-sm-6">
                                   <div class="form-group">
                                     <label>@lang('admin.TITLE_APPOINTMENT_DOCTOR')</label>
                                     <select
                                       name="doctor_id"
                                       id="doctor_id"
                                       required
                                       data-error="@lang('admin.ERR_APPOINTMENT_DOCTOR_REQUIRED')"
                                       class="form-control select2">
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
                                     <label>@lang('admin.TITLE_APPOINTMENT_TYPE')</label>
                                     <select
                                       name="appointment_type_id"
                                       id="appointment_type_id"
                                       required
                                       data-error="@lang('admin.ERR_APPOINTMENT_TYPE_REQUIRED')"
                                       class="form-control select2">
                                       <option value="">@lang('admin.TITLE_ROSTER_SELECT_APPOINTMENT_TYPE')</option>
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
                                       required
                                       data-error="@lang('admin.ERR_APPOINTMENT_DATE_REQUIRED')." readonly="readonly" style="background-color:white;">
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
                                       value="">


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
                                          required
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
                                 <!-- # Roshani Added this code # CR #102 -->
                                 <!-- <div class="col-sm-6 patient_details" style="display: none;">
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
                                 <!-- # Roshani Added this code # CR #102 -->
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

                               <!--------------Start--Checkbox added by divya--20-sept-22---------------->

                              <div class="col-sm-6 p-0" id="optimal_checkbox" style="margin-top: -8px;margin-bottom: -8px;">
                                 <div class="form-group mb-0">
                                   <label class="theme-blue mb-0">
                                   @lang('admin.TITLE_OPTIMAL_APPOINTMENT')</label>
                                 <!--   <div class="form-check">  -->
                                     <input type="checkbox" class="form-check-input" id="quarter_setting_check"
                                       name="quarter_setting_check" value="1" checked style="margin-left: 10px">

                                  <!--  </div> -->
                                 </div>
                              </div>

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
                                       <input type="hidden" name="time_frame" id="time_frame" class="new_appointment_datetime_added">
                                       <input type="hidden" name="date" id="appointment_date_new" class="new_appointment_date_added">
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
                              <div id="available_datetime1">
                              </div>
                              <div class="table-responsive table_bottom" id="doctor_duty_rosters">
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
                   <!--End Appointment Modal Form code by swapnil pawar 12-09-2022 -->


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

                   <!-----------added on 12-apr-24for #3app-------------------------------->

                     <!-- Modal -->
                     <div id="profileModal" class="modal fade" role="dialog" data-id="">
                      <div class="modal-dialog">

                        <!-- Modal content-->
                        <div class="modal-content">
                         <div class="modal-header">
                           <button type="button" class="close closeProfileBtn" data-dismiss="modal">&times;</button>
                           <h4 class="modal-title">@lang('admin.LABEL_PERSONAL_DATA')</h4>
                         </div>
                         <div class="modal-body">


                           <form name="userProfileUpdate" id='ProfileEditFrm'  action="" data-toggle="validator" autocomplete="off" class="form" role="form">

                           </form>

                         </div>
                         <!-- <div class="modal-footer">
                           <button type="button" class="btn btn-default closeProfileBtn" data-dismiss="modal">Close</button>
                         </div> -->
                        </div>

                      </div>
                     </div>


                     <!-----------added on 12-apr-24for #3app-------------------------------->




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
                                 @lang('admin.TITLE_ACTIONS_TEXT')
                              </th>
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
                        @if(!empty($getDismissalHasPatients) && count($getDismissalHasPatients) > 0)
                        @foreach($getDismissalHasPatients as $key => $val)
                        @if((!empty($val['patient']['appoinmant']) && sizeof($val['patient']['appoinmant']) > 0))
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
                          @if(!empty($val['patient']['appoinmant']['dismissal']) > 0 && sizeof($val['patient']['appoinmant']['dismissal']) > 0)
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
                          @if(!empty($val['patient']['appoinmant']['reminder']) > 0 && sizeof($val['patient']['appoinmant']['reminder']) > 0)
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
                          @if(!empty($val['patient']['appoinmant']['examination']) > 0 && sizeof($val['patient']['appoinmant']['examination']) > 0)
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
                        @foreach($duplicateRecord as $key => $value)
                        <div class="card  collapsed-card 23662_main_div">
                          <div class="card-header " >
                            <h3 class="card-title" data-card-widget="collapse" style="width:70%">{{ $value->first_name }} {{ $value->family_name }} </h3>
                          </div>
                          <div class="card-body 23662_sub">
                            <div class="col-md-12">
                              @foreach ($value->link_ids as $key => $id)
                              <a href="{{ route('admin.patients.edit', [base64_encode(base64_encode($id))]) }}" target="_blank" title="{{ $value->first_name }} {{ $value->family_name }}">{{ route('admin.patients.edit', [base64_encode(base64_encode($id))]) }}</a><br/>
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
        <form  method="POST" data-toggle="validator" enctype="multipart/form-data"  role="form"  action="{{ url('admin/assistant-dashboard/importFinding') }}" id="importFindingFrm">
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
        @if(!empty($finding_type) && sizeof($finding_type) > 0)
        @foreach($finding_type as $t_key => $t_val)
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
        <input  multiple  type="file" name="import[]" class="form-control"  data-error="@lang('admin.ERR_TODO_LIST_IMP')">
        <span class="help-block invalid-feedback with-errors">
        <ul class="list-unstyled">
        <li class="err_import"></li>
        </ul>
        </span>
        </div>
        </div>
        <div class="col-sm-12">
        <div class="form-group">
        <label class="theme-blue">
        @lang('admin.TITLE_DASHBOARD_NOTE')</label>
        <input  type="text" name="comment" id="comment" class="form-control"  data-error="@lang('admin.ERR_TODO_LIST_COMMENT')">
        <span class="help-block invalid-feedback with-errors">
        <ul class="list-unstyled">
        <li class="err_comment"></li>
        </ul>
        </span>
        </div>
        </div>
        </div>
        </div>
        </div>
        <div class="box-footer">
        <div class="col-md-12 align-right">
        <button type="submit" class="btn btn-primary btn_submit" id="btn-update-permission-submit">@lang('admin.TITLE_SAVE_BUTTON')</button>&nbsp;
        <button id="close" type="button" class="btn btn-danger close-div" data-dismiss="modal" aria-label="Close">@lang('admin.TITLE_CANCEL_BUTTON')</button>
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
        <div id="quarterSetting" data-quarter-setting="{{ $quarter_setting }}"></div>
      </div>
   </div>
@endsection
@section('scripts')
<script type="text/javascript">
   var sel_time_frame = "";
   var importnew_action="{{ url('admin/assistant-dashboard/importFindingNew') }}";
   var daterequired =  "{{ __('admin.ERR_APPOINTMENT_DATE_REQUIRED') }}";
</script>
<!-- ############## Roshani Added this code (22/02/2024) C) User settings ################ -->
<script type="text/javascript" src="{{ url('assets/admin/js/dashboard/commonJsForApp.js') }}"></script>
<!-- ############## Roshani Added this code (22/02/2024) C) User settings ################ -->
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
<script type="text/javascript" src="{{ url('assets/admin/js/assistant-dashboard/index.js?ver=0.024') }}"></script>
<!-- <script type="text/javascript" src="{{ url('assets/admin/js/dashboard/index.js') }}"></script> -->
<script type="text/javascript" src="{{ url('assets/admin/js/assistant-dashboard/waiting_index.js?ver=0.012') }}"></script>
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



@php
    $validUser = 0;
 @endphp
 @if(auth()->user()->can('optimal-appointment'))
   @php
    $validUser = 1;
   @endphp
 @endif



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
function handleCountrySelect(el) {
    var input = document.getElementById('format');
    if (el.value === 'other') {
        input.value = '';
        input.focus();
    } else {
        input.value = el.value;
    }
}
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


 // Appointment code by swapnil pawar 12-09-2022
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
    $("#time_frame1").val("");
    $("#time_frame").val("");
    $("#appointment_date_new").val("");
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
    $("#time_frame1").val("");
    $("#time_frame").val("");
    $("#appointment_date_new").val("");
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
            /* $("#quarter_setting_check").val(1);
            $("#quarter_setting_check").attr('checked', 'checked'); */
            /* added by vijay 8/3/24 */
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
        $("#time_frame1").val("");
        $("#time_frame").val("");
        $("#appointment_date_new").val("");
   });//quarter setting check function




   function commonFunctionDoctorAppointment(doctor_id, appointment_type_id) {
    //common code doctor select
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

          $("#optimal_checkbox").show(); // Added by divya on 19sept22

        $('.new_appointment_date_added').attr('name', 'date');
        $('.new_appointment_datetime_added').attr('name', 'time_frame');
        $(".old_appointment_date_added").removeAttr('name');
        $("#time_frame1").removeAttr('name');
        $("#appointment_date_calender").hide();
        $("#appointment_time_slot").hide();
        $("#available_datetime").show();
        //02-09-2022
        $('.appointment-loader').LoadingOverlay("show", {
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
                 "quarter_setting_check":quarter_setting_check
            },
            success: function(response) {
                if (response.count == 1) {
                    $('.appointment-loader').LoadingOverlay("hide");
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
                    $("#appointment_from_date" ).datepicker( "destroy");
                    $("#appointment_to_date" ).datepicker( "destroy");

                    $('#appointment_from_date').datepicker({
                    // dateFormat: 'yy-mm-dd', //swapnil commented 10-jan-23
                     dateFormat: 'dd-mm-yy', //swapnil added on 10-jan-23
                     orientation: "bottom",
                     autoclose: true,
                     todayHighlight: true,
                     minDate: response.calender_date1
                    });
                    $('#appointment_to_date').datepicker({
                    // dateFormat: 'yy-mm-dd', //swapnil commented 10-jan-23
                     dateFormat: 'dd-mm-yy', //swapnil added on 10-jan-23
                     orientation: "bottom",
                     autoclose: true,
                     todayHighlight: true,
                     minDate: response.calender_date2
                    });
                } else {
                  $('.appointment-loader').LoadingOverlay("hide");
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
      $('#time_frame1').attr('name', 'time_frame');
      $(".new_appointment_date_added").removeAttr('name');
      $(".new_appointment_datetime_added").removeAttr('name');
      $("#doctor_duty_rosters").empty();
      $("#appointment_date_calender").show();
      $("#appointment_time_slot").show();
      $("#available_datetime").hide();
      $("#dr_not_available").val("");
      $("#appointment_date").val('');
      $('#appointment_date').datepicker({
         //dateFormat: 'yy-mm-dd', //commented on 10-jan-23
         dateFormat: 'dd-mm-yy', //swapnil added on 10-jan-23
         orientation: "bottom",
         autoclose: true,
         todayHighlight: true,
         startDate: new Date(),
        // minDate: 0 //commented on 10-jan-23 for past date to show for emergency doctor
        });

      $("#optimal_checkbox").hide(); // Added by divya on 19sept22
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


    $('.appointment-loader').LoadingOverlay("show", {
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
            $("#appointment_to_date").datepicker("option", "minDate", end_date);
            if (response.count == 1) {
            $('.appointment-loader').LoadingOverlay("hide");
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

              $('#appointment_from_date').datepicker({
               // dateFormat: 'yy-mm-dd', //swapnil commented 10-jan-23
               dateFormat: 'dd-mm-yy', //swapnil added on 10-jan-23
               orientation: "bottom",
               autoclose: true,
               todayHighlight: true,
               minDate: response.calender_date1
              });
              $('#appointment_to_date').datepicker({
                // dateFormat: 'yy-mm-dd', //swapnil commented 10-jan-23
               dateFormat: 'dd-mm-yy', //swapnil added on 10-jan-23
               orientation: "bottom",
               autoclose: true,
               todayHighlight: true,
               minDate: response.calender_date2
              });
              //End code added by swapnil on 10-jan-23


            } else {
            $('.appointment-loader').LoadingOverlay("hide");
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
    var appointment_from_date = $("#appointment_from_date").val();
    var appointment_to_date = $("#appointment_to_date").val();
    var doctor_id = $('#doctor_id').find(":selected").val();
    var patient_id = $('#patient_id').find(":selected").val();
    var doctor_status = $('#doctor_id option:selected').attr('lang');
    var appointment_type_id = $('#appointment_type_id').find(":selected").val();
    var calender_search = 2;
    $("#doctor_duty_rosters").empty();
    var quarter_setting_check = $("#quarter_setting_check").val(); // Added by divya on 19 sept 22



    $('.appointment-loader').LoadingOverlay("show", {
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
            $("#appointment_to_date").datepicker("option", "minDate", end_date);
            if (response.count == 1) {
            $('.appointment-loader').LoadingOverlay("hide");
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

              //Start code added by swapnil on 10-jan-23
              $("#appointment_from_date" ).datepicker( "destroy");
              $("#appointment_to_date" ).datepicker( "destroy");

              $('#appointment_from_date').datepicker({
               // dateFormat: 'yy-mm-dd', //swapnil commented 10-jan-23
               dateFormat: 'dd-mm-yy', //swapnil added on 10-jan-23

               orientation: "bottom",
               autoclose: true,
               todayHighlight: true,
               minDate: response.calender_date1
              });
              $('#appointment_to_date').datepicker({
                // dateFormat: 'yy-mm-dd', //swapnil commented 10-jan-23
               dateFormat: 'dd-mm-yy', //swapnil added on 10-jan-23
               orientation: "bottom",
               autoclose: true,
               todayHighlight: true,
               minDate: response.getsecond_date
              });
              //End code added by swapnil on 10-jan-23

            } else {
            $('.appointment-loader').LoadingOverlay("hide");
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


      //Below code changed by swapnil on 21 sept 22
    // function gettimetoradiobutton(element)
    // {
    //     var timeSlot = $("#time_slot_" + element).val();
    //     var rvalue = $('input[name="select_appointment"]:checked').attr('data_time_timeslotradio');
    //     $('#select_appointment_' + element).attr('data-select_appointment_timeslot', timeSlot);
    //     var data_time_timeslot = $("#time_slot_" + element).attr('data_time_timeslot');
    //     let data_time_timeslotradio = $('input[name="select_appointment"]:checked').attr('data_time_timeslotradio');
    //     var data_time_frame = $("#time_frame").attr('data_time_frame');
    //     var data_time_timeslot = $("#time_slot_" + element).attr('data_time_timeslot');
    //     var roster_time_frame_id = $('#time_slot_' + element + ' option:selected').attr('lang');
    //     var appointmentdatetime = $("#select_appointment_" + element).attr("data-select_appointment_timeslot");
    //     var appointmentdate = $("#select_appointment_" + element).attr("data-select_appointment_date");
    //     if (data_time_timeslotradio == data_time_timeslot)
    //     {
    //       $("#time_frame").val(timeSlot);
    //       var roster_time_frame_id = $('#time_slot_' + element + ' option:selected').attr('lang');
    //       $("#roster_time_frame_id").val(roster_time_frame_id);
    //     }
    //     $("#appointment_date_new").val(appointmentdate);
    //     $(".new_appointment_datetime_added").val(timeSlot);
    //     $("#roster_time_frame_id").val(roster_time_frame_id);
    //     $("#select_appointment_"+ element).prop("checked", true);
    // }

   //  function gettimetoradiobutton(element) {
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
   //  }
   // }

   function gettimetoradiobutton(element)
   {
    var timeSlot = $("#time_slot_" + element).val();
    var appointmentdate = $("#select_appointment_" + element).attr("data-select_appointment_date");
    var roster_time_frame_id = $('#time_slot_' + element + ' option:selected').attr('lang');
    $("#time_frame").val(timeSlot);
    $("#roster_time_frame_id").val(roster_time_frame_id);
    $("#appointment_date_new").val(appointmentdate);
    $(".new_appointment_datetime_added").val(timeSlot);
    $("#roster_time_frame_id").val(roster_time_frame_id);
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
      $("#appointment_date_new").val(appointmentdate);
      $("#time_frame").val(appointmentdatetime);
      $('#time_frame').attr('data_time_frame', element);
      $('#time_frame').attr('data_time_frame', element);
      $("#roster_time_frame_id").val(roster_time_frame_id);
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
     $("#time_frame1").val("");
     $("#time_frame").val("");
     $("#appointment_date_new").val("");
   });



//Added below code on 10-nov-23 for dashboard popup on close button reset

   $(".addBtnClosePopup").on('click', function() {
     $("#select2-doctor_id-container").html('');
     $("#select2-appointment_type_id-container").html('');
     $("#suggesstion-box-patient").html('');
     //Swapnil Added Code 20-09-2022
     $("#available_datetime").hide();
     $("#doctor_duty_rosters").hide();
     $("#appointment_date_calender").hide();
     $("#appointment_time_slot").hide();
     $("#time_frame").val("");
     $("#appointment_date_new").val("");
     $("#time_frame1").val("");
    $("#status").attr('checked', false);  //added on 10-nov-23
    $("input[name='app_services[]']").attr('checked', false);//added on 10-nov-23
    $("#frmAppointment")[0].reset();
     //location.reload();
    $("#appointment_type_services").html('');//Aishwarya added this code on 26-may-25
   });
</script>
<!---- Aishwarya added this code on 26-may-25---------------->
 <script>
    $("#app_reset").on('click', function() {
    $("#appointment_type_services").html('');
    /***********Aishwarya added on 3-june-2025***********/
    $('.patient_details').hide();
    $('.patient_details').val("");
    $('#suggesstion_patient_div_id').show();
    $('#search_birth_date_div').show();
});

</script>
<!-- Appointment code by swapnil pawar 12-09-2022  -->




<!--------added on 12-apr-24-for #3app---------------------------->
<script>

   $(".openProfileModal").on('click', function() {

      const google_event_id = $(this).attr('data-id');
      //alert(google_event_id);


        /**********added on 3-apr-24 for app**#3****/

        const action = ADMINURL + "/dashboard/checkPatient/" + google_event_id;
        axios.get(action)
        .then(response => {
          const resp = response.data;
          //alert(resp);
          if(resp)
          {
            if(resp==1)
            {
               $("#profileModal").hide();

              // alert('after hide');


               const action3 = ADMINURL + "/dashboard/addtoDashboard/" + google_event_id;
                axios.get(action3)
                .then(response => {
                  const resp = response.data;
                  if(resp)
                  {
                      /******8-apr-24*******************/
                        if(resp.status == 'success')
                         {
                            toastr.success(resp.msg);
                            $('.openProfileModal').hide();
                            $('#appointmentModal').hide(); // added on 30-may-24 to hide popup
                         }//if status success
                      /******8-apr-24********************/
                  }
                })
                .catch(error => {
                  // $('.card-body').LoadingOverlay("hide");
                })


            }
            else
            {
                $("#profileModal").show();

                    /***********added on 2-apr-24 for app*#3****/
                      //const google_event_id = $(this).attr('data-id');
                      //alert(google_event_id);
                      const action1 = ADMINURL + "/dashboard/patientDetails/" + google_event_id;
                      axios.get(action1)
                      .then(response => {
                        const resp = response.data;
                        if(resp)
                        {
                           // alert(resp);
                          // $("#profileModal .modal-body #popup_description").html(resp);
                          $("#ProfileEditFrm").html(resp);
                        }

                      })
                      .catch(error => {
                        // $('.card-body').LoadingOverlay("hide");
                      })


                    /**********added on 2-apr-24 for app**#3****/


            }//else
          }
          else {

              $("#profileModal").show();

                   /***********added on 2-apr-24 for app*#3****/
                     // const google_event_id = $(this).attr('data-id');
                      //alert(google_event_id);
                      const action1 = ADMINURL + "/dashboard/patientDetails/" + google_event_id;
                      axios.get(action1)
                      .then(response => {
                        const resp = response.data;
                        if(resp)
                        {
                           // alert(resp);
                          // $("#profileModal .modal-body #popup_description").html(resp);
                          $("#ProfileEditFrm").html(resp);
                        }

                      })
                      .catch(error => {
                        // $('.card-body').LoadingOverlay("hide");
                      })


                    /**********added on 2-apr-24 for app**#3****/



          }//else
        })
        .catch(error => {
          // $('.card-body').LoadingOverlay("hide");
        })
        /**********added on 3-apr-24 for app**#3****/

        // $("#profileModal").show();
   });

   $(".closeProfileBtn").on('click', function() {
      $("#profileModal").hide();
   });



</script>
<!--------added on 12-apr-24-for #3app---------------------------->

<!---------start-added on 5-nov-24--for #225-CR--------------------->

<script>
$('#height').change(function()
{

        var height = $(this).val();

         $('.appointment-loader').LoadingOverlay("show", {
         background: "rgba(165, 190, 100, 0.4)",
        });

        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: BASEURL + "/update-height",
            type: 'POST',
            data: {
                height: height,
            },
            success: function(response){

                $('.appointment-loader').LoadingOverlay("hide");

                console.log("=========>");
                console.log(response);
                if (response.status === 'success') {
                    // Set the dropdown to the updated height value
                    $('#height').val(height);

                    // Log the success message
                    console.log("Height updated successfully");
                    window.location.reload();
                } else {
                    console.log(response.msg); // Log error message from server
                }


            },
            error: function(xhr, status, error) {
                console.log("Error occurred: ", error);
            }
        });

});
</script>
<!---------end-added on 5-nov-24-for #225-CR---------------------------------->

<!------- Aishwarya added code on 5-june-25-------->
<script>
    
    $('#new_patient_chkbox').on('change', function() {
        //alert('hi');
    if (!$(this).is(':checked')) {
        //alert("uncheck");
        // Checkbox is NOT checked — clear the services
        $('#appointment_type_services').html('');
        $("#doctor_duty_rosters").hide();
        $("#doctor_duty_rosters").val("");
        $("#available_datetime").hide();
        $("#available_datetime").val("");
        }
});
</script>
@endsection