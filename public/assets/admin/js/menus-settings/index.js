$(document).ready(function ()    
{
  var action = ADMINURL + '/menus-settings/getRecords';

  const table = $('#listingTable').DataTable(  
    {
      "responsive": true,   
      "processing": true,
      "bFilter": true,
      "bInfo": true,
      "bLengthChange": true,   
      // "autoWidth": false,
      "serverSide": 'true',
      "ajax": {
            "url": action, 
            "data": function (object)  
            {
                object.custom = {
                    "name" :  $('#name').val(),
                    "url" : $('#url').val(),
                    "status" : $('#status').val(),
                }
            }
        },
      "columns": [
        { "data": "id", "visible": false },
        { "data": "name" },
        { "data": "url" },
        { "data": "status" },
        { "data": "actions" }
      ],

      "aoColumnDefs": [{ "bSortable": false, "aTargets": [0,4] }],
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

    // $('#birth_date').datepicker({
    //     format: 'yyyy-mm-dd',
    // });
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
 
// processing message in german
$.extend( $.fn.dataTable.defaults, {
    language: {
        "processing": "Verarbeitung..."
    },
});