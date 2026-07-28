@extends('admin.layout.master')

@section('title')
{{ $moduleAction ?? 'Manage Permissions' }}
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

        <form action="{{ route('admin.permissions.store') }}" id="permissionsForm" data-toggle="validator" >
            <div class="row">
                <div class="col-md-12">
                    <div class="d-flex flex-column w-25 form-group mb-0">
                <select class="form-control my-select" name="role" placeholder="All Roles" onchange="return getPermissions(this)" required data-error="@lang('admin.ERR_ROLE')">
                    <option value="">@lang('admin.TITLE_SELECT_ROLE')</option>
                    @if(!empty($roles) && sizeof($roles) > 0)
                        @foreach($roles as $key => $role)
                            @if($role->name=="super-admin")
                            @else
                            <option value="{{ base64_encode(base64_encode($role->id)) }}">{{ ucfirst(str_replace('-', ' ', $role->identifier) ?? '--') }}</option>
                            @endif
                        @endforeach
                    @endif
                </select>
                <span class="help-block invalid-feedback with-errors">
                  <ul class="list-unstyled">
                     <li class="err_role"></li>
                  </ul>
               </span>

            </div>
                </div>
            </div>
            
            <div class="card-body panel-group toggle-group" id="accordion1">
                <!-- third -->
                @section('permissions')
                    @include('admin.permissions.role-permissions')
                @show

            </div>
            <div class="card-body pt-0 d-flex">
                <button class="btn btn-success pull-right" id="submitButton" type="submit">@lang('admin.TITLE_SAVE_BUTTON')</button>
            </div>
        </form>
        </div>
    </div>
    </div>
    </div>
</section>

   
@endsection

@section('scripts')
    <script type="text/javascript" src="{{ url('assets/admin/js/permissions/index.js') }}"></script>
@endsection