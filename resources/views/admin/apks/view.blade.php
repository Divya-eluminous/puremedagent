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
                @if($apks->isNotEmpty())
                    @foreach($apks as $apk)

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
                                            <p>{{ !empty($apk->apk_version) ? $apk->apk_version : '-' }}</p>
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
                                @php
                                    $apkPath = url('storage/apks_download');

                                @endphp
                                <div class="form-group">
                                    <a href="{{ $apkPath . '/' . $apk->apk_file_name }}"
                                    class="btn btn-success position-relative"
                                    target="_blank"
                                    download
                                    data-file-path="{{ $apkPath . '/' . $apk->apk_file_name }}"
                                    onclick="markAsDownloaded({{ $apk->id }}, '/opt/app-data/wwwroot/public/storage/apks_download/{{ $apk->apk_file_name }}')">
                                        @lang('admin.TITLE_DOWNLOAD_APK')
                                        
                                        @if($apk->is_downloaded == 0)
                                            <!-- Badge for New Download -->
                                            <span id="download-badge-{{ $apk->id }}"
                                                class="badge badge-danger position-absolute top-0 start-100 translate-middle p-2 rounded-circle">
                                                New
                                            </span>
                                        @endif
                                    </a>
                                </div>
                            </div>


                            </div>
                    <hr>

                        </div> 

                    @endforeach 
                @else
                    <div style="padding: 10px; font-size: 14px; color: #555; border: 1px solid #ddd; background-color: #f9f9f9;">
                        No APK present.
                    </div>
                @endif

                
            </div>
           
        </div>
    </div>
</div>    
</section>
@endsection

@section('scripts')
<script type="text/javascript" src="{{ asset('assets/admin/js/apks/view.js') }}"></script>

@endsection