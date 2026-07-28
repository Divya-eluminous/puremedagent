@extends('admin.layout.master')

@section('title')
   {{ $moduleTitle }}
@endsection
@section('styles')
    
@endsection
@section('content')
<div class="row mb-5">
        <div class="col-xs-12">
            <form id="customerForm" data-toggle="validator" action="{{ route($modulePath.'assignproduct') }}">
                <div class="card border-0 shadow">
                    <h1 class="title blue-border-bottom">
                        {{ strtoupper($moduleTitle) }}
                    </h1>
                    <div class="card-footer d-flex theme-bg-blue-light blue-border-bottom">
                    	<button class="btn-normal ml-auto blue-btn-inverse"><a  href="{{ route($modulePath.'assignproductindex', [base64_encode(base64_encode($customer->id))]) }}">Edit Assigned Products</a></button>
                    </div>
                    <h1 class="card-subtitle blue-border-bottom text-capitalize">
                        Customer Information
                    </h1>
                    <div class="card-body">
                        <div class="f-col-12 p-0">
                            <div class="f-row">
                                <div class="f-col-6 d-flex flex-column mb-25 form-group">
                                    <label class="theme-blue">Contact 
                                    Name  </label>
                                    {{ $customer->contact_name }} 
                                </div>

                                <div class="f-col-6 d-flex flex-column mb-25 form-group">
                                    <label class="theme-blue">Mobile 
                                    Number  </label>
                                    {{ $customer->mobile_number }}
                                </div>
                            </div>

                            <div class="f-row">
                                <div class="f-col-6 d-flex flex-column mb-25 form-group">
                                    <label class="theme-blue">Company 
                                    Name  </label>
                                    {{ $customer->company_name }}
                                </div>

                                <div class="f-col-6 d-flex flex-column mb-25">
                                    <label class="theme-blue">Email Id  </label>
                                    {{ $customer->email }}
                                </div>
                            </div>


                    </div>
                </div>

                <h1 class="card-subtitle blue-border-bottom text-capitalize">
                        Product Information
                </h1>

                <div class="card-body">
                    <table class="table">
                         <thead class="blue-border-bottom">
                            <tr>
                                <th class="w-80-px">Sr. No.</th>
                                <th>Product Name</th>
                                <th>Product Code</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(count($user_products)>0)
                                @foreach($user_products as $key=>$product)
                                    <tr  role="row"><!--class="even inner-td"  -->
                                        <td>{{ $key+1 }}</td>
                                        <td>{{ $product->products->name }}</td>
                                        <td>{{ $product->products->code }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="3">No Records found</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>
@endsection
@section('scripts')
<script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script>
@endsection