@extends('admin.layout.master')  

@section('title') 
{{ $moduleAction ?? 'Manage Menus Settings' }}   
@endsection
 
@section('styles') 
@endsection

@section('content')
<section class="content"> 
<div class="row">
    <div class="col-12"> 
        <div class="card">
            <div class="card-header">
                @if(auth()->user()->hasRole('super-admin'))
                @can('support-setting-add')
                <h3 class=""><a href="{{ route($modulePath.'create') }}" class="btn btn-primary float-right" >{{ $addButton }}</a></h3>
                @endcan
                @endif
            </div>
            <!-- /.card-header -->
            <div class="card-body">        
                <table id="listingTable" class="table table-bordered table-striped" style="width:100%" > 
                    <thead class="">    
                        <tr>
                            <th style="visibility: hidden;"></th> 
                            <th class="w-140-px">@lang('admin.TITLE_SUPPORT_NAME')</th> 
                            <th class="w-200-px">@lang('admin.TITLE_SUPPORT_URL')</th>
                            <th class="w-200-px">@lang('admin.TITLE_SUPPORT_APK')</th>
                            <th class="w-200-px">@lang('admin.TITLE_SUPPORT_STATUS')</th> 
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
    <script type="text/javascript" src="{{ asset('/assets/admin/js/support-settings/index.js') }}"></script>
@endsection 