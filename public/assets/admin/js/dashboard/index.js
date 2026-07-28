var events;
var calendar;
// added by vijay 20-2-2025
$(document).ready(function () {
    $("#addAppbutton").click(function () {
        // Hide patient details
        $("#new_patient_chkbox").prop("checked", false);
        $(".patient_details").hide();
        $(".patient_details input").val("");
        // Show suggestion and birth date divs
        $("#suggesstion_patient_div_id").show();
        $("#search_birth_date_div").show();
        $("#app_reset").trigger("click");
    });
});
// end
$(function () {
  $('#birth_date').mask("99-99-9999");
  $('#search_birth_date').mask("99-99-9999");
  $('#goto_date').mask("99-99-9999");

  $("#dashboard_data").css('display','block');

  $('#new_patient_chkbox').click(function()
  {
      if($(this).prop("checked") == true) {
          $(".patient_details").show();
          $("#suggesstion_patient_div_id").hide();
          $("#search_birth_date_div").hide();
      }
      else if($(this).prop("checked") == false) {
          $(".patient_details").hide();
          $("#suggesstion_patient_div_id").show();
          $("#search_birth_date_div").show();
      }
  });

  $('#birth_date').datepicker({
      dateFormat: 'dd-mm-yy',
      changeMonth: true,
      changeYear: true,
      yearRange: '1920:+0',
      startDate: new Date('1920-01-01'),
      maxDate: 0
  });
  $('#search_birth_date').datepicker({
      dateFormat: 'dd-mm-yy',
  });
  $('#goto_date').datepicker({
      dateFormat: 'dd-mm-yy',
  });

  $(".btnClosePopup").click(function() {
    $("#appointmentModal").hide();
  });
  $("#app_reset").click(function()
  {
    $("#select2-doctor_id-container").html('');
    $("#select2-appointment_type_id-container").html('');
    $("#suggesstion-box-patient").html('');
    //Swapnil Added Code 06-09-2022
    $("#available_datetime").hide();
    $("#doctor_duty_rosters").hide();
    $("#appointment_date_calender").hide();
    $("#appointment_time_slot").hide();
    $("#time_frame1").val("");
    $("#time_frame").val("");
    $("#appointment_date_new").val("");

    $("#status").attr('checked', false);  //added on 9-nov-23
    $("input[name='app_services[]']").attr('checked', false);//added on 9-nov-23

  });

  $('.content-wrapper').on("mouseover","#frmAppointment",function()
  {
    $('#s_button').removeClass('disabled');
  });

var searchPatient=0;
if(searchPatient==0)
{
  $("body").on('keyup','#suggesstion_patient_id',function ()
  {
      searchPatient=1;
      $("#search_birth_date").val('');
      var searchKey = $(this).val();
      var birthdateKey = $("#search_birth_date").val();
      var searchKeyLength=searchKey.length;
      if(searchKey=='' && birthdateKey ==''){
        $("#suggesstion-box-patient").empty();
        searchPatient=0;
      }
      else {
        if(searchKey.length < 3){
            searchPatient=0;
            $("#suggesstion-box-patient").empty();
            return false;
        }
        $.ajax({
          type: "GET",
          url: ADMINURL + "/dashboard/patients",
          data: 'keyword=' + searchKey+'&popup=1&birthdateKey='+birthdateKey,
          success: function (response) {
            searchPatient=0;
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
              $("#suggesstion-box-patient").show();
              $("#suggesstion-box-patient").html(response['data']);
              $("#suggesstion-box-patient").on('change','#patient_id',function()
              {
                $("#search_birth_date").val($("#patient_id option:selected").attr('title'));
              })

        var dob = $("#patient_id option:first").attr('title');
              $("#search_birth_date").val(dob);

              $('#search_birth_date').datepicker({
                format: 'dd-mm-yyyy',
              });
              $("#suggesstion_patient_id").css("background", "#FFF");

              document.getElementById("suggesstion_patient_id").addEventListener("search", function(event) {
                $("#suggesstion-box-patient").empty();
              });
            }else{
               $("#suggesstion-box-patient").empty();
            }
          }
        });

      }
  });
}

  $("#search_birth_date").change(function()
  {
    var searchKey = $("#suggesstion_patient_id").val();
    var birthdateKey = $(this).val();
    if(searchKey=='' && birthdateKey =='') {
      $("#suggesstion-box-patient").empty();
    }
    else {
      $.ajax({
        type: "GET",
        url: ADMINURL + "/dashboard/patients",
        data: 'keyword=' + searchKey+'&popup=1&birthdateKey='+birthdateKey,
        success: function (response)
        {
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
            $("#suggesstion-box-patient").show();
            if((response['data'].match(/option/g) || []).length > 2)
            {
              $("#suggesstion-box-patient").html(response['data']);
            }
            else {
              var selectVar = '<select class="form-control" id ="patient_id" name="patient_id">';
              var htmlData = response['data'].split(selectVar);
              selectVar += '<option value=""> - - - - </option>'+htmlData[1];
              $("#suggesstion-box-patient").html(selectVar);
            }
            $("#suggesstion_patient_id").css("background", "#FFF");
            document.getElementById("suggesstion_patient_id").addEventListener("search", function(event) {
              $("#suggesstion-box-patient").empty();
            });
          }
          else {
            $("#suggesstion-box-patient").empty();
          }
        }
      });
    }
  });

  /* initialize the calendar
    -----------------------------------------------------------------*/
  //Date for the calendar events (dummy data)
  var date = new Date()
  var d = date.getDate(),
    m = date.getMonth(),
    y = date.getFullYear()

  var Calendar = FullCalendar.Calendar;
  var calendarEl = document.getElementById('calendar');

  // var tooltip = $('<div/>').qtip(
  // {
  //     id: 'calendar',
  //     prerender: true,
  //     content: {
  //         text: ' ',
  //     },
  //     position: {
  //       at: 'center left',
  //       my: 'right center',
  //       effect: false,
  //       viewport: $('.fc-timegrid-event-harnes'),
  //       adjust: {
  //           method: 'none shift'
  //         }
  //     },
  //     show: false,
  //     hide: false,
  //     style: 'qtip-light'
  // }).qtip('api');

  /* filter the calendar
   -----------------------------------------------------------------*/
  $('#patient-id,#suggesstion-box,#doctor-id,#suggesstion-box1').on('change', function()
  {
    calendar.refetchEvents();
    calendar.refetchResources();
  })

  var activeLanguage =  $(".activeLanguage").attr('lang');
    calendar = new Calendar(calendarEl, {
      schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
      initialView: 'resourceTimeGridDay',
      height: 'auto',
      dayMaxEvents: true,
      slotMinTime : "07:00:00",
      slotDuration :'00:10:00',
      dayHeaders:true,
      allDaySlot :true,
      resourceOrder: '-type1,type2',
      filterResourcesWithEvents:true,
      navLinks: true,
      dayNames:true,
      eventBackgroundColor: "#de1f1f",
      slotEventOverlap:false,
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,resourceTimeGridWeek,resourceTimeGridDay'
      },
      views: {
        resourceTimeGridDay: { // name of view
          titleFormat: { weekday:'short',year: 'numeric', month: 'long', day: '2-digit' }
          // other view-specific options here
        }
      },
      // titleFormat: {
      //    day: 'short',year: 'numeric', month: '2-digit', day: '2-digit'
      // },
      eventDisplay: 'block',
      eventOverlap: false, // will cause the event to take up entire resource height
      resourceAreaWidth: '100%',
      resources: { // you can also specify a plain string like 'json/resources.json'
        url: ADMINURL + "/dashboard/getResourceId",
      },
      refetchResourcesOnNavigate: true,

      locale: 'de',
      events: {
        url: ADMINURL + '/calendar/getEvents',
         extraParams: {
          patient_name: ''
        },
      },
      selectable: true,
      displayEventEnd: true,
      refetchResourcesOnNavigate:true,
      contentHeight: 'auto',
      expandRows:'true',
      // editable: true,
      dayClick: function(date, jsEvent, view) { tooltip.hide(); },
      eventResizeStart: function() { tooltip.hide() },
      eventDragStart: function() { tooltip.hide() },

      eventMouseEnter: function(calEvent)
      {

         $('.fc-event-main-frame').tipso({
          titleContent: calEvent.event._def.extendedProps.description,
          onBeforeShow: function(ele, tipso) {
            console.log("test");
          }
        });
        // $('.fc-event-title').tipso({
        //   titleContent: calEvent.event._def.extendedProps.description
        // });
        // tooltip.set({
        //   'content.text': calEvent.event._def.extendedProps.description
        // })
        // .reposition(calEvent.event).show(calEvent.event);
        // $(this.el).mouseover(function(e) {
        //   $(this.el).css('z-index', 10000);
        // }).mousemove(function(e) {
        //   $('#qtip-calendar').css('top', e.pageY - 50);
        //   $('#qtip-calendar').css('left', e.pageX - 120);
        // });
      },
      eventMouseLeave: function(calEvent, jsEvent) {
        $(this.el).css('z-index', 8);
        //$('#qtip-calendar').css('display','none');
        $('.fc-event-main-frame').tipso('destroy');
      },
      eventClick: function (info)
      {
        $('#editAppointmentModal').attr('data-id', info.event.id);
        $('#deleteAppointmentModal').attr('data-id', info.event.id);
        $('#redirectToPatient').attr('data-id', info.event.id);
        $("#appointmentModal .modal-body #popup_description").html(info.event._def.extendedProps.description);
        $("#appointmentModal .modal-body #qr_code").html('');

        $.getScript( 'https://puremed.biz/assets/admin/js/dashboard/qrcode.js', function( data, textStatus, jqxhr) {
            $("#appointmentModal .modal-body #qr_code").qrcode(info.event._def.extendedProps.qr_code);
        });

        var google_e_id = info.event.id;
        const action = ADMINURL + "/dashboard/view/" + google_e_id;
        axios.get(action)
        .then(response => {
          const resp = response.data;
          if(resp)
          {
            $("#appointmentModal .modal-body #popup_description").html(resp);
          }
          else {
            $("#appointmentModal .modal-body #popup_description").html(info.event._def.extendedProps.description);
            // qrcode.makeCode("addme");
          }
        })
        .catch(error => {
          // $('.card-body').LoadingOverlay("hide");
        })


        /***********added on 11-apr-24 for app*#3****/

          var google_e_id = info.event.id;
          const action1 = ADMINURL + "/dashboard/checkAppointmentStatus/" + google_e_id;
          axios.get(action1)
          .then(response => {
            const resp = response.data;
            //alert(resp);
            if(resp)
            {
              if(resp==1)
              {
                $(".openProfileModal").hide();

              }
              else
              {

                   /***********added on 3-apr-24 for app*#3****/
                     $('.openProfileModal').attr('data-id', info.event.id);
                   /***********added on 3-apr-24 for app*#3****/

                   $(".openProfileModal").show();
              }
            }
            else
            {
               /***********added on 3-apr-24 for app*#3****/
                $('.openProfileModal').attr('data-id', info.event.id);
               /***********added on 3-apr-24 for app*#3****/

                $(".openProfileModal").show();

            }
          })
          .catch(error => {
            // $('.card-body').LoadingOverlay("hide");
          })
        /***********added on 11-apr-24 for app*#3****/





        $("#appointmentModal").show();
      },
      eventDidMount: function(event)
      {
        var showTypes, event_patient_id, showFacilities, showSearchTerms = true;
        var patient_id = $('#patient-id').val();
        var doctor_id = $('#doctor-id').val();
        var selected_patient_email = '';
        var email_record_exist = $("#getPatientsData").val();
        if (typeof email_record_exist === "undefined" ) {
          //
        }
        else {
         showSearchTerms = event.event._def.extendedProps.patient_name.toLowerCase().indexOf(email_record_exist.toLowerCase()) >= 0 || event.event._def.extendedProps.patient_name.toLowerCase().indexOf(email_record_exist) >= 0;
        }
        console.log(showSearchTerms);
        if(showSearchTerms)
        {
          event.event.setProp('display','block')
        }
        else {
          event.event.setProp('display','none')
        }
        $(".fc-timegrid-event").addClass('fc-timegrid-event-condensed');
        console.log('d');
        return showSearchTerms;
      }, //end: eventRender
      resourceLabelDidMount: function(arg) {
        arg.el.addEventListener('click', function() {
          console.log(arg);
          $(".fc-timegrid-event").addClass('fc-timegrid-event-condensed');
          console.log('d');
        })
      },
      select: function (date,start, end, jsEvent) {
        var selectDate = date.start;
        var selectedDate = moment(selectDate).format('YYYY-MM-DD');
        showCreateAppointmentView(selectedDate);
        dashboardData(selectedDate);

        // Add scroll to top after popup open
          setTimeout(() => {
            window.scrollTo({
              top: 0,
              behavior: 'smooth'
            });
          }, 100); //added on 30-may-25 for #341
      },
      viewDidMount: function(viewInfo) {
        setTimeout(() => {
          document.querySelectorAll('.fc-timegrid-slot').forEach(slot => {
            slot.addEventListener('click', function(e) {
              e.preventDefault();
              e.stopPropagation();

              const timeStr = this.getAttribute('data-time');
              const currentDate = moment(viewInfo.view.currentStart).format('YYYY-MM-DD');
              const fullDateTime = currentDate + 'T' + timeStr;

              const selectedDate = moment(fullDateTime).format('YYYY-MM-DD');
              console.log("Clicked time:", selectedDate);

              showCreateAppointmentView(selectedDate);
              dashboardData(selectedDate);

              // Add scroll to top after popup open
              setTimeout(() => {
                window.scrollTo({
                  top: 0,
                  behavior: 'smooth'
                });
              }, 1000);
            });
          });
        }, 0);
      }//added on 30-may-25 for #341
  })

  calendar.render();

  if(activeLanguage == ' de ')
  {
    calendar.setOption('locale','de');
  }

  //Autocomplete features
  $('input[name="name"]').focus();
  $('#appointment_date').datepicker({
   // dateFormat: 'yy-mm-dd', //commeted on 10-jan-23
    dateFormat: 'dd-mm-yy', //swapnil added on 10-jan-23
    orientation: "bottom",
    autoclose: true,
    todayHighlight: true,
    startDate: new Date(),
    //minDate: 0 //commented by swapnil on 10-jan-23
  });

  // var calenderHeader = $(".fc-toolbar-title").html();
  // var splittd_question = calenderHeader.split(".");
  // $final_title = splittd_question[0]+"."+splittd_question[1]+"."+splittd_question[2];
  // $(".fc-toolbar-title").html($final_title);

  $('#frmAppointment').validator().on('submit', function (e)
  {
      const $this = $(this);
       // CLEAR OLD ERRORS HERE (//Added above code for hide error if value is correct on submit button)
        $('[class^="err_"]').each(function () {
            // $(this).text('').hide();
            $(this).closest('.form-group').removeClass('has-error has-danger');
        });
        //Added above code for hide error if value is correct on submit button 
      const action = $this.attr('action');
      const formData = new FormData($this[0]);

      $('#frmAppointment,.model-body').LoadingOverlay("show", {
        background: "rgba(165, 190, 100, 0.4)",
      });

      var patient_id = $("#patient_id").val();
      if(patient_id==undefined) {
        patient_id = '';
      }
      formData.append('patient_id', patient_id);

      axios.post(action, formData)
        .then(function (response) {
          const resp = response.data;
          if (resp.status == 'success') {
            $this[0].reset();
            $("#select2-doctor_id-container").html('');
            $("#select2-appointment_type_id-container").html('');
            $("#suggesstion-box-patient").html('');
            //Swapnil Added Code 06-09-2022
            $("#available_datetime").hide();
            $("#doctor_duty_rosters").hide();
            $("#appointment_date_calender").hide();
            $("#appointment_time_slot").hide();
            $("#time_frame1").val("");
            $("#time_frame").val("");
            $("#appointment_date_new").val("");
            //End
            $("#addAppointmentModal").modal("hide");

            //Added by divya on 29sept22
             console.log('in dashboard appointment success');
             $(".patient_details").hide();
             $("#suggesstion_patient_div_id").show();
             $("#search_birth_date_div").show();
            //End added on 29sept22

            toastr.success(resp.msg);
            $('#frmAppointment,.model-body').LoadingOverlay("hide");
            $(".btnClosePopup").click();
            calendar.refetchEvents();
          }
          if (resp.status == 'error') {
            $('#frmAppointment,.model-body').LoadingOverlay("hide");
            toastr.error(resp.msg);
            const errorBag = resp.errors;
            //Swapnil Added Code 06-09-2022
            var selectdatetime = $(".new_appointment_datetime_added").val();
            var doctor_status = $('#doctor_id option:selected').attr('lang');
            if(selectdatetime.length==0 && doctor_status==1)
            {
              toastr.error("Date Time field is required.");
            }
            //Swapnil Added Code 06-09-2022
            $.each(errorBag, function (fieldName, value) {
              $('.err_' + fieldName).closest('.form-group').addClass('has-error has-danger');
              $('.err_' + fieldName).text(value[0]).closest('span').show();
            })
          }
        })
        .catch(function (error) {
          $('#frmAppointment,.model-body').LoadingOverlay("hide");
          const errorBag = error.response.data.errors;
          //Swapnil Added Code 06-09-2022
          var selectdatetime = $(".new_appointment_datetime_added").val();
          var doctor_status = $('#doctor_id option:selected').attr('lang');
          if(selectdatetime.length==0 && doctor_status==1)
          {
            toastr.error(daterequireddashboard);
          }
          //Swapnil Added Code 06-09-2022
          $.each(errorBag, function (fieldName, value) {
            $('.err_' + fieldName).closest('.form-group').addClass('has-error has-danger');
            $('.err_' + fieldName).text(value[0]).closest('span').show();
          })
        });
      return false;
  })

  $("#patient-id").keyup(function ()
  {
    var searchKey = $(this).val();
    if(searchKey=='') {
      $("#suggesstion-box").empty();
    }
    else {
      $.ajax({
        type: "GET",
        url: ADMINURL + "/dashboard/patients",
        data: 'keyword=' + searchKey,
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
            $("#suggesstion-box").show();
            $("#suggesstion-box").html(response['data']);
            $("#patient-id").css("background", "#FFF");
          }
        }
      });
    }
  });

  $("#doctor-id").keyup(function()
  {
    var searchKey = $(this).val();
    if(searchKey=='') {
      $("#suggesstion-box1").empty();
    }
    else {
      $.ajax({
        type: "GET",
        url: ADMINURL + "/dashboard/doctors",
        data: 'keyword=' + searchKey,
        success: function (response)
        {
          var len = 0;
          if (response['data'] != null) {
            len = response['data'].length;
            var data = response['data'];
          }
          if (len > 0) {
            for (var i = 0; i < len; i++) {
              var doctor_name = response['data'][i].first_name;
              var lname = response['data'][i].last_name;
              if (lname != null) {
                doctor_name += "-" + lname;
              }
            }
            $("#suggesstion-box1").show();
            $("#suggesstion-box1").html(response['data']);
            $("#doctor-id").css("background", "#FFF");
          }
        }
      });
    }
  });
  // document.getElementById("doctor-id").addEventListener("search", function(event) {
  //   $("#suggesstion-box1").empty();
  // });
  document.getElementById("patient-id").addEventListener("search", function(event) {
    $("#suggesstion-box").empty();
  });

  $("#deleteAppointmentModal").on("click", function()
  {
    console.log("1111--->");
    var userId = $(this).attr('data-id');
    console.log(userId);
    $.ajax({
      url: ADMINURL + "/dashboard/destroy/" + userId,
      type: "GET",
      //dataType: "json",
      beforeSend: function() {
        console.log("in befor send.");
        $("#deleteAppointmentModal").removeClass('fa fa-trash');
        $("#deleteAppointmentModal").addClass('fa fa-spin fa-spinner');
      },
      success: function (response) {
        console.log("response first");
        if(response.status == 'error')
        {
          console.log("response error");
          toastr.error(response.msg);
        }
        else {
          console.log("response 2nd");
          toastr.success(response.msg);
        }
        console.log("=====>");
        //location.reload(true);
        $("#deleteAppointmentModal").removeClass('fa fa-spin fa-spinner');
        $("#deleteAppointmentModal").addClass('fa fa-trash');
        $("#appointmentModal").hide();
        calendar.refetchEvents();
      }
    });
  });

  $("#editAppointmentModal").on("click", function()
  {
      $("#appointmentModal").hide();
      const google_event_id = $(this).attr('data-id');
      const action = ADMINURL + "/dashboard/edit/" + google_event_id;

      axios.get(action)
      .then(response => {
        const resp = response.data;
        // $("#frmAppointmentEdit").empty();
        $("#frmAppointmentEdit").html(resp);
        $("#suggesstion_edit_patient_id").keyup(function()
        {
          var searchKey = $(this).val();
          if(searchKey=='') {
            $("#suggesstion-box-edit-patient").empty();
          }
          else {
            $.ajax({
              type: "GET",
              url: ADMINURL + "/dashboard/patients",
              data: 'keyword=' + searchKey+'&popup=1&edit=1',
              success: function (response)
              {
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
                }
                else {
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

  $("#redirectToPatient").on("click", function()
  {
      const google_event_id = $(this).attr('data-id');
      const action = ADMINURL + "/dashboard/redirect/" + google_event_id;
      axios.post(action)
      .then(response => {
        const resp = response.data;
        if (resp.status == 'success') {
              if(resp.url!='') {
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

  $('#frmAppointmentEdit').on('submit', function (e)
  {
      if (!e.isDefaultPrevented())
      {
        const $this = $(this);
        const action = $("#update_url").val();
        const formData = new FormData($this[0]);

        var patient_id = $("#patient_idedit").val();
        if(patient_id==undefined) {
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
              $('#frmAppointmentEdit,.model-body').LoadingOverlay("hide");
              $("#editAppointmentDataModal").hide();
              $("#appointmentModal").hide();
              $(".btnClosePopup").click();
              calendar.refetchEvents();
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
          .catch(function (error)
          {
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

  /* Added By swati */
  selectDate = calendar.getDate();
  selectedDate = moment(selectDate).format('YYYY-MM-DD');
  dashboardData(selectedDate);

  $(".fc-today-button").click(function() {
    selectDate = calendar.getDate();
    selectedDate = moment(selectDate).format('YYYY-MM-DD');
    dashboardData(selectedDate);
    calendar.render('allDay','false');
  });
  $(".fc-resourceTimeGridWeek-button").click(function() {
    calendar.render('allDay','false');
  });

  $('body').on('click', 'button.fc-prev-button', function() {
    selectDate = calendar.getDate();
    selectedDate = moment(selectDate).format('YYYY-MM-DD');
    dashboardData(selectedDate);
  });

  $('body').on('click', 'button.fc-next-button', function() {
    selectDate = calendar.getDate();
    selectedDate = moment(selectDate).format('YYYY-MM-DD');
    dashboardData(selectedDate);
  });

  $("body").on('click','#notice_edit_click',function()
  {
    $(this).hide();
    $("#calender_notice").show();
    $(".button_section").show();
  });

  $("body").on('click','#cancel_notice', function()
  {
    $("#calender_notice").hide();
    $("#notice_edit_click").show();
    $(".button_section").hide();
    $("#err_contact_name").hide();
    $("#calender_notice").removeClass('custom_validation');
  });

  $("body").on('click',".click_me", function()
  {
      $('.click_me').prop('checked', false);
      $(this).prop('checked', true);
      $(".hide_type").css('display','none');
      $("#type"+$(this).attr('id')).css('display','block');
  });

  $(document).on('click','#calender_notice', function()
  {
    $("#calender_notice").removeClass('custom_validation');
    $("#err_contact_name").css('display','none');
  });

  $(document).on('click','#close', function()
  {
      var data = $("#calender_notice").val();
      if(data.trim() == '')
      {
        $("#calender_notice").addClass('custom_validation');
        $("#err_contact_name").css('display','block');
        return false;
      }
      selectDate = calendar.getDate();
      selectedDate = moment(selectDate).format('YYYY-MM-DD');
      $.ajax({
        type: "POST",
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: ADMINURL + "/dashboard/addUpdateNotices",
        data: 'data=' + data+"&selectedDate="+selectedDate,
        success: function (response)
        {
          $("#notice_edit_click").html($("#calender_notice").val());
          $("#notice_edit_click").show();
          $("#calender_notice").hide();
          $(".button_section").hide();
        }
      });
  });/* End */
});

$('#calendar').find('.fc-time-grid-event,.fc-event,.fc-start,.fc-end').addClass('fc-short');

  function gotoDate(element) {
    // console.log('gotoDate');
    // console.log($(element).val());
    var currentDate = $(element).val();
    if(currentDate!='') {
      var currentDatesplit = currentDate.split('-');
      var date = new Date(currentDatesplit[2], (parseInt(currentDatesplit[1])-1), currentDatesplit[0]);
      calendar.gotoDate( date );
      calendar.changeView('resourceTimeGridDay');

      //call sidebar date loader
      var selectedDate = moment(date).format('YYYY-MM-DD');
      dashboardData(selectedDate);
    }
  }

  function showCreateAppointmentView(selectDate)
  {
      console.log("showCreateAppointmentView");
      calendar.gotoDate( selectDate );
      calendar.changeView('resourceTimeGridDay');
      const action = ADMINURL + "/dashboard/create";
      axios.get(action)
      .then(response => {

        const resp = response.data;
          // $("#frmAppointment").empty();
          $("#frmAppointment").html(resp);
          $("#addAppbutton").click();
          $('.select2').select2();

          $('#appointment_date').datepicker({
            // changeMonth: true,
            // changeYear: true,
            //dateFormat: 'yy-mm-dd', //swapnil commented on 10-jan-23
             dateFormat: 'dd-mm-yy', //swapnil added on 10-jan-23
            orientation: "bottom",
            autoclose: true,
            todayHighlight: true,
            startDate: new Date(),
           // minDate: 0  //commented by swapnil on 10-jan-23
          });

          $('#birth_date').datepicker({
              dateFormat: 'dd-mm-yy',
              changeMonth: true,
              changeYear: true,
              yearRange: '1920:+0',
              startDate: new Date('1920-01-01'),
              maxDate: 0
          });
          $('#search_birth_date').datepicker({
              dateFormat: 'dd-mm-yy',
          });
          $('#birth_date').mask("99-99-9999");
          $('#search_birth_date').mask("99-99-9999");

          if(selectDate!='') {
            $("#appointment_date").datepicker("setDate", selectDate);
          }
          $("body").on('keyup','#suggesstion_patient_id', function()
          {
            var searchKey = $(this).val();
            var birthdateKey = $("#search_birth_date").val();
            if(searchKey=='' && birthdateKey ==''){
              $("#suggesstion-box-patient").empty();
            }
            else {
              $.ajax({
                type: "GET",
                url: ADMINURL + "/dashboard/patients",
                data: 'keyword=' + searchKey+'&popup=1&birthdateKey='+birthdateKey,
                success: function (response)
                {
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
                    $("#suggesstion-box-patient").show();
                    if((response['data'].match(/option/g) || []).length > 2)
                    {
                      $("#suggesstion-box-patient").html(response['data']);
                    }
                    else {
                      var selectVar = '<select class="form-control" id ="patient_id" name="patient_id">';
                      var htmlData = response['data'].split(selectVar);
                      selectVar += '<option value=""> - - - - </option>'+htmlData[1];
                      $("#suggesstion-box-patient").html(selectVar);
                    }
                    $("#suggesstion-box-patient").on('change','#patient_id',function()
                    {
                      $("#search_birth_date").val($("#patient_id option:selected").attr('title'));
                    })
                    $('#search_birth_date').datepicker({
                      dateFormat: 'dd-mm-yyyy',
                    });
                    $("#suggesstion_patient_id").css("background", "#FFF");

                    document.getElementById("suggesstion_patient_id").addEventListener("search", function(event) {
                      $("#suggesstion-box-patient").empty();
                    });
                  }
                  else {
                    $("#suggesstion-box-patient").empty();
                  }
                }
              });
            }
          });

          $("#search_birth_date").change(function()
          {
            var searchKey = $("#suggesstion_patient_id").val();
            var birthdateKey = $(this).val();
            if(searchKey=='' && birthdateKey =='') {
              $("#suggesstion-box-patient").empty();
            }
            else {
              $.ajax({
                type: "GET",
                url: ADMINURL + "/dashboard/patients",
                data: 'keyword=' + searchKey+'&popup=1&birthdateKey='+birthdateKey,
                success: function (response)
                {
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
                    $("#suggesstion-box-patient").show();
                    if((response['data'].match(/option/g) || []).length > 2)
                    {
                      $("#suggesstion-box-patient").html(response['data']);
                    }
                    else {
                      var selectVar = '<select class="form-control" id ="patient_id" name="patient_id">';
                      var htmlData = response['data'].split(selectVar);
                      selectVar += '<option value=""> - - - - </option>'+htmlData[1];
                      $("#suggesstion-box-patient").html(selectVar);
                    }
                    $("#suggesstion_patient_id").css("background", "#FFF");

                    document.getElementById("suggesstion_patient_id").addEventListener("search", function(event) {
                      $("#suggesstion-box-patient").empty();
                    });
                  }
                  else {
                    $("#suggesstion-box-patient").empty();
                  }
                }
              });
            }
          });

          $('#new_patient_chkbox').click(function() {
            if($(this).prop("checked") == true) {
                $(".patient_details").show();
                $("#suggesstion_patient_div_id").hide();
                $("#search_birth_date_div").hide();
            }
            else if($(this).prop("checked") == false) {
                $(".patient_details").hide();
                $("#suggesstion_patient_div_id").show();
                $("#search_birth_date_div").show();
            }
        });
      })
      .catch(error => {
        // $('.card-body').LoadingOverlay("hide");
      })
  }

  function assignValueToText()
  {
    console.log($("#time_frame").val());
    $("#time_frame1").val($("#time_frame").val());
    console.log($("#time_frame").val($("#time_frame").val()).attr('attr'));
    var time_frame_id = $('#time_frame option:selected').attr('lang');
    if(time_frame_id)
    {
      $.ajax({
        type: "POST",
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: ADMINURL + '/appointment/selectTimeFrame',
        data: 'time_frame_id=' + time_frame_id,
        success: function (response)
        {
          $('#roster_time_frame_id').val(time_frame_id);
        }
      });
    }
  }

  function getDoctorTimeFrames()
  {
    //console.log("getDoctorTimeFrames");
    var patient_id = $("#patient_id").val();
    var doctor_id = $("#doctor_id").val();
    var doctor_status = $('#doctor_id option:selected').attr('lang');
    var appointment_type_id = $("#appointment_type_id").val();
    var appointment_date = $("#appointment_date").val();
    if (appointment_type_id != "" )
    {
      var a_id = '';
      GetServices(appointment_type_id,patient_id,a_id);
    }
    if(doctor_status == 0)
    {
      $(".active_status").hide();
      $(".inactive_status").show();
      $("#time_slot").removeAttr('required'); $("#time_slot").removeAttr('data-error');
      return false;
    }
    else {
      $(".active_status").show();
      $(".inactive_status").hide();
    }
    //return false;

    if (doctor_id != "" && appointment_type_id != "" && appointment_date != "")
    {
      var action = ADMINURL + '/appointment/getDoctorTimeFrames';
      $('.modal-content').LoadingOverlay("show", {
        background: "rgba(165, 190, 100, 0.4)",
      });
      axios.post(action, {
          patient_id: patient_id,
          doctor_id: doctor_id,
          appointment_type_id: appointment_type_id,
          appointment_date: appointment_date,
          sel_time_frame: sel_time_frame
        })
        .then(response => {

          $('.modal-content').LoadingOverlay("hide");
          $("#time_frame").empty();
          $("#time_frame").html(response.data.html);

          if (response.data.msg) {
            toastr.error(response.data.msg);
          }
          /*plan_options=response.data.html;
          $("#material_"+index).html(response.data.html);
          */
        })
        .catch(error => {
          $('.modal-content').LoadingOverlay("hide");
        })
    }
    return false;
  }

  function GetServices(appointment_type_id,patient_id,a_id)
  {

     $("#s_button").prop("disabled", true);//added on 14-jan-26

    // console.log("dsjfdk");
    $.ajax({
      type: "POST",
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      url: ADMINURL + '/appointment/getServices',
      data: 'appointment_type_id=' + appointment_type_id+'&patient_id='+patient_id+'&a_id='+a_id,
      success: function (response)
      {
        fetchServices();
        $(".appointment_type_services").html(response.services);
         $("#s_button").prop("disabled", false);//added on 14-jan-26
      }
    });
  }

  function dashboardData(date)
  {
    $.ajax({
      type: "POST",
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      url: ADMINURL + "/dashboard/getSpecificDateRecords",
      data: 'date=' + date,
      beforeSend: function() {
        $("#dashboard_data").html('Loading....');
      },
      success: function (response)
      {
        $("#dashboard_data").html(response);
      }
    });
  }

  function getDoctorDates()
  {
    var doctor_id = $("#doctor_availability_id").val();
    if (doctor_id != "") {
      var action = ADMINURL + '/roster/getDoctorDates';
      $('#doctorAvailabilityModal .modal-body').LoadingOverlay("show", {
        background: "rgba(165, 190, 100, 0.4)",
      });
      axios.post(action, {
          doctor_id: doctor_id
        })
        .then(response => {
          const resp = response.data;
          // console.log(resp);
          $("#doctor_dates_id").html(resp.html);
          $('#doctorAvailabilityModal .modal-body').LoadingOverlay("hide");
        })
        .catch(error => {
          $('#doctorAvailabilityModal .modal-body').LoadingOverlay("hide");
        })
    }
    return false;
  }

  function getDoctorDutyRoster()
  {
    // console.log("getDoctorDutyRoster");
    var doctor_id = $("#doctor_availability_id").val();
    var doctor_date = $("#doctor_dates_id").val();
    //console.log(doctor_id);

    //$("#appointment_date").blur();
    //return false;
    if (doctor_id != "" && doctor_date!='') {
      var action = ADMINURL + '/roster/getDoctorDutyRoster';
      $('#doctorAvailabilityModal .modal-body').LoadingOverlay("show", {
        background: "rgba(165, 190, 100, 0.4)",
      });
      axios.post(action, {
          doctor_id: doctor_id,
          doctor_date: doctor_date
        })
        .then(response => {
          const resp = response.data;
          // console.log(resp);
          $("#doctorRosterData").html(resp.html);
          $('#doctorAvailabilityModal .modal-body').LoadingOverlay("hide");
        })
        .catch(error => {
          $('#doctorAvailabilityModal .modal-body').LoadingOverlay("hide");
        })
    }
    return false;
  }

//Added by Shyam 06-01-22
$(document).ready(function()
{
  setInterval(function()
  {
      var currDate = new Date();
      var currTime = currDate.getHours();
      if(Number(currTime) > 23)
      {
          $.ajax({
            type: "GET",
            headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: ADMINURL + "/dashboard/checkPatientAgeReminder",
            data: '',
            success: function(response)
            {
              console.log(response);
              console.log('success');
            }
          });
      }
  }, 1800000);
});
//Added by Shyam 06-01-22

  /*Code Added by Shyam 22-02-22 */
  function fetchServices()
  {
    var birth_date = $('#birth_date').val();
    birth_date = birth_date.split("-").reverse().join("-");
    var appointment_type_id = $('#appointment_type_id').val();
    if(birth_date != '' && appointment_type_id != '' && $('#new_patient_chkbox').prop("checked") == true)
    {
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
            $(".appointment_type_services").append(response.services);
          }
        });
      }, 1000);
    }
  }
  /*Code Added by Shyam 22-02-22 */

// processing message in german
$.extend( $.fn.dataTable.defaults, {
    language: {
        "processing": "Verarbeitung..."
    },
});