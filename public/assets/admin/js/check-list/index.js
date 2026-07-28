$(document).ready(function () 
{
  var action = ADMINURL + '/check-list/getRecords';

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
                object.specialist_id = $('#specialist').val();
                object.custom = {
                    "check_list_name"   :  $('#check_list_name').val(),
                    "type_of_checklist" :  $('#type_of_checklist').val(),
                    "introduction_text" :  $('#introduction_text').val(),
                    "final_name"        :  $('#final_name').val(),
                    "status" : $('#exam_status').val(),
                }
            }
        },
    "columns": [
        { "data": "id", "visible": false },  
        { "data": "check_list_name" },
        { "data": "type_of_checklist" },
        { "data": "introduction_text" },
        { "data": "final_name" },
        { "data": "status" },
        { "data": "actions" } 
      ],

      "aoColumnDefs": [{ "bSortable": false, "aTargets": [0,4,5,6] }],
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

function deleteCollection(element,type) 
{
  var $this = $(element);
  var action = $this.attr('data-href');
 
  if (action != '') 
  {
    if(type == 1)
    {
      deleteExamimationContent = DELETE_WARNING_MSG;
    }
    else
    {
      deleteExamimationContent = deleteContent.text;
    }

    swal({
      title: deleteContent.title,
      text: deleteExamimationContent,
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