@extends('admin.layout.master')

@section('title')
{{ $moduleTitle ?? 'Manage Profile Templates' }}   
@endsection

@section('styles')
@endsection

@section('content') 
<section class="content"> 
<div class="row">
    <div class="col-12">    
        <div class="card">
            <div class="card-header"> 
                 @can('profile-templates-add')
                <h3 class=""><a href="{{ route($modulePath.'.create') }}" class="btn btn-primary float-right" >{{ $addButton }}</a></h3> 
                @endcan
            </div>
            <!-- /.card-header -->
            <div class="card-body">         
                <table id="listingTable" class="table table-bordered table-striped" style="width:100%" >
                    <thead class="">
                        <tr>
                            <th style="visibility: hidden;"></th> 
                            <th class="w-140-px">@lang('admin.TITLE_PROFILE_NAME')</th>
                            <th class="w-200-px">@lang('admin.TITLE_PROFILE_AGE_FROM')</th>
                            <th class="w-200-px">@lang('admin.TITLE_PROFILE_AGE_TO')</th> 
                            <th class="text-center w-100-px">@lang('admin.TITLE_PROFILE_STATUS')</th>
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
    <script type="text/javascript" src="{{ asset('/assets/admin/js/profile-templates/index.js') }}"></script>
@endsection