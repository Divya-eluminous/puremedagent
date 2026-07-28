
$('#birth_date').mask("99-99-9999");  

 $('#birth_date').datepicker({ 
        // changeMonth: true,
        // changeYear: true,
        dateFormat: 'dd-mm-yy',
        changeMonth: true,
        changeYear: true, 
        yearRange: '1920:+0',
        startDate: new Date('1920-01-01'),
        maxDate: 0
    });

 $('#userLogin').validator().on('submit', function (e) 
  {
      if (!e.isDefaultPrevented()) 
     {
        const $this    = $(this); 
        const action   = $this.attr('action');
        const formData = new FormData($this[0]); 
        // console.log(action);
        //  console.log(formData);
        // return false;
        $.LoadingOverlay("show", {
           background  : "rgba(165, 190, 100, 0)",
        });

        axios.post(action,formData)
        .then(function (response) 
        {
           const resp =  response.data;
           $.LoadingOverlay("hide");
           if (resp.status == 'success') 
           {
              // $this[0].reset();
              
              toastr.success(resp.msg);
              setTimeout(function()
              {
                  window.location.href = resp.url;

              }, 5000)
           }

           if (resp.status == 'error') 
           {
              toastr.error(resp.msg);
           }
        })
        .catch(function (error) 
        {
          
           $.LoadingOverlay("hide");

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
