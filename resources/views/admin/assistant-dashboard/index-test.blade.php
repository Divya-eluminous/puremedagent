@extends('admin.layout.master')
@section('title')
{{ $moduleAction ?? 'Manage Dashboard' }}
@endsection
@section('style')
<!-- fullCalendar --> 
<link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/fullcalendar/main.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/fullcalendar-daygrid/main.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/fullcalendar-timegrid/main.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/fullcalendar-bootstrap/main.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/qtip2/jquery.qtip.min.css') }}">
<style type="text/css">
   /*.modal {
   position: absolute;   
   }*/
    .fc-axis{
      display: none !important;
    }
    .list-wrappper li {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border: 2px solid #2196F3;
    margin-bottom: 10px;
    padding: 10px;
    height: 62px;
    border-radius: 60px;

  }

   .clgtoggle {
     display: flex;
    align-items: center;
    justify-content: space-between;
    border: 1px solid #2196F3 !important;
    /*margin-bottom: 60px;*/
    padding: 0px 10px 0px 20px;
    /*height: 62px;*/
    border-radius: 10px;

  }
  .changeCol{
    background-color: #0da20d!important;
    color: #f1e8e8!important;
  }

  .list-wrappper li h5 {
      font-size: 16px;
      margin-bottom: 0;
  }

  .card-body.list-wrappper ul {
      padding: 0;
  }
  .btn-cnt{
    color: #fff;
    background-color: #F44336;
    border-color: #131112;
    box-shadow: none;
    border-radius: 30px;
    height: 24px;
    width: 40px;
    padding: 0px;
    margin-right: 10px;
  }
  .tableBorder{
    border: none!important;
  }
  .tdClsW
  {
    width:180px!important;
  }
  .w-100-px
  {
    width:100px!important;
  }
  .sticky
  {
      position: -webkit-sticky;
      position: sticky;
      top: 0;
      background: #f4f6f9;
      width: 100%;
      text-align: center;
      z-index: 999999;
  }
</style>
@endsection
@section('content')
<!-- Main content -->
<div class="container my-4">
  <div class="row">

    On test page
    <!-- Grid column -->
    <div class="col-xl-12 mb-3 mb-xl-0">
      <!-- Section: Live preview -->
       <section>
        <ul class="nav nav-tabs sticky" id="myTab" role="tablist">
          <li class="nav-item waves-effect waves-light">
            <a class="nav-link refreshclass" id="totdoList-tab" data-toggle="tab" href="#totdoList" role="tab" aria-controls="totdoList" aria-selected="false">
              <button type="button" class="btn btn-primary btn-cnt">{{count($patient_cnt)}}</button>
            @lang('admin.TITLE_ASSISTANT_DASHBOARD_TODO_LIST') </a>
          </li>
          <li class="nav-item  waves-effect waves-light">
            <a class="nav-link" id="appoinmant_list-tab" data-toggle="tab" href="#appoinmant_list" role="tab" aria-controls="appoinmant_list" aria-selected="false">
             <!--  <button type="button" class="btn btn-primary btn-cnt">{{count($getDismissalHasPatients)}}</button> -->
            @lang('admin.TITLE_ASSISTANT_DASHBOARD_APPOINMENT') </a>
          </li>
          <li class="nav-item waves-effect waves-light">
            <a class="nav-link " id="waiting_list-tab" data-toggle="tab" href="#waiting_list" role="tab" aria-controls="waiting_list" aria-selected="true">
              <button type="button" class="btn btn-primary btn-cnt">{{count($waiting_list)}}</button>
            @lang('admin.TITLE_ASSISTANT_DASHBOARD_WAITING') </a>
          </li>
          <li class="nav-item waves-effect waves-light">
            <a class="nav-link" id="dismissal-tab" data-toggle="tab" href="#dismissal_list" role="tab" aria-controls="dismissal_list" aria-selected="true"><button type="button" class="btn btn-primary btn-cnt">{{count($getDismissalHasPatients)}}</button>

              @lang('admin.TITLE_ASSISTANT_DASHBOARD_DISMISSAL') 
            </a>
          </li>
        </ul>
        <div class="tab-content" id="myTabContent">
          <!-- TODO LIST -->
          <div class="tab-pane fade" id="totdoList" role="tabpanel" aria-labelledby="totdoList-tab">
            <div class="row">
                <div class="col-12">
                    <div id="content" class="card">
                        <!-- /.card-header -->
                        
                        <div id="results" class="card-body list-wrappper" style="padding-top: 15px!important;padding-bottom: inherit!important;">  
                          
                          <?php 
                            $new_flag  = '0';
                            $finging_flag = '';
                            $p_cnt = sizeof($patient);
                            ?>
                            @if(sizeof($patient)>0)
                            @foreach ($patient as $key => $patient)
                            <?php 
                              $endcode_id = base64_encode(base64_encode($patient['id']));
                             
                            ?>
                            <p class="clgtoggle" id="main_{{$patient['id']}}">
                                  @if($patient['old_id'] != '99999' && $patient['note_report_request'] !='' && $patient['note_report_request_flag'] == '1')

                                      <a href="javascript:void(0)" onclick="removeClass('{{$endcode_id}}','{{$patient['id']}}')" target="_blank" style="color: black;" >{{ucfirst($patient['first_name'])}}  
                                      <span id="input_{{$patient['id']}}">{{$patient['family_name']}}</span> 
                                    @if(!empty($patient['birth_date']))
                                      ({{ Date('d.m.Y',strtotime($patient['birth_date']))}})
                                    @endif
                                      </a>

                                  @else
                                    <a  href="javascript:void(0)" onclick="hideDiv('{{$patient['id']}}')" style="color: black;" >
                                    {{ucfirst($patient['first_name'])}}  
                                      <span id="input_{{$patient['id']}}">{{$patient['family_name']}}</span> 
                                    @if(!empty($patient['birth_date']))
                                      ({{ Date('d.m.Y',strtotime($patient['birth_date']))}})
                                    @endif
                                  </a>
                               
                                 <span>
                                  @endif
                                  <?php  
                                  if($patient['old_id'] == '99999' && $patient['note_report_request']!='' && $patient['note_report_request_flag'] == '1')
                                  {
                                    $new_flag = '1';
                                    $finging_flag = 'finding';
                                  ?>
                                  <!-- <a class="btn btn-primary" data-toggle="collapse" href="#collapseExample" role="button" aria-expanded="false" aria-controls="collapseExample">
                                  new
                                  </a> -->
                                  
                                  <a  id="btn_new_{{$patient['id']}}" lang="{{$patient['id']}}" onclick="getPatientsDiv('{{$patient['id']}}',1)" class="newCls btn btn-primary" data-toggle="collapse" href="#collapseNew_{{$patient['id']}}" role="button" aria-expanded="false" aria-controls="collapseNew_{{$patient['id']}}" >@lang('admin.TITLE_NEW')</a>
                                   <?php if(!empty($patient->getOldAppoinmant) && sizeof($patient->getOldAppoinmant)){?>   
                                  <span><a id="btn_finding_{{$patient['id']}}" lang="{{$patient['id']}}" onclick="getPatientsDiv('{{$patient['id']}}',2)" class="findingCls @if($patient['flag'] == '1')btn btn-success @else btn btn-warning @endif" data-toggle="collapse" href="#collapseFinding_{{$patient['id']}}" role="button" aria-expanded="false" aria-controls="collapseFinding_{{$patient['id']}}" >@lang('admin.TITLE_FINDING')</a></span>
                                  <?php }
                                      } 
                                      elseif ($patient['old_id'] != '99999' && !empty($patient['note_report_request']) && $patient['note_report_request_flag'] == '1') { 
                                        $new_flag = '1';
                                        $finging_flag = 'finding';
                                        if(!empty($patient->getOldAppoinmant) && sizeof($patient->getOldAppoinmant)){
                                       ?>
                                        <span><a style="margin-right: -7px;" id="btn_finding_{{$patient['id']}}" lang="{{$patient['id']}}" onclick="getPatientsDiv('{{$patient['id']}}',2)" class="findingCls @if($patient['flag'] == '1')btn btn-success @else btn btn-warning @endif" data-toggle="collapse" href="#collapseFinding_{{$patient['id']}}" role="button" aria-expanded="false" aria-controls="collapseExample" >@lang('admin.TITLE_FINDING')</a>&nbsp;&nbsp;&nbsp;
                                        <a onclick="copyToClipboard('{{$patient["id"]}}')" lang="{{ $patient['id'] }}"   class="btn btn btn-primary highlight" href="javascript:void(0)" >@lang('admin.TITLE_COPY')
                                    </a></span>
                                       <?php 
                                     }
                                     // else
                                     // {
                                      ?>
                                     <!--  <span><a onclick="copyToClipboard('{{$patient["id"]}}')" lang="{{ $patient['id'] }}"   class="btn btn btn-primary highlight" href="javascript:void(0)" >Finding
                                    </a></span> -->
                                    <?php
                                    // }
                                      }
                                      elseif ($patient['old_id'] == '99999' && empty($patient['note_report_request']) && $patient['note_report_request_flag'] != '1') { $new_flag = '1';
                                      ?>
                                        <a  id="btn_new_{{$patient['id']}}" lang="{{$patient['id']}}" id="btn_new_{{$patient['id']}}" lang="{{$patient['id']}}" onclick="getPatientsDiv('{{$patient['id']}}',1)" class="newCls btn btn-primary" data-toggle="collapse" href="#collapseNew_{{$patient['id']}}" role="button" aria-expanded="false" aria-controls="collapseExample" >@lang('admin.TITLE_NEW')</a>

                                      <?php   
                                      }
                                      else
                                      {
                                        ?>
                                         <a lang="{{ $patient['id'] }}"   class="btn btn btn-primary highlight" onClick="getPatientsDiv('{{$patient['id']}}',3)" href="#collapseUpdate_{{$patient['id']}}" role="button" data-toggle="collapse" aria-expanded="false" aria-controls="collapseExample" >@lang('admin.TITLE_CHANGE_PASSWORD_BUTTON')</a>
                                        <?php 
                                      }
                                     ?>                                     
                                    </span>
                            </p>
                            <div class="collapse newClass" id="collapseNew_{{$patient['id']}}">
                              <div class="card card-body">
                                <table class="new-patients table table-bordered tableBorder" > 

                                                @if(!empty($patient->family_name))  
                                                  <tr class="tableBorder"> 
                                                      <td class="tableBorder tdClsW">
                                                        <strong>@lang('admin.TITLE_PATIENT_FAMILY_NAME')</strong>
                                                      </td>
                                                      <td class="tableBorder w-100-px">
                                                        <a onclick="copyTopatientDetails('family_name','{{$patient->id}}')"  class="btn btn btn-primary highlight" href="javascript:void(0)" >@lang('admin.TITLE_COPY')
                                                        </a>
                                                      </td>
                                                      <td class="tableBorder">
                                                        <input readonly class="form-control" type="text" name="family_name" id="family_name_{{$patient->id}}" value="{{ $patient->family_name }}"></td>
                                                  </tr>
                                                @endif
                                                
                                                @if(!empty($patient->first_name))    
                                                  <tr> 
                                                      <td class="tableBorder tdClsW">
                                                        <strong>@lang('admin.TITLE_PATIENT_FIRST_NAME')</strong></td>
                                                      <td class="tableBorder w-100-px">
                                                        <a onclick="copyTopatientDetails('first_name','{{$patient->id}}')"  class="btn btn btn-primary highlight" href="javascript:void(0)" >@lang('admin.TITLE_COPY')
                                                        </a>
                                                      </td>
                                                      <td class="tableBorder">
                                                        <input readonly class="form-control" type="text" name="first_name" id="first_name_{{$patient->id}}" value="{{ $patient->first_name }}">
                                                      </td>
                                                  </tr>
                                                @endif
                                                
                                                @if(!empty($patient->email))   
                                                   <tr> 
                                                      <td class="tableBorder tdClsW"><strong>@lang('admin.TITLE_PATIENT_EMAIL')</strong></td>
                                                      <td class="tableBorder w-100-px">
                                                        <a onclick="copyTopatientDetails('email','{{$patient->id}}')"  class="btn btn btn-primary highlight" href="javascript:void(0)" >@lang('admin.TITLE_COPY')
                                                        </a>
                                                      </td>
                                                      <td class="tableBorder">
                                                        <input readonly class="form-control" type="text" name="email" id="email_{{$patient->id}}" value="{{ $patient->email }}">
                                                      </td>
                                                  </tr>
                                                @endif
                                                @if(!empty($patient->road))     
                                                   <tr> 
                                                      <td class="tableBorder tdClsW"><strong>@lang('admin.TITLE_PATIENT_ROAD')</strong></td>
                                                      <td class="tableBorder w-100-px">
                                                        <a onclick="copyTopatientDetails('road','{{$patient->id}}')"  class="btn btn btn-primary highlight" href="javascript:void(0)" >@lang('admin.TITLE_COPY')
                                                        </a>
                                                      </td>
                                                      <td class="tableBorder">
                                                        <input readonly class="form-control" type="text" name="road" id="road_{{$patient->id}}" value="{{ $patient->road }}">
                                                      </td>
                                                  </tr>
                                                @endif
                                                @if(!empty($patient->postal_code))     
                                                  <tr> 
                                                      <td class="tableBorder tdClsW"><strong>@lang('admin.TITLE_PATIENT_POSTAL_CODE')</strong></td>
                                                      <td class="tableBorder w-100-px">
                                                        <a onclick="copyTopatientDetails('postal_code','{{$patient->id}}')"  class="btn btn btn-primary highlight" href="javascript:void(0)" >@lang('admin.TITLE_COPY')
                                                        </a>
                                                      </td>
                                                      <td class="tableBorder">
                                                        <input readonly class="form-control " type="text" name="postal_code" id="postal_code_{{$patient->id}}" value="{{ $patient->postal_code }}">
                                                      </td>
                                                  </tr>
                                                @endif
                                                @if(!empty($patient->place)) 
                                                  <tr> 
                                                      <td class="tableBorder tdClsW"><strong>@lang('admin.TITLE_PATIENT_PLACE')</strong></td>
                                                      <td class="tableBorder w-100-px">
                                                        <a onclick="copyTopatientDetails('place','{{$patient->id}}')"  class="btn btn btn-primary highlight" href="javascript:void(0)" >@lang('admin.TITLE_COPY')
                                                        </a>
                                                      </td>
                                                      <td class="tableBorder">
                                                         <input readonly class="form-control" type="text" name="place" id="place_{{$patient->id}}" value="{{ $patient->place }}">
                                                      </td>
                                                  </tr>
                                                @endif
                                                @if(!empty($patient->insurance_number)) 
                                                  <tr> 
                                                      <td class="tableBorder tdClsW"><strong>@lang('admin.TITLE_PATIENT_ENSURANCE_NUMBER')</strong></td>
                                                      <td class="tableBorder w-100-px">
                                                        <a onclick="copyTopatientDetails('insurance_number','{{$patient->id}}')"  class="btn btn btn-primary highlight" href="javascript:void(0)" >@lang('admin.TITLE_COPY')
                                                        </a>
                                                      </td>
                                                      <td class="tableBorder">
                                                        <input readonly class="form-control" type="text" name="insurance_number" id="insurance_number_{{$patient->id}}" value="{{ $patient->insurance_number }}">
                                                      </td>
                                                  </tr>
                                                @endif
                                                @if(!empty($patient->country_code) || !empty($patient->mobile_no)) 
                                                  <tr> 
                                                      <td class="tableBorder tdClsW"><strong>@lang('admin.TITLE_PATIENT_COUNTRY_CODE') -        
                                                        @lang('admin.TITLE_PATIENT_MOBILE_NO')</strong></td>
                                                      <td class="tableBorder w-100-px">
                                                        <a onclick="copyTopatientDetails('mobile_no','{{$patient->id}}')"  class="btn btn btn-primary highlight" href="javascript:void(0)" >@lang('admin.TITLE_COPY')
                                                        </a>
                                                      </td>
                                                      <td class="tableBorder">
                                                        <input readonly class="form-control" type="text" name="mobile_no" id="mobile_no_{{$patient->id}}" value="{{$patient->country_code}} . {{ $patient->mobile_no }}">
                                                      </td>
                                                  </tr>
                                                @endif
                                                @if(!empty($patient->size)) 
                                                  <tr> 
                                                      <td class="tableBorder tdClsW"><strong>@lang('admin.TITLE_PATIENT_SIZE')</strong></td>
                                                      <td class="tableBorder w-100-px">
                                                        <a onclick="copyTopatientDetails('size','{{$patient->id}}')"  class="btn btn btn-primary highlight" href="javascript:void(0)" >@lang('admin.TITLE_COPY')
                                                        </a>
                                                      </td>
                                                      <td class="tableBorder">
                                                        <input readonly class="form-control" type="text" name="size" id="size_{{$patient->id}}" value="{{$patient->size}}">
                                                      </td>
                                                  </tr>
                                                @endif
                                                @if(!empty($patient->weight)) 
                                                  <tr> 
                                                      <td class="tableBorder tdClsW"><strong>@lang('admin.TITLE_PATIENT_WEIGHT')</strong></td>
                                                      <td class="tableBorder w-100-px">
                                                        <a onclick="copyTopatientDetails('weight','{{$patient->id}}')"  class="btn btn btn-primary highlight" href="javascript:void(0)" >@lang('admin.TITLE_COPY')
                                                        </a>
                                                      </td>
                                                      <td class="tableBorder">
                                                        <input readonly class="form-control" type="text" name="weight" id="weight_{{$patient->id}}" value="{{$patient->weight}}">
                                                      </td>
                                                  </tr>
                                                @endif
                                                @if(!empty($patient->title)) 
                                                  <tr> 
                                                      <td class="tableBorder tdClsW"><strong>@lang('admin.TITLE_PATIENT_TITLE')</strong></td>
                                                      <td class="tableBorder w-100-px">
                                                        <a onclick="copyTopatientDetails('title','{{$patient->id}}')"  class="btn btn btn-primary highlight" href="javascript:void(0)" >@lang('admin.TITLE_COPY')
                                                        </a>
                                                      </td>
                                                      <td class="tableBorder">
                                                        <input readonly class="form-control" type="text" name="title" id="title_{{$patient->id}}" value="{{$patient->title}}">
                                                      </td>
                                                  </tr>
                                                @endif
                                                @if(!empty($patient->family_doctor)) 
                                                  <tr> 
                                                      <td class="tableBorder tdClsW"><strong>@lang('admin.TITLE_PATIENT_FAMILY_DOCTOR')</strong></td>
                                                      <td class="tableBorder w-100-px">
                                                        <a onclick="copyTopatientDetails('family_doctor','{{$patient->id}}')"  class="btn btn btn-primary highlight" href="javascript:void(0)" >@lang('admin.TITLE_COPY')
                                                        </a>
                                                      </td>
                                                      <td class="tableBorder">
                                                        <input readonly class="form-control" type="text" name="family_doctor" id="family_doctor_{{$patient->id}}" value="{{$patient->family_doctor}}">
                                                       </td>
                                                  </tr>
                                                @endif
                                                @if(!empty($patient->additional_insurance)) 
                                                  <tr> 
                                                      <td class="tableBorder tdClsW"><strong>@lang('admin.TITLE_PATIENT_ADDITIONAL_ENSURANCE')</strong></td>
                                                      <td class="tableBorder w-100-px">
                                                        <a onclick="copyTopatientDetails('additional_insurance','{{$patient->id}}')"  class="btn btn btn-primary highlight" href="javascript:void(0)" >@lang('admin.TITLE_COPY')
                                                        </a>
                                                      </td>
                                                      <td class="tableBorder">
                                                        <input readonly class="form-control" type="text" name="additional_insurance" id="additional_insurance_{{$patient->id}}" value="{{$patient->additional_insurance}}">
                                                      </td>
                                                  </tr>
                                                @endif
                                                @if(!empty($patient->birth_date))
                                                  <tr> 
                                                      <td class="tableBorder tdClsW"><strong>@lang('admin.TITLE_PATIENT_BIRTH_DATE')</strong></td>
                                                      <td class="tableBorder w-100-px">
                                                        <a onclick="copyTopatientDetails('birth_date','{{$patient->id}}')"  class="btn btn btn-primary highlight" href="javascript:void(0)" >@lang('admin.TITLE_COPY')
                                                        </a>
                                                      </td>
                                                      <td class="tableBorder">
                                                        <input readonly class="form-control" type="text" name="birth_date" id="birth_date_{{$patient->id}}" value="{{ $patient->birth_date?date('d.m.Y',strtotime($patient->birth_date)):'' }}">
                                                      </td>
                                                  </tr>
                                                @endif
                                                @if(!empty($patient->pat_nr))
                                                  <tr> 
                                                      <td class="tableBorder tdClsW"><strong>@lang('admin.TITLE_GANY_PAT_NR')</strong></td>
                                                      <td class="tableBorder w-100-px">
                                                        <a onclick="copyTopatientDetails('pat_nr','{{$patient->id}}')"  class="btn btn btn-primary highlight" href="javascript:void(0)" >@lang('admin.TITLE_COPY')
                                                        </a>
                                                      </td>
                                                      <td class="tableBorder">
                                                        <input readonly class="form-control" type="text" name="pat_nr" id="pat_nr_{{$patient->id}}" value="{{ $patient->pat_nr }}">
                                                      </td>
                                                  </tr>
                                                @endif  
                                </table>
                                <span class="span_cls" >
                                    <a onclick="completedNew(this,'{{$patient->id}}','completed','new')" class="btn btn btn-primary" href="javascript:void(0)" >@lang('admin.TITLE_TOTO_NEW_COMPLETED')</a>
                                    <a onclick="completedNew(this,'{{$patient->id}}','next','new')" class="btn btn btn-primary" href="javascript:void(0)" >@lang('admin.TITLE_TOTO_NEW_COMPLETED_NEXT')</a>
                                    <a onclick="CancelNew(this,'{{$patient->id}}')" class="btn btn btn-primary highlight" href="javascript:void(0)" >@lang('admin.TITLE_TOTO_ABORT')</a>
                                </span>
                              </div>
                            </div>

                            <div  class="collapse findClass" id="collapseFinding_{{$patient['id']}}">
                              <div class="card card-body">
                                <table class="old-appoinmant table table-bordered tableBorder " >
                                              @if(!empty($patient->getOldAppoinmant) && sizeof($patient->getOldAppoinmant))  
                                              <?php 
                                              $cnt = 1;
                                              $flag = '1';
                                              ?>
                                              @foreach($patient->getOldAppoinmant as $d_key => $d_val)
                                              <?php 
                                              $cls = '';
                                              if($d_val['imported_flag'] == '1')
                                              {
                                                $cls = 'changeCol';
                                              }
                                              else
                                              {
                                                $flag = '0';
                                              }
                                              ?>
                                                <tr class="tableBorder"> 
                                                    <td class="tableBorder" style="width: 25%">
                                                      <strong>Befunddatum</strong>
                                                    </td>
                                                    <td class="tableBorder" style="width: 30%">
                                                      <input readonly class="form-control" type="text" name="date[]" id="date_{{$d_val['id']}}" value="@if($d_val['appoinmant_date']) {{Date('d.m.Y',strtotime($d_val['appoinmant_date']))}} @endif"></td>
                                                    <td class="tableBorder" style="width: 40%">
                                                      
                                                        @if($cls == '')
                                                          @if($import_finding_setting['setting_value'] == 'on')
                                                          <a onclick="showImportaFinding(this,'{{$d_val['id']}}','{{$patient['id']}}','{{$d_val['appoinmant_date']}}')" title="{{__('admin.TITLE_TODO_LIST_IMPORT_FINDING')}}" type="button" class="btn btn btn-primary" >
                                                        @lang('admin.TITLE_TODO_LIST_IMPORT_FINDING')
                                                      </a>
                                                          @else
                                                          <!-- <a onclick="showSendFinding(this,'{{$import_finding_via_email_setting->setting_value}}','{{$patient['id']}}','{{$d_val['id']}}',1)" title="{{__('admin.TITLE_TODO_LIST_IMPORT_FINDING')}}" type="button" class="btn btn btn-success" >
                                                          @lang('admin.TITLE_TODO_LIST_SEND')
                                                          </a> -->
                                                          @endif  
                                                        @else
                                                        <a onclick="showSendFinding(this,'{{$import_finding_via_email_setting->setting_value}}','{{$patient['id']}}','{{$d_val['id']}}',1)" title="{{__('admin.TITLE_TODO_LIST_IMPORT_FINDING')}}" type="button" class="btn btn btn-success" >
                                                          @lang('admin.TITLE_TODO_LIST_SEND')
                                                          </a>
                                                         
                                                        @endif
                                                      
                                                    </td>
                                                </tr>
                                                @if($cnt == sizeof($patient->getOldAppoinmant))
                                                <tr>
                                                  <td class="tableBorder" style="width: 25%">
                                                      <strong>@lang('admin.TITLE_APPOINTMENT_NOTE')</strong>
                                                    </td>
                                                    <td class="tableBorder" style="width: 30%">
                                                      <textarea
                                                      type="text" 
                                                      name="notes" 
                                                      id="notes"
                                                      class="form-control" 
                                                      ></textarea>
                                                     
                                                    <td class="tableBorder" style="width: 40%">
                                                      @if($flag == '1')
                                                        <a onclick="showSendFinding(this,'{{$import_finding_via_email_setting->setting_value}}','{{$patient['id']}}',null,2)" title="{{__('admin.TITLE_TODO_LIST_IMPORT_FINDING')}}" type="button" class="btn btn btn-success" >
                                                        @lang('admin.TITLE_TODO_LIST_SEND_ALL')
                                                        </a>
                                                      @endif
                                                    </td>
                                                </tr>
                                                @endif
                                              <?php $cnt++?>
                                              @endforeach  
                                              @endif
                                          </table>
                                <span class="span_cls" >
                                    <a onclick="completedNew(this,'{{$patient->id}}','completed','finding')" class="btn btn btn-primary" href="javascript:void(0)" >@lang('admin.TITLE_TOTO_NEW_COMPLETED')</a>
                                    <a onclick="completedNew(this,'{{$patient->id}}','next','finding')" class="btn btn btn-primary" href="javascript:void(0)" >@lang('admin.TITLE_TOTO_NEW_COMPLETED_NEXT')</a>
                                    <a onclick="CancelNew(this)" class="btn btn btn-primary highlight" href="javascript:void(0)" >@lang('admin.TITLE_TOTO_ABORT')</a>
                                </span>          
                                       
                              </div>
                            </div>
                             <div  class="collapse updateClass" id="collapseUpdate_{{$patient['id']}}"> <div class="card card-body">
                                <table class="new-patients table table-bordered tableBorder" >
                                  @php
                                 echo $diff_data = App::make("App\Http\Controllers\Admin\AssistantDashboardController")->_checkRecordWithGanymed($patient->old_id);
                                  @endphp          
                                </table>
                                <span class="span_cls" >
                                    <a onclick="completedNew(this,'{{$patient->id}}','completed','new')" class="btn btn btn-primary" href="javascript:void(0)" >@lang('admin.TITLE_TOTO_NEW_COMPLETED')</a>
                                    <a onclick="completedNew(this,'{{$patient->id}}','next','new')" class="btn btn btn-primary" href="javascript:void(0)" >@lang('admin.TITLE_TOTO_NEW_COMPLETED_NEXT')</a>
                                    <a onclick="CancelNew(this,'{{$patient->id}}')" class="btn btn btn-primary highlight" href="javascript:void(0)" >@lang('admin.TITLE_TOTO_ABORT')</a>
                                </span>
                              </div></div>
                           @endforeach  
                        @endif
                        </div> 
                       
                    </div>
                </div>
            </div>
          </div>
          <!-- TODO LIST END -->

          <!-- Appoinmant tab -->
          <div class="tab-pane fade active" id="appoinmant_list" role="tabpanel" aria-labelledby="appoinmant_list-tab">
            <section class="content">

              <!-- <div id='loader'>Loading</div> -->
              <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-10"></div>
                    <div class="col-lg-2" style="margin-top: 2.5%;padding-left: 22px;"> 
                      <button type="button" id="addAppbutton" class="btn fc-button-primary" data-toggle="modal" data-target="#addAppointmentModal" >
                      @lang('admin.ADD_APPOINTMENT')
                      </button>
                    <!--   <button type="button" id="doctorAvailButton" class="btn fc-button-primary" data-toggle="modal" data-target="#doctorAvailabilityModal" >
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
                                                  maxlength="12" 
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
                                 
                                  @if (Auth::user()->hasRole('super-admin') || (Auth::user()->hasRole('Assistant')) )
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
                           <div class="form-group" id="appointment_type_services">
                              
                           </div>
                        </div>
                               </div>
                            </div>               
                         <div class="modal-footer">
                            <button type="submit" class="btn btn-success">@lang('admin.TITLE_SAVE_BUTTON')</button>
                            <button type="reset" class="btn btn-danger">@lang('admin.TITLE_RESET_BUTTON')</button>
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
                                        <th class="w-200-px">@lang('admin.TITLE_WAITING_PATIENT_QUEUE_NUMBER')</th>
                                        <th class="w-200-px">@lang('admin.TITLE_WAITING_TYPE')</th>
                                        <th class="w-200-px">@lang('admin.TITLE_WAITING_PATIENT_SCAN_TIME')</th>
                                        <th class="text-center w-130-px">@lang('admin.TITLE_ACTIONS_TEXT')</th>

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
                        @if(!empty($getDismissalHasPatients) && count($getDismissalHasPatients)>0)
                          @foreach($getDismissalHasPatients as $key => $val)
                            <div class="row">
                              <div class="col-sm-3"> 
                                <div class="form-group">
                                  <input type="hidden" name="p_id[]" value="{{$val['patient']['p_id']}}">
                                  <label class="theme-blue" style="font-weight: 500!important;font-size: 18px;">
                                    <p>{{$val['patient']['full_name']}}</p>
                                  </label>
                                </div>
                              </div>
                              <div class="col-sm-8"> 
                                  <div class="p-0 form-group"> 
                                       <button type="button" onclick="DismissalDone(this,'{{$val["patient"]["p_id"]}}')" class="btn btn-primary">@lang('admin.TITLE_DISMISSAL_BUTTON')</button> 
                                      
                                  </div>
                              </div>
                            </div>  
                            <div class="row">
                              @if(!empty($val['patient']['dismissal']) && count($val['patient']['dismissal'])>0)
                                @foreach($val['patient']['dismissal'] as $d_key => $d_val)
                                  <div class="col-sm-6">
                                   <div class="form-group">
                                      <div class="form-check"> 
                                         <input type="checkbox" class="form-check-input"
                                            name="dismissal_{{$val["patient"]["p_id"]}}[]" value="{{$d_val['id']}}" 
                                            >
                                         <label class="form-check-label" for="new_patient_chkbox">{{$d_val['name']}}</label>
                                      </div>
                                   </div>
                                  </div>
                                  
                                @endforeach  
                              @endif  
                            </div>
                            <div class="row">
                                    <label style="color:red;display: none;" class="error_{{$val["patient"]["p_id"]}}">@lang('admin.ERR_DISMISSAL_REQUIRED')</label>
                                  </div>
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
                                            <input required type="file" name="import" class="form-control"  data-error="@lang('admin.ERR_TODO_LIST_IMP')">
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
<script src="{{ asset('assets/admin-lte/plugins/fullcalendar/main.min.js') }}"></script>
<script src="{{ asset('assets/admin-lte/plugins/fullcalendar/locales-all.min.js') }}"></script>
<script src="{{ asset('assets/admin-lte/plugins/fullcalendar-daygrid/main.min.js') }}"></script>
<script src="{{ asset('assets/admin-lte/plugins/fullcalendar-timegrid/main.min.js') }}"></script>
<script src="{{ asset('assets/admin-lte/plugins/fullcalendar-interaction/main.min.js') }}"></script>
<script src="{{ asset('assets/admin-lte/plugins/fullcalendar-bootstrap/main.min.js') }}"></script>
<script src="{{ asset('assets/plugins/qtip2/jquery.qtip.min.js') }}"></script>
<script type="text/javascript" src="{{ url('assets/admin/js/assistant-dashboard/qrcode.js') }}"></script>
<script type="text/javascript" src="{{ url('assets/admin/js/assistant-dashboard/index.js') }}"></script>
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
 
</script>
@endsection