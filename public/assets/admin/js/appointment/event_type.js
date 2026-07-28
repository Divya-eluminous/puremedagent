$(document).ready(function () 
{
  var action = ADMINURL + '/appointment/getEventTypeRecords';

  const table = $('#listingTable').DataTable(  
    {
      "responsive": true,   
      "processing": true, 
      "bFilter": true,
      "bInfo": true,
      "bLengthChange": true,   
      "serverSide": 'true', 
      "ajax": {
            "url": action, 
            "data": function (object)  
            {
                object.custom = {
                    "start_date"  :  $('#start_date').val(),
		    "event_date"  :  $('#event_date').val(),
                    "patient_id"  : $('#patient_id').val(),
                    "name" : $('#name').val(),
                    "service_type" : $('#service_type').val(),
                    "event_type" : $('#event_type').val(),
                    "status"   : $('#status').val(),
                    
                }
            }
        },
      "columns": [
        { "data": "id", "visible": false },
        { "data": "start_date" },
	{ "data": "event_date" },
        // { "data": "end_date" },
        { "data": "patient_id" },
        { "data": "name" },  
        { "data": "service_type" },
        { "data": "event_type" },
        { "data": "status" },
        
        // { "data": "actions" }
      ],

      "aoColumnDefs": [{ "bSortable": false, "aTargets": [0,7] }],
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
    $('#start_date').datepicker({
        format: 'yyyy-mm-dd',
    });
$('#event_date').datepicker({
        format: 'yyyy-mm-dd',
    });
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
//   $('#gany_patient_id').val(),
//   $('#status').val(),
//   $('#listingTable').DataTable().draw();
// }
 function addBooking(element)   
{ 
    console.log($(element).attr('data-href'));
    console.log($(element).attr('data-add'));

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


