$(document).ready(function () 
{
    // adding focus event to first field
    $('input[name="name"]').focus();

    $('#date_of_last_activation').datepicker({ 
      format: 'dd-mm-yyyy',
      orientation: "bottom",  
      autoclose: true,  
      todayHighlight: true, 
      startDate: new Date(),  
      minDate: 0         
    });
})

$("#active_btn").click(function()
{
    $('#hd_flag').val('no');
    swal({
      title: deleteContent.title,
      text: last_date_confirmation,
      type: "warning",
      showCancelButton: true,
      cancelButtonText: deleteContent.cancel,
      confirmButtonText: warming_title,
      confirmButtonClass: "btn-danger",
      closeOnConfirm: true,
      showLoaderOnConfirm: true
    },
    function ()
    {
        $('#hd_flag').val('yes');
        $( ".date_of_last_activation_frm" ).val(current_date);
    });
    
});

// submitting form after validation
$('#checkListForm').validator().on('submit', function (e)  
{
    if (!e.isDefaultPrevented()) {
        const $this = $(this);
        const action = $this.attr('action');
        const formData = new FormData($this[0]);
        formData.append('introduction_text', editor.getData());
        formData.append('final_text', introduction.getData());
        
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

$("#btn_sub").click(function()
{  
    $('#err_frequency').hide();
    $('#err_frequency_type').hide();
    $('#err_date_of_last_activation_frm').hide();

    if($('#type_of_checklist').val() == 'general')
    {
        if($('#frequency').val()!='' &&  $('#frequency_type').val()!='' &&  $('.date_of_last_activation_frm').val()!='')
        {
            $('#btn_sub').submit();
        }
        else
        {
            if($('#frequency').val() =='')
            {
                $('#err_frequency').show();
                $('#err_frequency').focus();
            }
            if($('#frequency_type').val() =='')
            {
                $('#err_frequency_type').show();
                $('#err_frequency_type').focus();
            }
            if($('#date_of_last_activation').val() =='')
            {
                $('#err_date_of_last_activation').show();
                $('#date_of_last_activation').focus();
            }
        }
    }
    else
    {
         $('#btn_sub').submit();
    }
    
    
});


$('#addBtn').on('click', function () { 
    var counter = $(".wrapper").length;
    var cnt    = counter-1;
 
    var upload_area =`<br>
                    <div class="wrapper">
                        <div class="row">
                            <div class="col-sm-8"> 
                                <fieldset>
                                      <legend class="form-group">${heading_section} :
                                       <input 
                                        type="text" 
                                        name="heading_section[${counter}][heading_section][heading][]" 
                                        class="form-control"  
                                        required
                                        maxlength="250" 
                                        data-error="${err_heading}" />
                                        <span class="help-block invalid-feedback with-errors">
                                            <ul class="list-unstyled">
                                                <li class="err_heading_section"></li>
                                            </ul>
                                        </span>
                                      </legend>
                                       <div class="sub-wrapper-${counter}">
                                            <div class="row"> 
                                                <div class="form-group col-sm-8">
                                                    <label class="theme-blue"> 
                                                        ${question}<span class="required">*</span></label>
                                                    <input 
                                                        type="text" 
                                                        name="heading_section[${counter}][heading_section][question][]" 
                                                        class="form-control"  
                                                        required
                                                        maxlength="250" 
                                                        data-error="${err_question}" />
                                                        <span class="help-block invalid-feedback with-errors">
                                                            <ul class="list-unstyled">
                                                                <li class="err_heading_section"></li>
                                                            </ul>
                                                        </span>
                                                </div>
                                                <div class="col-sm-4" style="margin-top: 37px;">
                                                    <a onclick="AddQuetion(this,'${counter}')" class="action-icon" title="@lang('admin.TITLE_CHECKLIST_ADD_MORE')" ><button type="button" id="" class="btn btn-md btn-primary">+</button></a>
                                                    
                                                </div>
                                            </div>
                                        </div>
                                </fieldset>
                            </div>
                            <div class="col-sm-4">
                                <a onclick="headingSectionRemove(this,'${counter}',0,'')" class="action-icon" title="" ><button class="btn btn-danger remove" type="button"><span class="fas fa-trash"></span></button></a>
                            </div>
                        </div>
                    </div><br>`;

    $(upload_area).insertAfter($(".wrapper:last"));
});



function headingSectionRemove(element,basecnt,type,id='')
{ 
    swal({
      title: deleteContent.title,
      text: deleteContent.text,
      type: "warning",
      showCancelButton: true,
      cancelButtonText: deleteContent.cancel,
      confirmButtonText: deleteContent.confirm,
      confirmButtonClass: "btn-danger",
      closeOnConfirm: true,
      showLoaderOnConfirm: true
    },
    function ()
    {
        if(type == 1)
        {
              $.ajax({
                url: ADMINURL + "/check-list/check_list_delete",
                type: "POST",
                headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: 
                {
                    'id':id, 
                },
                async:false,
                success: function(responce)
                {
                   $(element).closest('.wrapper').find('*').attr('disabled', true);
                   $(element).closest('.wrapper').removeClass('d-flex');
                   $(element).closest('.wrapper').hide(); 
                }
            }); 
        }
        else
        {
            $(element).closest('.wrapper').find('*').attr('disabled', true);
            $(element).closest('.wrapper').removeClass('d-flex');
            $(element).closest('.wrapper').hide(); 
        }
        
    });
}

// Sub Section Add and Remove
function AddQuetion(element,basecnt)
{
    var counter = $(".sub-wrapper").length;
    var cnt    = counter+1;
    
    var upload_area =`<div class="sub-wrapper-${basecnt}">
                        <div class="row"> 
                            <div class="form-group col-sm-8">
                                <label class="theme-blue"> 
                                ${question} <span class="required">*</span></label>
                                <input 
                                    type="text" 
                                    name="heading_section[${basecnt}][heading_section][question][]" 
                                    class="form-control"  
                                    required
                                    maxlength="250" 
                                    data-error="${err_question}" 
                                >
                            </div>
                            <div class="col-sm-4" style="margin-top: 37px;">
                                <button onclick='javascript:return removeDivEdit(this,${basecnt},0,"")' class="btn btn-danger remove" type="button"><span class="fas fa-trash"></span></button>
                            </div>
                        </div>
                    </div>`;

    var sub_wp = "sub-wrapper-"+basecnt;   
    $(upload_area).insertAfter($("."+sub_wp+":last"));
}


function removeDivEdit(element,basecnt,type,id='')
{ 
    swal({
      title: deleteContent.title,
      text: deleteContent.text,
      type: "warning",
      showCancelButton: true,
      cancelButtonText: deleteContent.cancel,
      confirmButtonText: deleteContent.confirm,
      confirmButtonClass: "btn-danger",
      closeOnConfirm: true,
      showLoaderOnConfirm: true
    },
      function ()
      {
        if(type == 1)
        {
            $.ajax({
                url: ADMINURL + "/check-list/check_list_question_delete",
                type: "POST",
                headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: 
                {
                    'id':id, 
                },
                async:false,
                success: function(responce)
                {
                    $(element).closest('.sub-wrapper-'+basecnt).find('*').attr('disabled', true);
                    $(element).closest('.sub-wrapper-'+basecnt).removeClass('d-flex');
                    $(element).closest('.sub-wrapper-'+basecnt).hide(); 
                }
            }); 
        }
        else
        {
            $(element).closest('.sub-wrapper-'+basecnt).find('*').attr('disabled', true);
            $(element).closest('.sub-wrapper-'+basecnt).removeClass('d-flex');
            $(element).closest('.sub-wrapper-'+basecnt).hide(); 
        }
           
    });
}

function checkType(element)
{
    if($(element).val() == 'performance')
    {
        $('#div_general_type').hide();
        $('#frequency').hide();
        $('#frequency_type').hide();
        $('#date_of_last_activation').hide();
        $('#active_btn_div').hide();
    }
    else if($(element).val() == 'general')
    {
        $('#div_general_type').show();
        $('#frequency').show();
        $('#frequency_type').show();
        $('#date_of_last_activation').show();
        $('#active_btn_div').show();
    }
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
}

    
 // Aishwarya added code on 3-june-25
let isResetting = false;

$('#reset_btn').click(function () {
    isResetting = true;

    // Clear CKEditor contents
    if (CKEDITOR.instances['introduction_text']) {
        CKEDITOR.instances['introduction_text'].setData('');
    }
    if (CKEDITOR.instances['final_text']) {
        CKEDITOR.instances['final_text'].setData('');
    }

    // Remove error messages
    $('.err_introduction_text').text('');
    $('.err_final_text').text('');

    // Remove has-error class
    $('#introduction_text').closest('.form-group').removeClass('has-error');
    $('#final_text').closest('.form-group').removeClass('has-error');

    // Allow validation again after short delay
    setTimeout(function () {
        isResetting = false;
    }, 200); // Adjust timing if needed
});


// Aishwarya added code on 5-june-25 for CKEditor validation
function validateCKEditorField(fieldId, errorClass) {
    if (isResetting) return true; // Skip validation during reset

    const content = CKEDITOR.instances[fieldId].getData().replace(/<[^>]*>/g, '').trim();
    const formGroup = $('#' + fieldId).closest('.form-group');

    if (!content) {
        $('.' + errorClass).text($('#' + fieldId).data('error'));
        formGroup.addClass('has-error');
        return false;
    } else {
        $('.' + errorClass).text('');
        formGroup.removeClass('has-error');
        return true;
    }
}


function attachLiveValidation(fieldId, errorClass) {
    const editor = CKEDITOR.instances[fieldId];

    if (editor) {
        editor.on('change', function () {
            validateCKEditorField(fieldId, errorClass);
        });
        editor.on('key', function () {
            validateCKEditorField(fieldId, errorClass);
        });
    } else {
        // Wait and retry
        setTimeout(function () {
            attachLiveValidation(fieldId, errorClass);
        }, 100);
    }
}

// Button click validation
$('#btn_sub').on('click', function () {
    if (CKEDITOR.instances['introduction_text']) {
        CKEDITOR.instances['introduction_text'].updateElement();
    }
    if (CKEDITOR.instances['final_text']) {
        CKEDITOR.instances['final_text'].updateElement();
    }

    // Just run validations
    validateCKEditorField('introduction_text', 'err_introduction_text');
    validateCKEditorField('final_text', 'err_final_text');
});


// Start watching once DOM is ready
$(document).ready(function () {
    attachLiveValidation('introduction_text', 'err_introduction_text');
    attachLiveValidation('final_text', 'err_final_text');
});
