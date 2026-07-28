@if(auth()->check())
<div id="updateUserPassword" class="modal fade note-model" role="dialog">
 <div class="modal-dialog">
   <div class="modal-content">
     <div class="modal-header">
       <h4 class="modal-title">Update User Password</h4>
       <button type="button" class="close" data-dismiss="modal" aria-label="Close">
         <span aria-hidden="true">&times;</span></button> 
     </div>
     <form id="updateUserPasswordForm" action="{{ url('/admin/users/updatePassword') }}" data-toggle="validator" role="form">
            <div class="modal-body border-0">

                  <div class="f-col-12 form-group">
                     <label class="theme-blue">Old Password <span class="required">*</span></label>
                     <input 
                        class="form-control" 
                        type="password" 
                        name="old_password"
                        required 
                        data-error="Old password field is required."
                     >
                     <span class="help-block invalid-feedback with-errors">
                        <ul class="list-unstyled">
                           <li class="err_old_password"></li>
                        </ul>
                     </span>
                  </div>

                  <div class="f-col-12 form-group">
                     <label class="theme-blue">@lang('admin.TITLE_PASS') <span class="required">*</span></label>
                     <input 
                        class="form-control" 
                        type="password" 
                        name="password" 
                        required
                        data-error="New password field is required."
                     >
                     <span class="help-block invalid-feedback with-errors">
                        <ul class="list-unstyled">
                           <li class="err_password"></li>
                        </ul>
                     </span>
                  </div>
               
                  <div class="f-col-12 form-group">
                     <label class="theme-blue">@lang('admin.TITLE_CONFIRM_PASS') <span
                        class="required">*</span></label>

                     <input 
                        class="form-control" 
                        type="password" 
                        name="confirm_password" 
                        required
                        data-error="@lang('admin.ERR_CONFIRM_PASS')"
                     >
                     <span class="help-block invalid-feedback with-errors">
                        <ul class="list-unstyled">
                           <li class="err_confirm_password"></li>
                        </ul>
                     </span>
               
               </div>
              <!--  <div class="d-flex pt-4">
                  <button type="submit" class="blue-btn ml-auto">Update</button>
               </div> -->
            </div>
         
     <div class="modal-footer">
       <!-- <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button> -->
       <button type="submit" class="btn btn-primary">Update</button>
     </div>
     </form>
   </div>
   <!-- /.modal-content -->
 </div>
 <!-- /.modal-dialog -->
</div>

@endif