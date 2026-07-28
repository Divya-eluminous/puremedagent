$(document).ready(function ()  
{
  var action = ADMINURL + '/waiting-queue-number/getRecords';

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
                    "patient_id"    :         $('#patient_id').val(),
                    "doctor_id"     :         $('#doctor_id').val(),
                    "appointment_type_id"   : $('#appointment_type_id').val(),
                    "start_date"    :         $('#start_date').val(),
                    "appointment_status"    : $('#appointment_status').val(),
                    "queue_number"  :         $('#queue_number').val(), 
                }
            }
        },
      "columns": [
        { "data": "id", "visible": false }, 
        { "data": "patient_id" },
        { "data": "doctor_id" },
        { "data": "appointment_type_id" },
        { "data": "start_date" },
        { "data": "appointment_status" },
        { "data": "queue_number" },  
        { "data": "queue_number_type" },  
        { "data": "created_at" },  
        { "data": "actions" }
      ],

      "aoColumnDefs": [{ "bSortable": false, "aTargets": [0,9] }],
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
    setTimeout(function(){

          $('#listingTable').DataTable().draw();

      },60000);

  }).each(function () {
    setCustomPagingSigns.call($(this));
  });

  function setCustomPagingSigns() {
    $('#start_date').datepicker({
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

function updateCallStatus(element) 
{
  var $this = $(element);
  var action = $this.attr('data-href'); 
  var call_status = $this.attr('data-status'); 

  var call_status_title = 'Wollen sie die Wartenummer aufrufen ?'; 
  if(call_status==1){
     call_status_title = 'Wollen sie den Aufruf beenden?'; 
  }

  if (action != '') {
    swal({
      title: deleteContent.title,
      text: call_status_title,
      type: "warning",
      showCancelButton: true,
      cancelButtonText: deleteContent.cancel,
      confirmButtonText: 'Aufrufen',
      confirmButtonClass: "btn-danger",
      closeOnConfirm: false,
      showLoaderOnConfirm: true
    },
      function () {
        axios.post(action)
          .then(function (response) {
            if (response.data.status === 'success') {
                swal("Success", response.data.msg, 'success');
                
                setTimeout(function () {
                 swal.close();
                },1000);
              // toastr.success(response.data.msg);
              $('#listingTable').DataTable().ajax.reload();
            }

            if (response.data.status === 'error') {
                swal("Error", response.data.msg, 'error');
              
                setTimeout(function () {
                  swal.close();
                },1000);
              // toastr.error(response.data.msg);
              //swal("Error", response.data.msg, 'error');
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

function removeSearch(element)
{ 
  $('#queue_number').val('');
  $('#appointment_status').val('');
  $('#start_date').val('');
  $('#appointment_type_id').val('');
  $('#doctor_id').val('');
  $('#patient_id').val('');
  $('#listingTable').DataTable().draw();
}
 
// processing message in german
$.extend( $.fn.dataTable.defaults, {
    language: {
        "processing": "Verarbeitung..."
    },
});