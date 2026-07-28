$(document).ready(function () 
{
  var action = ADMINURL + '/ordination/getRecords';

  const table = $('#listingTable').DataTable(
    {
      "responsive": true,  
      "processing": true,   
      "bFilter": true, 
      "bInfo": true,
      "bLengthChange": true,
      "serverSide": true,
      "ajax": {
            "url": action,
            "data": function (object) 
            {
                object.custom = {
                    "name"    :  $('#name').val(),
                    "logo" :  $('#logo').val(),
                    "status"    :  $('#status').val(),
                }
            }
        },
    "columns": [
        { "data": "id", "visible": false },  
        { "data": "name" }, 
        { "data": "link", "orderable":false},
        { "data": "logo" ,"orderable":false},
        { "data": "status" },
        { "data": "actions" ,"orderable":false}
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
    var wrapper = this.parent();

    // set global class
    wrapper.find('.dataTables_info').addClass('card-subtitle pb-0');

    // entries info class
    wrapper.find('tbody tr').addClass('inner-td');
  }
}); 

function deleteCollection(element) 
{
  var $this = $(element);
  var action = $this.attr('data-href');
 
  if (action != '') 
  {
    
    swal({
      title: deleteContent.title,
      text:  deleteContent.text,
      type: "warning",
      showCancelButton: true,
      cancelButtonText: deleteContent.cancel,
      confirmButtonText: deleteContent.confirm,
      confirmButtonClass: "btn-danger",
      closeOnConfirm: true,
      showLoaderOnConfirm: true
    },
      function () {
        axios.delete(action)
          .then(function (response) {
            if (response.data.status === 'success') {
              swal("Success", response.data.msg, 'success');
              $('#listingTable').DataTable().ajax.reload();
              // location.reload();
            }

            if (response.data.status === 'error') {
              swal("Error", response.data.msg, 'error');
              location.reload();
            }

          })
          .catch(function (error) { 
            // swal("Error",error.response.data.msg,'error');
          });
      });
  }
  else
  {

  }
}

function deleteCollectionNew(element) 
{
  var $this = $(element);
  var action = $this.attr('data-href');
 
  if (action != '') 
  {
    
    swal({
      title: deleteContent.title,
      text:  deleteContent.textnew,
      type: "warning",
      showCancelButton: true,
      cancelButtonText: deleteContent.cancel,
      confirmButtonText: deleteContent.confirm,
      confirmButtonClass: "btn-danger",
      closeOnConfirm: true,
      showLoaderOnConfirm: true
    },
      function () {
        axios.delete(action)
          .then(function (response) {
            if (response.data.status === 'success') {
              swal("Success", response.data.msg, 'success');
              $('#listingTable').DataTable().ajax.reload();
              // location.reload();
            }

            if (response.data.status === 'error') {
              swal("Error", response.data.msg, 'error');
              location.reload();
            }

          })
          .catch(function (error) { 
            // swal("Error",error.response.data.msg,'error');
          });
      });
  }
  else
  {

  }
}

function doSearch(element)
{
  $('#listingTable').DataTable().draw();
}

// function removeSearch(element)
// { 
//   $('#check_list_name').val(''),
//   $('#introduction_text').val(''),
//   $('#final_name').DataTable().draw();
// }

// processing message in german
$.extend( $.fn.dataTable.defaults, {
    language: {
        "processing": "Verarbeitung..."
    },
});