$('#userForm').validator().on('submit', function (e) 
{
   if (!e.isDefaultPrevented()) 
   {
      const $this    = $(this); 
      const action   = $this.attr('action');
      const formData = new FormData($this[0]); 

      $($this).closest('.modal-content').LoadingOverlay("show", {
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
            $('#userListingTable').DataTable().ajax.reload();
            $($this).closest('.modal-content').LoadingOverlay("hide");

            setTimeout(function()
            {
               $('#addUser').modal('hide');
            }, 1000)
         }

         if (resp.status == 'error') 
         {
            $($this).closest('.modal-content').LoadingOverlay("hide");
            toastr.error(resp.msg);
         }
      })
      .catch(function (error) 
      {
         $('#submitButton').show();
         $($this).closest('.modal-content').LoadingOverlay("hide");

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


$('#updateUserPasswordForm').validator().on('submit', function (e) 
{
   if (!e.isDefaultPrevented()) 
   {
      const $this    = $(this); 
      const action   = $this.attr('action');
      const formData = new FormData($this[0]); 

      $($this).closest('.modal-content').LoadingOverlay("show", {
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
            $($this).closest('.modal-content').LoadingOverlay("hide");

            setTimeout(function()
            {
               $('#updateUserPassword').modal('hide');
            }, 1000)
            location.reload();
         }

         if (resp.status == 'error') 
         {
            $($this).closest('.modal-content').LoadingOverlay("hide");
            toastr.error(resp.msg);
         }
      })
      .catch(function (error) 
      {
         $($this).closest('.modal-content').LoadingOverlay("hide");

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

function updateLanguage(lang){
      
      // console.log(lang);

      const $this    = $(this); 
      const action   = ADMINURL+'/users/updateLanguage';
      // const formData = new FormData(); 
/*
      $($this).closest('.modal-content').LoadingOverlay("show", {
         background  : "rgba(165, 190, 100, 0.2)",
      });*/

      axios.post(action,{'lang':lang})
      .then(function (response) 
      {
         const resp =  response.data;

         if (resp.status == 'success') 
         {
           // $this[0].reset();
            toastr.success(resp.msg);

            setTimeout(function()
            {
               window.location.reload();
            }, 1000)
         }

         if (resp.status == 'error') 
         {
            //$($this).closest('.modal-content').LoadingOverlay("hide");
            toastr.error(resp.msg);
         }
      })
      .catch(function (error) 
      {
         //$($this).closest('.modal-content').LoadingOverlay("hide");

         const errorBag = error.response.data.errors;
         
      }); 

      return false;

}
