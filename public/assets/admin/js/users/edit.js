$(document).ready(function()
{
   getPermissions();
})

$('#userEditForm').validator().on('submit', function (e) 
{
   if (!e.isDefaultPrevented()) 
   {
      const $this    = $(this); 
      const action   = $this.attr('action');
      const formData = new FormData($this[0]); 

      $('.box-body').LoadingOverlay("show", {
         background  : "rgba(165, 190, 100, 0.2)",
      });

      axios.post(action,formData)
      .then(function (response) 
      {
         const resp =  response.data;

         if (resp.status == 'success') 
         {
            $this[0].reset();
            toastr.success(resp.msg);
            $('.box-body').LoadingOverlay("hide");

            setTimeout(function()
            {
               window.location.href = ADMINURL+'/users';

            }, 2000)
         }

         if (resp.status == 'error') 
         {
            $('.box-body').LoadingOverlay("hide");
            toastr.error(resp.msg);
         }
      })
      .catch(function (error) 
      {
         $('.box-body').LoadingOverlay("hide");
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

function getPermissions(element)
{
   $this = $('#user-role');

   // remove all checkboex
   $('.checkbox-permissions').each( function(){
      $(this).prop( "checked", false);
      $(this).closest('.sub-menu').find("em").text('view');

   })

   // variables
   $id = $this.val(); 
   
   // formdata
   const formData = new FormData();
   formData.append('id', $id);

   // action 
   const action = ADMINURL+'/permissions/byRole';

   axios.post(action,formData)
   .then(response => {
      
      if(response.data.object.length !== 0)
      {
         $object = response.data.object;

         $.each($object, function(key, value)
         {
            var permission = value.permissions.name;
            var element = $('#permission-'+permission);
            $(element).prop( "checked", true);

            // set text 
            var text = '';
              if ($(element).closest('.sub-menu').find("em").text() === "edit") 
              {
                  text = 'view';
              } 
              else 
              {
                  text = 'edit';
              }

              $(element).closest('.sub-menu').find("em").text(text);

         })
      }

   })
   .catch(error => {
      console.log(error);
   })
}
