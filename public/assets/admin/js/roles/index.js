$(document).ready(function () {
    var action = ADMINURL + '/roles/getRecords';
    const table = $('#listingTable').DataTable(
        {
            "responsive": true, 
            "processing": true, 
            "bFilter": true,
            "bInfo": true,
            "bLengthChange": true,
            // "pagingType": "full_numbers",
            "serverSide": 'true',
            "ajax": {
                "url": action,
                "data": function (object) {
                    object.custom = {
                        "name": $('#name').val(),
                    }
                }
            },
            "columns": [
                { "data": "name" },
                { "data": "identifier" },
                { "data": "actions" }
            ],
            "aoColumnDefs": [{ "bSortable": false, "aTargets": [2] }],
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
            /*"language": {
                "processing": "Loading ...",
                "paginate":
                {
                    "first": `<a href="#" class="arrow hover-img"><span><img src="${BASEURL}/assets/admin/images/icons/left_arrow-Active.svg" alt=" view"></span></a>`,
                    "previous": `<a href="#" class="arrow hover-img"><span><img src="${BASEURL}/assets/admin/images/icons/left_double_arrow-Active.svg" alt=" view"></span></a>`,
                    "next": `<a href="#" class="arrow hover-img"><span><img src="${BASEURL}/assets/admin/images/icons/right_double_arrow-Active.svg" alt=" arrow"></span></a>`,
                    "last": `<a href="#" class="arrow hover-img"><span><img src="${BASEURL}/assets/admin/images/icons/right_arrow-Active.svg" alt=" arrow"></span></a>`
                }
            }*/
        });

    table.on("draw.dt", function (e) {
        setCustomPagingSigns.call($(this));
    }).each(function () {
        setCustomPagingSigns.call($(this));
    });

    function setCustomPagingSigns() {
        /*var wrapper = this.parent();

        // view informations
        wrapper.find('.dataTables_info').addClass('card-subtitle pb-0');

        // entries info class
        wrapper.find('tbody tr').addClass('inner-td');

        // for each tr td
        wrapper.find('tbody tr').each(function (index, element) {
            if (index != '0') {
                $(element).find('td:nth-child(6)').addClass('text-center');
            }
        })

        // $('.my-select').selectbox();
        // for search only
        wrapper.find('tbody tr').first().addClass('inner-td theme-bg-blue-light vertical-align-middle');
        wrapper.find('tbody tr').first().find('td').last().addClass('text-center');

        // pagination 
        if (wrapper.find("a.first").hasClass("disabled")) {
            wrapper.find("a.first").html(`<a href="#" class="arrow hover-img"><span><img src="${BASEURL}/assets/admin/images/icons/left_arrow.svg" alt=" view"></span></a>`);
        }

        if (wrapper.find("a.previous").hasClass("disabled")) {
            wrapper.find("a.previous").html(`<a href="#" class="arrow hover-img"><span><img src="${BASEURL}/assets/admin/images/icons/left_double_arrow.svg" alt=" view"></span></a>`);
        }

        if (wrapper.find("a.last").hasClass("disabled")) {
            wrapper.find("a.last").html(`<a href="#" class="arrow hover-img"><span><img src="${BASEURL}/assets/admin/images/icons/right_arrow.svg" alt=" view"></span></a>`);
        }

        if (wrapper.find("a.next").hasClass("disabled")) {
            wrapper.find("a.next").html(`<a href="#" class="arrow hover-img"><span><img src="${BASEURL}/assets/admin/images/icons/right_double_arrow.svg" alt=" view"></span></a>`);
        }*/
    }

});

function doSearch(element) {
    $('#listingTable').DataTable().draw();
}

function removeSearch(element)
{ 
  $('#name').val(''),
  $('#listingTable').DataTable().draw();
}

function addRole(element) 
{
    var action = $(element).attr('data-href');
    var addText = $(element).attr('data-add');
    $("#AddRoleForm").attr('action',action);
    $("#addRole .modal-title").text(addText);//$(".modal-title").text("Edit Role");
    $("#AddRoleForm")[0].reset();
    $("#role_name").prop( "disabled", false );
    $("#role_name").prop('required',true);
    $("#addRole").modal("show");
}

function editCollection(element)  
{ 
    var $this = $(element);
    var action = $this.attr('data-href');
    var role_name = $this.attr('role-name');
    var role_identifier = $this.attr('role-identifier');
    var editText = $(element).attr('data-edit'); 
    $("#AddRoleForm").attr('action',action);
    $("#addRole .modal-title").text(editText);
    $("#role_name").val(role_name); 
    $("#role_name").prop( "disabled", true );
    $("#role_name").removeAttr("required");
    $("#role_identifier").val(role_identifier);
    $("#addRole").modal("show");

    $("#role_name").blur();
    $("#role_identifier").blur();

    $("#addRole").validator();
    return false;    
}

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

$('#AddRoleForm').validator().on('submit', function (e) {
    
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
                              $("#addRole").modal("hide");
            
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
