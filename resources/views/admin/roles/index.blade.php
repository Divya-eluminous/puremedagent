@extends('admin.layout.master')

@section('title')
{{ $moduleTitle }}
@endsection

@section('content')
<section class="content">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h3 class="">
            <a onclick="return addRole(this)" data-href="{{ route('admin.roles.store') }}" data-add="@lang('admin.TITLE_ADD_BUTTON') @lang('admin.TITLE_ROLE')" data-toggle="modal" class="btn btn-primary float-right">{{ $addButton }}</a>
          </h3>
        </div>
        <!-- /.card-header -->
        <div class="card-body">             
            <table id="listingTable" class="table table-bordered table-striped" style="width:100%" >
                <thead class="">
                    <tr>
                        <th class="">@lang('admin.TITLE_ROLE_NAME')</th>
                        <th class="">@lang('admin.TITLE_ROLE_IDENTIFIER')</th>
                        <th class="w-130-px">@lang('admin.TITLE_ACTIONS_TEXT')</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
  </div>
 </div>
</section>

@section('model')
    @include('admin.roles.create-role-model')
@show 
@endsection

@section('scripts')
<script type="text/javascript" src="{{ asset('assets/admin/js/roles/index.js') }}"></script>
@stop

