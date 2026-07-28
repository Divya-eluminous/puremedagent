$(document).ready(function ()  
{
  var action = ADMINURL + '/assistant-dashboard/getRecordsForWaitingList';
  $('.listingTable_filter').hide();
  // setInterval(function() 
  // {
    const table = $('#listingTable').DataTable(  
      {
        "responsive": true,   
        "processing": true, 
        "bFilter": false,
        "bInfo": true,
        "bLengthChange": false,   
        "serverSide": 'true', 
        "ajax": {
              "url": action, 
              "data": function (object)   
              {
                 
              }
          },
        "columns": [
          { "data": "id", "visible": false }, 
          { "data": "full_name" },
          { "data": "queue_number" },  
          { "data": "queue_number_type" },  
          { "data": "created_at" },  
          { "data": "actions" }
        ],

        "aoColumnDefs": [{ "bSortable": false, "aTargets": [2,4,5] }],
        "lengthMenu": [[20, 25, 50, 100], [20, 25, 50, 100]],
        "aaSorting": [[0, 'DESC']],
        "language": {
              "info": PAGE_SHOW+" _START_ "+PAGE_TO+" _END_ "+PAGE_OF+" _TOTAL_",
              "infoEmpty": PAGE_SHOW+" 0 "+PAGE_TO+" 0 "+PAGE_OF+" 0",
          },
          "oLanguage": {
            "sEmptyTable": "Aktuell keine Daten vorhanden",
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
          if($('.active').hasClass('waitingCls'))
          {
            console.log("pppp--->");
            $('#listingTable').DataTable().draw();
          }
        },6000);

    }).each(function () {
      setCustomPagingSigns.call($(this));
    });
  // }, 10000);  


  function setCustomPagingSigns() {
    $('#start_date').datepicker({
        dateFormat: 'yy-mm-dd',
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
 
// processing message in german
$.extend( $.fn.dataTable.defaults, {
    language: {
        "processing": "Verarbeitung..."
    },
});

