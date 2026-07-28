@if(auth()->check())
<div id="updateUserPassword" class="modal fade note-model" role="dialog">
 <div class="modal-dialog">
   <div class="modal-content">
     <div class="modal-header">
       <h4 class="modal-title">@lang('admin.TITLE_CHANGE_PASSWORD_HEADING')</h4>
       <?php 
          $is_updated = Session::get('is_updated');
          //dd($is_updated);
       $is_updated = 0;
          if($is_updated == '1')
          {
        ?>
       <button type="button" class="close" data-dismiss="modal" aria-label="Close">
         <span aria-hidden="true">&times;</span></button>
       <?php } ?>
     </div>
     <form id="updateUserPasswordForm" action="{{ url('/admin/users/updatePassword') }}" data-toggle="validator" role="form">
            <div class="modal-body border-0">

                  <div class="f-col-12 form-group">
                     <label class="theme-blue">@lang('admin.TITLE_CHANGE_PASSWORD_OLD')<span class="required">*</span></label>
                     <input 
                        class="form-control" 
                        type="password" 
                        name="old_password"
                        required 
                        data-error="@lang('admin.ERR_CHANGE_PASSWORD_OLD')"
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
                        data-error="@lang('admin.ERR_CHANGE_PASSWORD_NEW')"
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
            </div>
         
     <div class="modal-footer">
       <!-- <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button> -->
       <button type="submit" class="btn btn-primary">@lang('admin.TITLE_CHANGE_PASSWORD_BUTTON')</button>
     </div>
     </form>
   </div>
   <!-- /.modal-content -->
 </div>
 <!-- /.modal-dialog -->
</div>

@endif
