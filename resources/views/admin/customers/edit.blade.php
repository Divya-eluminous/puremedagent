@extends('admin.layout.master')

@section('title')
   {{ $moduleTitle }}
@endsection

@php
$isCurrent = auth()->user()->id === (int)base64_decode(base64_decode(request()->segment(3))) ?true:false;

@endphp

@section('content')
<div class="row mb-5">
        <div class="col-xs-12">
            <form id="customerForm" data-toggle="validator" action="{{ route($modulePath.'update', [base64_encode(base64_encode($customer->id))]) }}">
                <input type="hidden" name="_method" value="PUT">
                <div class="card border-0 shadow">
                    <h1 class="title blue-border-bottom">
                        {{ strtoupper($moduleAction) }}
                    </h1>
                    <div class="card-footer d-flex theme-bg-blue-light blue-border-bottom">
                    	<!-- <button class="btn-normal ml-auto blue-btn-inverse" type="reset">Reset</button> -->
                        <button class="btn-normal ml-auto blue-btn-inverse" type="submit">Save</button>
                    </div>
                    <h1 class="card-subtitle blue-border-bottom text-capitalize">
                        Customer Information
                    </h1>
                    <div class="card-body">
                        <div class="f-col-6 p-0">

                            <div class="d-flex flex-column mb-25 form-group">
                                <label class="theme-blue">Contact 
                                Name <span class="required">*</span></label>
                                <input 
                                    type="text" 
                                    name="contact_name"
                                    value="{{ $customer->contact_name }}"  
                                    class="form-control" 
                                    required
                                    maxlength="250" 
                                    data-error="Name field is required." 
                                >
                                <span class="help-block with-errors">
                                    <ul class="list-unstyled">
                                        <li class="err_contact_name"></li>
                                    </ul>
                                </span>
                            </div>

                            <div class="d-flex flex-column mb-25 form-group">
                                <label class="theme-blue">Mobile 
                                Number <span class="required">*</span></label>
                                <input 
                                    type="text" 
                                    name="mobile_number" 
                                    value="{{ $customer->mobile_number }}"  
                                    class="form-control" 
                                    required
                                    maxlength="12" 
                                    data-error="Mobile Number field is required" 
                                >
                                <span class="help-block with-errors">
                                    <ul class="list-unstyled">
                                        <li class="err_mobile_number"></li>
                                    </ul>
                                </span>
                            </div>

                            <div class="d-flex flex-column mb-25 form-group">
                                <label class="theme-blue">Company 
                                Name <span class="required"></span></label>
                                 <input 
                                    type="text" 
                                    name="company_name" 
                                    class="form-control" 
                                    maxlength="250" 
                                    value="{{ $customer->company_name }}" 
                                >
                                
                            </div>

                            @if(!$isCurrent)

                            <!-- <div class="d-flex flex-column mb-25 form-group">
                                <label class="theme-blue">Company 
                                Name <span class="required">*</span></label>
                                <select class="form-control my-select" name="company_id" required
                                    data-error="Company Name field is required." >
                                    <option value="">Select Company</option>
                                    @if(!empty($companies) && sizeof($companies) > 0)
                                    @foreach($companies as $key => $company)
                                    <option value="{{ base64_encode(base64_encode($company->id)) }}" @if($company->id==$customer->company_id) selected @endif>
                                       {{ $company->name }}
                                    </option>
                                    @endforeach 
                                    @endif
                                 </select>
                                 <span class="help-block with-errors">
                                    <ul class="list-unstyled">
                                       <li class="err_company_id"></li>
                                    </ul>
                                 </span>
                            </div> -->
                            @endif

                            <div class="d-flex flex-column mb-25 form-group">
                                <label class="theme-blue">Email Id <span class="required">*</span></label>
                                <input 
                                    type="text" 
                                    name="email" 
                                    value="{{ $customer->email }}"  
                                    class="form-control" 
                                    required
                                    data-error="Email field is required." 
                                    pattern='^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$'
                                    data-pattern-error="@lang('admin.ERR_EMAIL_FORMAT')">
                                <span class="help-block with-errors">
                                    <ul class="list-unstyled">
                                        <li class="err_email"></li>
                                    </ul>
                                </span>
                            </div>

                            @if(!$isCurrent)

                            <!-- <div class="f-col-6 p-0 d-flex flex-column mb-25 form-group">
                             <label class="theme-blue">@lang('admin.TITLE_SELECT_ROLE') <span
                                class="required">*</span></label>
                             <select class="form-control my-select" name="role" required
                                data-error="@lang('admin.ERR_ROLE')">
                                <option value="">@lang('admin.TITLE_SELECT_ROLE')</option>
                                @php
                                    $user_role = '';
                                    if(count($customer->getRoleNames())>0){
                                        $user_role = $customer->getRoleNames()[0];
                                    }
                                @endphp
                                @if(!empty($roles) && sizeof($roles) > 0)
                                    @foreach($roles as $key => $role)
                                    <option value="{{ base64_encode(base64_encode($role->id)) }}" @if($role->name ==
                                            $user_role) selected @endif>
                                       {{ ucfirst(str_replace('-', ' ',$role->name)) }}
                                    </option>
                                    @endforeach 
                                @endif
                             </select>
                             <span class="help-block with-errors">
                                <ul class="list-unstyled">
                                   <li class="err_role"></li>
                                </ul>
                             </span>
                          </div> -->
                          @endif


                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
@section('scripts')
<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
    <script type="text/javascript" src="{{ url('assets/admin/js/customers/create-edit.js') }}"></script>
@endsection