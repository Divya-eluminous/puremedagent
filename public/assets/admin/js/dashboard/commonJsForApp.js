// ############## Roshani Added this code (22/02/2024) C) User settings ################ -->
$(document).ready(function(){
    // Initialize a variable to store the original list of doctors
    $('#doctor_id').change(function(){

        /**********29-may-24*****************************/
        var WebHiddenApptypeId =0;
        var hidenApptypeId = $("#hidenApptypeId").val();
        //alert(hidenApptypeId);
        if(hidenApptypeId){
            WebHiddenApptypeId = hidenApptypeId;
        }

        /*********29-may-24*********************************/

        var fromWeb = $('#hidden_web').val();
        var fieldValue = fromWeb === 'from_web' ? 'from_web' : '';

        // Get the select element
        var appointmentTypeSelect = $('#appointment_type_id');

        // Clear existing options and add the first line
        appointmentTypeSelect.empty().append($('<option>', {
            value: '',
            text: 'Termin-Typ wählen'
        }));

        var originalDoctorList = $('#appointment_type_id').html();
        selectedAppointmentIdHid = $('#appointment_type_id_hidden').val();

        $('.appointment-loader').LoadingOverlay("show", {
            background: "rgba(165, 190, 100, 0.4)",
        });

        // Reset the list of doctors to its original state
        $('#appointment_type_id').html(originalDoctorList);

        var doctorId = $(this).val();
        var hiddenFieldValue = $('#hidden_field_web').val();
        var url = hiddenFieldValue === 'yes' ? WEBURL : BASEURL;

        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: url + "/get-appointment-types-on-doctors",
            type: 'POST',
            data: {
                doctor_id: doctorId,
                from : fieldValue,
                WebHiddenApptypeId:WebHiddenApptypeId   //added on 29-may-24
            },
            success: function(response){
                var appointmentTypeIds = response;

                // Iterate through the response object and append options to the select element

                //commented below code on 10-june-24
                
                /*$.each(appointmentTypeIds, function(id, name) {
                    appointmentTypeSelect.append($('<option>', {
                        value: id,
                        text: name
                    }));
                });  */


                 //start changed below code on 10-june-24  
                var dropdown = $('#appointment_type_id');
                dropdown.empty();
                dropdown.append('<option value="">Termintyp wählen</option>');

                //commented below code on 18-june-24
                /*$.each(appointmentTypeIds, function(index, item) {
                    dropdown.append($('<option>', { 
                        value: item.id, 
                        text: item.name,
                        'data-optimal-appointment': item.optimal_appointment // Set the data attribute                        
                    }));
                });*/

                //end changed above code on 10-june-24     


                //Added below code on 18-june-24
                 $.each(appointmentTypeIds, function(index, item) {
                    var option = $('<option>', { 
                        value: item.id, 
                        text: item.name,
                        'data-optimal-appointment': item.optimal_appointment
                    });

                    if (item.id == WebHiddenApptypeId) {
                        option.attr('selected', 'selected'); // Set the selected attribute if IDs match


                          var patient_id = $("#hidden_patient_id").val();
                          var is_already_registered = $("#is_already_registered").val();

                           $.ajax({
                                 headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                },
                                url: url+"/online-appointment/getWebAppointmentStartDate",
                                type: "POST",
                                data: {
                                    "doctor_id": doctorId,                                  
                                    "patient_id": patient_id,
                                    "is_already_registered": is_already_registered,
                                    "appoinmant_type_id": WebHiddenApptypeId,
                                },
                                success: function(response) {
                                    console.log(response);
                                    if (response.count == 1) {
                                        $('.card-body').LoadingOverlay("hide");

                                        $("#doctor_duty_rosters").empty();
                                        $("#doctor_duty_rosters").html(" ");

                                        $(".chk").datepicker("destroy");
                                        $(".chk").val("");
                                        $('.chk').datepicker({
                                            dateFormat: 'dd/mm/yy',
                                            minDate: response.avaliable_date,
                                            setDate: response.avaliable_date
                                        });
                                        $(".chk").val(response.avaliable_date);

                                        $(".chk_enddate").datepicker("destroy");
                                        $(".chk_enddate").val("");
                                        $('.chk_enddate').datepicker({
                                            dateFormat: 'dd/mm/yy',
                                            minDate: response.end_date,
                                            setDate: response.end_date
                                        });
                                        $(".chk_enddate").val(response.end_date);


                                    } else {
                                        $('.card-body').LoadingOverlay("hide");

                                        $("#doctor_duty_rosters").empty();
                                        $("#doctor_duty_rosters").html(" ");
                                        /********commented on 30-aug-24*fordatepicker fromtodate not remove***/
                                        // $(".chk").datepicker("destroy");
                                        // $(".chk").val("");

                                        // $(".chk_enddate").datepicker("destroy");
                                        // $(".chk_enddate").val("");
                                        /*******commented on 30-aug-24*fordatepicker fromtodate not remove****/

                                        var msg = Doctor_No_Available;                                       
                                        // toastr.error(msg);
                                         $("#doctor_duty_rosters").html(`
                                            <table id="customers">
                                                <thead>
                                                    <tr>
                                                        <td colspan="3" style="text-align: center;">
                                                        ${msg}
                                                        </td>
                                                    </tr>
                                                </thead>
                                            </table>
                                        `);
                                        $('.ui-datepicker-calendar').css("display", "none");
                                    }
                                }
                            });



                    }//if both same id
                    dropdown.append(option);

                });
               //end changed above code on 18-june-24             



                // If selectedAppointmentIdHid is not empty and matches any option value, set it as selected
                // if (selectedAppointmentIdHid) {
                //     $('#appointment_type_id option[value="' + selectedAppointmentIdHid + '"]').prop('selected', true);
                // }

                $('.appointment-loader').LoadingOverlay("hide");
            },
            error: function(xhr, status, error) {
                // Handle error if needed
            }
        });
    });
});


// $(document).ready(function(){
//     // Initialize a variable to store the original list of doctors
//     var originalDoctorList = $('#doctor_id').html();

//     $('#appointment_type_id').change(function(){
//         selectedAppointmentId = $('#appointment_type_id').val();
//         if(selectedAppointmentId)
//         {
//             $('#appointment_type_id_hidden').val(selectedAppointmentId);
//         }
//       if ($('#doctor_id').val() == '') {
//         // ############## Roshani Added this code (12/03/2024) C) User settings ################ -->
//         // Reset the list of doctors to its original state
//         // $('#appointment_type_id').html(originalDoctorList).prop('disabled', true);
//         // ############## Roshani Added this code (12/03/2024) C) User settings ################ -->
//     $('.appointment-loader').LoadingOverlay("show", {
//          background: "rgba(165, 190, 100, 0.4)",
//       });
//        // Reset the list of doctors to its original state
//         $('#doctor_id').html(originalDoctorList);
//         var appointmentTypeId = $(this).val();
//         var hiddenFieldValue = $('#hidden_field_web').val();
//         var url = hiddenFieldValue === 'yes' ? WEBURL : BASEURL;

//         $.ajax({
//             headers: {
//                 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
//             },
//             url: url + "/get-doctors-on-appointment-types",
//             type: 'POST',
//             data: {
//                 appointmentTypeId: appointmentTypeId,
//             },
//             success: function(response){
//                 var doctorTypeIds = response;
//                 $('#doctor_id option').each(function(){
//                     var optionValue = $(this).val();
//                     if(optionValue && $.inArray(parseInt(optionValue), doctorTypeIds) !== -1) {
//                         $(this).remove();
//                     }
//                 });
//                 $('.appointment-loader').LoadingOverlay("hide");
//                 // ############## Roshani Added this code (12/03/2024) C) User settings ################ -->
//                 // Enable the select box after successful response
//             // $('#appointment_type_id').prop('disabled', false);
//                 // ############## Roshani Added this code (12/03/2024) C) User settings ################ -->
//             },
//             error: function(xhr, status, error) {
//             }
//         });
//       }
//     });
// });


$(document).ready(function(){
  // Initialize a variable to store the original list of doctors
    $('#doctor_idedit').change(function(){
        // Get the select element
        var appointmentTypeSelect = $('#appointment_type_idedit');

        $('#appointment_type_idedit').val('');
        // Clear existing options and add the first line
        appointmentTypeSelect.empty().append($('<option>', {
            value: '',
            text: 'Termin-Typ wählen'
        }));


        $('.appointment-loader').LoadingOverlay("show", {
         background: "rgba(165, 190, 100, 0.4)",
        });
        var originalDoctorList = $('#appointment_type_idedit').html();
        $('#appointment_type_idedit').html(originalDoctorList);
        var doctorId = $(this).val();
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: BASEURL + "/get-appointment-types-on-doctors",
            type: 'POST',
            data: {
                doctor_id: doctorId,
            },
            success: function(response){
                var appointmentTypeIds = response;
                // $('#appointment_type_idedit option').each(function(){
                //     var optionValue = $(this).val();
                //     if(optionValue && $.inArray(parseInt(optionValue), appointmentTypeIds) !== -1) {
                //         $(this).remove();
                //     }
                // });
                // Iterate through the response object and append options to the select element
                console.log(appointmentTypeIds);


                //commented below code on 10-june-24  
               /* $.each(appointmentTypeIds, function(id, name) {
                    appointmentTypeSelect.append($('<option>', {
                        value: id,
                        text: name
                    }));
                }); */

                //start changed below code on 10-june-24  
                var dropdown = $('#appointment_type_idedit');
                dropdown.empty();
                dropdown.append('<option value="">Termintyp wählen</option>');

                $.each(appointmentTypeIds, function(index, item) {
                    dropdown.append($('<option>', { 
                        value: item.id, 
                        text: item.name,
                        'data-optimal-appointment': item.optimal_appointment // Set the data attribute
                    }));
                });
               //end changed above code on 10-june-24      



                $('.appointment-loader').LoadingOverlay("hide");
            },
            error: function(xhr, status, error) {
            }
        });
    });
});

// $(document).ready(function(){
//     // Initialize a variable to store the original list of doctors
//     var originalDoctorList = $('#doctor_idedit').html();

//     $('#appointment_type_idedit').change(function(){

//       if ($('#doctor_idedit').val() == '') {
//         // ############## Roshani Added this code (12/03/2024) C) User settings ################ -->
//         // Reset the list of doctors to its original state
//         // $('#appointment_type_id').html(originalDoctorList).prop('disabled', true);
//         // ############## Roshani Added this code (12/03/2024) C) User settings ################ -->
//         $('.appointment-loader').LoadingOverlay("show", {
//          background: "rgba(165, 190, 100, 0.4)",
//       });
//        // Reset the list of doctors to its original state
//         $('#doctor_idedit').html(originalDoctorList);
//         var appointmentTypeId = $(this).val();
//         $.ajax({
//             headers: {
//                 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
//             },
//             url: BASEURL + "/get-doctors-on-appointment-types",
//             type: 'POST',
//             data: {
//                 appointmentTypeId: appointmentTypeId,
//             },
//             success: function(response){
//                 var doctorTypeIds = response;
//                 $('#doctor_idedit option').each(function(){
//                     var optionValue = $(this).val();
//                     if(optionValue && $.inArray(parseInt(optionValue), doctorTypeIds) !== -1) {
//                         $(this).remove();
//                     }
//                 });
//                 $('.appointment-loader').LoadingOverlay("hide");
//                 // ############## Roshani Added this code (12/03/2024) C) User settings ################ -->
//                 // Enable the select box after successful response
//             // $('#appointment_type_id').prop('disabled', false);
//                 // ############## Roshani Added this code (12/03/2024) C) User settings ################ -->
//             },
//             error: function(xhr, status, error) {
//             }
//         });
//       }
//     });
// });

// ############## Roshani Added this code (22/02/2024) C) User settings ################ -->

