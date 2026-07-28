<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DownloadApksRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = base64_decode(base64_decode($this->route('setting'))) ?? null;
        // dd($this->file('signdoc_app')->getMimeType());
        // General validation rules for APK files
        $apkValidationRule = 'mimes:apk,zip|mimetypes:application/vnd.android.package-archive,application/zip';
    
        return [
            'signdoc_app' => $apkValidationRule,
            'master_data_app' => $apkValidationRule,
            'wating_num_app' => $apkValidationRule,
        ];
    }

    public function messages()
    {
        return [
            'signdoc_app.required' => __('admin.ERR_SIGNDOC_APP_REQUIRED'),
            'signdoc_app.file' => __('admin.ERR_SIGNDOC_APP_FILE'),
            'signdoc_app.mimes' => __('admin.ERR_SIGNDOC_APP_APK_ONLY'),
            'signdoc_app.mimetypes' => __('admin.ERR_SIGNDOC_APP_MIMETYPES'),

            'master_data_app.required' => __('admin.ERR_MASTER_DATA_APP_REQUIRED'),
            'master_data_app.file' => __('admin.ERR_MASTER_DATA_APP_FILE'),
            'master_data_app.mimes' => __('admin.ERR_MASTER_DATA_APP_APK_ONLY'),
            'master_data_app.mimetypes' => __('admin.ERR_MASTER_DATA_APP_MIMETYPES'),

            'wating_num_app.required' => __('admin.ERR_WATING_NUM_APP_REQUIRED'),
            'wating_num_app.file' => __('admin.ERR_WATING_NUM_APP_FILE'),
            'wating_num_app.mimes' => __('admin.ERR_WATING_NUM_APP_APK_ONLY'),
            'wating_num_app.mimetypes' => __('admin.ERR_WATING_NUM_APP_MIMETYPES'),
        ];
    }
}
