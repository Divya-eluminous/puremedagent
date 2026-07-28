// Submitting form after validation
$('#uploadApkForm').validator().on('submit', function (e) {
    if (!e.isDefaultPrevented()) {
        const $this = $(this);
        const action = $this.attr('action');
        const formData = new FormData($this[0]);

        $('.card-body').LoadingOverlay("show", {
            background: "rgba(165, 190, 100, 0.4)",
        });

        axios.post(action, formData)
            .then(function (response) {
                const resp = response.data;

                if (resp.status === 'success') {
                    toastr.success(resp.msg);
                    $('.card-body').LoadingOverlay("hide");
                    setTimeout(function () {
                        window.location.href = resp.url;
                    }, 2000);
                }

                if (resp.status === 'error') {
                    $('.card-body').LoadingOverlay("hide");
                    toastr.error(resp.msg);

                    const errorBag = resp.errors || {};

                    $.each(errorBag, function (fieldName, value) {
                        $('.err_' + fieldName).closest('.form-group').addClass('has-error has-danger');
                        $('.err_' + fieldName).text(value[0]).closest('span').show();
                    });
                }
            })
            .catch(function (error) {
                $('.card-body').LoadingOverlay("hide");

                // Check if error response and data exist before accessing
                const errorBag = error.response && error.response.data && error.response.data.errors;

                if (errorBag) {
                    $.each(errorBag, function (fieldName, value) {
                        $('.err_' + fieldName).closest('.form-group').addClass('has-error has-danger');
                        $('.err_' + fieldName).text(value[0]).closest('span').show();
                    });
                } else {
                    // Handle cases where errorBag is undefined or error.response is missing
                    toastr.error('An unexpected error occurred. Please try again later.');
                    console.error('Error:', error);  // Log the complete error object for debugging
                }
            });

        return false;
    }
});
