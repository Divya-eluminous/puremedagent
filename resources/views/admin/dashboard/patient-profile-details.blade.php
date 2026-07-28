<!-- <link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/select2/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
 -->
<!-- <link rel="stylesheet" href="{{ asset('assets/plugins/toastr/toastr.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/admin-lte/plugins/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css') }}">
 -->
<!-- <form id="frmAppointmentEdit" role="form" data-toggle="validator" action="">   --> 


<!-- <script src="{{ asset('assets/admin-lte/plugins/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('assets/admin-lte/plugins/jquery-ui/jquery-ui.min.js') }}"></script>  -->
<script>
  $( function() {
    $( "#datepicker" ).datepicker({
         dateFormat: 'dd-mm-yy',
          changeMonth: true,
          changeYear: true, 
          yearRange: '1920:+0',
           maxDate: 0
    });
  } );
</script>
    


      <input type="hidden" name="patientId" id="patientId" value="{{ $patient->id }}">
      <input type="hidden" name="google_event_id" id="google_event_id" value="{{ $google_event_id }}">

      <input type="hidden" name="profile_update_url" id="profile_update_url" value="{{ route('admin.dashboardupdatePatientProfile', [base64_encode(base64_encode($patient->id))]) }}">
    
    <div class="row">
            <div class="col-sm-6">
            
              <div class="form-group ">
                  <label class="theme-blue"> 
                  @lang('admin.TITLE_PATIENT_COUNTRY_CODE') <span class="required">*</span></label> 
                   <div class="select-editable"><select 
                      class="form-control my-select"
                      name="country_code"
                      id="country_code"
                      required
                      maxlength="250" 
                      data-error="@lang('admin.ERR_COUNTRY_CODE_REQUIRED')"
                      onchange="this.nextElementSibling.value=this.value">
                      @php
                          $country_codes = ['+43', '0043', '0','+91'];
                          if(!in_array($patient->country_code,$country_codes)){
                           $country_codes[] = $patient->country_code;
                          }

                      @endphp
                      @foreach($country_codes as $item)
                          <option value="{{$item}}"
                          @if($patient->country_code == old('country_code', $item)) selected="selected" @endif >{{$item}}</option>
                      @endforeach
                  </select>
                 <input  
                      type="text" 
                      name="format"
                      id="format"  
                      class="form-control"
                      placeholder="@lang('admin.TITLE_PATIENT_COUNTRY_CODE')"   
                      required
                      value="{{ $patient->country_code }}"
                      maxlength="5" 
                      pattern="(\+[0-9]+|0[0-9]+|00[0-9]+)"
                      data-error="@lang('admin.ERR_COUNTRY_CODE_REQUIRED')"
                      data-pattern-error ="@lang('admin.ERR_COUNTRY_CODE_WRONG')"  />
                   </div>
                  <span class="help-block invalid-feedback with-errors" >
                      <ul class="list-unstyled">
                          <li class="err_country_code"></li>
                      </ul>
                  </span>
              </div> 
            </div>
            
            <div class="col-sm-6">
              <div class="form-group">
                  <label class="theme-blue"> @lang('admin.TITLE_PATIENT_MOBILE_NO') <span class="required">*</span></label>

                   <input class="form-control" type="text" id="mobile_no" name="mobile_no" required value="{{ isset($patient->mobile_no)? $patient->mobile_no:''}}" maxlength="15" data-error="@lang('admin.ERR_MOBILE_NO_REQUIRED')">
                   
                 <span class="help-block invalid-feedback with-errors">
                    <ul class="list-unstyled">
                       <li class="err_mobile_no"></li>
                    </ul>
                 </span>
              </div>
            
            </div>
   
    </div>

     <div class="row">
        <div class="col-sm-6">
           <div class="form-group">
               <label class="theme-blue"> @lang('admin.TITLE_PATIENT_FIRST_NAME') <span class="required">*</span></label>

                <input class="form-control" type="text" id="first_name" name="first_name" value="{{ isset($patient->first_name)? $patient->first_name:''}}"    required maxlength="250" data-error="@lang('admin.ERR_FIRST_NAME_REQUIRED')" >
                
              <span class="help-block invalid-feedback with-errors">
                 <ul class="list-unstyled">
                    <li class="err_first_name"></li>
                 </ul>
              </span>
           </div>
        </div>

        <div class="col-sm-6">
           <div class="form-group">
               <label class="theme-blue"> @lang('admin.TITLE_PATIENT_FAMILY_NAME') <span class="required">*</span></label>

                <input class="form-control" type="text" id="family_name" name="family_name"   required value="{{ isset($patient->family_name)? $patient->family_name:''}}"   data-error="@lang('admin.ERR_PATIENT_FAMILY_NAME_REQUIRED')">
                
              <span class="help-block invalid-feedback with-errors">
                 <ul class="list-unstyled">
                    <li class="err_family_name"></li>
                 </ul>
              </span>
           </div>
        </div>

    </div>    

    <div class="row">

        <div class="col-sm-6">
           <div class="form-group">
               <label class="theme-blue"> @lang('admin.TITLE_PATIENT_BIRTH_DATE') <span class="required">*</span></label>

                <input class="form-control" type="text" id="datepicker" name="birth_date"  value="{{ $patient->birth_date?date('d-m-Y',strtotime($patient->birth_date)):'' }}"
                maxlength="250"data-error="@lang('admin.ERR_BIRTH_DATE_REQUIRED')"
                  required readonly="readonly" style="background-color:white;">
                
              <span class="help-block invalid-feedback with-errors">
                 <ul class="list-unstyled">
                    <li class="err_birth_date"></li>
                 </ul>
              </span>
           </div>
        </div>
         
        <div class="col-sm-6">
           <div class="form-group">
               <label class="theme-blue"> @lang('admin.TITLE_PATIENT_GENDER') <span class="required">*</span></label>

              <select name="gender" id="gender" class="form-control"
                required
                maxlength="250" 
                data-error="@lang('admin.ERR_PATIENT_GENDER_REQUIRED')">
                  <option value="" name="">@lang('admin.TITLE_SELECT_GENDER')</option>
                  @foreach(['M', 'W','D'] as $item)
                       <option value="{{$item}}"
                       @if($patient->gender == old('gender', $item)) selected="selected" @endif >{{$item}}</option>
                  @endforeach 
               </select>
                
              <span class="help-block invalid-feedback with-errors">
                 <ul class="list-unstyled">
                    <li class="err_gender"></li>
                 </ul>
              </span>
           </div>
        </div>
        
    </div>

    <div class="row">
         <!-- <div class="col-sm-6">
           <div class="form-group">
               <label class="theme-blue"> @lang('admin.TITLE_COUNTRY') <span class="required">*</span></label>

              <select name="country" id="country" class="form-control"
                required
                maxlength="250" 
                data-error="@lang('admin.ERR_COUNTRY_REQUIRED')">
                  <option value="" name="">@lang('admin.TITLE_SELECT_COUNTRY')</option>
                    <option value="Austria" @if($patient->country == 'Austria') selected="selected" @endif>@lang('admin.TITLE_COUNTRY_AUSTRIA')</option>
                    <option value="Germany" @if($patient->country == 'Germany') selected="selected" @endif>@lang('admin.TITLE_COUNTRY_GERMANY')</option>
                    <option value="Switzerland" @if($patient->country == 'Switzerland') selected="selected" @endif>@lang('admin.TITLE_COUNTRY_SWITZERLAND')</option>
               </select>
                
              <span class="help-block invalid-feedback with-errors">
                 <ul class="list-unstyled">
                    <li class="err_country"></li>
                 </ul>
              </span>
           </div>
        </div> -->
        <div class="col-sm-6">
           <div class="form-group">
               <label class="theme-blue"> @lang('admin.TITLE_PATIENT_EMAIL') <span class="required">*</span></label>

                <input class="form-control" type="email" id="email" name="email" required value="{{ isset($patient->email)? $patient->email:''}}" maxlength="250" data-error="@lang('admin.ERR_EMAIL_REQUIRED')" >
                
              <span class="help-block invalid-feedback with-errors">
                 <ul class="list-unstyled">
                    <li class="err_email"></li>
                 </ul>
              </span>
           </div>
        </div>
    </div>
       
  
   
    
   </div>




<!-----------end code added by swapnil 13-09-2022 ---------------------------------------->
   <div class="modal-footer">
      <button type="submit" class="btn btn-success">@lang('admin.TITLE_SAVE_BUTTON')</button>
   </div>
<!-- </form> -->

<!-- <script>
    $(document).ready(function ()  {
     $('#birth_date').datepicker({
      dateFormat: 'dd-mm-yy',
      changeMonth: true,
      changeYear: true, 
      yearRange: '1920:+0',
      startDate: new Date('1920-01-01'),
      maxDate: 0
  });
 });
</script> -->

<!-- <script src="{{ asset('assets/admin-lte/plugins/moment/moment.min.js') }}"></script> -->
<!-- <script type="text/javascript" src="{{ asset('assets/plugins/input-mask/mask.js') }}"></script> -->

<!-- <script type="text/javascript" src="{{ url('assets/admin/js/dashboard/index.js?ver=0.044') }}"></script> -->

<!-- <script src="{{ asset('assets/admin-lte/plugins/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('assets/admin-lte/plugins/jquery-ui/jquery-ui.min.js') }}"></script> -->

<!-- <script src="{{ asset('assets/admin-lte/plugins/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"></script>  -->

<!-- jQuery UI 1.11.4 -->
<!--  <script src="{{ asset('assets/admin-lte/plugins/jquery-ui/jquery-ui.min.js') }}"></script>
<script src="{{ asset('assets/admin-lte/plugins/select2/js/select2.full.min.js') }}"></script>
-->
<!--<script src="{{ asset('assets/plugins/lodingoverlay/loadingoverlay.min.js') }}"></script>
<script src="{{ asset('assets/plugins/toastr/toastr.min.js') }}"></script>
<script src="{{ asset('assets/plugins/toastr/toastr.options.js') }}"></script> -->
<!-- overlayScrollbars -->
<!-- <script src="{{ asset('assets/admin-lte/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script> -->


<script type="text/javascript">

   
    

/**************added on 3-apr-24*for #3app*******************************/

  
  
 $('#ProfileEditFrm').on('submit', function (e) 
  {
     //alert('innnnnnnnnnn');
      if (!e.isDefaultPrevented()) 
      {
        const $this = $(this);
        const action = $("#profile_update_url").val();
        const formData = new FormData($this[0]);

        var patient_id = $("#patientId").val();
        var google_event_id = $("#google_event_id").val();

        //alert(google_event_id);


        if(patient_id==undefined) {
          patient_id = '';
        }
        // console.log(patient_id);
        formData.append('patient_id', patient_id);

        $('#ProfileEditFrm,.model-body').LoadingOverlay("show", {
          background: "rgba(165, 190, 100, 0.4)",
        });

        axios.post(action, formData)
          .then(function (response) {
            const resp = response.data;

            if (resp.status == 'success') {
              $this[0].reset();
              toastr.success(resp.msg);
              $('#ProfileEditFrm,.model-body').LoadingOverlay("hide");

              $("#profileModal").hide(); //added on 13-march-24


              /***********added on 8-apr-24************************/

                const action3 = ADMINURL + "/dashboard/addtoDashboard/" + google_event_id;
                axios.get(action3)
                .then(response => {
                  const resp = response.data;
                  if(resp)
                  { 
                     if(resp.status == 'success')
                     {
                        toastr.success(resp.msg);
                        $('.openProfileModal').hide();
                        $('#appointmentModal').hide(); // added on 30-may-24 to hide popup
                     }//if status success   
                  }//if resp
                }) 
                .catch(error => {
                  // $('.card-body').LoadingOverlay("hide");
                })   


              /**********added on 8-apr-24***************************/



            }
            if (resp.status == 'error') {
              $('#ProfileEditFrm,.model-body').LoadingOverlay("hide");
              toastr.error(resp.msg);
              const errorBag = resp.errors;
              $.each(errorBag, function (fieldName, value) {
                $('.err_' + fieldName).closest('.form-group').addClass('has-error has-danger');
                $('.err_' + fieldName).text(value[0]).closest('span').show();
              })
            }
          })
          .catch(function (error)
          {
            $('#ProfileEditFrm,.model-body').LoadingOverlay("hide");
            const errorBag = error.response.data.errors;
            $.each(errorBag, function (fieldName, value) {
              $('.err_' + fieldName).closest('.form-group').addClass('has-error has-danger');
              $('.err_' + fieldName).text(value[0]).closest('span').show();
            })
          });
        return false;
      }
  });

/***************added on 3-apr-24**for #3*app*********************************/




    
</script>
