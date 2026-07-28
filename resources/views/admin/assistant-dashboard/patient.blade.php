@extends('admin.layout.master')

@section('title')
   {{ $moduleTitle }}
@endsection

@section('content') 
<section class="content">
<div class="container-fluid">     
    <div class="row"> 
        <!-- left column -->
        <div class="col-md-12"> 
            <!-- jquery validation -->
            <div class="card card-primary"> 
                <div class="card-header">
                    <h3 class="card-title">{{ $formTitle }}</h3>
                    <!-- <button class="btn btn-light float-right" onclick="window.history.back()">@lang('admin.TITLE_BACK_BUTTON')</button> -->
                </div>

                <form id="frmPatients" role="form" data-toggle="validator" action="">
                    <input type="hidden" name="_method" value="PUT">
                    <div class="card-body">
                        <div class="row">
                            @if(!empty($patient->family_name))
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_FAMILY_NAME') <span class="required">*</span></label>
                                    <input 
                                        readonly 
                                        type="text" 
                                        name="family_name" 
                                        class="form-control"
                                        value="{{ $patient->family_name }}"  
                                        required
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
                            @endif

                            @if(!empty($patient->first_name))
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_FIRST_NAME') <span class="required">*</span></label>
                                    <input 
                                        type="text"
                                        readonly  
                                        name="first_name" 
                                        class="form-control"  
                                        value="{{ $patient->first_name }}" 
                                        required
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
                            @endif
                        <!-- </div>
                        <div class="row"> -->
                            @if(!empty($patient->email))
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_EMAIL') </label>
                                    <input 
                                        type="email" 
                                        readonly 
                                        name="email" 
                                        class="form-control"  
                                        value="{{ $patient->email }}"
                                        maxlength="250" 
                                    >
                                        <!-- required
                                        data-error="@lang('admin.ERR_EMAIL_NAME')" -->
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_email"></li>
                                        </ul>
                                    </span> 
                                </div>
                            </div>
                            @endif

                            @if(!empty($patient->country_code))
                            <div class="col-sm-2"> 
                                <div class="form-group ">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_COUNTRY_CODE') <span class="required">*</span></label> 
                                     <div class="select-editable"><select 
                                        class="form-control my-select"
                                        name="country_code"
                                        readonly 
                                        id="country_code"
                                        required
                                        maxlength="250" 
                                        data-error="@lang('admin.ERR_COUNTRY_CODE_REQUIRED')"
                                        onchange="this.nextElementSibling.value=this.value">
                                        @php
                                            $country_codes = ['+43', '0043', '0'];
                                            if(!in_array($patient->country_code,$country_codes)){
                                             $country_codes[] = $patient->country_code;
                                            }

                                        @endphp
                                        @foreach($country_codes as $item)
                                            <option value="{{$item}}"
                                            @if($patient->country_code == old('country_code', $item)) selected="selected" @endif >{{$item}}</option>
                                        @endforeach
                                    </select>
                                   <input  
                                        type="text" 
                                        name="format"
                                        id="format"  
                                        class="form-control"
                                        placeholder="@lang('admin.TITLE_PATIENT_COUNTRY_CODE')"   
                                        required
                                        value="{{ $patient->country_code }}"
                                        maxlength="5" 
                                        pattern="(\+[0-9]+|0[0-9]+|00[0-9]+)"
                                        data-error="@lang('admin.ERR_COUNTRY_CODE_REQUIRED')"
                                        data-pattern-error ="@lang('admin.ERR_COUNTRY_CODE_WRONG')"  />
                                     </div>
                                    <span class="help-block invalid-feedback with-errors" >
                                        <ul class="list-unstyled">
                                            <li class="err_country_code"></li>
                                        </ul>
                                    </span>
                                </div> 
                            </div>
                            @endif

                            @if(!empty($patient->mobile_no))
                            <div class="col-sm-4"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_MOBILE_NO') <span class="required">*</span></label> 
                                    <input 
                                        type="text" 
                                        readonly 
                                        name="mobile_no" 
                                        id="mobile_no"  
                                        class="form-control"  
                                        value="{{ $patient->mobile_no }}"
                                        maxlength="17" 
                                        required
                                        data-error="@lang('admin.ERR_MOBILE_NO_REQUIRED')" 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_mobile_no"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            @endif
                        <!-- </div>
                        <div class="row"> -->
                            @if(!empty($patient->birth_date))
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_BIRTH_DATE') <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        readonly 
                                        name="birth_date" 
                                        class="form-control"  
                                        id="birth_date"
                                        value="{{ $patient->birth_date?date('d-m-Y',strtotime($patient->birth_date)):'' }}"
                                        maxlength="250"
                                        data-error="@lang('admin.ERR_BIRTH_DATE_REQUIRED')"
                                        required 
                                    >
                                        <!-- required
                                        data-error="@lang('admin.ERR_BIRTH_DATE_REQUIRED')"  -->
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_birth_date"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            @endif
                        
                            @if(!empty($patient->road))
                            <div class="col-sm-6">  
                                <div class="form-group"> 
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_ROAD') </label>
                                    <input 
                                        type="text"
                                        readonly  
                                        name="road"  
                                        class="form-control" 
                                        value="{{ $patient->road }}" 
                                        maxlength="250" 
                                    >
                                        <!-- required
                                        data-error="@lang('admin.ERR_ROAD_REQUIRED')"  -->
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_road"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            @endif
                        <!-- </div>

                        <div class="row"> -->
                            @if(!empty($patient->place))
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_PLACE') </label>
                                    <input 
                                        type="text" 
                                        readonly 
                                        name="place" 
                                        class="form-control" 
                                        value="{{ $patient->place }}" 
                                        maxlength="250" 
                                    >
                                        <!-- required
                                        data-error="@lang('admin.ERR_PLACE_REQUIRED')"  -->
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_place"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            @endif
                        
                            @if(!empty($patient->postal_code))
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_POSTAL_CODE') </label>
                                    <input 
                                        type="text" 
                                        readonly 
                                        name="postal_code" 
                                        class="form-control" 
                                        value="{{ $patient->postal_code }}"
                                        maxlength="250" 
                                    >
                                        <!-- required
                                        data-error="@lang('admin.ERR_PATIENT_POSTAL_CODE_REQUIRED')"   -->
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_postal_code"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            @endif
                        
                            @if(!empty($patient->title))
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_TITLE')</label>
                                    <input 
                                        type="text" 
                                        readonly 
                                        name="title" 
                                        class="form-control" 
                                        value="{{ $patient->title }}"
                                        maxlength="250" 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_title"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            @endif
                            
                       <!--  </div>
                        <div class="row"> -->
                            @if(!empty($patient->family_doctor))
                            <div class="col-sm-6"> 
                                <div class="form-group"> 
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_FAMILY_DOCTOR')</label>
                                    <input 
                                        type="text"
                                        readonly  
                                        name="family_doctor" 
                                        id="family_doctor"
                                        class="form-control" 
                                        value="{{ $patient->family_doctor }}" 
                                        maxlength="250" 
                                    >
                                    <span id="validate_name"></span>
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_family_doctor"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            @endif
                        
                            @if(!empty($patient->insurance_number))
                            <div class="col-sm-6">  
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_ENSURANCE_NUMBER') </label>
                                    <input 
                                        type="text" 
                                        readonly 
                                        name="insurance_number" 
                                        class="form-control"  
                                        value="{{ $patient->insurance_number }}"
                                        maxlength="250" 
                                    >
                                        <!-- required
                                        data-error="@lang('admin.ERR_PATIENT_ENSURANCE_NUMBER_REQUIRED')"  -->
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_insurance_number"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            @endif
                       <!--  </div>
                        <div class="row"> -->
                            @if(!empty($patient->additional_insurance))
                            <div class="col-sm-6">  
                                <div class="form-group">
                                    <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_ADDITIONAL_ENSURANCE') </label>
                                    <input 
                                        type="text" 
                                        readonly 
                                        name="additional_insurance" 
                                        class="form-control"   
                                        value="{{ $patient->additional_insurance }}"
                                        maxlength="250" 
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_additional_insurance"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            @endif

                        <!-- </div>
                        <div class="row"> -->

                       <!--  </div>

                        <div class="row"> -->
                            @if(!empty($patient->pat_nr))
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                   <label class="theme-blue"> 
                                    @lang('admin.TITLE_GANY_PAT_NR')</label>
                                    <input 
                                        type="text" 
                                        readonly 
                                        name="old_id" 
                                        class="form-control"
                                        maxlength="250" 
                                        disabled="" 
                                        value="{{ $patient->pat_nr }}"
                                    >
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_old_id"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            @endif

                            @if(!empty($patient->note_report_request))
                            <div class="col-sm-6"> 
                                <div class="form-group">
                                   <label class="theme-blue"> 
                                    @lang('admin.TITLE_PATIENT_NOTE')</label>
                                    <textarea
                                    name="note" 
                                    readonly 
                                    class="form-control"
                                    id="note"
                                    >{{ $patient->note_report_request }}</textarea>
                                    <span class="help-block invalid-feedback with-errors">
                                        <ul class="list-unstyled">
                                            <li class="err_note"></li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div><!-- /.card-body -->

                    <!-- <div class="card-footer">
                        <button type="submit" id="savebtn" class="btn btn-success">@lang('admin.TITLE_SAVE_BUTTON')</button> 
                    </div> -->
                </form>
            </div>
        </div>
    </div>
</div>    
</section>
@endsection

@section('scripts')
<script type="text/javascript">
    var familyNameText = "{{ __('admin.ERR_FAMILY_DOCTOR_NAME') }}";
</script>
<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
<script type="text/javascript" src="{{ url('assets/admin/js/patients/create-edit.js') }}"></script>
@endsection