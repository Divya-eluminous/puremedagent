$(document).ready(function () 
{
    
    // adding focus event to first field
    $('input[name="name"]').focus();
    $('.colorpicker').colorpicker();
    $('.textcolorpicker').colorpicker();

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

$(".textcolorpicker").on('blur', function(event)
{
    var text_color_code = $('.textcolorpicker').val();
    $('.textcolorpicker').val('');
    $('#background_color').val(text_color_code);
    $('.textsetColorCode').css('background-color', '"'+text_color_code+'"');
});


// submitting form after validation
$('#DocumentForm').validator().on('submit', function (e)  
{
    if (!e.isDefaultPrevented()) {
        const $this = $(this);
        const action = $this.attr('action');
        console.log(action);
        const formData = new FormData($this[0]);
        formData.append('html_text', editor.getData());
        
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




