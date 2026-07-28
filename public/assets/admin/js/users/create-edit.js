$(document).ready(function () 
{
    // adding focus event to first field
    $('input[name="name"]').focus();
    //$('input[name="mobile_number"]').mask('999-999-9999');

     //color picker with addon
    /*$('.my-colorpicker2').colorpicker();
    $('.my-colorpicker2').on('colorpickerChange', function(event) {
      $('.my-colorpicker2 .fa-square').css('color', event.color.toString());
    });*/

    $("#google_color").change(function () {

        let color_code = $(this).find(':selected').data('code');
        $("#preview").children('span').css({
            'background-color': color_code  
        });  
    }); 

    // Edit User
    if(add_edit_page){
        $(".save_button").removeClass("disabled");
        // var roleName = $(".role").find("option:selected").attr('name').toLowerCase();
        var roleName = ($(this).find("option:selected").attr('name') || '').toLowerCase();
        // console.log(roleName); return;
        if(roleName == 'doctor')
          {    
            $("#colorId").css("display","block");
            $("#doctor_speciality_id").css("display","block");
           // $('#doctor').removeAttr("required");  
            $("#appointment_id_hide").css("display","block");
             // Show Emergency Doctor
            $("#isEmergencyDoctor").show();
            $("#ed_yes, #ed_no").prop("disabled", false);

            // Hide Status + DISABLE so it does not submit
            $("#isStatus").hide();
            $("#status").prop("disabled", true);
          } 
        else
          {
            $("#colorId").css("display","none"); 
            $("#doctor_speciality_id").css("display","none");
            //$("#doctor").prop('required',true);
            // Show Status
        $("#isStatus").show();
        $("#status").prop("disabled", false);

        // Hide Emergency Doctor + DISABLE so it does not submit
        $("#isEmergencyDoctor").hide();
        $("#ed_yes, #ed_no").prop("disabled", true);

          }

        // On selecting role as assistant, the doctor dropdown should display
        if(roleName == 'assistant')
          {    
            $("#doctorId").css("display","block");
            $('#google_color').removeAttr("required");
            $('#doctor_speciality').removeAttr("required");
          }
        else
          {
            $("#doctorId").css("display","none"); 
            $("#google_color").prop('required',true); 
            $("#doctor_speciality").prop('required',true);
          }

        if(roleName !== 'doctor' && roleName !== 'assistant')
          {    
            // console.log('test1234'); return;
            $('#google_color').removeAttr("required");
            $('#doctor_speciality').removeAttr("required");
          //  $('#doctor').removeAttr("required");  
          }  
    } 
    // On change role
    $(".role").on('change', function() { 
        var roleName = ($(this).find("option:selected").attr('name') || '').toLowerCase();
        // console.log(roleName); 
        // On selecting role as doctor, the color dropdown should display
        // if(roleName == 'doctor')
        //   {    
        //     $("#colorId").css("display","block");
        //     $("#doctor_speciality_id").css("display","block");
        //    // $('#doctor').removeAttr("required");  
        //   } 
        // else
        //   {
        //     $("#colorId").css("display","none"); 
        //     $("#doctor_speciality_id").css("display","none");
        //   //  $("#doctor").prop('required',true);
        //   }
          if(roleName == 'doctor')
          {    
            $("#colorId").css("display","block");
            $("#doctor_speciality_id").css("display","block");
            // $("#isEmergencyDoctor").css("display","block");
            // $("#isStatus").css("display","none"); 
           // $('#doctor').removeAttr("required");  
            // Show Emergency Doctor
        $("#isEmergencyDoctor").show();
        $("#ed_yes, #ed_no").prop("disabled", false);

        // Hide Status + DISABLE so it does not submit
        $("#isStatus").hide();
        $("#status").prop("disabled", true);
          } 
        else
          {
            $("#colorId").css("display","none"); 
            $("#doctor_speciality_id").css("display","none");
            // $("#isEmergencyDoctor").css("display","none");
            // $("#isStatus").css("display","block"); 
          // Show Status
        $("#isStatus").show();
        $("#status").prop("disabled", false);

        // Hide Emergency Doctor + DISABLE so it does not submit
        $("#isEmergencyDoctor").hide();
        $("#ed_yes, #ed_no").prop("disabled", true);

          //  $("#doctor").prop('required',true);
          }

        // On selecting role as assistant, the doctor dropdown should display
        if(roleName == 'assistant')
          {    
            $("#doctorId").css("display","block");
            $('#google_color').removeAttr("required");
            $('#doctor_speciality').removeAttr("required");
          }
        else
          {
            $("#doctorId").css("display","none"); 
            $("#google_color").prop('required',true); 
            $("#doctor_speciality").prop('required',true);
          }

        if(roleName !== 'doctor' && roleName !== 'assistant')
          {    
            // console.log('test1234'); return;
            $('#google_color').removeAttr("required");
            $('#doctor_speciality').removeAttr("required");
           // $('#doctor').removeAttr("required");  
          } 

    }); 
    // submitting form after validation
    $('#customerForm').validator().on('submit', function (e) 
    {
        if (!e.isDefaultPrevented()) {
            const $this = $(this);
            const action = $this.attr('action');
            const formData = new FormData($this[0]);

            //Roshani added this code 
            var selected_appointment_list = [];
            
            $('[name="appointments"]').each(function(key,selected){
            selected_appointment_list.push($(selected).val());
            });
            formData.append('selected_appointment_list',selected_appointment_list);
            //Roshani added this code
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
                    // ############# Roshani Added this code on 13-mar-24 ################# 

                    if (resp.status == 'custom_error_appointment_type') {
                     $('.card-body').LoadingOverlay("hide");
                     $('.err_appointments').closest('.form-group').addClass('has-error has-danger');
                     $('.err_appointments').text(resp.msg).closest('span').show();
                    
                    }
                    // ############# Roshani Added this code on 13-mar-24 ################# 
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
    $('#updateCustomerForm').validator().on('submit', function (e) 
    {
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


    //jquery crop image functionality

    var jcrop_flag = 1,
    trigger_click = 1;

    var jcrop_api, boundx, boundy;
    $(function(){

    // var p = $("#previewimage");
    $("body").on("change", ".image", function(evt){

        var file=evt.currentTarget.files[0];

        var imageReader = new FileReader();
        var image  = new Image();
        imageReader.readAsDataURL(document.querySelector(".image").files[0]);
        imageReader.onload = function (evt) {
            // p.attr('src', oFREvent.target.result).fadeIn();

            if((file.type == 'image/jpeg' || file.type == 'image/png' || file.type == 'image/gif') )
            {
                if(~~(file.size/1024) <4000)
                {
                    image.src = evt.target.result;
                    image.onload = function() 
                    {
                        
                        var w = this.width, h = this.height, t = file.type, n = file.name, file_size = ~~(file.size/1024);
                        if(w >= 150 && h >= 150)
                        {
                            j_crop();
                            boundx = w;
                            boundy = h;
                            $('#previewimage').attr('src',image.src);
                            jcrop_api.setImage(image.src); 
                            // $('#imgbase64').val(image.src);
                            $('#sideprofileimage').attr('src',image.src);
                            /*if(window.location.href.indexOf('profile') > -1)
                            {
                                $('#profilepicimg').attr('src',image.src);
                            }
                            $('#con_img').attr('src',image.src);
                            $('#default_img').val('0');*/
                            jcrop_api.setSelect([0,160, 160,0]);
                            jcrop_api.setOptions({allowSelect:false,minSize: [ 100,100 ]});
                            if(trigger_click==1){
                                trigger_click = 0;
                                $('.image').trigger('change');   

                            }
                            // $('#file_trriger').text('Change');
                            // $('#remove_image').show();
                        } else {
                            alert('Image must be greater than 150 X 150px');

                            /*bootbox.alert("Image must be greater than 150 X 150px", function(answer) {
                              $('#image_change').val('');
                            }); */
                            move_flag = 1;
                            set_one = 0; 
                            return false;
                        }
                        
                    }  
                } else {
                    alert('The maximum size for file upload is 4Mb.');
                    /*bootbox.alert("The maximum size for file upload is 4Mb.", function(answer) {
                      $('#image_change').val('');
                    });*/
                    move_flag = 1;
                    set_one = 0;
                    return false;
                }
                
            } else {
                alert('Please upload image only.');
               /* bootbox.alert("Please upload image only.", function(answer) {
                  $('#image_change').val('');
                });*/
                move_flag = 1;
                set_one = 0;
                return false;
            }


        };
    });


    });

    /* $('#previewimage').imgAreaSelect({
    onSelectEnd: function (img, selection) {
        $('input[name="x1"]').val(selection.x1);
        $('input[name="y1"]').val(selection.y1);
        $('input[name="w"]').val(selection.width);
        $('input[name="h"]').val(selection.height);            
    }
    });*/

    function j_crop()
    {
    // $('#defailt_profile_pic').hide();
    // $('.jcrop_data').show();
    var thumbnail;
    if(jcrop_flag == 1 )
    {
        jcrop_flag = 0;
        $('#previewimage').Jcrop(
        {
            boxWidth: 300,
            boxHeight: 300,
            allowSelect:false,      
            setSelect: [0,160, 160,  0],
            aspectRatio: 1,
            onSelect: function(c){
                updatePreview(c);
            }                
        },function(){
          // Use the API to get the real image size
          var bounds = this.getBounds();
          boundx = bounds[0];
          boundy = bounds[1];
          $('.jcrop-holder').addClass('jcrop_data');
          // Store the API in the jcrop_api variable
          jcrop_api = this;
          //thumbnail = this.initComponent('Thumbnailer', { width: 130, height: 130 });
          jcrop_api.setOptions({ minSize:[150, 150] }); 
          // Move the preview into the jcrop container for css positioning
        });
    }
    }

    var move_cursor = 0;
    function release(c)
    {

    if($(".jcrop-keymgr").length > 0)
    {
       $.uniform.restore(".jcrop-keymgr");
    }
    //console.log($('.tab_2_visible').is(':visible'));
    if( $('.tab_2_visible').is(':visible'))
    {
       // remove radio button unifom
        $('#tab_1_2').removeClass('tab_2_visible');
        $('#sideprofileimage').prop('src',$('#previewimage').prop('src'));
        $('#con_img').prop('src',$('#previewimage').prop('src'));   
        $('#imgbase64').val($('#previewimage').attr('src'));
        convertImgToBase64URL($('#imgbase64').val(), function(base64Img){
        $('#imgbase64').val(base64Img);
        });
    }

    }

    var set_one = 0;
    function updatePreview(c)
    {
    console.log('updatePreview');
    if(parseInt(c.w) > 0)
    {
    console.log('yes');
    /*if(move_flag == 1 && set_one == 0)
    {
        set_one = 1;
        $('#sideprofileimage').prop('src',$('#previewimage').prop('src'));   
        $('#imgbase64').val($('#previewimage').attr('src'));
        convertImgToBase64URL($('#imgbase64').val(), function(base64Img){
        $('#imgbase64').val(base64Img);
    });
    }*/
    $('#x').val(c.x);
    $('#y').val(c.y);
    $('#x2').val(c.x2);
    $('#y2').val(c.y2);
    $('#w').val(c.w);
    $('#h').val(c.h);
    /*var rx = xsize / c.w;
    var ry = ysize / c.h;
    $pimg.css({
      width: Math.round(rx * boundx) + 'px',
      height: Math.round(ry * boundy) + 'px',
      marginLeft: '-' + Math.round(rx * c.x) + 'px',
      marginTop: '-' + Math.round(ry * c.y) + 'px'
    });
    $('#con_img').css({
      width: Math.round(rx * boundx) + 'px',
      height: Math.round(ry * boundy) + 'px',
      marginLeft: '-' + Math.round(rx * c.x) + 'px',
      marginTop: '-' + Math.round(ry * c.y) + 'px'
    });*/
    }
    };

   


})


// ############# Roshani Added this code ################# 
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
// ############# Roshani Added this code ################# 


// ############# Roshani Added this code on 13-mar-24 ################# 
$("#role").change(function () {
    var roleName = ($(".role").find("option:selected").attr('name') || '').toLowerCase();
    if(roleName == 'doctor') {    
        $("#appointment_id_hide").css("display","block");
        refreshNonSelectedList();
    } else {
        $("#appointment_id_hide").css("display","none");
// ############# Roshani Added this code on 15-mar-24 ################# 
        var removeAllButtons = document.querySelectorAll('.removeall');
        for (var i = 0; i < removeAllButtons.length; i++) {
          removeAllButtons[i].click();
        }
// ############# Roshani Added this code on 15-mar-24 ################# 
    }
});

function refreshNonSelectedList() {
    $("#box1").html($("#box1").html());
}

// $(document).on('click', '.moveall', function() {
//     console.log("Button clicked");
//     $('.err_appointments').html('Need to keep at least one appointment type.');

// });


// ############# Roshani Added this code on 13-mar-24 ################# 


// handling country code select change
function handleCountrySelect(el) {
    var input = document.getElementById('format');
    if (el.value === 'other') {
        input.value = '';
        input.focus();
    } else {
        input.value = el.value;
    }
}




