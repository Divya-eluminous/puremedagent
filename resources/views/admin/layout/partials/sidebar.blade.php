<?php 
if(!empty(Config('ordination_id')))
{
?>
<!-- @include('admin/layout/partials/ordination_css') -->
<?php
} 


//Code for logo path
use App\Traits\GeneralTrait;
 $logo = $logoPath =  $newTag = '';
// $newTag = GeneralTrait::checkForNewApps(); 
if(!empty(Config('ordination_id')))
{
  $logoPath = GeneralTrait::getLogoPath();
}//if not empty logopath
?>
<!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4" id="reloadSidebar">
    <!-- Brand Logo -->
    <a href="{{ route('admin.dashboard') }}" class="brand-link" >
      <img src="{{ asset('assets/admin/images/logo-p.png') }}" alt="AdminLTE Logo" class="sidebar-logo"
           style="opacity: .8">
      <!-- <span class="brand-text font-weight-light">@lang('admin.TITLE_SITE_BEGIN')@lang('admin.TITLE_SITE_END')</span> -->
    </a>

    <!-- Sidebar -->
    <div class="sidebar">   
      <!-- Sidebar user panel (optional) -->
      <!-- <div class="user-panel mt-3 pb-3 mb-3 d-flex"> 
        <div class="image">
          <img src="{{ asset('assets/admin-lte/dist/img/user2-160x160.jpg') }}" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
          <a href="#" class="d-block">{{ ucwords(auth()->user()->name) }}</a>
        </div>
      </div> -->

      <!-- Sidebar Menu --> 
      <nav class="mt-2"> 
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
         
        @php
        $websiteId = 0;
        $ordination_id = 0;
        $is_speciality = 0;
        
        // Hyn tenancy code (commented out)
        // $website   = \Hyn\Tenancy\Facades\TenancyFacade::website();
        
        // Stancl tenancy logic - using tenancy() helper
        $website = tenancy()->tenant;
        if(!empty($website)) {
            $websiteId = $website->id;
            $ordination_id = $website->ordination_id;
        }
        
        // Check for speciality
        if($ordination_id > 0) {
            $is_speciality = DB::table('ordination_has_specialist')->where('ordination_id',$ordination_id)->whereNull('deleted_at')->count();
            if($is_speciality > 0) {
                $is_speciality = 1;
            }
        }
        @endphp      
       
        @if($is_speciality)
            @can('dashboard') 
            <li class="nav-item {{ active(['admin/dashboard','admin/dashboard/*']) }}">
              <a href="{{ route('admin.dashboard') }}" class="nav-link">
                <i class="nav-icon fas fa-calendar"></i>
                <p>
                  @lang('admin.TITLE_DASHBOARD') 
                </p>
              </a>
            </li>
            @endcan
            @can('doctor-dashboard') 
            <li class="nav-item {{ active('admin/doctor-dashboard') }}">
              <a href="{{ route('admin.doctor-dashboard') }}" class="nav-link {{ active('admin/doctor-dashboard') }}">
                <i class="nav-icon fas fa-tachometer-alt "></i>
                <p>
                  @lang('admin.TITLE_CURRENT_APPOINTMENT_TEXT') 
                </p>
              </a>
            </li>
            @endcan
            <!-- Assistientin Dashboard -->
            @can('assistant-dashboard') 
            <li class="nav-item {{ active(['admin/assistant-dashboard','admin/assistant-dashboard/*']) }}">
              <a href="{{ route('admin.assistant-dashboard') }}" class="nav-link">
                <i class="nav-icon fas fa-tachometer-alt"></i>
                <p>
                  @lang('admin.TITLE_ASSISTANT_DASHBOARD') 
                </p>
              </a>
            </li>
            @endcan
            <!-- End Assistientin Dashboard -->
            @can('manage-appointment')
            <li class="nav-item has-treeview @if (is_active(['admin/appointment','admin/notification','admin/notification/*','admin/appointment/*'])) menu-open @endif ">
              <a href="#" class="nav-link">
                <i class="nav-icon fas fa-handshake"></i>
                <p>
                  @lang('admin.TITLE_APPOINTMENT_TEXT')
                  @lang('admin.TITLE_MANAGE_TEXT') 
                  <i class="right fas fa-angle-left"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">   

                @can('appointment-add')         
                <li class="nav-item">
                  <a href="{{ route('admin.appointment.create') }}" class="nav-link {{ active('admin/appointment/create') }}">
                    <i class="far fa-circle nav-icon"></i> 
                   <p>@lang('admin.TITLE_APPOINTMENT_TEXT')
                   @lang('admin.TITLE_ADD_BUTTON') </p>
                  </a>
                </li>  
                @endcan 

                @can('appointment-listing')
                <li class="nav-item">  
                  <a href="{{ route('admin.appointment.index') }}" class="nav-link {{ active('admin/appointment') }}">
                    <i class="far fa-circle nav-icon"></i> 
                   <p>@lang('admin.TITLE_APPOINTMENT_TEXT')
                   @lang('admin.TITLE_VIEW_BUTTON') </p> 
                  </a>
                </li>  
                @endcan 
                <li class="nav-item">  
                  <a href="{{ url('admin/appointment/event_type_list') }}" class="nav-link {{ active('admin/appointment/event_type_list') }}">
                    <i class="far fa-circle nav-icon"></i> 
                   <p>@lang('admin.SERVICE_EVENT_TYPE_MENU')
                   </p> 
                  </a>
                </li>
                <!-- @can('activity-logs') -->
                <li class="nav-item {{ active('admin/notification') }}">
                  <a href="{{ route('admin.notification.index') }}" class="nav-link {{ active('admin/notification') }}"> 
                    <i class="far fa-circle nav-icon"></i> 
                   <p>@lang('admin.TITLE_NOTIFICATION_TEXT')</p>
                  </a>
                </li>
                <!-- @endcan   -->

                

              </ul>
            </li>
            @endcan 

            @can('manage-waiting-queue')
            <li class="nav-item has-treeview @if (is_active(['admin/waiting-queue-number','admin/waiting-number-symbols','admin/waiting-queue-number/*'])) menu-open @endif ">
              <a href="#" class="nav-link">
                <i class="nav-icon fas fa-hourglass-half"></i>
                <p>
                  @lang('admin.TITLE_WAITING_QUEUE_TEXT') 
                  @lang('admin.TITLE_MANAGE_TEXT') 
                  <i class="right fas fa-angle-left"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">    

                @can('waiting-number-symbols')         
                <li class="nav-item">
                  <a href="{{ route('admin.waiting-number-symbols.index') }}" class="nav-link {{ active('admin/waiting-number-symbols') }}">
                    <i class="far fa-circle nav-icon"></i> 
                   <p>@lang('admin.TITLE_WAITING_NUMBER_SYMBOLS_TEXT')</p> 
                  </a>
                </li>  
                @endcan   

                @can('waiting-queue-number')
                <li class="nav-item">  
                  <a href="{{ route('admin.waiting-queue-number.index') }}" class="nav-link {{ active('admin/waiting-queue-number') }}">
                    <i class="far fa-circle nav-icon"></i>  
                   <p>@lang('admin.TITLE_WAITING_QUEUE_TEXT')</p> 
                  </a>
                </li>  
                @endcan 

              </ul>
            </li>
            @endcan  

             @can('manage-patients')
          <li class="nav-item has-treeview @if (is_active(['admin/patients','admin/patients/*'])) menu-open @endif ">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-user-injured"></i>
              <p>
                @lang('admin.TITLE_PATIENT_TEXT')
                @lang('admin.TITLE_MANAGE_TEXT') 
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">

              @can('patients-add')
              <li class="nav-item">
                <a href="{{ route('admin.patients.create') }}" class="nav-link {{ active('admin/patients/create') }}">
                  <i class="far fa-circle nav-icon"></i> 
                 <p>@lang('admin.TITLE_PATIENT_TEXT')
                 @lang('admin.TITLE_ADD_BUTTON') </p>
                </a>
              </li>
              @endcan

              @can('patients-listing')
              <li class="nav-item">
                <a href="{{ route('admin.patients.index') }}" class="nav-link {{ active('admin/patients') }}">
                  <i class="far fa-circle nav-icon"></i> 
                 <p>@lang('admin.TITLE_PATIENT_TEXT')
                 @lang('admin.TITLE_VIEW_BUTTON') </p>
                </a>
              </li>
              @endcan
              <li class="nav-item">
                <a href="{{ url('admin/patients/patient_reminder') }}" class="nav-link {{ active('admin/patients/patient_reminder') }}">
                  <i class="far fa-circle nav-icon"></i> 
                 <p>@lang('admin.TITLE_REMINDER')
                 @lang('admin.TITLE_VIEW_BUTTON') </p>
                </a>
              </li>
            </ul>
          </li>
          @endcan
            @can('manage-roster')  
            <li class="nav-item has-treeview @if (is_active(['admin/roster','admin/roster/*'])) menu-open @endif ">
              <a href="#" class="nav-link">
                <i class="nav-icon fa fa-list-ol"></i> 
                <p>
                  @lang('admin.TITLE_ROSTER_MODULE')
                  @lang('admin.TITLE_MANAGE_TEXT') 
                  <i class="right fas fa-angle-left"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">

                @can('roster-add')
                 <li class="nav-item">
                  <a href="{{ route('admin.roster.create') }}" class="nav-link {{ active('admin/roster/create') }}">
                    <i class="far fa-circle nav-icon"></i> 
                   <p>@lang('admin.TITLE_ROSTER_MODULE')
                   @lang('admin.TITLE_ADD_BUTTON') </p>
                  </a>
                </li>
                @endcan

                @can('roster-listing')
                <li class="nav-item"> 
                  <a href="{{ route('admin.roster.index') }}" class="nav-link {{ active('admin/roster') }}">
                    <i class="far fa-circle nav-icon"></i> 
                   <p>@lang('admin.TITLE_ROSTER_MODULE')
                   @lang('admin.TITLE_VIEW_BUTTON') </p> 
                  </a>
                </li>
                @endcan 

              </ul>
            </li>
            @endcan 

         
           @endif
          @can('manage-users')
          <li class="nav-item has-treeview @if (is_active(['admin/users','admin/roles','admin/permissions', 'admin/activity-logs', 'admin/users/*'])) menu-open @endif ">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-users"></i>
              <p>@lang('admin.TITLE_USERS_MODULE')/@lang('admin.TITLE_ROLES_MODULE')
                @lang('admin.TITLE_MANAGE_TEXT')
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            
            <ul class="nav nav-treeview">
              @can('users-add')
              <li class="nav-item">
                <a href="{{ route('admin.users.create') }}" class="nav-link {{ active('admin/users/create') }}">
                  <i class="far fa-circle nav-icon"></i> 
                 <p>@lang('admin.TITLE_USER_MODULE')
                 @lang('admin.TITLE_ADD_BUTTON')</p>
                </a>
              </li>
              @endcan

              @can('users-listing')
              <li class="nav-item">
                <a href="{{ route('admin.users.index') }}" class="nav-link {{ active('admin/users') }}">
                  <i class="far fa-circle nav-icon"></i> 
                 <p>@lang('admin.TITLE_USER_MODULE')
                 @lang('admin.TITLE_VIEW_BUTTON') </p>
                </a>
              </li>
              @endcan

              @can('manage-roles')
              <li class="nav-item">
                <a href="{{ route('admin.roles.index') }}" class="nav-link {{ active('admin/roles') }}">
                  <i class="far fa-circle nav-icon"></i> 
                 <p>@lang('admin.TITLE_ROLES_MODULE')
                 @lang('admin.TITLE_MANAGE_TEXT')</p>
                </a>
              </li>
              @endcan

              @can('manage-permissions')
              <li class="nav-item {{ active('admin/permissions') }}">
                <a href="{{ route('admin.permissions.index') }}" class="nav-link {{ active('admin/permissions') }}">
                  <i class="far fa-circle nav-icon"></i> 
                 <p>@lang('admin.TITLE_PERMISSION_MODULE')
                 @lang('admin.TITLE_MANAGE_TEXT') </p>
                </a>
              </li>
              @endcan

              @can('activity-logs')
              <li class="nav-item {{ active('admin/activity-logs') }}">
                <a href="{{ route('admin.activity-logs.index') }}" class="nav-link {{ active('admin/activity-logs') }}">
                  <i class="far fa-circle nav-icon"></i> 
                 <p>@lang('admin.TITLE_ACTIVITY')</p>
                </a>
              </li>
              @endcan

            </ul> 
          </li>
          @endcan

          @can('manage-settings')
          <li class="nav-item has-treeview @if (is_active(['admin/settings','admin/settings/*'])) menu-open @endif ">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-cog"></i>
              <p>@lang('admin.TITLE_SETTING_TEXT')
                @lang('admin.TITLE_MANAGE_TEXT') 
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview"> 

              @can('setting-add')
              <li class="nav-item">
                <a href="{{ route('admin.settings.create') }}" class="nav-link {{ active('admin/settings/create') }}">
                  <i class="far fa-circle nav-icon"></i> 
                 <p>@lang('admin.TITLE_SETTING_TEXT')
                 @lang('admin.TITLE_ADD_BUTTON') </p>
                </a>
              </li>
              @endcan

              @can('setting-listing')
              <li class="nav-item">
                <a href="{{ route('admin.settings.index') }}" class="nav-link {{ active('admin/settings') }}">
                  <i class="far fa-circle nav-icon"></i> 
                 <p>@lang('admin.TITLE_SETTING_TEXT')
                 @lang('admin.TITLE_VIEW_BUTTON') </p>
                </a>
              </li>
              @endcan

            </ul>
          </li>
          @endcan

          @can('manage-exams')
          <li class="nav-item has-treeview @if (is_active(['admin/examinations','admin/examinations/*'])) menu-open @endif ">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-diagnoses"></i> 
              <p>@lang('admin.TITLE_EXAMINATIONS_TEXT')
                @lang('admin.TITLE_MANAGE_TEXT')
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview"> 

              @can('exams-add') 
              <li class="nav-item">
                <a href="{{ route('admin.examinations.create') }}" class="nav-link {{ active('admin/examinations/create') }}">
                  <i class="far fa-circle nav-icon"></i> 
                 <p>@lang('admin.TITLE_EXAMINATIONS_TEXT')
                 @lang('admin.TITLE_ADD_BUTTON')</p>
                </a>
              </li>
              @endcan

              @can('exams-listing')
              <li class="nav-item">
                <a href="{{ route('admin.examinations.index') }}" class="nav-link {{ active('admin/examinations') }}">
                  <i class="far fa-circle nav-icon"></i> 
                 <p>@lang('admin.TITLE_EXAMINATIONS_TEXT')
                 @lang('admin.TITLE_VIEW_BUTTON')</p>
                </a>
              </li>
              @endcan

            </ul>
          </li>
          @endcan
          <!-- Roshani added the code for CR #210 on 7-nov-2024 -->

            <!-- @if($is_speciality)

                
              @can('manage-apk') 
                <li class="nav-item {{ active(['admin/apks','admin/apks/*']) }}">
                  <a href="{{ route('admin.apks.index') }}" class="nav-link">
                    <i class="nav-icon fas fa-calendar"></i>
                    <p>
                      @lang('admin.TITLE_APK_DOWNLOAD') 
                    </p>
                     @if($newTag == true)
                      <span id="download-badge" class="badge badge-danger position-absolute top-0 start-100 translate-middle">New</span>
                     @endif                  

                  </a>
                </li>
              @endcan
            @else
              @can('manage-apk')
              <li class="nav-item has-treeview @if (is_active(['admin/apks','admin/apks/*'])) menu-open @endif ">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-diagnoses"></i> 
                  <p>@lang('admin.TITLE_APK_DOWNLOAD')
                    <i class="right fas fa-angle-left"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview"> 

                  @can('apk-add') 
                  <li class="nav-item">
                    <a href="{{ route('admin.apks.create') }}" class="nav-link {{ active('admin/apks/create') }}">
                      <i class="far fa-circle nav-icon"></i> 
                     <p>@lang('admin.TITLE_APK_ADD')</p>
                    </a>
                  </li>
                  @endcan

                  @can('apk-listing')
                  <li class="nav-item">
                    <a href="{{ route('admin.apks.index') }}" class="nav-link {{ active('admin/apks') }}">
                      <i class="far fa-circle nav-icon"></i>
                     <p>@lang('admin.TITLE_APK_VIEW')</p>
                      @if($newTag == true)
                     <span id="download-badge" class="badge badge-danger position-absolute top-0 start-100 translate-middle">New</span>
                     @endif     
                    </a>
                  </li>
                  @endcan

                </ul>
              </li>
              @endcan
            @endif  -->

          <!-- Roshani added the code for CR #210 on 7-nov-2024 -->


          @can('manage-check-list')
          <li class="nav-item has-treeview @if (is_active(['admin/check-list','admin/check-list/*'])) menu-open @endif ">
            <a href="#" class="nav-link">
             <i class="fa fa-check-circle nav-icon" aria-hidden="true"></i>
              <p>@lang('admin.TITLE_CHECKLIST_TEXT')
                @lang('admin.TITLE_MANAGE_TEXT')
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview"> 

              @can('manage-check-add') 
              <li class="nav-item">
                <a href="{{ route('admin.check-list.create') }}" class="nav-link {{ active('admin/check-list/create') }}">
                  <i class="far fa-circle nav-icon"></i> 
                 <p>@lang('admin.TITLE_CHECKLIST_TEXT')
                 @lang('admin.TITLE_ADD_BUTTON')</p>
                </a>
              </li>
              @endcan

              @can('manage-check-listing')
              <li class="nav-item">
                <a href="{{ route('admin.check-list.index') }}" class="nav-link {{ active('admin/check-list') }}">
                  <i class="far fa-circle nav-icon"></i> 
                 <p>@lang('admin.TITLE_CHECKLIST_TEXT')
                 @lang('admin.TITLE_VIEW_BUTTON')</p>
                </a>
              </li>
              @endcan

            </ul>
          </li>
          @endcan

          @can('manage-appointment-types')
          <li class="nav-item has-treeview @if (is_active(['admin/apointment-types','admin/apointment-types/*'])) menu-open @endif ">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-calendar-check"></i>
              <p>
                @lang('admin.TITLE_APPOINTMENT_TYPE_TEXT') 
                @lang('admin.TITLE_MANAGE_TEXT')
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview"> 
              @can('appointment-types-add')
              <li class="nav-item">
                <a href="{{ route('admin.apointment-types.create') }}" class="nav-link {{ active('admin/apointment-types/create') }}">
                  <i class="far fa-circle nav-icon"></i> 
                 <p>@lang('admin.TITLE_APPOINTMENT_TYPE_TEXT')
                 @lang('admin.TITLE_ADD_BUTTON') </p>
                </a>
              </li>
              @endcan

              @can('appointment-types-listing')
              <li class="nav-item">
                <a href="{{ route('admin.apointment-types.index') }}" class="nav-link {{ active('admin/apointment-types') }}">
                  <i class="far fa-circle nav-icon"></i> 
                 <p>@lang('admin.TITLE_APPOINTMENT_TYPE_TEXT')
                 @lang('admin.TITLE_VIEW_BUTTON')</p>
                </a>
              </li>
              @endcan

            </ul>
          </li>
          @endcan


         <!--  @can('manage-profile-templates')
          <li class="nav-item has-treeview @if (is_active(['admin/profile-templates','admin/profile-templates/*'])) menu-open @endif ">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-id-card"></i>
              <p>@lang('admin.TITLE_PROFILE_TEMPLATE_TEXT')
                @lang('admin.TITLE_MANAGE_TEXT')
                 <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview"> 

              @can('profile-templates-add')
              <li class="nav-item">
                 <a href="{{ route('admin.profile-templates.create') }}" class="nav-link {{ active('admin/profile-templates/create') }}">
                  <i class="far fa-circle nav-icon"></i> 
                 <p>@lang('admin.TITLE_PROFILE_TEMPLATE_TEXT')
                 @lang('admin.TITLE_ADD_BUTTON')</p>
                   </a>
              </li>
              @endcan

              @can('profile-templates-listing')             
              <li class="nav-item">
                  <a href="{{ route('admin.profile-templates.index') }}" class="nav-link {{ active('admin/profile-templates') }}">
                  <i class="far fa-circle nav-icon"></i> 
                 <p>@lang('admin.TITLE_PROFILE_TEMPLATE_TEXT')
                 @lang('admin.TITLE_VIEW_BUTTON')</p>
                 </a>
              </li>
              @endcan

            </ul>
          </li>
          @endcan -->

          <!-- @can('manage-diagnostic-finding-types') --> 
          <li class="nav-item has-treeview @if (is_active(['admin/diagnostic-finding-types','admin/diagnostic-finding-types/*'])) menu-open @endif ">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-file-contract"></i>
              <p>
                @lang('admin.TITLE_DIAGNOSTIC_FINDING_TYPES_TEXT')
                @lang('admin.TITLE_MANAGE_TEXT') 
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">   

              <!-- @can('diagnostic-finding-types-add')          -->
              <li class="nav-item">
                <a href="{{ route('admin.diagnostic-finding-types.create') }}" class="nav-link {{ active('admin/diagnostic-finding-types/create') }}">
                  <i class="far fa-circle nav-icon"></i> 
                 <p>@lang('admin.TITLE_DIAGNOSTIC_FINDING_TYPES_TEXT')
                 @lang('admin.TITLE_ADD_BUTTON') </p>
                </a>
              </li>  
              <!-- @endcan  -->

              <!-- @can('diagnostic-finding-types-listing') -->
              <li class="nav-item">  
                <a href="{{ route('admin.diagnostic-finding-types.index') }}" class="nav-link {{ active('admin/diagnostic-finding-types') }}">
                  <i class="far fa-circle nav-icon"></i> 
                 <p>@lang('admin.TITLE_DIAGNOSTIC_FINDING_TYPES_TEXT')
                 @lang('admin.TITLE_VIEW_BUTTON') </p> 
                </a>
              </li>  
              <!-- @endcan   -->

            </ul>
          </li>
          <!-- @endcan -->

          

          @can('manage-menu-setting') 
          <li class="nav-item has-treeview @if (is_active(['admin/menus-settings','admin/menus-settings/*'])) menu-open @endif ">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-bars"></i>
              <p>
                @lang('admin.TITLE_MENU_SETTING_TEXT')
                @lang('admin.TITLE_MANAGE_TEXT') 
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">

              @can('menu-setting-add')
              <li class="nav-item">
                <a href="{{ route('admin.menus-settings.create') }}" class="nav-link {{ active('admin/menus-settings/create') }}">
                  <i class="far fa-circle nav-icon"></i> 
                 <p>@lang('admin.TITLE_MENU_SETTING_TEXT')
                 @lang('admin.TITLE_ADD_BUTTON') </p>
                </a>
              </li>
              @endcan

              @can('menu-setting-listing')
              <li class="nav-item">  
                <a href="{{ route('admin.menus-settings.index') }}" class="nav-link {{ active('admin/menus-settings') }}">
                  <i class="far fa-circle nav-icon"></i> 
                 <p>@lang('admin.TITLE_MENU_SETTING_TEXT')
                 @lang('admin.TITLE_VIEW_BUTTON') </p> 
                </a>
              </li>
              @endcan 

            </ul>
          </li>
          @endcan 

          @can('manage-finding-services')
          <li class="nav-item has-treeview @if (is_active(['admin/finding-services','admin/finding-services/*'])) menu-open @endif ">
            <a href="#" class="nav-link">
             <i class="fa fa-search nav-icon" aria-hidden="true"></i>
              <p>
                @lang('admin.TITLE_FINDING_SERVICES_TEXT')
                @lang('admin.TITLE_MANAGE_TEXT') 
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">

              @can('finding-services-add')
              <li class="nav-item">
                <a href="{{ route('admin.finding-services.create') }}" class="nav-link {{ active('admin/finding-services/create') }}">
                  <i class="far fa-circle nav-icon"></i> 
                 <p>@lang('admin.TITLE_FINDING_SERVICES_TEXT')
                 @lang('admin.TITLE_ADD_BUTTON') </p>
                </a>
              </li>
              @endcan

              @can('finding-services-listing')
              <li class="nav-item">  
                <a href="{{ route('admin.finding-services.index') }}" class="nav-link {{ active('admin/finding-services') }}">
                  <i class="far fa-circle nav-icon"></i> 
                 <p>@lang('admin.TITLE_FINDING_SERVICES_TEXT')
                 @lang('admin.TITLE_VIEW_BUTTON') </p> 
                </a>
              </li>
              @endcan 

            </ul>
          </li>
          @endcan 

          @if(!$is_speciality)
            @can('manage-ordination')
              <li class="nav-item has-treeview @if (is_active(['admin/ordination','admin/ordination/*'])) menu-open @endif ">
                <a href="#" class="nav-link">
                 <i class="fa fa-search nav-icon" aria-hidden="true"></i>
                  <p>
                    @lang('admin.TITLE_ORDINATION_TEXT')
                    @lang('admin.TITLE_MANAGE_TEXT')
                    <i class="right fas fa-angle-left"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">

                  @can('ordination-add')
                  <li class="nav-item">
                    <a href="{{ route('admin.ordination.create') }}" class="nav-link {{ active('admin/ordination/create') }}">
                      <i class="far fa-circle nav-icon"></i> 
                     <p>@lang('admin.TITLE_ORDINATION_TEXT')
                     @lang('admin.TITLE_ADD_BUTTON') </p>
                    </a>
                  </li>
                  @endcan

                  @can('ordination-listing')
                  <li class="nav-item">  
                    <a href="{{ route('admin.ordination.index') }}" class="nav-link {{ active('admin/ordination') }}">
                      <i class="far fa-circle nav-icon"></i> 
                     <p>@lang('admin.TITLE_ORDINATION_TEXT')
                     @lang('admin.TITLE_VIEW_BUTTON') </p> 
                    </a>
                  </li>
                  @endcan 

                </ul>
              </li>
            @endcan
          @endif
            @can('manage-support-setting') 
          <li class="nav-item has-treeview @if (is_active(['admin/support-settings','admin/support-settings/*'])) menu-open @endif ">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-bars"></i>
              <p>
                @lang('admin.TITLE_SUPPORT_SETTING_TEXT')
                @lang('admin.TITLE_MANAGE_TEXT') 
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">

              @can('support-setting-add')
              <li class="nav-item">
                <a href="{{ route('admin.support-settings.create') }}" class="nav-link {{ active('admin/support-settings/create') }}">
                  <i class="far fa-circle nav-icon"></i> 
                 <p>@lang('admin.TITLE_SUPPORT_SETTING_TEXT')
                 @lang('admin.TITLE_ADD_BUTTON') </p>
                </a>
              </li>
              @endcan

              @can('support-setting-listing')
              <li class="nav-item">  
                <a href="{{ route('admin.support-settings.index') }}" class="nav-link {{ active('admin/support-settings') }}">
                  <i class="far fa-circle nav-icon"></i> 
                 <p>@lang('admin.TITLE_SUPPORT_SETTING_TEXT')
                 @lang('admin.TITLE_VIEW_BUTTON') </p> 
                </a>
              </li>
              @endcan 

            </ul>
          </li>
          @endcan 
     
          @if(empty($website ) )         
          @can('manage-specialist')
          <li class="nav-item has-treeview @if (is_active(['admin/specialist','admin/specialist/*'])) menu-open @endif ">
            <a href="#" class="nav-link">
             <i class="fa fa-search nav-icon" aria-hidden="true"></i>
              <p>
                @lang('admin.TITLE_SPECIALIST_TEXT')
                @lang('admin.TITLE_MANAGE_TEXT')
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">

              @can('manage-specialist-add')
              <li class="nav-item">
                <a href="{{ route('admin.specialist.create') }}" class="nav-link {{ active('admin/specialist/create') }}">
                  <i class="far fa-circle nav-icon"></i> 
                 <p>@lang('admin.TITLE_SPECIALIST_TEXT')
                 @lang('admin.TITLE_ADD_BUTTON') </p>
                </a>
              </li>
              @endcan

               @can('manage-specialist-listing')
              <li class="nav-item">  
                <a href="{{ route('admin.specialist.index') }}" class="nav-link {{ active('admin/specialist') }}">
                  <i class="far fa-circle nav-icon"></i> 
                 <p>@lang('admin.TITLE_SPECIALIST_TEXT')
                 @lang('admin.TITLE_VIEW_BUTTON') </p> 
                </a>
              </li>
              @endcan  

            </ul>
          </li>
          @endcan 
         
          
        @endif


        @can('manage-specialist')
          <li class="nav-item has-treeview @if (is_active(['admin/specialist','admin/specialist/*'])) menu-open @endif ">
            <a href="#" class="nav-link">
             <i class="fa fa-search nav-icon" aria-hidden="true"></i>
              <p>
                @lang('admin.TITLE_SPECIALIST_DOCUMENT_TEXT')
                @lang('admin.TITLE_MANAGE_TEXT')
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">

              @can('manage-document-add')
              <li class="nav-item">
                <a href="{{ url('admin/specialist/document/create/'.base64_encode(base64_encode(Session::get("specialist")))) }}" class="nav-link {{ active('admin/specialist/document/create/'.base64_encode(base64_encode(Session::get("specialist")))) }}">
                  <i class="far fa-circle nav-icon"></i> 
                 <p>@lang('admin.TITLE_SPECIALIST_DOCUMENT_TEXT')
                 @lang('admin.TITLE_ADD_BUTTON') </p>
                </a>
              </li>
              @endcan

              @can('manage-document-listing')
              <li class="nav-item">  
                <a href="{{ url('admin/specialist/documents/'.base64_encode(base64_encode(Session::get("specialist")))) }}" class="nav-link {{ active('admin/specialist/documents/'.base64_encode(base64_encode(Session::get("specialist")))) }}">
                  <i class="far fa-circle nav-icon"></i> 
                 <p>@lang('admin.TITLE_SPECIALIST_DOCUMENT_TEXT')
                 @lang('admin.TITLE_VIEW_BUTTON') </p> 
                </a>
              </li>
              @endcan  

            </ul>
          </li>
          @endcan

          @can('manage-notification-patients')
          <li class="nav-item has-treeview @if (is_active(['admin/notification-patient','admin/notification-patient/*'])) menu-open @endif ">
            <a href="#" class="nav-link">
             <i class="fa fa-solid fa-bell" aria-hidden="true"></i> &nbsp;  
              <p> 
                @lang('admin.MANAGE_NOTIFICATION_PATIENT')
                <!-- @lang('admin.TITLE_MANAGE_TEXT') -->
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">

              @can('notification-patients-add')
              <li class="nav-item">
                <a href="{{ url('admin/notification-patient/create') }}" class="nav-link {{ active('admin/notification-patient/create') }}">
                  <i class="far fa-circle nav-icon"></i> 
                 <p>@lang('admin.TITTLE_NOTIFICATION_PATIENT')
                 @lang('admin.TITLE_ADD_BUTTON') </p>
                </a>
              </li>
              @endcan

              @can('notification-patients-list')
              <li class="nav-item">  
                <a href="{{ url('admin/notification-patient') }}" class="nav-link {{ active('admin/notification-patient') }}">
                  <i class="far fa-circle nav-icon"></i> 
                 <p>@lang('admin.TITTLE_NOTIFICATION_PATIENT')
                 @lang('admin.TITLE_VIEW_BUTTON') </p> 
                </a>
              </li>
              @endcan  

            </ul>
          </li>
          @endcan 

        </ul>
      </nav>
      <!-- /.sidebar-menu -->
     
      <div class="card" id="dashboard_data" style="display:none;color: #000; background-color: #fff">
        
      </div>
     
    </div>
    <!-- /.sidebar -->
  </aside>

