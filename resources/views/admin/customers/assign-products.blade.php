@extends('admin.layout.master')

@section('title')
   {{ $moduleTitle }}
@endsection
@section('styles')
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.6.3/css/bootstrap-select.min.css" />
    <style type="text/css">
        .btn-group.bootstrap-select {
            width: 100% !important;
        }
    </style>
<!-- <link rel="stylesheet" href="{{ asset('admin/css/bootstrap-select.min.css') }}" />
 -->@endsection
@section('content')
<div class="row mb-5">
        <div class="col-xs-12">
            <form id="customerForm" data-toggle="validator" action="{{ route($modulePath.'assignproduct') }}">
                <div class="card border-0 shadow">
                    <h1 class="title blue-border-bottom">
                        {{ strtoupper($moduleAction) }}
                    </h1>
                    <div class="card-footer d-flex theme-bg-blue-light blue-border-bottom">
                    	<!-- <button class="btn-normal ml-auto blue-btn-inverse" type="reset">Reset</button> -->
                        <button class="btn-normal ml-auto blue-btn-inverse" type="submit">Save</button>
                    </div>
                    <h1 class="card-subtitle blue-border-bottom text-capitalize">
                        Customer and Product Information
                    </h1>
                    <div class="card-body">
                        <div class="f-col-12 p-0">

                            <div class="d-flex flex-column mb-25 form-group">
                                <label class="theme-blue">Select Customer <span class="required">*</span></label>
                                <select class="selectpicker" data-live-search="true" id="dd_customer" required
                        data-error="Customer field is required" data-dropup-auto="false">
                                    <option value="">Select Customer</option>   
                                    @if(!empty($customers) && sizeof($customers)>0)
                                        @foreach($customers as $customer)
                                            @php
                                                $selected="";
                                                if(!empty($customer_id) && $customer_id==$customer->id){
                                                    $selected="selected";
                                                }

                                            @endphp

                                            <option data-subtext="({{ $customer->company_name }})" value="{{ $customer->id }}" {{ $selected }}>{{ $customer->contact_name }}</option>   
                                        @endforeach
                                    @endif
                                  </select>
                                <span class="help-block with-errors">
                                    <ul class="list-unstyled">
                                        <li class="err_contact_name"></li>
                                    </ul>
                                </span>
                            </div>

                            <div class="d-flex flex-column mb-25 form-group">
                                <label class="theme-blue">Select Products <span class="required">*</span></label>
                                <select multiple class="selectpicker" data-show-subtext="true" data-live-search="true" id="dd_products" required
                        data-error="Products field is required"  data-dropup-auto="false">
                                   @if(!empty($products) && sizeof($products)>0)
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}" data-subtext="({{ $product->code }})">{{ $product->name }}</option>   
                                        @endforeach
                                    @endif
                                </select>
                                <span class="help-block with-errors">
                                    <ul class="list-unstyled">
                                        <li class="erro_cntact_name"></li>
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
<script type="text/javascript">
    var customer_id = "{{ $customer_id }}";
    console.log(customer_id);
</script>
<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
    <script type="text/javascript" src="{{ url('assets/admin/js/customers/assignproduct.js') }}"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.6.3/js/bootstrap-select.min.js"></script>
@endsection