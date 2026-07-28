@extends('admin.layout.master')
@section('title')
    Appointment Cancellation
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12"></div>
        </div>
    </div>
</section>
@endsection
@section('scripts')
<script type="text/javascript">
    var encID = "{{$encID}}";
    var appointmentDetails = "{{$appointmentDetails}}";
    var returnUrl = "{{$returnUrl}}";
    if(appointmentDetails.length > 0)
    {
        swal({
            title: "{{ __('admin.TITLE_DELETE_SURE') }}",
            text: "{{$appointmentDetails}}",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "{{ __('admin.WARNING_TITLE') }}",
            cancelButtonText: "{{ __('admin.WARNING_TITLE_NO') }}",
            closeOnConfirm: false,
            closeOnCancel: false 
        },
        function(isConfirm) {
            if (isConfirm)
            {
                $('.showSweetAlert').LoadingOverlay("show", {
                    background: "rgba(165, 190, 100, 0.4)",
                });
                $.ajax({
                    type: "GET",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    url: "{{url('/confirmCancelAppointment')}}/"+encID,
                    data: '',
                    success: function (response) 
                    {
                            $('.showSweetAlert').LoadingOverlay("hide");

                        if(response == 'success')
                        {

                           // swal("{{ __('admin.TITLE_DELETE_BUTTON') }}!", "{{ __('admin.APPOINTMENT_TYPE_DELETED') }}", "success");
                           swal({
                                title: "{{ __('admin.TITLE_DELETE_BUTTON') }}!",
                                text: "{{ __('admin.APPOINTMENT_TYPE_DELETED') }}",
                                type: "success"
                            }, function() {
                                window.location.href = returnUrl;
                            });
                        }
                        else {
                            //swal("{{ __('admin.ERR_NOT_FOUND') }}", "{{ __('admin.ERR_SOMETHING_WRONG') }}.", "error");
                            //commented below code on 13-sept-23
                            /*swal({
                                title: "{{ __('admin.ERR_NOT_FOUND') }}!",
                                text: "{{ __('admin.ERR_SOMETHING_WRONG') }}",
                                type: "error"
                            }, function() {
                                window.location.href = returnUrl;
                            });*/
                            //Added below code on 13-sept-23
                            swal({
                               // title: "{{ __('admin.ERR_DISMISSAL_REQUIRED_NOT_EXIST') }}",
                                title: "{{ __('admin.APPOINTMENT_TYPE_DELETED') }}",    // Changed on 20-sept-23
                                text: "",                          // Changed on 20-sept-23
                                type: "success"
                            }, function() {
                                window.location.href = returnUrl;
                            });

                        }
                    }
                });
            }
            else {
                //swal("{{ __('admin.TITLE_CANCEL_BUTTON') }}", "{{ __('admin.APPOINTMENT_TYPE_NOT_DELETED') }}", "error");
                swal({
                    title: "{{ __('admin.TITLE_CANCEL_BUTTON') }}!",
                    text: "{{ __('admin.APPOINTMENT_TYPE_NOT_DELETED') }}",
                    type: "error"
                }, function() {
                    window.location = returnUrl;
                });
            }
        });
    }
    else {
        // swal(
        //     "{{ __('admin.ERR_DISMISSAL_REQUIRED_NOT_EXIST') }}",
        //     "{{ __('admin.APPOINTMENT_TYPE_DELETED') }}",
        //     "error"
        // );
        swal({
            title: "{{ __('admin.ERR_DISMISSAL_REQUIRED_NOT_EXIST') }}",
            text: "{{ __('admin.APPOINTMENT_TYPE_DELETED') }}",
            type: "error"
        }, function() {
            window.location.href = returnUrl;
        });
    }
</script>
<script type="text/javascript" src="{{ url('assets/admin/js/dashboard/index.js') }}"></script>
@endsection
