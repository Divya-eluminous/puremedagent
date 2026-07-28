$(document).ready(function () 
{
    // adding focus event to first field
    $('input[name="name"]').focus();

    $('.duallistbox').bootstrapDualListbox({
        infoText:false,
        sortByInputOrder: 'false',
        moveOnSelect:true, 
        removeAllLabel: 'Remove all', 
    })

    if(flag == 1)
    {
        $('.buttons').find('.removeall').prop('disabled', 'disabled');
    }
    

})
// added by vijay 20/3/24 (ashish raised issue)
document.addEventListener("DOMContentLoaded", function () {
    var descriptionField = document.getElementById("description");
     descriptionField.addEventListener("input", function () {
        var descriptionValue = descriptionField.value;
        var descriptionRequiredError =
            "{{ __('admin.ERR_APPOINTMENT_TYPE_DESCRIPTION_REQUIRED') }}";
        if (/^\s*$/.test(descriptionValue)) {
            descriptionField.setCustomValidity(descriptionRequiredError);
        } else {
            descriptionField.setCustomValidity("");
        }
     });

     var nameField = document.getElementById("name");
     nameField.addEventListener("input", function () {
         var descriptionValue = nameField.value;
         var descriptionRequiredError =
             "{{ __('admin.ERR_APPOINTMENT_TYPE_NAME_REQUIRED') }}";
         if (/^\s*$/.test(descriptionValue)) {
             nameField.setCustomValidity(descriptionRequiredError);
         } else {
             nameField.setCustomValidity("");
         }
     });
 });
// removing data after deleting files
function removeFile(element) 
{
   
    $('.old_file').html('');
    $('.old_file').val('');
    $('.removefile').hide();
    $('#patient_document_status').val(0);
    //$(element).closest('.fileParentDiv').find('.file-upload-filename').html('No file Selected.');
    //$(element).closest('.fileParentDiv').find('.choosefile').show();
}

// showing names after uploading files
$('input[type="file"]').change(function (e) 
{
    $('#patient_document_status').val(1);
    /*var fileName = e.target.files[0].name;
    $(this).closest('.fileParentDiv').find('.file-upload-filename').html(fileName);
    $(this).closest('.fileParentDiv').find('.removefile').show();
    $(this).closest('.fileParentDiv').find('.choosefile').hide();*/
})

// submitting form after validation
$('#appointmentForm').validator().on('submit', function (e) 
{
    if (!e.isDefaultPrevented()) {
        const $this = $(this);
        const action = $this.attr("action");
        var selected_exam_list = [];
        // added by vijay
        var selected_non_exam_list = [];

        $("#bootstrap-duallistbox-selected-list_examinations option").each(
            function (key, selected) {
                selected_exam_list.push($(selected).val());
            }
        );

        // added by vijay
        $("#bootstrap-duallistbox-selected-list_non_examinations option").each(
            function (key, selected) {
                selected_non_exam_list.push($(selected).val());
            }
        );

        const formData = new FormData($this[0]);
        formData.append("selected_exam_list", selected_exam_list);
        // added by vijay
        formData.append("selected_non_exam_list", selected_non_exam_list);

        $(".card-body").LoadingOverlay("show", {
            background: "rgba(165, 190, 100, 0.4)",
        });

        axios
            .post(action, formData)
            .then(function (response) {
                const resp = response.data;

                if (resp.status == "success") {
                    // $this[0].reset();
                    toastr.success(resp.msg);
                    $(".card-body").LoadingOverlay("hide");
                    setTimeout(function () {
                        window.location.href = resp.url;
                    }, 2000);
                }

                if (resp.status == "error") {
                    $(".card-body").LoadingOverlay("hide");
                    toastr.error(resp.msg);

                    const errorBag = resp.errors;

                    $.each(errorBag, function (fieldName, value) {
                        $(".err_" + fieldName)
                            .closest(".form-group")
                            .addClass("has-error has-danger");
                        $(".err_" + fieldName)
                            .text(value[0])
                            .closest("span")
                            .show();
                    });
                }
            })
            .catch(function (error) {
                $(".card-body").LoadingOverlay("hide");

                const errorBag = error.response.data.errors;

                $.each(errorBag, function (fieldName, value) {
                    $(".err_" + fieldName)
                        .closest(".form-group")
                        .addClass("has-error has-danger");
                    $(".err_" + fieldName)
                        .text(value[0])
                        .closest("span")
                        .show();
                });
            });

        return false;
    }
})

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
           $('#specialist_id').val($(element).val());
          // session set   
        }
    });
}


