$(document).ready(function ()  
{
  // console.log(patientId); return;
  var action = ADMINURL + '/getdocument/' + patientId;  
  // console.log(action); return;
  const table = $('#listingExamTable').DataTable(  
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
                    "id" :  $('#id').val(), 
                    "name" :  $('#name').val(),
                    "document_url" : $('#document_url').val(),
                    "type" : $('#type').val(),
                     "document_type" : $('#document_type').val(),
                    // "place" : $('#place').val(),
                    // "status" : $('#status').val(),
                }
            }
        }, 
      "columns": [
        { "data": "id", "visible": false },
        { "data": "name" }, 
        { "data": "document_url" },
        { "data": "download" },
        { "data": "type" },
        { "data": "document_status" }, 
        // { "data": "place" }, 
        // { "data": "status" },
        // { "data": "actions" }
      ],
      
      "aoColumnDefs": [{ "bSortable": false, "aTargets": [0,1,2,3,4] }],
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
  // console.log('action'); return;
  table.on("draw.dt", function (e) 
  {
    setCustomPagingSigns.call($(this));
  }).each(function () {
    setCustomPagingSigns.call($(this));
  });

  function setCustomPagingSigns() {

    $('#birth_date').datepicker({
        // changeMonth: true,
        // changeYear: true,
        format: 'yyyy-mm-dd',
    });
  }
});


function doSearch(element)
{
  $('#listingExamTable').DataTable().draw();
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
 


// processing message in german
$.extend( $.fn.dataTable.defaults, {
    language: {
        "processing": "Verarbeitung..."
    },
});
