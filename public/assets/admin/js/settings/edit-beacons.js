 $(document).ready(function () { 
  
      // Denotes total number of rows 
      var rowIdx = 0; 
      var total_record = $("#total_count").val();
      // jQuery button click event to add a row 
      $('#addBtn').on('click', function () { 
  
        // Adding a row inside the tbody. 
        $('#tbody').append(`<tr id="R${++rowIdx}">
            <td class="row-index text-center form-group">  
             <input type="checkbox" name="b_status[]" value="1" class="form-check-input"/>      
             </td>   
              
             
             <td class="row-index text-center form-group">    
             <input type="text" name="identifier[]" value="" class="form-control" />
             <span class="help-block invalid-feedback with-errors">
                <ul class="list-unstyled">
                    <li class="err_identifier_`+total_record+`"></li>
                </ul>
             </span>          
             </td> 
              <td class="row-index text-center form-group">    
             <input type="text" name="uuid[]" value="" class="form-control" />
             <span class="help-block invalid-feedback with-errors">
                <ul class="list-unstyled">
                    <li class="err_uuid_`+total_record+`"></li>
                </ul>
             </span>          
             </td> 
              <td class="row-index text-center form-group">    
             <input type="text" name="minor[]" value="" class="form-control" />
             <span class="help-block invalid-feedback with-errors">
                <ul class="list-unstyled">
                    <li class="err_minor_`+total_record+`"></li>
                </ul>
             </span>          
             </td> 
              <td class="row-index text-center form-group">    
             <input type="text" name="major[]" value="" class="form-control" />
             <span class="help-block invalid-feedback with-errors">
                <ul class="list-unstyled">
                    <li class="err_major_`+total_record+`"></li>
                </ul>
             </span>          
             </td> 
              <td class="text-center"> 
                <button class="btn btn-danger remove" type="button"><span class="fas fa-trash"></span></button> 
                </td> 
              </tr>`); 
                total_record++;
      }); 
  
      // jQuery button click event to remove a row. 
      $('#tbody').on('click', '.remove', function () { 
  
        // Getting all the rows next to the row 
        // containing the clicked button 
        var child = $(this).closest('tr').nextAll(); 
  
        // Iterating across all the rows  
        // obtained to change the index 
        child.each(function () { 
  
          // Getting <tr> id. 
          var id = $(this).attr('id'); 
  
          // Getting the <p> inside the .row-index class. 
          var idx = $(this).children('.row-index').children('p'); 
  
          // Gets the row number from <tr> id. 
          var dig = parseInt(id.substring(1)); 
  
          // Modifying row index. 
          idx.html(`Row ${dig - 1}`); 
  
          // Modifying row id. 
          $(this).attr('id', `R${dig - 1}`); 
        }); 
  
        // Removing the current row. 
        $(this).closest('tr').remove(); 
  
        // Decreasing total number of rows by 1. 
        rowIdx--; 
      }); 


    // submitting form after validation
    $('#settingForm').validator().on('submit', function (e) 
    {
        if (!e.isDefaultPrevented()) {
            const $this = $(this);
            const action = $this.attr('action');
            const formData = new FormData($this[0]);            
            $('.card-body').LoadingOverlay("show", {
                background: "rgba(165, 190, 100, 0.4)",
            }); 
            axios.post(action, formData)
                .then(function (response) {
                    const resp = response.data;

                    if (resp.status == 'success') 
                    {
                        toastr.success(resp.msg);
                        $('.card-body').LoadingOverlay("hide");
                        setTimeout(function () {
                            window.location.href = resp.url;
                        }, 2000)
                    }
                    if (resp.status == 'error') {
                        $('.card-body').LoadingOverlay("hide");
                        toastr.error(resp.msg);
                        const errorBag = resp.errors;
                        $.each(errorBag, function (fieldName, value) {
                            $('.err_' + fieldName).closest('.form-group').addClass('has-error has-danger');
                            $('.err_' + fieldName).text(value[0]).closest('span').show();
                        })
                    }
                })
                .catch(function (error) {
                    $('.card-body').LoadingOverlay("hide");
                    const errorBag = error.response.data.errors;
                    $.each(errorBag, function (fieldName, value) { 
                        field_index = fieldName.split(".");
                        if(field_index.length > 1)
                        {
                            console.log(field_index );
                            var name = '.err_' + field_index[0]+'_'+field_index[1];
                            console.log(name);
                        }else
                        {
                            var name = fieldName;
                        }
                        $(name).closest('.form-group').addClass('has-error has-danger');
                        $(name).text(value[0]).closest('span').show();
                    })
                });
            return false;
        }
    })
});