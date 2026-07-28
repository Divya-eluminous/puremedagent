<div id="roleWisePermissions">

    {{-- Dashboard --}}
    <div class="panel panel-default">
        <div class="panel-heading active">
            <div class="list-group-item">
                <a href="#">@lang('admin.TITLE_DASHBOARD')  </a>
                <div class="label-anchor">
                    <label class="switch ml-auto">
                        <input type="checkbox" class="checkbox-permissions" id="permission-dashboard" name="dashboard" value="dashboard" >
                        <span class="knob"></span>
                    </label>
                    <a data-toggle="collapse" href="#permission1" class="theme-green ml-3 text-underline">@lang('admin.DETAIL')</a>
                </div>
            </div> 
        </div>
        <!--         <div id="permission1" class="panel-collapse collapse">
            <div class="panel-body border-0 py-0">
                <ul class="list-group toggle-wrapper">
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_TOTAL_USERS')</a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions" id="permission-total-users" name="total-users" value="total-users" >
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                </ul>
            </div>
        </div> -->


    </div>

    <div class="panel panel-default">
        <div class="panel-heading active">
            <div class="list-group-item">
                <a href="#">@lang('admin.TITLE_CURRENT_APPOINTMENT_TEXT')  </a>
                <div class="label-anchor">
                    <label class="switch ml-auto">
                        <input type="checkbox" class="checkbox-permissions" id="permission-doctor-dashboard" name="doctor-dashboard" value="doctor-dashboard" >
                        <span class="knob"></span>
                    </label>
                    <a data-toggle="collapse" href="#permission2" class="theme-green ml-3 text-underline">@lang('admin.DETAIL')</a>
                </div>
            </div> 
        </div>
    </div>

    {{-- Assistant Dashboard --}}
    <div class="panel panel-default">
        <div class="panel-heading active">
            <div class="list-group-item">
                <a href="#">@lang('admin.TITLE_ASSISTANT_DASHBOARD')  </a>
                <div class="label-anchor">
                    <label class="switch ml-auto">
                        <input type="checkbox" class="checkbox-permissions" id="permission-assistant-dashboard" name="assistant-dashboard" value="assistant-dashboard" >
                        <span class="knob"></span>
                    </label>
                    <a data-toggle="collapse" href="#permission3" class="theme-green ml-3 text-underline">@lang('admin.DETAIL')</a>
                </div>
            </div> 
        </div>
    </div>

    {{-- Manage Appointment --}}
    <div class="panel panel-default">
        <div class="panel-heading">
            <div class="list-group-item d-flex">
                <a href="#">@lang('admin.TITLE_MANAGE_TEXT') @lang('admin.TITLE_APPOINTMENT_TEXT')</a>
                <div class="label-anchor">
                    <label class="switch ml-auto">
                        <input type="checkbox" class="checkbox-permissions checkbox-master"
                            id="permission-manage-appointment" name="manage-appointment"
                            value="manage-appointment" data-target="#permission4">
                        <span class="knob"></span>
                    </label>
                    <a data-toggle="collapse" href="#permission4"
                        class="theme-green ml-3 text-underline">@lang('admin.DETAIL')</a>
                </div>
            </div>
        </div>
        <div id="permission4" class="panel-collapse collapse">
            <div class="panel-body border-0 py-0">
                <ul class="list-group toggle-wrapper">
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_VIEW_BUTTON')  @lang('admin.TITLE_APPOINTMENT_TEXT')</a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-appointment-listing" name="appointment-listing"
                                value="appointment-listing"> 
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_ADD_BUTTON') @lang('admin.TITLE_APPOINTMENT_TEXT')</a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-appointment-add" name="appointment-add"
                                value="appointment-add">
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>

                      <!-----------------added on 16-feb-24------------------------------------>

                     <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_OPTIMAL')</a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-optimal-appointment" name="optimal-appointment"
                                value="optimal-appointment">
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>

                    <!----------------added on 16-feb-24------------------------------------->


                </ul>
            </div>
        </div>
    </div>
     {{-- Manage Waiting Number --}}
    <div class="panel panel-default">
        <div class="panel-heading">
            <div class="list-group-item d-flex">
                <a href="#">@lang('admin.TITLE_WAITING_SCREEEN')</a>
                <div class="label-anchor">
                    <label class="switch ml-auto">
                        <input type="checkbox" class="checkbox-permissions checkbox-master"
                            id="permission-manage-waiting-queue" name="manage-waiting-queue"
                            value="manage-waiting-queue" data-target="#permission5">
                        <span class="knob"></span>
                    </label>
                    <a data-toggle="collapse" href="#permission5"
                        class="theme-green ml-3 text-underline">@lang('admin.DETAIL')</a>
                </div>
            </div>
        </div>
        <div id="permission5" class="panel-collapse collapse">
            <div class="panel-body border-0 py-0">
                <ul class="list-group toggle-wrapper">
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_WAITING_NUMBER_SYMBOLS_TEXT')  </a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-waiting-number-symbols" name="waiting-number-symbols"
                                value="waiting-number-symbols"> 
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_WAITING_QUEUE_TEXT')</a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-waiting-queue-number" name="waiting-queue-number"
                                value="waiting-queue-number">
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                </ul>
            </div>
        </div>
    </div>

      {{-- Manage Patients --}}
    <div class="panel panel-default">
        <div class="panel-heading">
            <div class="list-group-item d-flex">
                <a href="#">@lang('admin.TITLE_MANAGE_TEXT') @lang('admin.TITLE_PATIENT_TEXT') </a>
                <div class="label-anchor">
                    <label class="switch ml-auto">
                        <input type="checkbox" class="checkbox-permissions checkbox-master"
                            id="permission-manage-patients" name="manage-patients"
                            value="manage-patients" data-target="#permission6">
                        <span class="knob"></span>
                    </label>
                    <a data-toggle="collapse" href="#permission6"
                        class="theme-green ml-3 text-underline">@lang('admin.DETAIL')</a>
                </div>
            </div> 
        </div>
        <div id="permission6" class="panel-collapse collapse">
            <div class="panel-body border-0 py-0">
                <ul class="list-group toggle-wrapper">
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_VIEW_BUTTON') @lang('admin.TITLE_PATIENT_TEXT') </a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-patients-listing" name="patients-listing"
                                value="patients-listing">
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_ADD_BUTTON') @lang('admin.TITLE_PATIENT_TEXT') </a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-patients-add" name="patients-add"
                                value="patients-add">
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                </ul>
            </div>
        </div>
    </div>

     {{-- Manage Roster --}}
    <div class="panel panel-default">
        <div class="panel-heading">
            <div class="list-group-item d-flex">
                <a href="#">@lang('admin.TITLE_MANAGE_TEXT') @lang('admin.TITLE_ROSTER_MODULE')</a>
                <div class="label-anchor">
                    <label class="switch ml-auto">
                        <input type="checkbox" class="checkbox-permissions checkbox-master"
                            id="permission-manage-roster" name="manage-roster"
                            value="manage-roster" data-target="#permission7">
                        <span class="knob"></span>
                    </label>
                    <a data-toggle="collapse" href="#permission7"
                        class="theme-green ml-3 text-underline">@lang('admin.DETAIL')</a>
                </div>
            </div>
        </div>
        <div id="permission7" class="panel-collapse collapse">
            <div class="panel-body border-0 py-0">
                <ul class="list-group toggle-wrapper">
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_VIEW_BUTTON')  @lang('admin.TITLE_ROSTER_MODULE')</a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-roster-listing" name="roster-listing"
                                value="roster-listing"> 
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_ADD_BUTTON') @lang('admin.TITLE_ROSTER_MODULE')</a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-roster-add" name="roster-add"
                                value="roster-add">
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                </ul>
            </div>
        </div>
    </div>

   <!--  {{-- manage-users --}}
    <div class="panel panel-default">
        <div class="panel-heading">
            <div class="list-group-item d-flex">
                <a href="#">@lang('admin.TITLE_MANAGE_TEXT') @lang('admin.TITLE_USERS_MODULE') </a>
                <div class="label-anchor">
                    <label class="switch ml-auto">
                        <input type="checkbox" class="checkbox-permissions"
                            id="permission-manage-users" name="manage-users"
                            value="manage-users">
                        <span class="knob"></span>
                    </label>
                    <a data-toggle="collapse" href="#permission3"
                        class="theme-green ml-3 text-underline">@lang('admin.DETAIL')</a>
                </div>
            </div>
        </div>
        <div id="permission3" class="panel-collapse collapse">
            <div class="panel-body border-0 py-0">
                <ul class="list-group toggle-wrapper">
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_VIEW_BUTTON') @lang('admin.TITLE_USERS_MODULE') </a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-users-listing" name="users-listing"
                                value="users-listing">
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_ADD_BUTTON') @lang('admin.TITLE_USER_MODULE') </a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-users-add" name="users-add"
                                value="users-add">
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_MANAGE_TEXT') @lang('admin.TITLE_ROLES_MODULE') </a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-manage-roles" name="manage-roles"
                                value="manage-roles">
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_MANAGE_TEXT') @lang('admin.TITLE_PERMISSION_MODULE') </a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-manage-permissions" name="manage-permissions"
                                value="manage-permissions">
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span> 
                        </label>
                    </li>
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_ACTIVITY') </a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-activity-logs"
                                id="permission-activity-logs" name="activity-logs"
                                value="activity-logs">
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                </ul>
            </div>
        </div>
    </div> -->
    {{-- Users --}}
    <div class="panel panel-default">
        <div class="panel-heading">
            <div class="list-group-item d-flex">
                <a href="#">@lang('admin.TITLE_MANAGE_TEXT') @lang('admin.TITLE_USERS_MODULE') </a>
                <div class="label-anchor">
                    <label class="switch ml-auto">
                        <input type="checkbox" class="checkbox-permissions checkbox-master"
                            id="permission-manage-users" name="manage-users"
                            value="manage-users" data-target="#permission8">
                        <span class="knob"></span>
                    </label>
                    <a data-toggle="collapse" href="#permission8"
                        class="theme-green ml-3 text-underline">@lang('admin.DETAIL')</a>
                </div>
            </div>
        </div>
        <div id="permission8" class="panel-collapse collapse">
            <div class="panel-body border-0 py-0">
                <ul class="list-group toggle-wrapper">
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_VIEW_BUTTON') @lang('admin.TITLE_USERS_MODULE') </a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-users-listing" name="users-listing"
                                value="users-listing">
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_ADD_BUTTON') @lang('admin.TITLE_USER_MODULE') </a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-users-add" name="users-add"
                                value="users-add">
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_MANAGE_TEXT') @lang('admin.TITLE_ROLES_MODULE') </a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-manage-roles" name="manage-roles"
                                value="manage-roles">
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_MANAGE_TEXT') @lang('admin.TITLE_PERMISSION_MODULE') </a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-manage-permissions" name="manage-permissions"
                                value="manage-permissions">
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span> 
                        </label>
                    </li>
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_ACTIVITY') </a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-activity-logs" name="activity-logs"
                                value="activity-logs">
                                <!-- //class="checkbox-activity-logs" -->
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                </ul>
            </div>
        </div>
    </div>

      {{-- Manage Settings --}}
    <div class="panel panel-default">
        <div class="panel-heading">
            <div class="list-group-item d-flex">
                <a href="#">@lang('admin.TITLE_MANAGE_TEXT') @lang('admin.TITLE_SETTING_TEXT') </a>
                <div class="label-anchor">
                    <label class="switch ml-auto">
                        <input type="checkbox" class="checkbox-permissions checkbox-master"
                            id="permission-manage-settings" name="manage-settings"
                            value="manage-settings" data-target="#permission9">
                        <span class="knob"></span>
                    </label>
                    <a data-toggle="collapse" href="#permission9"
                        class="theme-green ml-3 text-underline">@lang('admin.DETAIL')</a>
                </div>
            </div>
        </div>
        <div id="permission9" class="panel-collapse collapse">
            <div class="panel-body border-0 py-0">
                <ul class="list-group toggle-wrapper">
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_VIEW_BUTTON') @lang('admin.TITLE_SETTING_TEXT')</a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-setting-listing" name="setting-listing"
                                value="setting-listing">
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_ADD_BUTTON') @lang('admin.TITLE_SETTING_TEXT') </a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-setting-add" name="setting-add"
                                value="setting-add">
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Manage Examinations --}}
    <div class="panel panel-default">
        <div class="panel-heading">
            <div class="list-group-item d-flex">
                <a href="#">@lang('admin.TITLE_MANAGE_TEXT')                    @lang('admin.TITLE_EXAMINATIONS_TEXT')</a>
                <div class="label-anchor">
                    <label class="switch ml-auto">
                        <input type="checkbox" class="checkbox-permissions checkbox-master"
                            id="permission-manage-exams" name="manage-exams"
                            value="manage-exams" data-target="#permission10">
                        <span class="knob"></span>
                    </label>
                    <a data-toggle="collapse" href="#permission10"
                        class="theme-green ml-3 text-underline">@lang('admin.DETAIL')</a>
                </div>
            </div>
        </div>
        <div id="permission10" class="panel-collapse collapse">
            <div class="panel-body border-0 py-0">
                <ul class="list-group toggle-wrapper">
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_VIEW_BUTTON') @lang('admin.TITLE_EXAMINATIONS_TEXT') </a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-exams-listing" name="exams-listing"
                                value="exams-listing">
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_ADD_BUTTON') @lang('admin.TITLE_EXAMINATIONS_TEXT') </a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-exams-add" name="exams-add"
                                value="exams-add">
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
      {{-- Manage Check --}}
    <div class="panel panel-default">
        <div class="panel-heading">
            <div class="list-group-item d-flex">
                <a href="#">@lang('admin.TITLE_MANAGE_TEXT')  @lang('admin.TITLE_CHECKLIST_TEXT')</a>
                <div class="label-anchor">
                    <label class="switch ml-auto">
                        <input type="checkbox" class="checkbox-permissions checkbox-master"
                            id="permission-manage-check-list" name="manage-check-list"
                            value="manage-check-list" data-target="#permission11">
                        <span class="knob"></span>
                    </label>
                    <a data-toggle="collapse" href="#permission11"
                        class="theme-green ml-3 text-underline">@lang('admin.DETAIL')</a>
                </div>
            </div>
        </div>
        <div id="permission11" class="panel-collapse collapse">
            <div class="panel-body border-0 py-0">
                <ul class="list-group toggle-wrapper">
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_VIEW_BUTTON') @lang('admin.TITLE_CHECKLIST_TEXT') </a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-manage-check-listing" name="manage-check-listing"
                                value="manage-check-listing">
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_ADD_BUTTON') @lang('admin.TITLE_CHECKLIST_TEXT') </a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-manage-check-add" name="manage-check-add"
                                value="manage-check-add">
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Manage Appointment Types --}}
    <div class="panel panel-default">
        <div class="panel-heading">
            <div class="list-group-item d-flex">
                <a href="#">@lang('admin.TITLE_MANAGE_TEXT') @lang('admin.TITLE_APPOINTMENT_TYPE_TEXT') </a> 
                <div class="label-anchor">
                    <label class="switch ml-auto">
                        <input type="checkbox" class="checkbox-permissions checkbox-master"
                            id="permission-manage-appointment-types" name="manage-appointment-types"
                            value="manage-appointment-types" data-target="#permission12">
                        <span class="knob"></span>
                    </label>
                    <a data-toggle="collapse" href="#permission12"
                        class="theme-green ml-3 text-underline">@lang('admin.DETAIL')</a>
                </div>
            </div>
        </div>
        <div id="permission12" class="panel-collapse collapse">
            <div class="panel-body border-0 py-0">
                <ul class="list-group toggle-wrapper">
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_VIEW_BUTTON') @lang('admin.TITLE_APPOINTMENT_TYPE_TEXT') </a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-appointment-types-listing" name="appointment-types-listing"
                                value="appointment-types-listing">
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_ADD_BUTTON') @lang('admin.TITLE_APPOINTMENT_TYPE_TEXT') </a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-appointment-types-add" name="appointment-types-add"
                                value="appointment-types-add">
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                </ul>
            </div>
        </div>
    </div>

     {{-- Manage Profile Templates --}}
    <div class="panel panel-default">
        <div class="panel-heading">
            <div class="list-group-item d-flex">
                <a href="#">@lang('admin.TITLE_MANAGE_TEXT') @lang('admin.TITLE_PROFILE_TEMPLATE_TEXT') </a>
                <div class="label-anchor">
                    <label class="switch ml-auto">
                        <input type="checkbox" class="checkbox-permissions checkbox-master"
                            id="permission-manage-profile-templates" name="manage-profile-templates"
                            value="manage-profile-templates" data-target="#permission13">
                        <span class="knob"></span>
                    </label>
                    <a data-toggle="collapse" href="#permission13"
                        class="theme-green ml-3 text-underline">@lang('admin.DETAIL')</a>
                </div>
            </div>
        </div>
        <div id="permission13" class="panel-collapse collapse">
            <div class="panel-body border-0 py-0">
                <ul class="list-group toggle-wrapper">
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_VIEW_BUTTON') @lang('admin.TITLE_PROFILE_TEMPLATE_TEXT') </a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-profile-templates-listing" name="profile-templates-listing"
                                value="profile-templates-listing">
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_ADD_BUTTON') @lang('admin.TITLE_PROFILE_TEMPLATE_TEXT') </a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-profile-templates-add" name="profile-templates-add"
                                value="profile-templates-add">
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                </ul>
            </div>
        </div>
    </div>

     {{-- Manage Diagnostic Finding Types --}}
    <div class="panel panel-default">
        <div class="panel-heading">
            <div class="list-group-item d-flex">
                <a href="#">@lang('admin.TITLE_MANAGE_TEXT') @lang('admin.TITLE_DIAGNOSTIC_FINDING_TYPES_TEXT')</a>
                <div class="label-anchor">
                    <label class="switch ml-auto">
                        <input type="checkbox" class="checkbox-permissions checkbox-master"
                            id="permission-manage-diagnostic-finding-types" name="manage-diagnostic-finding-types"
                            value="manage-diagnostic-finding-types" data-target="#permission14">
                        <span class="knob"></span>
                    </label>
                    <a data-toggle="collapse" href="#permission14"
                        class="theme-green ml-3 text-underline">@lang('admin.DETAIL')</a>
                </div>
            </div>
        </div>
        <div id="permission14" class="panel-collapse collapse">
            <div class="panel-body border-0 py-0">
                <ul class="list-group toggle-wrapper">
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_VIEW_BUTTON')  @lang('admin.TITLE_DIAGNOSTIC_FINDING_TYPES_TEXT')</a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-diagnostic-finding-types-listing" name="diagnostic-finding-types-listing"
                                value="diagnostic-finding-types-listing"> 
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_ADD_BUTTON') @lang('admin.TITLE_DIAGNOSTIC_FINDING_TYPES_TEXT')</a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-diagnostic-finding-types-add" name="diagnostic-finding-types-add"
                                value="diagnostic-finding-types-add">
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                </ul>
            </div>
        </div>
    </div>

       {{-- Manage Menus Settings --}}
    <div class="panel panel-default">
        <div class="panel-heading">
            <div class="list-group-item d-flex">
                <a href="#">@lang('admin.TITLE_MANAGE_TEXT') @lang('admin.TITLE_MENU_SETTING_TEXT')</a>
                <div class="label-anchor">
                    <label class="switch ml-auto">
                        <input type="checkbox" class="checkbox-permissions checkbox-master"
                            id="permission-manage-menu-setting" name="manage-menu-setting"
                            value="manage-menu-setting" data-target="#permission15">
                        <span class="knob"></span>
                    </label>
                    <a data-toggle="collapse" href="#permission15"
                        class="theme-green ml-3 text-underline">@lang('admin.DETAIL')</a>
                </div>
            </div>
        </div>
        <div id="permission15" class="panel-collapse collapse">
            <div class="panel-body border-0 py-0">
                <ul class="list-group toggle-wrapper">
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_VIEW_BUTTON')  @lang('admin.TITLE_MENU_SETTING_TEXT')</a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-menu-setting-listing" name="menu-setting-listing"
                                value="menu-setting-listing"> 
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_ADD_BUTTON') @lang('admin.TITLE_MENU_SETTING_TEXT')</a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-menu-setting-add" name="menu-setting-add"
                                value="menu-setting-add">
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                </ul>
            </div>
        </div>
    </div>


     {{-- Manage Finding Services --}}
    <div class="panel panel-default">
        <div class="panel-heading">
            <div class="list-group-item d-flex">
                <a href="#">@lang('admin.TITLE_MANAGE_TEXT')  @lang('admin.TITLE_FINDING_SERVICES_TEXT')</a>
                <div class="label-anchor">
                    <label class="switch ml-auto">
                        <input type="checkbox" class="checkbox-permissions checkbox-master"
                            id="permission-manage-finding-services" name="manage-finding-services"
                            value="manage-finding-services" data-target="#permission16">
                        <span class="knob"></span>
                    </label>
                    <a data-toggle="collapse" href="#permission16"
                        class="theme-green ml-3 text-underline">@lang('admin.DETAIL')</a>
                </div>
            </div>
        </div>
        <div id="permission16" class="panel-collapse collapse">
            <div class="panel-body border-0 py-0">
                <ul class="list-group toggle-wrapper">
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_VIEW_BUTTON') @lang('admin.TITLE_FINDING_SERVICES_TEXT') </a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-finding-services-listing" name="finding-services-listing"
                                value="finding-services-listing">
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_ADD_BUTTON') @lang('admin.TITLE_FINDING_SERVICES_TEXT') </a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-finding-services-add" name="finding-services-add"
                                value="finding-services-add">
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                </ul>
            </div>
        </div>
    </div>
   
   {{-- Manage ordinations --}}
    <div class="panel panel-default">
        <div class="panel-heading">
            <div class="list-group-item d-flex">
                <a href="#">@lang('admin.TITLE_MANAGE_TEXT')  @lang('admin.TITLE_ORDINATION_TEXT')</a>
                <div class="label-anchor">
                    <label class="switch ml-auto">
                        <input type="checkbox" class="checkbox-permissions checkbox-master"
                            id="permission-manage-ordination" name="manage-ordination"
                            value="manage-ordination" data-target="#permission17">
                        <span class="knob"></span>
                    </label>
                    <a data-toggle="collapse" href="#permission17"
                        class="theme-green ml-3 text-underline">@lang('admin.DETAIL')</a>
                </div>
            </div>
        </div>
        <div id="permission17" class="panel-collapse collapse">
            <div class="panel-body border-0 py-0">
                <ul class="list-group toggle-wrapper">
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_VIEW_BUTTON') @lang('admin.TITLE_ORDINATION_TEXT') </a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-manage-ordination-listing" name="manage-ordinations-listing"
                                value="manage-ordination-listing">
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_ADD_BUTTON') @lang('admin.TITLE_ORDINATION_TEXT') </a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-manage-ordination-add" name="manage-ordinations-add"
                                value="manage-ordination-add">
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                </ul>
            </div>
        </div>
    </div>
 
    {{--Manage  Specialist--}}
    <div class="panel panel-default">
        <div class="panel-heading">
            <div class="list-group-item d-flex">
                <a href="#">@lang('admin.TITLE_MANAGE_TEXT')  @lang('admin.TITLE_SPECIALIST_TEXT')</a>
                <div class="label-anchor">
                    <label class="switch ml-auto">
                        <input type="checkbox" class="checkbox-permissions checkbox-master"
                            id="permission-manage-specialist" name="manage-specialist"
                            value="manage-specialist" data-target="#permission18">
                        <span class="knob"></span>
                    </label>
                    <a data-toggle="collapse" href="#permission18"
                        class="theme-green ml-3 text-underline">@lang('admin.DETAIL')</a>
                </div>
            </div>
        </div>
        <div id="permission18" class="panel-collapse collapse">
            <div class="panel-body border-0 py-0">
                <ul class="list-group toggle-wrapper">
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_VIEW_BUTTON')    @lang('admin.TITLE_SPECIALIST_TEXT')</a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-manage-specialist-listing" name="manage-specialist-listing"
                                value="manage-specialist-listing">
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_ADD_BUTTON') @lang('admin.TITLE_SPECIALIST_TEXT') </a>
                        <label class="sub-menu switch ml-auto">
                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-manage-specialist-add" name="manage-specialists-add"
                                value="manage-specialist-add">
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                </ul>
            </div>
        </div>

    </div>

     {{--Manage  Document--}}
    <div class="panel panel-default">
        <div class="panel-heading">
            <div class="list-group-item d-flex">
                <a href="#">@lang('admin.TITLE_MANAGE_TEXT')  @lang('admin.TITLE_SPECIALIST_DOCUMENT_TEXT')</a>
                <div class="label-anchor">
                    <label class="switch ml-auto">
                        <input type="checkbox" class="checkbox-permissions checkbox-master"
                            id="permission-manage-document" name="manage-document"
                            value="manage-document" data-target="#permission19">
                        <span class="knob"></span>
                    </label>
                    <a data-toggle="collapse" href="#permission19"
                        class="theme-green ml-3 text-underline">@lang('admin.DETAIL')</a>
                </div>
            </div>
        </div>
        <div id="permission19" class="panel-collapse collapse">
            <div class="panel-body border-0 py-0">
                <ul class="list-group toggle-wrapper">
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_VIEW_BUTTON')    @lang('admin.TITLE_SPECIALIST_DOCUMENT_TEXT')</a>
                        <label class="sub-menu switch ml-auto">

                           <!--  <input type="checkbox" class="checkbox-permissions"
                                id="permission-manage-document-listing" name="manage-specialist-listing"
                                value="manage-document-listing"> -->

                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-manage-document-listing" name="manage-document-listing"
                                value="manage-document-listing">     


                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                    <li class="list-group-item d-flex">
                        <a href="#">@lang('admin.TITLE_ADD_BUTTON') @lang('admin.TITLE_SPECIALIST_DOCUMENT_TEXT') </a>
                        <label class="sub-menu switch ml-auto">
                            <!-- <input type="checkbox" class="checkbox-permissions"
                                id="permission-manage-document-add" name="manage-document-add"
                                value="manage-specialist-add"> -->

                            <input type="checkbox" class="checkbox-permissions"
                                id="permission-manage-document-add" name="manage-document-add"
                                value="manage-document-add">    
                                
                            <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                        </label>
                    </li>
                </ul>
            </div>
        </div>

         {{--Manage Notification Patients --}}
            <div class="panel panel-default">
                <div class="panel-heading">
                    <div class="list-group-item d-flex">
                        <!-- <a href="#">@lang('admin.TITLE_MANAGE_TEXT')  @lang('admin.TITTLE_NOTIFICATION_PATIENT')</a> -->

                        <a href="#">@lang('admin.MANAGE_NOTIFICATION_PATIENT')</a>

                        <div class="label-anchor">
                            <label class="switch ml-auto">
                                <input type="checkbox" class="checkbox-permissions checkbox-master"
                                    id="permission-manage-notification-patients" name="manage-notification-patients"
                                    value="manage-notification-patients" data-target="#permission20">
                                <span class="knob"></span>
                            </label>
                            <a data-toggle="collapse" href="#permission20"
                                class="theme-green ml-3 text-underline">@lang('admin.DETAIL')</a>
                        </div>
                    </div>
                </div>
                <div id="permission20" class="panel-collapse collapse">
                    <div class="panel-body border-0 py-0">
                        <ul class="list-group toggle-wrapper">
                            <li class="list-group-item d-flex">
                                <a href="#">@lang('admin.TITLE_VIEW_BUTTON')    @lang('admin.TITTLE_NOTIFICATION_PATIENT')</a>
                                <label class="sub-menu switch ml-auto">
                                    <input type="checkbox" class="checkbox-permissions"
                                        id="permission-notification-patients-list" name="notification-patients-list"
                                        value="notification-patients-list">
                                    <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                                </label>
                            </li>
                            <li class="list-group-item d-flex">
                                <a href="#">@lang('admin.TITLE_ADD_BUTTON') @lang('admin.TITTLE_NOTIFICATION_PATIENT') </a>
                                <label class="sub-menu switch ml-auto">
                                    <input type="checkbox" class="checkbox-permissions"
                                        id="permission-notification-patients-add" name="notification-patients-add"
                                        value="notification-patients-add">
                                    <span class="knob text-white"><em>@lang('admin.TITLE_OFF')</em></span>
                                </label>
                            </li>
                        </ul>
                    </div>
                </div>
                
            </div>
        
    </div>
</div>