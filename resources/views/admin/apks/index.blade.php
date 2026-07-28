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
            <!-- /.card-header -->
            <div class="card-body">        
                <table id="listingTable" class="table table-bordered table-striped" style="width:100%" > 
                    <thead class="">    
                        <tr>
                            <th style="visibility: hidden;"></th> 
                            <th class="w-140-px">@lang('admin.TITLE_APP_NAME')</th> 
                            <th class="w-200-px">@lang('admin.TITLE_APP_VERSION')</th>
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
    <script type="text/javascript" src="{{ asset('/assets/admin/js/apks/index.js') }}"></script>
@endsection 