@extends('admin.layout.master')

@section('title')
{{ $moduleAction ?? 'Manage Roster' }} 
@endsection

@section('styles')
@endsection

@section('content')
<section class="content">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header">  
            @can('roster-add')
            <h3 class=""><a href="{{ route($modulePath.'.create') }}" class="btn btn-primary float-right" >{{ $addButton }}</a></h3>
            @endcan
        </div>

        <!-- /.card-header -->
        <div class="card-body">        
            <table id="listingTable" class="table table-bordered table-striped" style="width:100%" >
                <thead class="">  
                    <tr>
                        <th style="visibility: hidden;"></th>
                        <th class="w-140-px">@lang('admin.TITLE_ROSTER_DOCTOR')</th>
                        <th class="w-100-px">@lang('admin.TITLE_ROSTER_DATE')</th>
                        <th class="w-100-px">@lang('admin.TITLE_ROSTER_TIME_FROM')</th>
                        <th class="w-100-px">@lang('admin.TITLE_ROSTER_TIME_TO')</th> 
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
@section('model')
    @include('admin.roster.excluded-date-model')
@show    
@endsection

@section('scripts')
    <script type="text/javascript" src="{{ asset('/assets/admin/js/roster/index.js') }}"></script>
@endsection
