$(document).ready(function () 
{

  var action = ADMINURL + '/notification-patient/getRecords';
  const table = $('#listingTable').DataTable(  
    {
      "responsive": true,   
      "processing": true,
      "bFilter": true,
      "bInfo": true,
      "bLengthChange": true,   
      // "autoWidth": false,
      //"pagingType": "full_numbers",
      "serverSide": 'true', 
      "ajax": {
            "url": action, 
            "data": function (object)  
            {
                object.custom = {
                    "fullname" :  $('#fullname').val(),
                    "email" : $('#email').val()
                  
                }
            }
        },
      "columns": [
        { "data": "id", "visible": false },
        { "data": "fullname" }, 
        { "data": "email" },
        { "data": "actions" }
      ],

      "aoColumnDefs": [{ "bSortable": false, "aTargets": [0,2,3] }],
      "lengthMenu": [[20, 25, 50, 100], [20, 25, 50, 100]],
      "aaSorting": [[0, 'DESC']],
      "language": {
            "info": PAGE_SHOW+" _START_ "+PAGE_TO+" _END_ "+PAGE_OF+" _TOTAL_",
            "infoEmpty": PAGE_SHOW+" 0 "+PAGE_TO+" 0 "+PAGE_OF+" 0",
        },
        "oLanguage": {
          "sLengthMenu": "Show _MENU_ Einträge",
          "sSearch": "Suche",
          "oPaginate": {
            "sPrevious": "Vorherige",
            "sNext": "Nächste"
          },
          // "sInfoEmpty": "Showing 0 to 0 of 0 records",
          "infoFiltered": "(gefiltert aus _MAX_ Einträgen)"
        }
     
    });

  table.on("draw.dt", function (e) 
  {
    setCustomPagingSigns.call($(this));
  }).each(function () {
    setCustomPagingSigns.call($(this));
  });

  function setCustomPagingSigns() {

     $('#birth_date').datepicker({  
        changeMonth: true,  
        changeYear: true, 
        dateFormat: 'yy-mm-dd', 
         yearRange: '1920:+0',  
        startDate: new Date('1920-01-01') 
    }); 
    $('#birth_date').mask("9999-99-99"); 
  }
});


function deleteCollection(element) 
{
  var $this = $(element);
  var action = $this.attr('data-href');

  if (action != '') {
    swal({
      title: deleteContent.title,
      text: deleteContent.text,
      type: "warning",
      showCancelButton: true,
      cancelButtonText: deleteContent.cancel,
      confirmButtonText: deleteContent.confirm,
      confirmButtonClass: "btn-danger",
      closeOnConfirm: false,
      showLoaderOnConfirm: true
    },
      function () {
        axios.delete(action)
          .then(function (response) {
            if (response.data.status === 'success') {
              swal("Success", response.data.msg, 'success');
              $('#listingTable').DataTable().ajax.reload();
            }

            if (response.data.status === 'error') {
              swal("Error", response.data.msg, 'error');
            }

          })
          .catch(function (error) {
            // swal("Error",error.response.data.msg,'error');
          });
      });
  }
}

function doSearch(element)
{
  $('#listingTable').DataTable().draw();
}

// function removeSearch(element)
// { 
//   $('#fullname').val(),
//   $('#email').val(),
//   $('#phone_no').val(),
//   $('#date_of_birth').val(),
//   $('#place').val(),
//   $('#status').val(),
//   $('#listingTable').DataTable().draw();
// }

function addPatient(element)   
{ 
    var action = $(element).attr('data-href'); 
    var addText = $(element).attr('data-add');
    $("#ImportPatientForm").attr('action',action);
    $("#importPatient .modal-title").text(addText);//$(".modal-title").text("Edit Role");
    $("#ImportPatientForm")[0].reset();
    $("#importPatient").modal("show"); 
}
 

$('#ImportPatientForm').validator().on('submit', function (e) {
    
      if (!e.isDefaultPrevented()) {
            const $this = $(this);
            const action = $this.attr('action');
            const formData = new FormData($this[0]);


            $($this).closest('.modal-content').LoadingOverlay("show", {
                  background: "rgba(165, 190, 100, 0.4)",
            });
            $('#submitButton').hide();

            axios.post(action, formData) 
                  .then(function (response) {
                        const resp = response.data;

                        if (resp.status == 'success') {
                              $this[0].reset();
                              $("#importPatient").modal("hide");
            
                              $($this).closest('.modal-content').LoadingOverlay("hide");
                              toastr.success(resp.msg);
                              $('#listingTable').DataTable().ajax.reload();
                        }
                        if (resp.status == 'error') {
                              toastr.error(resp.msg);
                        }
                  })
                  .catch(function (error) {
                        const errorBag = error.response.data.errors;
                        $($this).closest('.modal-content').LoadingOverlay("hide");
                        $.each(errorBag, function (fieldName, value) {
                              $('.err_' + fieldName).closest('div').addClass('has-error has-danger');
                              $('.err_' + fieldName).text(value[0]).closest('span').show();
                        })

                  });

            return false;
      }
})

  
// processing message in german
$.extend( $.fn.dataTable.defaults, {
    language: {
        "processing": "Verarbeitung..."
    },
});



function changeStatus(element)
{
  var $this = $(element);
  var id = $this.attr('data-id');
  var send_email = $this.attr('data-status');
  var action = ADMINURL + '/patients/changeEmailStatus'; //changeSMSStatus
  if (action != '') {
      $.ajax({
            url: action,
            type: "POST",
            headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
            data: 
            {
                'send_email':send_email, 
                'id':id, 
            },
            async:false,
            success: function(responce)
            {
              if (send_email === '1') 
              {
                swal("Yes", responce.msg, 'success');
                $('#listingTable').DataTable().ajax.reload();
              }
              if (send_email === '0') 
              {
                swal("No", responce.msg, 'error');
                $('#listingTable').DataTable().ajax.reload();
              }
                
            }
        });
    
  }
}

function changeSMSStatus(element)
{
  var $this = $(element);
  var id = $this.attr('data-id');
  var send_sms = $this.attr('data-status');
  var action = ADMINURL + '/patients/changeSMSStatus'; //changeSMSStatus
  if (action != '') {
      $.ajax({
            url: action,
            type: "POST",
            headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
            data: 
            {
                'send_sms':send_sms, 
                'id':id, 
            },
            async:false,
            success: function(responce)
            {
              if (send_sms === '1') 
              {
                swal("Yes", responce.msg, 'success');
                $('#listingTable').DataTable().ajax.reload();
              }
              if (send_sms === '0') 
              {
                swal("No", responce.msg, 'error');
                $('#listingTable').DataTable().ajax.reload();
              }
                
            }
        });
    
  }
}