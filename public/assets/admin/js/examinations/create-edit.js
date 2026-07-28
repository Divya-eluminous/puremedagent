$(document).ready(function () 
{
console.log($("#show_as_reminder").attr('checked'));
if(typeof $("#show_as_reminder").attr('checked') != "undefined")
    {
    $('#btn_reminder').show();
    }
    else{ $('#btn_reminder').hide(); }
    // adding focus event to first field
    $('input[name="name"]').focus();

    $(".age_to").blur(function()
    {
        if($(this).val() < $(".age_from").val())
        {
            $(this).val('');
          //  $(this).parent('div').addClass('has-error');
        }
    });

    // $('#Show_as_control').click(function()
    // {
    //     if($("#Show_as_control").prop('checked') == true)
    //     {
    //         if($("#status").prop('checked') != true)
    //         {
    //             $("#Show_as_control").prop('checked',false);
    //             toastr.error(warning_msg);
    //         }
    //         else
    //         {
    //             $('#btn_save').prop('disabled', false);
    //         }
    //     }
    //     else
    //     {
    //         $('#btn_save').prop('disabled', false);
    //     }
    // });

    // $('#status').click(function()
    // {
    //     if($("#Show_as_control").prop('checked') == true)
    //     {
    //         if($("#status").prop('checked') == false)
    //         {
    //             //$("#Show_as_control").prop('checked',false);
    //             $('#btn_save').prop('disabled', true);
    //             toastr.error(warning_msg);
    //         }
    //         else
    //         {
    //             $('#btn_save').prop('disabled', false);
    //         }
    //     }
    //     else
    //     {
    //         $('#btn_save').prop('disabled', false);
    //     }
    // });

    $('.setReminder').click(function()
    {
       if($("#show_as_reminder").prop('checked') == true)
       {
            $('#btn_reminder').show();
       }
       else
       {
            $("#Show_as_control").prop('checked',false); // condition added on 13-sept-23
            $('#btn_reminder').hide();
            //Below code is added on 21-sept-23
            if ($('#Show_as_control').prop('checked')) {
                // Checkbox is checked
                console.log('Checkbox is checked');
            } else {
                // Checkbox is unchecked
                console.log('Checkbox is unchecked');
            }
       }//else
    });
    if($('input[name="chkReminder"]:checked').val() == 'general')
    {
        $(".reminderSetting").removeClass('d-none');
        $(".age").addClass('d-none'); 
        $(".checkup").addClass('d-none');

    }else if($('input[name="chkReminder"]:checked').val() == 'age')
    {
         $(".reminderSetting").addClass('d-none');
        $(".age").removeClass('d-none');
         $(".checkup").addClass('d-none');

    }
    else{
        $(".reminderSetting").addClass('d-none');
        $(".age").addClass('d-none');
         $(".checkup").removeClass('d-none');
    }

    $(".chkReminder").click(function()
    {
        $(".reminderSetting").addClass('d-none');
        $("."+$(this).val()).removeClass('d-none');
    })
})


// removing data after deleting files
function removeFile(element) 
{
    // console.log('test');
    $('.old_file').html('');
    $('.old_file').val('');
    $('.removefile').hide();
    $('#document_status').val(0);
    //$(element).closest('.fileParentDiv').find('.file-upload-filename').html('No file Selected.');
    //$(element).closest('.fileParentDiv').find('.choosefile').show();
}

// showing names after uploading files
$('input[type="file"]').change(function (e) 
{
    $('#document_status').val(1);
    /*var fileName = e.target.files[0].name;
    $(this).closest('.fileParentDiv').find('.file-upload-filename').html(fileName);
    $(this).closest('.fileParentDiv').find('.removefile').show();
    $(this).closest('.fileParentDiv').find('.choosefile').hide();*/
})

// submitting form after validation
$('#examinationForm').validator().on('submit', function (e)  
{
    if (!e.isDefaultPrevented()) {

        const $this = $(this);
        const action = $this.attr('action');
        const formData = new FormData($this[0]);
         formData.append('description', editor.getData()); // Added on 25-sept-23
        
        $('.card-body').LoadingOverlay("show", {
            background: "rgba(165, 190, 100, 0.4)",
        });

        axios.post(action, formData)
            .then(function (response) {
                const resp = response.data;

                if (resp.status == 'success') {
                    // $this[0].reset();
                    toastr.success(resp.msg);
                    $('.card-body').LoadingOverlay("hide");
                    setTimeout(function () {
                        window.location.href = resp.url;
                    }, 2000)
                }

                if (resp.status == 'error') {
                    $('.card-body').LoadingOverlay("hide");
                    toastr.error(resp.msg);

                    const errorBag = resp.errors;

                    $.each(errorBag, function (fieldName, value) {
                        $('.err_' + fieldName).closest('.form-group').addClass('has-error has-danger');
                        $('.err_' + fieldName).text(value[0]).closest('span').show();
                    })
                }
            })
            .catch(function (error) {
                $('.card-body').LoadingOverlay("hide");

                const errorBag = error.response.data.errors;

                $.each(errorBag, function (fieldName, value) {
                    $('.err_' + fieldName).closest('.form-group').addClass('has-error has-danger');
                    $('.err_' + fieldName).text(value[0]).closest('span').show();
                })
            });

        return false;
    }
})

// submitting form after validation 
$('#updateExaminationForm').validator().on('submit', function (e) 
{
    if (!e.isDefaultPrevented()) {

        const $this = $(this);
        const action = $this.attr('action');
        const formData = new FormData($this[0]);
         formData.append('description', editor.getData());
        
        $('.card-body').LoadingOverlay("show", {
            background: "rgba(165, 190, 100, 0.4)",
        });

        axios.post(action, formData)
            .then(function (response) {
                const resp = response.data;

                if (resp.status == 'success') {
                    // $this[0].reset();
                    toastr.success(resp.msg);
                    $('.card-body').LoadingOverlay("hide");
                    setTimeout(function () {
                        window.location.href = resp.url;
                    }, 2000)
                }

                if (resp.status == 'error') {
                    $('.card-body').LoadingOverlay("hide");
                    toastr.error(resp.msg);

                    const errorBag = resp.errors;

                    $.each(errorBag, function (fieldName, value) {
                        $('.err_' + fieldName).closest('.form-group').addClass('has-error has-danger');
                        $('.err_' + fieldName).text(value[0]).closest('span').show();
                    })
                }
            })
            .catch(function (error) {
                $('.card-body').LoadingOverlay("hide");

                const errorBag = error.response.data.errors;

                $.each(errorBag, function (fieldName, value) {
                    $('.err_' + fieldName).closest('.form-group').addClass('has-error has-danger');
                    $('.err_' + fieldName).text(value[0]).closest('span').show();
                })
            });

        return false;
    }
})

$('#reminderForm').validator().on('submit', function (e)  
{
    if (!e.isDefaultPrevented()) {


        /*************added on 11-spet-24******************************/
        var general_period = $("#general_period").val();
        var general_period_frequency_type = $("#general_period_frequency_type").val();
        if(general_period>12 && general_period_frequency_type=="year"){
            toastr.error(year_of_control_reminder_msg);
            $('.card-body').LoadingOverlay("hide");            
            return false;
        }
      /**************added on 11-spet-24*******************************/

        const $this = $(this);
        const action = $this.attr('action');
        const formData = new FormData($this[0]);
         formData.append('description', editor.getData());
        
        $('.card-body').LoadingOverlay("show", {
            background: "rgba(165, 190, 100, 0.4)",
        });

        axios.post(action, formData)
            .then(function (response) {
                const resp = response.data;

                if (resp.status == 'success') {
                    // $this[0].reset();
                    toastr.success(resp.msg);
                    $('.card-body').LoadingOverlay("hide");
                    $("#addReminderModal").modal("hide");
                    $("#Show_as_control").prop("checked", false); // added on 13-sept-23
                }

                if (resp.status == 'error') {
                    $('.card-body').LoadingOverlay("hide");
                    toastr.error(resp.msg);

                    const errorBag = resp.errors;

                    $.each(errorBag, function (fieldName, value) {
                        $('.err_' + fieldName).closest('.form-group').addClass('has-error has-danger');
                        $('.err_' + fieldName).text(value[0]).closest('span').show();
                    })
                }
            })
            .catch(function (error) {
                $('.card-body').LoadingOverlay("hide");

                const errorBag = error.response.data.errors;

                $.each(errorBag, function (fieldName, value) {
                    $('.err_' + fieldName).closest('.form-group').addClass('has-error has-danger');
                    $('.err_' + fieldName).text(value[0]).closest('span').show();
                })
            });

        return false;
    }
});

$('#reminderAgeForm').validator().on('submit', function (e)  
{
    if (!e.isDefaultPrevented()) {

        /*************added on 11-spet-24******************************/
        var age_period_controls = $("#age_period_controls").val();
        var age_period_frequency_type = $("#age_period_frequency_type").val();
        if(age_period_controls>12 && age_period_frequency_type=="year"){
            toastr.error(year_of_control_reminder_msg);
            $('.card-body').LoadingOverlay("hide");            
            return false;
        }
         /*************added on 11-spet-24******************************/

        const $this = $(this);
        const action = $this.attr('action');
        const formData = new FormData($this[0]);
         formData.append('description', editor.getData());
        
        $('.card-body').LoadingOverlay("show", {
            background: "rgba(165, 190, 100, 0.4)",
        });

        axios.post(action, formData)
            .then(function (response) {
                const resp = response.data;

                if (resp.status == 'success') {
                    // $this[0].reset();
                    toastr.success(resp.msg);
                    $('.card-body').LoadingOverlay("hide");
                    $("#addReminderModal").modal("hide");
                    $("#Show_as_control").prop("checked", false); // added on 13-sept-23
                }

                if (resp.status == 'error') {
                    $('.card-body').LoadingOverlay("hide");
                    toastr.error(resp.msg);

                    const errorBag = resp.errors;

                    $.each(errorBag, function (fieldName, value) {
                        $('.err_' + fieldName).closest('.form-group').addClass('has-error has-danger');
                        $('.err_' + fieldName).text(value[0]).closest('span').show();
                    })
                }
            })
            .catch(function (error) {
                $('.card-body').LoadingOverlay("hide");

                const errorBag = error.response.data.errors;

                $.each(errorBag, function (fieldName, value) {
                    $('.err_' + fieldName).closest('.form-group').addClass('has-error has-danger');
                    $('.err_' + fieldName).text(value[0]).closest('span').show();
                })
            });

        return false;
    }
});


$('#reminderCheckupForm').validator().on('submit', function (e)  
{
    
    if (!e.isDefaultPrevented()) {

        /*************added on 11-spet-24******************************/
        var checkup_period_controls = $("#checkup_period_controls").val();
        var checkup_period_frequency_type = $("#checkup_period_frequency_type").val();
        if(checkup_period_controls>12 && checkup_period_frequency_type=="year"){
            toastr.error(year_of_control_reminder_msg);
            $('.card-body').LoadingOverlay("hide");            
            return false;
        }
        /*************added on 11-spet-24******************************/

        const $this = $(this);
        const action = $this.attr('action');
        const formData = new FormData($this[0]);
         formData.append('description', editor.getData());
        
        $('.card-body').LoadingOverlay("show", {
            background: "rgba(165, 190, 100, 0.4)",
        });

        axios.post(action, formData)
            .then(function (response) {
                const resp = response.data;

                if (resp.status == 'success') {
                    // $this[0].reset();
                    toastr.success(resp.msg);
                    $('.card-body').LoadingOverlay("hide");
                    $("#addReminderModal").modal("hide");
                    $("#Show_as_control").prop("checked", true); // added on 13-sept-23
                }

                if (resp.status == 'error') {
                    $('.card-body').LoadingOverlay("hide");
                    toastr.error(resp.msg);

                    const errorBag = resp.errors;

                    $.each(errorBag, function (fieldName, value) {
                        $('.err_' + fieldName).closest('.form-group').addClass('has-error has-danger');
                        $('.err_' + fieldName).text(value[0]).closest('span').show();
                    })
                }
            })
            .catch(function (error) {
                $('.card-body').LoadingOverlay("hide");

                const errorBag = error.response.data.errors;

                $.each(errorBag, function (fieldName, value) {
                    $('.err_' + fieldName).closest('.form-group').addClass('has-error has-danger');
                    $('.err_' + fieldName).text(value[0]).closest('span').show();
                })
            });

        return false;
    }
});


$(document).on('input','#document_name',function(){
    var image= $('#document_name').val();
    var ext = $('#document_name').val().split('.').pop().toLowerCase();
    var validate_image =  /^[^\d]+$/;
  
    if(image != ''){
        if($.inArray(ext, ['pdf','text','rtf']) == -1) 
        {
            $('#validate_document').html(Text); 
            $('#validate_document').css('color','red'); 
            $('#savebtn').addClass("disabled");
            return false; 
        }
        else
        {
            $('#validate_document').html("");
            $('#savebtn').removeClass("disabled");
            return true;
        }
    }
    else{
        $('#validate_image').html("");
        $('#savebtn').removeClass("disabled"); 
    }
});

// check list
function getAllCheckList(element)
{
    $.ajax({
        url: ADMINURL + "/examinations/getAllActivecheckList",
        type: "POST",
        data: {
            exam_id:$('#hd_exam_id').val()
        },
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(responce)
        {
            $('#check_label').show();
            $('#check_label_else').show();
            $('#checkListId').html(responce);
            $('#div_ul').show();
        }
    });
}


// Document list
function getAllDocumentList(element)
{
    $.ajax({
        url: ADMINURL + "/examinations/getAllActiveDocumentList",
        type: "POST",
        data: {
            exam_id:$('#hd_exam_id').val()
        },
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(responce)
        {
            console.log(responce);
            $('#document_label').show();
            $('#document_label_else').show();
            $('#documentId').html(responce);
            $('#div_doc').show();
        }
    });
}

function uncheck(id)
{
    var check_list_id = 'customCheckbox'+id;
    if($("#"+check_list_id).prop('checked') == false)
    {
        $('#'+check_list_id).prop('checked', false); // Unchecks it
    }
    else
    {
        $('#'+check_list_id).prop('checked', true); // checked it
    }
    
}

function uncheckDocument(id)
{
    var document_list_id = 'customDocumentbox'+id;
    if($("#"+document_list_id).prop('checked') == false)
    {
        $('#'+document_list_id).prop('checked', false); // Unchecks it
    }
    else
    {
        $('#'+document_list_id).prop('checked', true); // checked it
    }
    
}

function deleteChecklist(id)
{
    var check_list_id = 'check_id_'+id;
    var sp_id = 'sp_id_'+id;
    //console.log(check_list_id);
    $('#'+sp_id).html('');
    $('#'+check_list_id).remove();
}


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
}//




//Added on 22-sept-23 for service add form reminder popup function starts here

$('#createreminderForm').validator().on('submit', function (e)  
{
    if (!e.isDefaultPrevented()) {

        // const $this = $(this);
        // const action = $this.attr('action');
        // const formData = new FormData($this[0]);
        //  formData.append('description', editor.getData());


         /*************added on 11-spet-24******************************/
        var general_period = $("#general_period").val();
        var general_period_frequency_type = $("#general_period_frequency_type").val();
        if(general_period>12 && general_period_frequency_type=="year"){
            toastr.error(year_of_control_reminder_msg);
            $('.card-body').LoadingOverlay("hide");            
            return false;
        }
        /*************added on 11-spet-24******************************/
        
        $('.card-body').LoadingOverlay("show", {
            background: "rgba(165, 190, 100, 0.4)",
        });



        $("#hidden_chkReminder").val($("#chkReminder").val());
        $("#hidden_reminder_service").val($("#reminder_service").val());
        $("#hidden_general_period").val($("#general_period").val());
        $("#hidden_general_period_frequency_type").val($("#general_period_frequency_type").val());
        $("#hidden_general_new_frequency").val($("#general_new_frequency").val());
        $("#hidden_general_new_frequency_type").val($("#general_new_frequency_type").val());
        $("#hidden_general_first_frequency").val($("#general_first_frequency").val());
        $("#hidden_general_first_frequency_type").val($("#general_first_frequency_type").val());
        $("#hidden_general_time_interval").val($("#general_time_interval").val());
        $("#hidden_general_time_interval_frequency_type").val($("#general_time_interval_frequency_type").val());
        $("#hidden_general_number_of_interval").val($("#general_number_of_interval").val());
        $("#hidden_general_end_cycle").val($("#general_end_cycle").val());
        $("#hidden_general_end_cycle_frequency_type").val($("#general_end_cycle_frequency_type").val());

        if( $("#hidden_chkReminder").val() && $("#hidden_general_period").val() && $("#hidden_general_period_frequency_type").val()
             && $("#hidden_general_new_frequency").val()
             && $("#hidden_general_new_frequency_type").val() && $("#hidden_general_first_frequency").val() 
             && $("#hidden_general_first_frequency_type").val() && 
            $("#hidden_general_time_interval").val() && $("#hidden_general_time_interval_frequency_type").val() && 
            $("#hidden_general_number_of_interval").val() &&  $("#hidden_general_end_cycle").val() && 
            $("#hidden_general_end_cycle_frequency_type").val()
            )
       
        {
              toastr.success(reminderMsg);
              $("#Show_as_control").prop("checked", false); // added on 21-sept-23
              $('.card-body').LoadingOverlay("hide");
              $("#createReminderModal").modal("hide");
        }

        return false;
    }//if
});

$('#createreminderAgeForm').validator().on('submit', function (e)  
{
    if (!e.isDefaultPrevented()) {

        // const $this = $(this);
        // const action = $this.attr('action');
        // const formData = new FormData($this[0]);
        // formData.append('description', editor.getData());
        
        $('.card-body').LoadingOverlay("show", {
            background: "rgba(165, 190, 100, 0.4)",
        });

         /*************added on 11-spet-24******************************/
        var age_period_controls = $("#age_period_controls").val();
        var age_period_frequency_type = $("#age_period_frequency_type").val();
        if(age_period_controls>12 && age_period_frequency_type=="year"){
            toastr.error(year_of_control_reminder_msg);
            $('.card-body').LoadingOverlay("hide");            
            return false;
        }
        /*************added on 11-spet-24******************************/

        //Add values to hidden fields for age reminder 

        $("#hidden_chkReminder").val("age");
        $("#hidden_age_age_from").val($("#age_age_from").val());
        $("#hidden_age_age_to").val($("#age_age_to").val());
        $("#hidden_age_period_controls").val($("#age_period_controls").val());
        $("#hidden_age_period_frequency_type").val($("#age_period_frequency_type").val());
        $("#hidden_age_new_frequency").val($("#age_new_frequency").val());
        $("#hidden_age_new_frequency_type").val($("#age_new_frequency_type").val());
        $("#hidden_age_first_frequency").val($("#age_first_frequency").val());
        $("#hidden_age_first_frequency_type").val($("#age_first_frequency_type").val());
        $("#hidden_age_time_interval").val($("#age_time_interval").val());
        $("#hidden_age_time_interval_frequency_type").val($("#age_time_interval_frequency_type").val());
        $("#hidden_age_number_of_interval").val($("#age_number_of_interval").val());
        $("#hidden_age_end_cycle").val($("#age_end_cycle").val());
        $("#hidden_age_end_cycle_frequency_type").val($("#age_end_cycle_frequency_type").val());

        if($("#hidden_chkReminder").val() && $("#hidden_age_age_from").val() && $("#hidden_age_age_to").val() && 
           $("#hidden_age_period_controls").val() && $("#hidden_age_period_frequency_type").val() &&  $("#hidden_age_new_frequency").val() &&
           $("#hidden_age_new_frequency_type").val() &&  $("#hidden_age_first_frequency").val() &&
           $("#hidden_age_first_frequency_type").val() && $("#hidden_age_time_interval").val() && 
           $("#hidden_age_time_interval_frequency_type").val() && $("#hidden_age_number_of_interval").val() &&
           $("#hidden_age_end_cycle").val() && $("#hidden_age_end_cycle_frequency_type").val()
           )
        {  

            toastr.success(reminderMsg);
            $('.card-body').LoadingOverlay("hide");
            $("#createReminderModal").modal("hide");
            $("#Show_as_control").prop("checked", false); // added on 21-sept-23     
        }
  
        return false;
    }//if
});

$('#createreminderCheckupForm').validator().on('submit', function (e)  
{
   
    if (!e.isDefaultPrevented()) {

        // const $this = $(this);
        // const action = $this.attr('action');
        // const formData = new FormData($this[0]);
        //  formData.append('description', editor.getData());
        
        $('.card-body').LoadingOverlay("show", {
            background: "rgba(165, 190, 100, 0.4)",
        });

         /*************added on 11-spet-24******************************/
        var checkup_period_controls = $("#checkup_period_controls").val();
        var checkup_period_frequency_type = $("#checkup_period_frequency_type").val();
        if(checkup_period_controls>12 && checkup_period_frequency_type=="year"){
            toastr.error(year_of_control_reminder_msg);
            $('.card-body').LoadingOverlay("hide");            
            return false;
        }
        /*************added on 11-spet-24******************************/


        $("#hidden_chkReminder").val("checkup");
        $("#hidden_checkup_period_controls").val($("#checkup_period_controls").val());
        $("#hidden_checkup_period_frequency_type").val($("#checkup_period_frequency_type").val());
        $("#hidden_checkup_new_frequency").val($("#checkup_new_frequency").val());
        $("#hidden_checkup_new_frequency_type").val($("#checkup_new_frequency_type").val());
        $("#hidden_checkup_first_frequency").val($("#checkup_first_frequency").val());
        $("#hidden_checkup_first_frequency_type").val($("#checkup_first_frequency_type").val());
        $("#hidden_checkup_time_interval").val($("#checkup_time_interval").val());
        $("#hidden_checkup_time_interval_frequency_type").val($("#checkup_time_interval_frequency_type").val());
        $("#hidden_checkup_number_of_interval").val($("#checkup_number_of_interval").val());
        $("#hidden_checkup_end_cycle").val($("#checkup_end_cycle").val());
        $("#hidden_checkup_end_cycle_frequency_type").val($("#checkup_end_cycle_frequency_type").val());

        if($("#hidden_chkReminder").val() && $("#hidden_checkup_period_controls").val() && $("#hidden_checkup_period_frequency_type").val() 
            && $("#hidden_checkup_new_frequency").val() && $("#hidden_checkup_new_frequency_type").val() &&
            $("#hidden_checkup_first_frequency").val() && $("#hidden_checkup_first_frequency_type").val() && 
            $("#hidden_checkup_time_interval").val() && $("#hidden_checkup_time_interval_frequency_type").val() &&
            $("#hidden_checkup_number_of_interval").val() && $("#hidden_checkup_end_cycle").val() &&
            $("#hidden_checkup_end_cycle_frequency_type").val()
            ){  
            toastr.success(reminderMsg);
            $('.card-body').LoadingOverlay("hide");
            $("#createReminderModal").modal("hide");
            $("#Show_as_control").prop("checked", true); // added on 21-sept-23
        }
        return false;
    }//if
}); 

//Added on 22-sept-23 for service add form reminder popup function ends here
