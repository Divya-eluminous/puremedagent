$(document).ready(function () 
{
    // adding focus event to first field
    $('input[name="name"]').focus();
})


// submitting form after validation
$('#FindingServicesForm').validator().on('submit', function (e)  
{
    if (!e.isDefaultPrevented()) {
        const $this = $(this);
        const action = $this.attr('action');
        console.log(action);
        const formData = new FormData($this[0]);
        
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




