@extends('admin.layout.master')

@section('title')
{{ $moduleAction ?? 'Manage Users' }}
@endsection

@section('styles')
@endsection

@section('content')
<section class="content">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header">            
            @can('users-add')
            <h3 class=""><a href="{{ route($modulePath.'create') }}" class="btn btn-primary float-right" >{{ $addButton }}</a></h3>
            @endcan
        </div>
        <!-- /.card-header -->
        <div class="card-body">        
            <table id="listingTable" class="table table-bordered table-striped" style="width:100%" >
                <thead class="">
                    <tr>
                        <th style="visibility: hidden;"></th>
                        <th class="w-140-px">@lang('admin.TITLE_USER_NAME')</th>
                        <th class="w-200-px">@lang('admin.TITLE_EMAIL_ADDRESS')</th>
                        <th class="w-100-px">@lang('admin.TITLE_MOBILE_NO')</th>
                        <th class="w-100-px">@lang('admin.TITLE_ROLE')</th>
                        <!-- <th class="text-center w-100-px">Status</th> -->
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
</section>
   
@endsection

@section('scripts')
    <script type="text/javascript" src="{{ asset('/assets/admin/js/users/index.js') }}"></script>
@endsection