$(document).ready(function () 
{
  var action = ADMINURL + '/examinations/getRecords';
  var sortOrderaction = ADMINURL + '/examinations/sortOrderaction';
  const table = $('#listingTable').DataTable(
    {
      "responsive": true,  
      "rowReorder":  {
        selector: 'td:first-child,tr:not(:first-child, :last-child) td:not(:first-child, :last-child)',
        dataSrc: 'sorting_order'
       },    
      "processing": true,   
      "bFilter": true, 
      "bInfo": true,
      "bLengthChange": true,
      "serverSide": true,
      "ajax": {
            "url": action,
            "data": function (object) 
            {
                object.specialist_id = $('#specialist').val();
                object.custom = {
                    "name" :  $('#exam_name').val(),
                    "status" : $('#exam_status').val(),
                }
            }
        },
    "columns": [
        { "data": "id", "visible": false },  
        // { "data": "start_date" },
        { "data": "sorting_order" },
        { "data": "name" },
        { "data": "url" },
        { "data": "status" },
        { "data": "actions" } 
      ],

      "aoColumnDefs": [{ "bSortable": false, "aTargets": [0,4,5] }],
      "lengthMenu": [[20, 25, 50, 100], [20, 25, 50, 100]],
      "aaSorting": [[1, 'DESC']],
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
table.on('row-reorder', function (e, details) { 
      if(details.length) {
          let rows = [];
          details.forEach(element => {
              rows.push({
                  id: table.row(element.node).data().id,
                  sorting_order: element.newData
              });
          });
          $.ajax({
              url: sortOrderaction,
              type: "POST",
              headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
              },
              data: { rows }
          }).done(function () { 
            table.ajax.reload() 
          });
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

//   $('#exam_name').val(''),
//   $('#exam_status').val(''),
//   $('#listingTable').DataTable().draw();
// }

// processing message in german
$.extend( $.fn.dataTable.defaults, {
    language: {
        "processing": "Verarbeitung..."
    },
});

function SetSession(element)
{
  $.ajax({
        url: ADMINURL + "/specialist/SetSession",
        type: "POST",
        data: {
            specialist_id:$('#specialist').val(),
        },
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(responce)
        {
          
          $('#listingTable').DataTable().draw();
          // session set   
        }
    });
}