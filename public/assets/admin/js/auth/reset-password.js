$(document).ready(function()
{
   $('input[name="password"]').focus();

   $('#resetPasswordForm').validator().on('submit', function (e) 
   {
      if (!e.isDefaultPrevented()) 
      {
         $('.card').LoadingOverlay("show", {
            background  : "rgba(165, 190, 100, 0.2)",
         });

         const $this    = $(this); 
         const action   = $this.attr('action');
         const formData = new FormData($this[0]); 

         axios.post(action,formData)
         .then(function (response) 
         {
            const resp =  response.data;

            if (resp.status == 'success') 
            {
               $this[0].reset();
               toastr.success(resp.msg);
               $('.card').LoadingOverlay("hide");
               setTimeout(function()
               {
                  window.location.href = resp.url;

               }, 2000)
            }

            if (resp.status == 'error') 
            {
               $('.card').LoadingOverlay("hide");
               toastr.error(resp.msg);
            }
         })
         .catch(function (error) 
         {
            $('.card').LoadingOverlay("hide");

            const errorBag = error.response.data.errors;
            $.each(errorBag, function(fieldName, value) 
            {
               $('.err_'+fieldName).closest('div').addClass('has-error has-danger'); 
               $('.err_'+fieldName).text(value[0]).closest('span').show(); 
            })

         }); 

         return false;
      }
   })
})


/***************** Roshani added this for CR #229 *****************/
$(document).ready(function() {
    $("#show_hide_password a").on('click', function(event) {
        event.preventDefault();
        if($('#show_hide_password input').attr("type") == "text"){
            $('#show_hide_password input').attr('type', 'password');
            $('#show_hide_password i').addClass( "fa-eye-slash" );
            $('#show_hide_password i').removeClass( "fa-eye" );
        }else if($('#show_hide_password input').attr("type") == "password"){
            $('#show_hide_password input').attr('type', 'text');
            $('#show_hide_password i').removeClass( "fa-eye-slash" );
            $('#show_hide_password i').addClass( "fa-eye" );
        }
    });
});

$(document).ready(function() {
    $("#show_hide_password_confirm a").on('click', function(event) {
        event.preventDefault();
        if($('#show_hide_password_confirm input').attr("type") == "text"){
            $('#show_hide_password_confirm input').attr('type', 'password');
            $('#show_hide_password_confirm i').addClass( "fa-eye-slash" );
            $('#show_hide_password_confirm i').removeClass( "fa-eye" );
        }else if($('#show_hide_password_confirm input').attr("type") == "password"){
            $('#show_hide_password_confirm input').attr('type', 'text');
            $('#show_hide_password_confirm i').removeClass( "fa-eye-slash" );
            $('#show_hide_password_confirm i').addClass( "fa-eye" );
        }
    });
});
/***************** Roshani added this for CR #229 *****************/