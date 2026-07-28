@extends('admin.layout.master')

@section('title')
   {{ $moduleTitle }}
@endsection

@section('styles')
@endsection

@section('content')

    <div class="row mb-5">
            <div class="col-xs-12 bundle">
                <div class="card border-0 shadow">
                    <h1 class="title blue-border-bottom">
                        Manage Customers
                    </h1>
                    <div class="card-footer d-flex theme-bg-blue-light blue-border-bottom">
                        <a href="{{ route($modulePath.'create') }}" class="blue-btn ml-auto">Add Customer</a>
                        <!-- <a href="javascript:void(0)" onclick="return deleteCollections(this)" class="blue-btn ml-3">Delete Selected</a> -->
                    </div>
                    <table id="listingTable" class="table mb-0 first-child-border-0 even-odd-row-1">
                        <thead class="">
                            <tr>
                                <th style="display: none"></th>
                                <!-- <th class="w-90-px">Select</th> -->
                                <th class="w-160-px">Name</th>
                                <th class="w-90-px">Company Name</th>
                                <th class="w-120-px">Email</th>
                                <th class="w-70-px">Mobile </th>
                                <th class="w-50-px">Orders</th>
                                <th class="w-60-px">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
@endsection

@section('scripts')
   <script type="text/javascript" src="{{ url('assets/admin/js/customers/index.js') }}"></script>
@endsection