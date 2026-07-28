<!-- @extends('admin.layout.master') -->

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
              <h3 class=""><a href="{{ route($modulePath.'export') }}" class="btn btn-primary float-right">Export</a></h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">        
                <table id="listingTable" class="table table-bordered table-striped table-responsive" style="width:100%" >
                    <thead class=""> 
                        <tr> 
                            <th style="visibility: hidden;"></th> 
                            <th class="w-100-px">@lang('admin.TITLE_ACTIVITY_MODULE')</th>
                            <th class="w-100-px">@lang('admin.TITLE_ACTIVITY_MESSAGE')</th>
                            <th class="w-100-px">@lang('admin.TITLE_ACTIVITY_METHOD')</th>
                            <th class="w-200-px">@lang('admin.TITLE_ACTIVITY_URL')</th>
                            <th class="w-100-px">@lang('admin.TITLE_ACTIVITY_IP')</th>
                            <th class="w-100-px">@lang('admin.TITLE_ACTIVITY_AGENT')</th>
                            <!-- <th class="w-100-px"> @lang('admin.TITLE_ACTIVITY_USER_ID')</th> -->
                            <th class="w-100-px"> @lang('admin.TITLE_ACTIVITY_USER')</th>
                            <th class="text-center w-130-px">@lang('admin.TITLE_CREATED_AT_TEXT')</th>
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
    <script type="text/javascript" src="{{ asset('/assets/admin/js/activity-logs/index.js') }}"></script>
@endsection