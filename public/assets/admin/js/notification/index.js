$(document).ready(function ()  
{
  var action = ADMINURL + '/notification/getRecords'; 

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
                    "patient_id"  :  $('#patient_id').val(),
                    "notify_time"  :  $('#notify_time').val(),
                    "title" :  $('#title').val(),
                    "content" :  $('#content').val(),
                    "status"     :  $('#status').val(), 
                    // "ip"      :  $('#ip').val(),
                    // "agent"   :  $('#agent').val(),
                    // "name"    :  $('#name').val(),
                    // "created_at"    :  $('#created_at').val(),
                }
            }
        },
    "columns": [
        { "data": "id", "visible": false },   
        { "data": "patient_id" },
        { "data": "notify_time" },
        { "data": "title" },
        { "data": "content" },
        { "data": "status" },
        // { "data": "ip" },
        // { "data": "agent" },
        // // { "data": "user_id" },
        // { "data": "name" }, 
        // { "data": "created_at" }, 
        // { "data": "actions" }, 
      ], 

      "aoColumnDefs": [{ "bSortable": false, "aTargets": [0] }],
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



    // $(document).ready(function(){

    //     $("#myModal").on("show.bs.modal", function(e) {
    //         var id = $(e.relatedTarget).data('target-id');
    //         $.get( "/controller/" + id, function( data ) {
    //           console.log(data.html); return;
    //             $(".modal-body").html(data.html);
    //         });

    //     });
    // }); 

  $(document).on("click", "#sendnotification" , function() {
    var id = $(this).data('id');
    var url = $(this).data('url');
    // console.log(url); return; 
    $.ajax({
      url: url, 
      data: { id: id },
      type: 'GET',
      success: function(response){
        
         //start added on 10-nov-25
        if(response.status === "success") {
            toastr.success(response.message);
        } else {
            toastr.error(response.message);
        }
        //end added on 10-nov-25

        return true;
      }
    });
  });

     $(document).on('click', '#getDeatil', function(e){
        e.preventDefault();
        var url = $(this).data('url');  
        // console.log(url); return;
        $('.detail-modal').html(''); 
        // console.log('testttttttt'); return;
        $('#modal-loader').show();
        // console.log('testttttttt'); return;     
        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'html' 
        })
        .done(function(data){
            // console.log(data); return;    
            // $('.detail-modal').html('');    
            $('.detail-modal').html(data); // load response 
            $('activityLogDetail').show();     // hide ajax loader   
        })
        .fail(function(){
            $('#dynamic-content').html('<i class="glyphicon glyphicon-info-sign"></i> Something went wrong, Please try again...');
            $('#modal-loader').hide();
        });
    });

// processing message in german
$.extend( $.fn.dataTable.defaults, {
    language: {
        "processing": "Verarbeitung..."
    },
});