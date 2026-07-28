@extends('admin.layout.master')

@section('title')
{{ $moduleTitle }}
@endsection
@section('content')

<div class="row mb-5"> 
    <div class="col-xs-12">
        <form id="teleconferenceForm" action="{{ route($modulePath.'store') }}">
            <div class="card border-0 shadow">

                <h1 class="title blue-border-bottom">
                    {{ strtoupper($moduleAction) }}
                </h1> 

                <div class="card-body">

                    <div class="d-flex flex-column mb-3 form-group">
                       <label class="theme-blue">Name <span class="required">*</span></label>
                       <input class="form-control" 
                             type="text"   
                             name="name" 
                             required
                             data-error="Role name field is required."
                          >
                       <span class="help-block with-errors">
                           <ul class="list-unstyled">
                               <li class="err_name"></li>
                           </ul>
                       </span>
                    </div>

                </div> 
                <div class="card-footer d-flex theme-bg-blue-light blue-border-bottom">
                    <button class="btn-normal ml-auto blue-btn-inverse" type="submit">@lang('admin.TITLE_SAVE_BUTTON')</button>
                </div>  


            </div>
        </form>
    </div>
</div>

@endsection
@section('scripts')
<!-- <script type="text/javascript" src="{{ url('assets/admin/js/roles/create-edit.js') }}"></script> -->
@endsection