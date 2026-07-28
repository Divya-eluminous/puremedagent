@extends('admin.layout.master')

@section('title')
{{ $moduleTitle }}
@endsection

@section('styles')
@endsection

@section('content')

@php
$isVisible = auth()->user()->hasRole('super-admin');
$dispatcher = auth()->user()->hasRole('dispatcher')?false:true;
@endphp

<div class="row mb-5">
      <div class="col-xs-12 bundle">
            <div class="card border-0 shadow">
                  <h1 class="title blue-border-bottom">
                        {{ $moduleTitle }}
                  </h1>
                  <div class="card-footer d-flex theme-bg-blue-light blue-border-bottom">
                        <!-- <a href="{{ route($modulePath.'create') }}" class="blue-btn ml-auto">Add Order</a> -->
                  </div>
                  <table id="listingTable" class="table mb-0 even-odd-row-1">
                        <thead class="blue-border-bottom">
                              <tr>
                                    <th style="display: none"></th>
                                    <th class="w-120-px">Order Id</th>
                                    <th class="w-150-px">Product Name <br />(Code)</th>
                                    <th class="w-60-px">EDD</th>
                                    <th class="w-60-px">Dipatch Date</th>
                                    <th class="w-50-px">Qty</th>
                                    <th class="w-70-px">Cost</th>
                                    <!--  <th class="w-60-px">PO</th>
                                    <th class="w-90-px">Comment</th> -->
                                    <th class="w-90-px">Status</th>
                                   <!--  <th class="w-170-px">Actions</th> -->
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
<script type="text/javascript">
      var isVisible= "{{ $isVisible }}";
      var dispatcher= "{{ $dispatcher }}";
      var customer_id= "{{ $customer->id }}";
</script>
<script type="text/javascript" src="{{ url('assets/admin/js/customers/orders.js') }}"></script>
@endsection