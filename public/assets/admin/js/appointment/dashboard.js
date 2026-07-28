 
 var $showMe = 0;
 var dashboard_flag = 0;
 var setTimerId = '';
  $(document).ready(function()
  {   
      //console.log(dashboard_flag);
      var baseurl = window.location.href;
      var id = baseurl.substring(baseurl.lastIndexOf('?') + 1);
      var id = parseInt(id);
      if(Math.floor(id) == id && $.isNumeric(id)) 
      {
        patient_id = id;
      }
     
      var searchKey = $("#doctor_id").val();
      if(dashboard_flag == 0)
      {
        // console.log('1- '+searchKey);
        doctor_dashboard(searchKey,patient_id);
      }
    

    $("#calendar").on("click",".dismissalButton",function()
    {
      var $button = $(this);
      $appoitment_id = $(this).attr('lang');
      $parent_div =  $(this).parent('div').parent('div').parent('div').attr('class');
      $patient_id = $parent_div.split('_');

      swal({  
      title: '',   
      text: dismissContent_title,
      type: "warning",
      showCancelButton: true,
      cancelButtonText: dismissContent_no,
      confirmButtonText: dismissContent_yes,
      confirmButtonClass: "btn-primary",
      closeOnConfirm: true,
      showLoaderOnConfirm: true
      },
      function () {
           $.ajax({
              type: "GET",
              url: ADMINURL + "/updateAppoitmentStatus",
              data: 'appoitment_id=' + $appoitment_id, 
              success: function (response) { 
                $button
                    .closest(".card")
                    .find('[data-card-widget="collapse"]')
                    .trigger("click");
                $("."+$patient_id[0]+"_main_div").remove();
                // console.log(searchKey);
                // var searchKey = $("#doctor_id").val();   
                setTimeout(function(){
                  if(dashboard_flag>0)
                  {
                    dashboard_flag = dashboard_flag - 1;
                  }
                  var searchKey = $("#doctor_id").val();
                  doctor_dashboard(searchKey,false);
                },1000);
              }
            });
         
      });     
    });

    $("#calendar").on("click",".reminderAction",function()
    {
      $(this).parent('p').next('form').toggle();
    });
    
    $("#calendar").on("click",".update_reminder",function()
    {
      const $this       = $(this).closest("form");
      var patinet_id    = $(this).attr('reminder-p-id');
      var appoitment_id = $(this).attr('reminder-a-id');
      const action      = $("#action_url").val();
      //console.log("-->"+action);
      const formData = new FormData($this[0]);

      axios.post(action, formData)
        .then(function (response) {
          const resp = response.data;

          if (resp.status == 'success') {
            // $this[0].reset();
            toastr.success(resp.msg);
            //window.location.reload(); reminderForm
            
            var searchKey = $("#doctor_id").val();
            services_dashboard(searchKey,patinet_id,appoitment_id); 
          }

        })
        $('.reminderAction').parent('p').next('form').hide();
    });

    $("#btn-update-permission-submit").click(function()
    {
      $("#frm_send_doc").submit();
      $('#btn-update-permission-submit').prop('disabled', true);
    });

    $("#calendar").on("click",".card-header",function()
    {     

      if($(this).closest('.card').hasClass('collapsed-card'))
      {
        if($(this).find(".highlight").length ==0)
        {
            dashboard_flag = dashboard_flag +1;

            //console.log("---->");
            //console.log(attr('lang')+'_Reminder');

            $(this).find(".notification_class").first().addClass('highlight');  
            clearTimeout(setTimerId);//Clear setTimeout
            //console.log('clear =>'+setTimerId);
         
          $("."+$(this).find(".notification_class").first().attr('lang')+'_Examination').css('display','block');
          $("."+$(this).find(".notification_class").first().attr('lang')+'_Reminder').css('display','block');
        }        
      }else 
      {      
        $(this).closest('.card').find(".notification_class").removeClass('highlight');
        if(dashboard_flag>0)
        {
          dashboard_flag = dashboard_flag - 1;
        }

        $(this).closest('.card').children(".card-body").css('display','none');
        if($(".highlight").length ==0 && dashboard_flag ==0)
        {

            // setTimerId = setTimeout(function() {
                var searchKey = $("#doctor_id").val();
                if(dashboard_flag < 1)
                {
                  clearTimeout(setTimerId);//Clear setTimeout
                  // console.log('3- '+searchKey);
                  doctor_dashboard(searchKey,false);
                }
            // },30000);
        }        
      }
    });

    $("#calendar").on("click",".editAppointmentModal",function()
    {
      const google_event_id = $(this).attr('data-id');
      const action = ADMINURL + "/dashboard/edit/" + google_event_id;
      axios.get(action)
      .then(response => {
        const resp = response.data;
        $("#frmAppointmentEdit").html(resp);
        $("#suggesstion_edit_patient_id").keyup(function () {
    
          var searchKey = $(this).val();
          if(searchKey==''){
            $("#suggesstion-box-edit-patient").empty();
          }else{
            $.ajax({
              type: "GET",
              url: ADMINURL + "/dashboard/patients",
              data: 'keyword=' + searchKey+'&popup=1&edit=1', 
              success: function (response) {

                var len = 0;
                if (response['data'] != null) {
                  len = response['data'].length;
                  var data = response['data'];
                }
                if (len > 0) {
                  for (var i = 0; i < len; i++) {
                    var patient_name = response['data'][i].first_name;
                    var lname = response['data'][i].family_name;

                    if (lname != null) {
                      patient_name += "-" + lname;
                    }
                  }
                  $("#suggesstion-box-edit-patient").show();
                  $("#suggesstion-box-edit-patient").html(response['data']);
                  $("#suggesstion_edit_patient_id").css("background", "#FFF");

                  document.getElementById("suggesstion_edit_patient_id").addEventListener("search", function(event) {
                    $("#suggesstion-box-edit-patient").empty();
                  });
                }else{
                   $("#suggesstion-box-edit-patient").empty();
                }
              }
            });
          }
        });
      })
      .catch(error => {
      })
    });

    $("#calendar").on("click",".editAppointmentTypeModal",function()
    {
      const type_id = $(this).attr('data-id');
      const action = ADMINURL + "/appoitment/type/"+ type_id;
      axios.get(action)
      .then(response => {

        //console.log(response);
        const resp = response.data;
        $("#frmAppointmentTypeEdit").html(resp);
      })
      .catch(error => {
      })
    });
    
    $("#calendar").on("click",".redirectToPatient",function()
    {
        const google_event_id = $(this).attr('data-id');

        const action = ADMINURL + "/dashboard/redirect/" + google_event_id;
        axios.post(action)
        .then(response => {
          const resp = response.data;
          if (resp.status == 'success') {
            if(resp.url!=''){
            window.location.href = resp.url;
            }
          }
          if (resp.status == 'error') {
            toastr.error(resp.msg);
          }
        })
        .catch(error => {
        })
    });
    
    $("#calendar").on("click",".updateDocumentStatus",function()
    {
      var doc_status = 0;
      if($(this).prop('checked') == true)
      {       
         doc_status = $(this).attr('lang');
      }else
      {
        if($(this).attr('lang') == 2)
         doc_status = 1;
      }
     
      var doc_id = $(this).val();
      swal({
      title: deleteContent.title,
      text: deleteContent.other_text,
      type: "warning",
      showCancelButton: true,
      cancelButtonText: deleteContent.cancel,
      confirmButtonText: deleteContent.other_confirm,
      confirmButtonClass: "btn-primary",
      closeOnConfirm: true,
      showLoaderOnConfirm: true
      },
      function () {
          $.ajax({
          type: "POST",
          headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          url: ADMINURL + "/appoitment/updateDocumentStatus",
          data: 'doc_status=' + doc_status+'&doc_id='+doc_id,
          success: function (response) {
           
            if (response.status === 'success') {
              swal("Success", response.msg, 'success');
              window.location.reload();
            }

            if (response.status === 'error') {
              swal("Error", response.msg, 'error');
            }
          }          
          });
         
      });
    });

   $("#calendar").on("click",".updateChecklistStatus",function()
    {
      var checklist_status = 0;
      if($(this).prop('checked') == true)
      {       
         checklist_status = $(this).attr('lang');
      }else
      {
        if($(this).attr('lang') == 1)
         checklist_status = 1;
      }
     
      var exam_id = $(this).val();
      var a_id    = $(this).attr('lang-a-id');
      var p_id    = $(this).attr('lang-p-id');
      var chkg_id = $(this).attr('lang-chk-id'); 
      var type = $(this).attr('lang-type');

      swal({
      title: deleteContent.title,
      text: deleteContent.other_text,
      type: "warning",
      showCancelButton: true,
      cancelButtonText: deleteContent.cancel,
      confirmButtonText: deleteContent.other_confirm,
      confirmButtonClass: "btn-primary",
      closeOnConfirm: true,
      showLoaderOnConfirm: true
      },
      function () {
          $.ajax({
          type: "POST",
          headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          url: ADMINURL + "/appoitment/updateChecklistStatus",
          data: 'checklist_status=' + checklist_status+'&exam_id='+exam_id+'&a_id='+a_id+'&p_id='+p_id+'&chkg_id='+chkg_id+'&type='+type,
          success: function (response) {
           
            if (response.status === 'success') {
              swal("Success", response.msg, 'success');
              window.location.reload();
            }

            if (response.status === 'error') {
              swal("Error", response.msg, 'error');
            }
          }          
          });
         
      });
    });

    $("#calendar").on("click",".updatePrintStatus",function()
    {
      var id = $(this).attr('lang');
      var type = $(this).attr('lang-type');
      var a_id = $(this).attr('lang-a-id');
      var p_id = $(this).attr('lang-p-id');
      var exam_id = $(this).attr('lang-exam');
      
   
      $.ajax({
          type: "POST",
          headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          url: ADMINURL + "/appoitment/updatePrintStatus",
          data: 'id=' + id+'&type='+type+'&a_id='+a_id+'&p_id='+p_id+'&exam_id='+exam_id,
          success: function (response) {
           
            // if (response.status === 'success') {
            //   swal("Success", response.msg, 'success');
            //   window.location.reload();
            // }

            // if (response.status === 'error') {
            //   swal("Error", response.msg, 'error');
            // }
          }          
      });
    });
    
    $("#calendar").on("click",".exportFindings",function()
    {
      var finding_array =new Array();
      var document_array =new Array();
      var checklist_array =new Array();
      var p_id = $(this).attr('id');
      var a_id = $(this).attr('lang');
      $( ".export_status_finding"+$(this).attr('lang')).each(function( index ) {
       
        if(this.checked == true){
          finding_array.push($(this).val());
        }       
      });

      $( ".export_status_document"+$(this).attr('lang')).each(function( index ) {
       
        if(this.checked == true){
          document_array.push($(this).val());
        }       
      });


      $( ".export_status_checklist"+$(this).attr('lang')).each(function( index ) {
       
        if(this.checked == true){
          checklist_array.push($(this).val());
        }       
      });
      // console.log("---->");
      // console.log($(this).attr('id'));
      $.ajax({
        url: $(".export_url").val(),
        type: "post", 
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
        data: "findings="+finding_array+"&document="+document_array+"&checklist="+checklist_array+'&appoitment_id='+$(this).attr('lang')+'&patient_id='+$(this).attr('id'),    
        success: function (response) {
          //console.log(response);
          if(response.status == 'error')
          {
            toastr.error(response.msg);  
          }
          else
          {
            // =========================================
            //var orgurl = "https://puregyn.puremed.biz/storage/tenancy/tenants/9b15a68114c94800aa29355b4d3c9944//diagnostic_findings/20210627083922-file1.jpg";
            if(response.arr_donload.length>0)
            {
              var i = 0
              for(i=0 ;i<=response.arr_donload.length;i++)
              {
                var aTag = '<a href="'+response.arr_donload[i]+'" target="_blank" download class="link downloadFinding">STD-Screening</a>';
                $('#donload_files').html(aTag);
                $('.downloadFinding').get(0).click();
                $('#donload_files').html('');
              }
            }
            toastr.success(response.msg);
            // window.location.reload();
            //console.log(ADMINURL+'/doctor-dashboard');
            //window.location.href=ADMINURL+'/doctor-dashboard';
            var searchKey = $("#doctor_id").val();
            finding_dashboard(searchKey,p_id,a_id); 
          }    
        }
      })

    });

    $("#calendar").on("click",".deleteAppointmentModal",function()
    {
      var userId = $(this).attr('data-id');
      $.ajax({
        url: ADMINURL + "/dashboard/destroy/" + userId,
        type: "GET",
        dataType: "json",
        beforeSend: function () {
          $("#deleteAppointmentModal").removeClass('fa fa-trash');
          $("#deleteAppointmentModal").addClass('fa fa-spin fa-spinner');
        },
        success: function (response) {
          if(response.status == 'error')
          {
            toastr.error(response.msg);  
          }
          else
          {
            toastr.success(response.msg);
          }        
          //location.reload(true);
          $("#deleteAppointmentModal").removeClass('fa fa-spin fa-spinner');
          $("#deleteAppointmentModal").addClass('fa fa-trash');
          $("#appointmentModal").hide();
          calendar.refetchEvents();
        }
      });
    });

    $("#doctor_id").change(function () 
    {     
        //console.log("change dr id");
        if(dashboard_flag == 0)
        {
          // console.log('4- '+$(this).val());
          doctor_dashboard($(this).val(),false);    
        }
         
    });

    $("#calendar").on("click",".notification_class",function()
    {

        if($(this).closest('.card').hasClass('card'))
        {
          $(this).closest('.card').addClass('card collapsed-card');
        }
        if($(this).attr('title') == 'Leistungen')
        {
          $(this).closest('.card').find(".notification_class").removeClass('highlight');
          
          if(dashboard_flag>0)
          {
            dashboard_flag = dashboard_flag - 1;
          }
          
          $(this).addClass('highlight');
          dashboard_flag = dashboard_flag + 1;

          $("."+$(this).attr('lang')+'_Examination').css('display','block');
          $("."+$(this).attr('lang')+'_Document').css('display','none');
          $("."+$(this).attr('lang')+'_Findings').css('display','none');
          $("."+$(this).attr('lang')+'_Dismissal_section').css('display','block');
          $("."+$(this).attr('lang')+'_Reminder').css('display','block');
        }
        if($(this).attr('title') == 'Revers')
        {
          $(this).closest('.card').find(".notification_class").removeClass('highlight');
          if(dashboard_flag>0)
          {
            dashboard_flag =dashboard_flag - 1;
          }
          $(this).addClass('highlight');
          dashboard_flag = dashboard_flag + 1;
          $("."+$(this).attr('lang')+'_Document').css('display','block');
          $("."+$(this).attr('lang')+'_Examination').css('display','none');
          $("."+$(this).attr('lang')+'_Findings').css('display','none');
          $("."+$(this).attr('lang')+'_Dismissal_section').css('display','none');
          $("."+$(this).attr('lang')+'_Reminder').css('display','none');
        }
        if($(this).attr('title') == 'Befunde')
        {
          $(this).closest('.card').find(".notification_class").removeClass('highlight');
          if(dashboard_flag>0)
          {
            dashboard_flag = dashboard_flag - 1;
          }
          $(this).addClass('highlight');
          dashboard_flag = dashboard_flag + 1;
          $("."+$(this).attr('lang')+'_Findings').css('display','block');
          $("."+$(this).attr('lang')+'_Examination').css('display','none');
          $("."+$(this).attr('lang')+'_Document').css('display','none');
          $("."+$(this).attr('lang')+'_Dismissal_section').css('display','none');
          $("."+$(this).attr('lang')+'_Reminder').css('display','none');
        }

        //console.log(dashboard_flag);
        //console.log($(this).attr('title')+"click count"+$(".highlight").length);
    });

   

    $("#calendar").on("click",".displayExamination",function()
    {
        $("."+$(this).attr('id')).removeClass('hide_me');
        $(this).addClass('removeExaminationSection');
        $(this).removeClass('displayExamination');
        $(this).val(close_examination);
    });

    $("#calendar").on("click",".removeExaminationSection",function()
    {
       $("."+$(this).attr('id')).each(function() {
        //console.log($(this).hasClass('dont_operate'));
            if(!$(this).hasClass('dont_operate'))
            {
               $(this).addClass('hide_me');
            }
        });

        $(this).addClass('displayExamination');
        $(this).removeClass('removeExaminationSection');
        $(this).val(show_examination);
    });
   
    $("#calendar").on("click",".addExamination",function()
    {
      var athis =  $(this);
      // var patinet_id = $(this).parent('div').attr('class');
      // var exam_id = $(this).val();
      // var appoitment_id = $(this).parent('div').attr('lang');
      var appoitment_id = $(this).data("appointment-id");
      var patinet_id = $(this).data("patient-id");
      var exam_id = $(this).val();

      $.ajax({
        type: "POST",
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: ADMINURL + "/appoitment/assignExamination",
        data: 'appoitment_id=' + appoitment_id+'&exam_id='+exam_id+'&patinet_id='+patinet_id,
        success: function (response) {
          if (response.status === 'success') {
            //Added by Shyam 10-01-22
            if($(athis).prop('checked') === false)
            {
              var newPatId = patinet_id.split(' ');
              var btnValue = $('#'+newPatId[0]).val();
              if(btnValue == 'Show Service')
              {
                $(athis).parent('div').addClass('hide_me');
              }
              $(athis).parent('div').removeClass('dont_operate');
            }
            else {
              $(athis).parent('div').addClass('dont_operate');
            }
            //Commented by Shyam 10-01-22
            // $(athis).parent('div').addClass('dont_operate');
            // // swal("Success", response.msg, 'success');
            // $(".examination_section div").each(function( index ) {
            //     if(!$(this).hasClass('dont_operate'))
            //     {
            //       $(this).addClass('hide_me');
            //       //window.location.reload();
            //       //var href_new = "https://puregyn.puremed.biz/admin/doctor-dashboard"+"?"+patinet_id;
            //       //window.location.href=href_new;
            //     }
            // });
            //Commented by Shyam 10-01-22
            var searchKey = $("#doctor_id").val();
            services_dashboard(searchKey,patinet_id,appoitment_id);
          }
        }
      });
      // swal({
      // title: deleteContent.title,
      // text: deleteContent.other_text,
      // type: "warning",
      // showCancelButton: true,
      // cancelButtonText: deleteContent.cancel,
      // confirmButtonText: deleteContent.other_confirm,
      // confirmButtonClass: "btn-primary",
      // closeOnConfirm: true,
      // showLoaderOnConfirm: true
      // },
      // function (isConfirm) {
      //   if(isConfirm){
      //   }
      //   else {
      //     $(athis).prop("checked", false);
      //    // $("#task-checkbox1").prop("checked", false);
      //     return false;
      //   }
      // });
    });

    $('#frmAppointmentEdit').on('submit', function (e) 
    {
      if (!e.isDefaultPrevented()) {

        const $this = $(this);
        const action = $("#update_url").val();

        const formData = new FormData($this[0]);

        var patient_id = $("#patient_idedit").val();
        if(patient_id==undefined){
          patient_id = '';
        }
        // console.log(patient_id);
        formData.append('patient_id', patient_id);

        $('#frmAppointmentEdit,.model-body').LoadingOverlay("show", {
          background: "rgba(165, 190, 100, 0.4)",
        });

        axios.post(action, formData)
          .then(function (response) {
            const resp = response.data;

            if (resp.status == 'success') {
              $this[0].reset();
              toastr.success(resp.msg);
              window.location.reload();

            }

            if (resp.status == 'error') {
              $('#frmAppointmentEdit,.model-body').LoadingOverlay("hide");
              toastr.error(resp.msg);

              const errorBag = resp.errors;

              $.each(errorBag, function (fieldName, value) {
                $('.err_' + fieldName).closest('.form-group').addClass('has-error has-danger');
                $('.err_' + fieldName).text(value[0]).closest('span').show();
              })
            }
          })
          .catch(function (error) {
            $('#frmAppointmentEdit,.model-body').LoadingOverlay("hide");

            const errorBag = error.response.data.errors;

            $.each(errorBag, function (fieldName, value) {
              $('.err_' + fieldName).closest('.form-group').addClass('has-error has-danger');
              $('.err_' + fieldName).text(value[0]).closest('span').show();
            })
          });

        return false;
      }
    });

    $('#frmAppointmentTypeEdit').on('submit', function (e) 
    {
      if (!e.isDefaultPrevented()) {

        const $this = $(this);
        const action = $("#update_url").val();

        const formData = new FormData($this[0]);

        var patient_id = $("#patient_idedit").val();
        if(patient_id==undefined){
          patient_id = '';
        }
        // console.log(patient_id);
        formData.append('patient_id', patient_id);

        $('#frmAppointmentTypeEdit,.model-body').LoadingOverlay("show", {
          background: "rgba(165, 190, 100, 0.4)",
        });

        axios.post(action, formData)
          .then(function (response) {
            const resp = response.data;

            if (resp.status == 'success') {
              $this[0].reset();
              toastr.success(resp.msg);
              window.location.reload();

            }

            if (resp.status == 'error') {
              $('#frmAppointmentEdit,.model-body').LoadingOverlay("hide");
              toastr.error(resp.msg);

              const errorBag = resp.errors;

              $.each(errorBag, function (fieldName, value) {
                $('.err_' + fieldName).closest('.form-group').addClass('has-error has-danger');
                $('.err_' + fieldName).text(value[0]).closest('span').show();
              })
            }
          })
          .catch(function (error) {
            $('#frmAppointmentEdit,.model-body').LoadingOverlay("hide");

            const errorBag = error.response.data.errors;

            $.each(errorBag, function (fieldName, value) {
              $('.err_' + fieldName).closest('.form-group').addClass('has-error has-danger');
              $('.err_' + fieldName).text(value[0]).closest('span').show();
            })
          });

        return false;
      }
    });

    $(".btnClosePopup").click(function ()
    {
        $("#appointmentModal").hide(); 
    });   
    
  });

  //// commented by Shyam 15-12-21
  //Set Interval for dashboard refresh data
  // var refreshInterval = setInterval(function(){
  //   doctor_dashboard($("#doctor_id").val(),false);
  // }, 30000);

  // Refresh List if last element is collapsed
  $("#calendar").on('click', '.card', function(){
      if(!$(this).hasClass('collapsed-card'))
      {
        total_blocks = $('div #calendar div.card').length;
        collapsed_blocks =  $('div #calendar div.collapsed-card').length;
        //console.log(total_blocks);
        //console.log(collapsed_blocks);
        if(total_blocks == collapsed_blocks+1 || total_blocks == collapsed_blocks)
        {
          var searchKey = $("#doctor_id").val();
          //// commented by Shyam 15-12-21
          // clearInterval(refreshInterval);
          // doctor_dashboard(searchKey,false);
          // refreshInterval = setInterval(function(){
          //   doctor_dashboard($("#doctor_id").val(),false);
          // }, 30000);

        }
        else if(total_blocks > collapsed_blocks)
        {
          //// commented by Shyam 15-12-21
          // clearInterval(refreshInterval);
        }
      }
  });
  
  function chk_reminder(element)
  {
    var exam_id = $(element).attr('data-rem-id');
    var p_id = $(element).attr('data-rem-p-id');

    var a_id = $(element).attr('data-rem-a-id');
    //console.log($("#rem_checkbox_reminder_"+exam_id+'_'+p_id+'_'+a_id).prop('checked'));
    if($("#rem_checkbox_reminder_"+exam_id+'_'+p_id+'_'+a_id).prop('checked') == true)
    {
      $('#rem_div_'+exam_id+'_'+p_id+'_'+a_id).show();
      $('#period_frequency_div_'+exam_id+'_'+p_id+'_'+a_id).show();
    }
    else if($("#rem_checkbox_reminder_"+exam_id+'_'+p_id+'_'+a_id).prop('checked') == false)
    {
      $('#rem_div_'+exam_id+'_'+p_id+'_'+a_id).hide();
      $('#period_frequency_div_'+exam_id+'_'+p_id+'_'+a_id).hide();
    }
  }

  function doctor_dashboard(doctor_id,patient_id)
  {
    if($(".highlight").length == 0 && dashboard_flag < 1)
    {
      console.log("in doctor function");
      $('body').LoadingOverlay("show");
      $.ajax({
        type: "GET",
        url: ADMINURL + "/appoitment/getDoctorEvents",
        timeout: 30000,
        data: 'doctor_id=' + doctor_id, 
        success: function (response)
        {
          console.log("get response");
          $("#calendar").html(response);
          $('body').LoadingOverlay("hide");
          $('.number').mask('999');

          //// added by Shyam 15-12-21
          setTimerId = setTimeout(function() {
            if($(".highlight").length == 0)
            {
              if(dashboard_flag == 0)
              {
                var searchKey = $("#doctor_id").val();
                clearTimeout(setTimerId);//Clear setTimeout
                doctor_dashboard(searchKey,false);
              }
            }
          },60000);
          // console.log('set =>'+setTimerId);
          //// added by Shyam 15-12-21
        },
        error: function (jqXHR, exception) { //// added by Shyam 15-12-21
          $('body').LoadingOverlay("hide");
          $('.number').mask('999');
          if($(".highlight").length == 0)
          {
            if(dashboard_flag == 0)
            {
              var searchKey = $("#doctor_id").val();
              clearTimeout(setTimerId);//Clear setTimeout
              doctor_dashboard(searchKey,false);
            }
          }
        },
      });
    }
  }

  function services_dashboard(doctor_id,patient_id,appoitment_id)
  {
      //// added by Shyam 15-12-21
      setTimeout(function(){
        if(dashboard_flag > 0 && $(".highlight").length == 0)
        {
          dashboard_flag = 0;
          clearTimeout(setTimerId);//Clear setTimeout

            // console.log('7- '+doctor_id);
          doctor_dashboard(doctor_id,false);
        }
      },30000);
      //// added by Shyam 15-12-21
      
      $.ajax({
        type: "GET",
        url: ADMINURL + "/appoitment/getDoctorEvents",
        data: 'doctor_id=' + doctor_id, 
        success: function (response) {
          //Commented by Shyam 10-01-22
          // $("#calendar").html(response);

          //$('.content-wrapper').LoadingOverlay("hide");
          $('.number').mask('999');
          if(patient_id!='')
          {
              $("."+patient_id+appoitment_id+'_main_div').removeClass('collapsed-card');
              $("."+patient_id+appoitment_id+'_sub').css('display','block');
              $("."+patient_id+appoitment_id+'_Document').css('display','none');
              $("."+patient_id+appoitment_id+'_Examination').css('display','block');
              $("."+patient_id+appoitment_id+'_Findings').css('display','none');
              $("."+patient_id+appoitment_id+'_Dismissal_section').css('display','block');
              $("."+patient_id+appoitment_id+'_Reminder').css('display','block');
              // $("form[name=reminderUpdate"+appoitment_id+"]").css('display','block');
          }
          $("."+patient_id+appoitment_id+'_main_div').find('.notification_class:first-child').addClass('highlight');
          // dashboard_flag =  dashboard_flag +1;
        }
      });
  }

  function finding_dashboard(doctor_id,patient_id,appoitment_id)
  {
      $.ajax({
      type: "GET",
      url: ADMINURL + "/appoitment/getDoctorEvents",
      data: 'doctor_id=' + doctor_id, 
      success: function (response) {
        
         $("#calendar").html(response);
         //$('.content-wrapper').LoadingOverlay("hide");
         $('.number').mask('999');
          if(patient_id!='')
          {
              // $(this).closest('.card').find(".notification_class").removeClass('highlight');
              // $(this).addClass('highlight');
              $("."+patient_id+appoitment_id+'_main_div').removeClass('collapsed-card');
              $("."+patient_id+appoitment_id+'_Findings').css('display','block');
              $("."+patient_id+appoitment_id+'_Examination').css('display','none');
              $("."+patient_id+appoitment_id+'_Document').css('display','none');
              $("."+patient_id+appoitment_id+'_Dismissal_section').css('display','none');
              $("."+patient_id+appoitment_id+'_Reminder').css('display','none');
          
          }
          $("."+patient_id+appoitment_id+'_main_div').find('.notification_class[title=Befunde]').addClass('highlight');
          // dashboard_flag =  dashboard_flag + 1;
          
        }
      });  
    //}
  }

  function storeDismissal(id,p_id,a_id)
  {
     
      $.ajax({
        headers: 
        {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        },
        type: "POST",
        url: ADMINURL + "/appoitment/storeDismissal",
        data: 
        {
          'id': id,
          'p_id':p_id,

          'a_id':a_id,
          'flag' : $("#chk_"+id+'_'+p_id+'_'+a_id).prop('checked'),

        },
        success: function (response) 
        {
           
        }
      });
  }

  function sendDocumentForPatients(element,p_id,a_id,exam_id,doc_id,type)
  {
    var exam_id = $(element).attr('lang-exam');
    var doc_type = $(element).attr('lang-type');
  
    swal({
    title: deleteContent.title,
    text:msg_send_doc_for_patient,
    type: title_warning,
    showCancelButton: true,
    cancelButtonText: deleteContent.cancel,
    confirmButtonText: 'Ok',
    confirmButtonClass: "btn-danger",
    closeOnConfirm: true,
    showLoaderOnConfirm: true
    },
    function ()
    {
       $.ajax({
        headers: 
        {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        },
        type: "POST",
        url: ADMINURL + "/appoitment/getPatientDetails",
        data: 
        {
          'doc_id': doc_id,
          'exam_id':exam_id,
          'p_id':p_id,
          'type':type,
        },
        success: function (response) 
        {
          $('#patient_name').val(response['patient_name']);
          $('#to').val(response['email']);
          $('#hd_patient_id').val(response['p_id']);
          $('#hd_doc_id').val(response['doc_id']);
          $('#type').val(type);
          $('#doc_type').val(doc_type);
          $('#exam_id').val(exam_id);
          $('#a_id').val(a_id);
          $( "#btn-send-finding-via-email" ).trigger( "click" );
        }
      });
      })
  }

  function generateChecklistPDF(chk_id)
  {
      $.ajax({
      type: "GET",
      url: ADMINURL + "/appoitment/generateChecklistPDF/"+chk_id,
      data: 'chk_id=' + chk_id, 
      success: function (response) 
      {
        var url =BASEURL+response
        window.open(url, "_new");
      }
         
      });
  }

  function generateDocumentPDF(doc_id)
  {
     $.ajax({
      type: "GET",
      url: ADMINURL + "/appoitment/generateDocumentPDF/"+doc_id,
      data: 'doc_id=' + doc_id, 
      success: function (response) 
      {
        var url =BASEURL+response
        window.open(url, "_new");
      }
         
      });
  }

  function PrintDiv(element) 
  {    
    var path = $(element).attr('lang-path');
    var url =BASEURL+path
    $("#doc_img").attr("src",url);
    var divToPrint = document.getElementById('divToPrint');
    var popupWin = window.open('', '_blank', 'width=700,height=700');
    popupWin.document.open();
    popupWin.document.write('<html><body onload="window.print()">' + divToPrint.innerHTML + '</html>');
    popupWin.document.close();
 }
  function GetServices(appointment_type_id,patient_id,a_id)
  {  

    $("#save_apptype_btn").prop("disabled", true); //added on 28-nov-25 
    $.ajax({
      type: "POST",
      headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      url: ADMINURL + '/appointment/getServices',
      data: 'appointment_type_id=' + appointment_type_id+'&patient_id='+patient_id+'&a_id='+a_id, 
      success: function (response) 
      {
        //console.log(response.services);
        fetchServices();
        $("#appointment_type_services").html(response.services);
         $("#save_apptype_btn").prop("disabled", false);//added on 28-nov-25
      }          
    });  
  }
  
  /*Code Added by Shyam 22-02-22 */
  function fetchServices()
  {
    console.log('inn fetchServices doctor dashboard function....');

    var birth_date = $('#birth_date').val();
    if(birth_date)
    {
      birth_date = birth_date.split("-").reverse().join("-");
    }

    console.log(birth_date);

    var appointment_type_id = $('#appointment_type_id').val();
    //alert(appointment_type_id+"Birthdate"+birth_date+"NewPatient"+$('#new_patient_chkbox').prop("checked"));
    if(birth_date != '' && appointment_type_id != '' && $('#new_patient_chkbox').prop("checked") == true) //uncommented on 11-dec-23
    // if(appointment_type_id != '') //commented on 11-dec-23
    {
       console.log('innnnnnnnnnn func....');

      //console.log("Appointment"+appointment_type_id);
      setTimeout(function() {
        $.ajax({
          type: "POST",
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          url: ADMINURL + '/appointment/getExtraServices',
          data: 'appointment_type_id=' + appointment_type_id+'&birth_date='+birth_date,
          success: function (response)
          {
            //console.log(response.services);
            $(".appointment_type_services").append(response.services);
          }
        });
      }, 1000);
    }
  }
  /*Code Added by Shyam 22-02-22 */

