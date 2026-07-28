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
    border: 2px solid #2196F3 !important;
    /*margin-bottom: 60px;*/
    padding: 10px;
    /*height: 62px;*/
    border-radius: 60px;

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
    width: 29px;
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
        <ul class="nav nav-tabs" id="myTab" role="tablist">
          <li class="nav-item waves-effect waves-light">
            <a class="nav-link active refreshclass" id="totdoList-tab" data-toggle="tab" href="#totdoList" role="tab" aria-controls="totdoList" aria-selected="false">
              <button type="button" class="btn btn-primary btn-cnt">{{count($patient)}}</button>
            @lang('admin.TITLE_ASSISTANT_DASHBOARD_TODO_LIST') </a>
          </li>
        
        </ul>
        <div class="tab-content" id="myTabContent">
          <!-- TODO LIST -->
          <div class="tab-pane fade active show" id="totdoList" role="tabpanel" aria-labelledby="totdoList-tab">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <!-- /.card-header -->
                        <?php 
                        $new_flag  = '0';
                        $finging_flag = '';
                        ?>
                        @foreach ($patient as $key => $patient)

                        <div class="card-body list-wrappper" style="padding-top: 15px!important;padding-bottom: inherit!important;">  
                          
                          <div id="div_{{$patient['id']}}" class="card collapsed-card getDiv" style="box-shadow: none!important;">
                            <div class="card-header clgtoggle" style="background-color:'.$value->code.'">

                                <h3 class="card-title " data-card-widget="collapse">
                                  {{ucfirst($patient['first_name'])}}  {{$patient['id']}}
                                  <span id="input_{{$patient['id']}}">{{$patient['family_name']}}</span> 
                                  @if(!empty($patient['birth_date']))
                                    ({{ Date('d-m-Y',strtotime($patient['birth_date']))}})
                                  @endif
                                </h3>

                                <div class="card-tools">                               
                                    <span style="padding-right: 10px!important;" class="" type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Leistungen">
                                      <?php 

                                      if($patient['old_id'] == '99999' && $patient['note_report_request']!='' && $patient['note_report_request_flag'] == '1')
                                      {
                                        $new_flag = '1';
                                        $finging_flag = 'finding';
                                      ?>
                                        <a onclick="getPatientsDiv('{{$patient['id']}}','new')" class="btn btn-primary" href="" >@lang('admin.TITLE_NEW')</a>
                                      
                                        <a onclick="getPatientsDiv('{{$patient['id']}}','finding')" class="btn btn-warning" href="" >@lang('admin.TITLE_FINDING')</a>
                                      <?php 
                                      } 
                                      elseif ($patient['old_id'] != '99999' && !empty($patient['note_report_request']) && $patient['note_report_request_flag'] == '1') { 
                                        $new_flag = '1';
                                        $finging_flag = 'finding';
                                       ?>
                                        <a onclick="getPatientsDiv('{{$patient['id']}}','finding')" class="btn btn-danger" href="" >@lang('admin.TITLE_FINDING')</a>
                                       <?php 
                                      }
                                      elseif ($patient['old_id'] == '99999' && empty($patient['note_report_request']) && $patient['note_report_request_flag'] != '1') { $new_flag = '1';
                                      ?>
                                        <a onclick="getPatientsDiv('{{$patient['id']}}','new')" class="btn btn btn-primary" href="" >@lang('admin.TITLE_NEW')</a>
                                      <?php   
                                      }
                                      ?>  
                                      <a onclick="copyToClipboard(this,'{{$patient['id']}}')"  class="btn btn btn-primary highlight" href="javascript:void(0)" >@lang('admin.TITLE_COPY')</a>
                                    </span>
                                   
                                </div>
                            </div>
                              <!-- <div class="div-empty" style="display: none!important;"> -->
                              <div id="sub_{{$patient['id']}}" class="card-body sub-div">
                                  <div class="col-md-12" >
                                      <div class="col-md-12 col-sm-12" id="popup_description">

                                          <p>
                                            <!-- FINDING FOR PATIENTS -->
                                            <table style="display: none!important;" class="old-appoinmant table table-bordered tableBorder " style="width:60%;" >
                                                @if(!empty($patient->getOldAppoinmant) && sizeof($patient->getOldAppoinmant))  
                                                <?php 
                                                $cnt = 1;
                                                $flag = '';
                                                ?>
                                                @foreach($patient->getOldAppoinmant as $d_key => $d_val)
                                                <?php 
                                                $cls = '';
                                                if($d_val['imported_flag'] == '1')
                                                {
                                                  $cls = 'changeCol';
                                                  $flag = '1';
                                                }
                                                ?>
                                                  <tr class="tableBorder"> 
                                                      <td class="tableBorder" style="width: 25%">
                                                        <strong>@lang('admin.TITLE_TODO_LIST_FINDING')</strong>
                                                      </td>
                                                      <td class="tableBorder" style="width: 30%">
                                                        <input readonly class="form-control" type="text" name="date[]" id="date_{{$d_val['id']}}" value="@if($d_val['appoinmant_date']) {{Date('d-m-Y',strtotime($d_val['appoinmant_date']))}} @endif"></td>
                                                      <td class="tableBorder" style="width: 40%">
                                                        
                                                          @if($cls == '')
                                                            @if($import_finding_setting['setting_value'] == 'on')
                                                            <a onclick="showImportaFinding(this,'{{$d_val['id']}}','{{$patient['id']}}','{{$d_val['appoinmant_date']}}')" title="{{__('admin.TITLE_TODO_LIST_IMPORT_FINDING')}}" type="button" class="btn btn btn-primary" >
                                                          @lang('admin.TITLE_TODO_LIST_IMPORT_FINDING')
                                                          </a>
                                                            @endif  
                                                          @else

                                                            @if($import_finding_via_email_setting['setting_value'] == 'on')
                                                            <a title="{{__('admin.TITLE_TODO_LIST_IMPORT_FINDING')}}" type="button" class="btn btn btn-success" >
                                                            @lang('admin.TITLE_TODO_LIST_SEND')
                                                            </a>
                                                            @endif
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
                                                        class="form-control" 
                                                        ></textarea>
                                                       
                                                      <td class="tableBorder" style="width: 40%">
                                                        @if($flag == '1' &&  $import_finding_via_email_setting['setting_value'] == 'on')
                                                          <a title="{{__('admin.TITLE_TODO_LIST_IMPORT_FINDING')}}" type="button" class="btn btn btn-success" >
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
                                         
                                            <!-- NEW LABEL FOR PATIENTS -->
                                            <table class="new-patients table table-bordered tableBorder" style="width:100%;display: none!important;" > 
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
                                                        <td class="tableBorder tdClsW"><strong>@lang('admin.TITLE_PATIENT_COUNTRY_CODE'). @lang('admin.TITLE_PATIENT_MOBILE_NO')</strong></td>
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
                                                          <input readonly class="form-control" type="text" name="birth_date" id="birth_date_{{$patient->id}}" value="{{ $patient->birth_date?date('d-m-Y',strtotime($patient->birth_date)):'' }}">
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
                                            <span class="span_cls" style="display: none">
                                                <a onclick="completedNew(this,'{{$patient->id}}','completed')" class="btn btn btn-primary" href="javascript:void(0)" >@lang('admin.TITLE_TOTO_NEW_COMPLETED')</a>
                                                <a onclick="completedNew(this,'{{$patient->id}}','next')" class="btn btn btn-primary" href="javascript:void(0)" >@lang('admin.TITLE_TOTO_NEW_COMPLETED_NEXT')</a>
                                                <a onclick="CancelNew(this)" class="btn btn btn-primary highlight" href="javascript:void(0)" >@lang('admin.TITLE_TOTO_ABORT')</a>
                                            </span>
                                         
                                         </p>
                                      </div>
                                  </div>
                              </div>
                            <!-- </div> -->
                          </div> 
                        </div>
                        @endforeach  
                    </div>
                </div>
            </div>
          </div>
          <!-- TODO LIST END -->

         
         
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
 //  setTimeout(function(){
 //    if($(".refreshclass").hasClass('active'))
 //    {
 //      window.location.reload();
 //    }
    
 // },10000);
</script>
@endsection