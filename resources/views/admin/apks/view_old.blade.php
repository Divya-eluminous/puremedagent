@extends('admin.layout.master')

@section('title')
   {{ $moduleTitle }}
@endsection
@section('content')

<!-- Main content -->        
<section class="content">
<div class="container-fluid">      
    <div class="row">
        <!-- left column -->
        <div class="col-md-12">
            <!-- jquery validation -->
            <div class="card card-primary"> 
                <div class="card-header">
                    <h3 class="card-title">{{ $formTitle }}</h3>
                    <button class="btn btn-light float-right" onclick="window.history.back()">@lang('admin.TITLE_BACK_BUTTON')</button>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-3"> 
                            <div class="form-group">
                                <label class="theme-blue"> 
                                @lang('admin.TITLE_APK_NAME'):</label>
                                
                                
                            </div>
                        </div>
                        <div class="col-sm-3"> 
                                <div class="form-group">
                                    <p>{{ $apk->app_name }} </p>
                                </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-3"> 
                            <div class="form-group">
                                <label class="theme-blue"> 
                                @lang('admin.TITLE_APK_FILE_NAME'):</label>
                                
                                
                            </div>
                        </div>
                        <div class="col-sm-3"> 
                                <div class="form-group">
                                    <p>{{ $apk->apk_file_name }} </p>
                                </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-3"> 
                            <div class="form-group">
                                <label class="theme-blue"> 
                                @lang('admin.TITLE_APK_VERSION'):</label>
                                
                                
                            </div>
                        </div>
                        <div class="col-sm-3"> 
                                <div class="form-group">
                                    <p>{{ $apk->apk_version }} </p>
                                </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-3"> 
                            <div class="form-group">
                                <label class="theme-blue"> 
                                @lang('admin.TITLE_DOWNLOAD_APK'):</label>
                                
                                
                            </div>
                        </div>
                        <div class="col-sm-3"> 
                            <div class="form-group">
                                <a href="{{ asset($apkPath . $apk->file_name) }}" 
                                   class="btn btn-success position-relative" 
                                   download
                                   onclick="markAsDownloaded({{ $apk->id }})">
                                    @lang('admin.TITLE_DOWNLOAD_APK')
                                    @if($apk->is_downloaded == 0)
                                        <span id="download-badge-{{ $apk->id }}" class="badge badge-danger position-absolute top-0 start-100 translate-middle">New</span> 
                                    @endif
                                </a>
                            </div>
                        </div>


                    </div>
                </div>  

            <hr>
                
            </div>

        </div>
    </div>
</div>    
</section>
@endsection

@section('scripts')
<script type="text/javascript" src="{{ asset('assets/admin/js/apks/view.js') }}"></script>

@endsection