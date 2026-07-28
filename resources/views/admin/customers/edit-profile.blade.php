@extends('admin.layout.master')

@section('title')
   {{ $moduleTitle }}
@endsection

@section('content')
<div class="row mb-5">
        <div class="col-xs-12">
            <form id="updateCustomerForm" data-toggle="validator" action="{{ route($modulePath.'updateCustomerProfile', [base64_encode(base64_encode($customer->id))]) }}">
                
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