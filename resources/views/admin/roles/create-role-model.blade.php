<div id="addRole" class="modal fade note-model" role="dialog">
 <div class="modal-dialog">
   <div class="modal-content">    
     <div class="modal-header">
       <h4 class="modal-title">@lang('admin.TITLE_ROLE') 
       @lang('admin.TITLE_ADD_BUTTON')</h4>
       <button type="button" class="close" data-dismiss="modal" aria-label="Close">
         <span aria-hidden="true">&times;</span></button>
     </div>
     <form id="AddRoleForm" action="{{ route('admin.roles.store') }}" data-toggle="validator" role="form" > 
            <div class="modal-body border-0">

                  <div class="d-flex flex-column mb-3 form-group">
                     <label class="theme-blue">@lang('admin.TITLE_ROLE_NAME') <span class="required">*</span></label> 
                     <input class="form-control" 
                           type="text"   
                           name="name" 
                           id="role_name"
                           autocomplete="off"  
                           required
                           data-error="@lang('admin.ERR_ROLE_NAME')"
                        >
                     <span class="help-block invalid-feedback with-errors">
                         <ul class="list-unstyled">
                             <li class="err_name"></li>
                         </ul>
                     </span> 
                  </div>
                  <div class="d-flex flex-column mb-3 form-group">
                     <label class="theme-blue">@lang('admin.TITLE_ROLE_IDENTIFIER') <span class="required">*</span></label>
                     <input class="form-control" 
                           type="text"   
                           name="identifier" 
                           id="role_identifier"
                           autocomplete="off" 
                           required
                           data-error="@lang('admin.ERR_ROLE_IDENTIFIER')"
                        >
                     <span class="help-block invalid-feedback with-errors">
                         <ul class="list-unstyled">
                             <li class="err_identifier"></li>
                         </ul>
                     </span>
                  </div>
              <!--  <div class="d-flex pt-4">
                  <button type="submit" class="blue-btn ml-auto">Update</button>
               </div> -->
            </div>
         
     <div class="modal-footer">
      @if(auth()->user()->hasRole('super-admin'))
       <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-success">@lang('admin.TITLE_SAVE_BUTTON')</button>
        @else
        <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
      @endif
     </div>
     </form>
   </div>
   <!-- /.modal-content -->
 </div>
 <!-- /.modal-dialog -->
</div>