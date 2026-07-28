var events;
var calendar;

var busy = false;
var limit  = 200
var offset = 0;
var todo_flag = 0;
var dismissal_flag = 0;

// Refresh TODO list 
// Refresh dismissal 

$(function () {

  setInterval(function() 
  {
    // window.location.reload();  
    var dismissalcnt = examinaton_cnt = 0;
    // if($('.active').hasClass('dismissalCls'))
    // {
        if($('#hd_dismissal_cnt').val() !=undefined)
        {
          var dismissalcnt = $('#hd_dismissal_cnt').val();
        }

        if($('#hd_examinaton_cnt').val() !=undefined)
        {
          var examinaton_cnt = $('#hd_examinaton_cnt').val();
        }
        
        $.ajax({
            type: "GET",
            url: ADMINURL + "/assistant-dashboard/getDismissalCount",
            data: 'count=' +  $('#btn_dismissal_cnt').text()+'&dismissalcnt='+dismissalcnt+'&examinaton_cnt='+examinaton_cnt, 
            success: function (response) 
            {
              if(response == 1)
              {
                //$('.dismissal_frm').LoadingOverlay("show");

                $.ajax({
                      type: "GET",
                      url: ADMINURL + "/assistant-dashboard/getDismissalRefreshData",
                      data: 'count=' + $('#btn_dismissal_cnt').text(),
                      success: function (response) 
                      {
                        var res = response.split("****");
                       
                        $('#dismissal_list').html(res[0]);
                        $('#btn_dismissal_cnt').html(res[1]);
                       
                        $('.dismissal_frm').LoadingOverlay("hide");
                      }
                });
              }
              $('.dismissal_frm').LoadingOverlay("hide");
            }
        });   
    //}

    if($('.active').hasClass('refreshclass'))
    {
      $.ajax({
        type: "GET",
        url: ADMINURL + "/assistant-dashboard/getTodoListCount",
        data: 'count=' + $('#btn_todoList_cnt').text(), 
        success: function (response) 
        {
          if(response != 0)
          {
            $('#totdoList-tab').html('<button id="btn_todoList_cnt" type="button" class="btn btn-primary btn-cnt">'+response+'</button>'+todolist_title);
            displayRecords(limit, offset);
          }
        }
      });
    }
  }, 10000);

  $('#birth_date').mask("99-99-9999");   
  $('#search_birth_date').mask("99-99-9999");   
  $('#appointment_date').mask("9999-99-99");


// Todo List 
// displayRecords(limit, offset); //Commented by Shyam 29-12-21
//Added by Shyam 29-12-21
$.ajax({
  type: "GET",
  url: ADMINURL + "/assistant-dashboard/getTodoListCount",
  data: 'count=' + $('#btn_todoList_cnt').text(), 
  success: function (response) 
  {
    if(response != 0)
    {
      $('#totdoList-tab').html('<button id="btn_todoList_cnt" type="button" class="btn btn-primary btn-cnt">'+response+'</button>'+todolist_title);
    }
  }
});
setTimeout(function(){
    displayRecords(limit, offset);
}, 2000);
// $("#totdoList-tab").on("click", function()
// {
//   $('#appoinmant_list-tab').removeClass('active');
//   $('#appoinmant_list').removeClass('active show');
//   setTimeout(function(){
//     $('#totdoList-tab').addClass('active');
//     $('#totdoList').addClass('active show');
//   },1000);
//   displayRecords(limit, offset);
// });

if(todo_flag == 0)
{
  todo_flag = 1;
  offset = limit + offset;
}
// End TodoList Function
//End Dismissal tab

    $('#results').LoadingOverlay("show", {
      background: "rgba(165, 190, 100, 0.4)",
    });

    setTimeout(function(){
      $('#results').LoadingOverlay("hide", {
        background: "rgba(165, 190, 100, 0.4)",
      });
    },1000);

$("#send_findings").click(function()
{
  $("#frm_send_findings").submit();
  $('#send_findings').prop('disabled', true);

});

if(imp_patient_id!='')
{
  $('#btn_finding_'+imp_patient_id).trigger('click');

  toastr.success(finding_imp_suc);
}

$(".copy_last_name").click(function()
{

  copyToClipboard($(this).attr('lang'));
})
// submitting form after validation
$('#importFindingFrm').validator().on('submit', function (e)  
{
    if (!e.isDefaultPrevented()) {

        const $this = $(this);
        const action = $this.attr('action');
        const formData = new FormData($this[0]);
        //formData.append('description', editor.getData());
        
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

$("#dashboard_data").css('display','block');

  $('#new_patient_chkbox').click(function(){
        if($(this).prop("checked") == true){
            $(".patient_details").show();
            $("#suggesstion_patient_div_id").hide();
             $("#search_birth_date_div").hide();
        }
        else if($(this).prop("checked") == false){
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
        startDate: new Date('1920-01-01')    
  });
  $('#search_birth_date').datepicker({ 
      dateFormat: 'dd-mm-yy',        
  });

  $(".btnClosePopup").click(function () {
    $("#appointmentModal").hide(); 
  });

  $("body").on('keyup','#suggesstion_patient_id',function () 
  {  
          $("#search_birth_date").val('');
          var searchKey = $(this).val();          
           var birthdateKey = $("#search_birth_date").val();
          if(searchKey=='' && birthdateKey ==''){          
            $("#suggesstion-box-patient").empty();
          }else{

            $.ajax({
              type: "GET",
              url: ADMINURL + "/assistant-dashboard/patients",
              data: 'keyword=' + searchKey+'&popup=1&birthdateKey='+birthdateKey, 
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
                  $("#suggesstion-box-patient").show();
                  $("#suggesstion-box-patient").html(response['data']);
                  $("#suggesstion-box-patient").on('change','#patient_id',function()
                  {
                    $("#search_birth_date").val($("#patient_id option:selected").attr('title'));
                  });
			
		  var dob = $("#patient_id option:first").attr('title');
                  $("#search_birth_date").val(dob);

                  $('#search_birth_date').datepicker({ 
                    dateFormat: 'dd-mm-yy', 
                     
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
   $("#search_birth_date").change(function () {
          
          var searchKey = $("#suggesstion_patient_id").val();
          var birthdateKey = $(this).val();
          if(searchKey=='' && birthdateKey ==''){
            $("#suggesstion-box-patient").empty();
          }else{

            $.ajax({
              type: "GET",
              url: ADMINURL + "/assistant-dashboard/patients",
              data: 'keyword=' + searchKey+'&popup=1&birthdateKey='+birthdateKey, 
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
                  $("#suggesstion-box-patient").show();
                  $("#suggesstion-box-patient").html(response['data']);
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

  /* initialize the calendar
    -----------------------------------------------------------------*/
  //Date for the calendar events (dummy data)
  var date = new Date()
  var d = date.getDate(),
    m = date.getMonth(),
    y = date.getFullYear()

  

  var Calendar = FullCalendar.Calendar;

  var calendarEl = document.getElementById('calendar');

  var tooltip = $('<div/>').qtip({
        id: 'calendar',
        prerender: true,
        content: {
            text: ' ',
           /* title: {
                button: true
            }*/
        },
        position: {
            my: 'bottom center',
            at: 'top center',
            target: 'mouse',
            viewport: $('#calendar'),
            adjust: {
                mouse: false,
                scroll: false
            }
        },
        show: false,
        hide: false,
        style: 'qtip-light'
    }).qtip('api');

  /* filter the calendar
   -----------------------------------------------------------------*/
  $('#suggesstion-box,#suggesstion-box1').on('change', function () {
    calendar.render();
  })


  var activeLanguage =  $(".activeLanguage").attr('lang');
  
  var activeLanguage =  $(".activeLanguage").attr('lang');
      calendar = new Calendar(calendarEl, {
      schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
      initialView: 'resourceTimeGridDay',  
      height: 'auto', 
      dayMaxEvents: true, 
      slotMinTime : "07:00:00",
      slotDuration :'00:10:00',
     // scrollTime: '00:00', // undo default 6am scrollTime
      allDaySlot :true,
      resourceOrder: '-type1,type2',
      filterResourcesWithEvents:true,

      eventBackgroundColor: "#de1f1f",
      slotEventOverlap:false,
      headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,resourceTimeGridWeek,resourceTimeGridDay'
      },
      stickyHeaderDates:true,
      views: {
        resourceTimeGridDay: { // name of view
        titleFormat: { weekday:'short',year: 'numeric', month: 'long', day: '2-digit' }
        // other view-specific options here
        }
      },
      eventDisplay: 'block',
      eventOverlap: false, // will cause the event to take up entire resource height
      resourceAreaWidth: '100%',
      resources: { // you can also specify a plain string like 'json/resources.json'
        url: ADMINURL + "/assistant-dashboard/getResourceId",
        
      },
      refetchResourcesOnNavigate: true,

  
    locale: 'de',
    events: {
      url: ADMINURL + '/assistant-dashboard/calendar/getEvents',
       extraParams: {
        patient_name: ''
      },
    },
    selectable: true,
    displayEventEnd: true,
    refetchResourcesOnNavigate:true,
    timeFormat: 'h:mma',
    contentHeight: 'auto',
    expandRows:'true',
    // editable: true,
    dayClick: function(date, jsEvent, view) { tooltip.hide(); },
    eventResizeStart: function() { tooltip.hide() },
    eventDragStart: function() { tooltip.hide() },

    eventMouseEnter: function(calEvent) 
    {          

      // console.log(calEvent);
      //  console.log(calEvent.event._def.extendedProps); 
     var type = (calEvent.event.title).split('-');
     var note = (calEvent.event._def.extendedProps.description).split('Notizen:</strong>');
     note = note[1].split('</p>')[0];
     var start = calEvent.event.start.getHours()+':'+calEvent.event.start.getMinutes();  
     var end = calEvent.event.end.getHours()+':'+calEvent.event.end.getMinutes();
        content = `<p><strong>Patient:</strong>`
        +calEvent.event._def.extendedProps.patient_name+`</p><p><strong>Arzt:</strong>`+
        calEvent.event._def.extendedProps.doctor_name+`</p><p><strong>Typ:</strong>`+type[1]+`</p><p><strong>Beginn: </strong>`
        +start+` - `+end+`
        <p></p><p><strong>Notizen: </strong>`+note+`</p>`; 

      tooltip.set({
                'content.text': content
            })
      .reposition(calEvent.event).show(calEvent.event);

      $(this.el).mouseover(function(e) {
      $(this.el).css('z-index', 10000);
      }).mousemove(function(e) {
      $('#qtip-calendar').css('top', e.pageY + 10);
      $('#qtip-calendar').css('left', e.pageX + 20);
      });
    },
    eventMouseLeave: function(calEvent, jsEvent) {
      $(this.el).css('z-index', 8);
      $('#qtip-calendar').css('display','none');
    },
    eventClick: function (info) 
    {
      $('#editAppointmentModal').attr('data-id', info.event.id);
      $('#deleteAppointmentModal').attr('data-id', info.event.id);
      $('#redirectToPatient').attr('data-id', info.event.id);
      //$("#appointmentModal .modal-body #popup_description").html(info.event._def.extendedProps.description);
      $("#appointmentModal .modal-body #qr_code").html('');
      
      $.getScript('https://puregyn.dextra-data.at/assets/admin/js/dashboard/qrcode.js', function( data, textStatus, jqxhr ) {
      $("#appointmentModal .modal-body #qr_code").qrcode(info.event._def.extendedProps.qr_code);
      } );

      var google_e_id = info.event.id;
      const action = ADMINURL + "/assistant-dashboard/view/" + google_e_id;

      axios.get(action)      
      .then(response => {
        const resp = response.data;      
        if(resp)
        {
          $("#appointmentModal .modal-body #popup_description").html(resp);
         
        }
        else
        {
          $("#appointmentModal .modal-body #popup_description").html(info.event._def.extendedProps.description);
         // qrcode.makeCode("addme");
        }
      })
      .catch(error => {
        // $('.card-body').LoadingOverlay("hide");
      })      
    
      $("#appointmentModal").show();
    },

    eventDidMount: function(event) {
    
      var showTypes, event_patient_id, showFacilities, showSearchTerms = true;      
      var patient_id = $('#patient-id').val();
      var doctor_id = $('#doctor-id').val(); 
      var selected_patient_email = '';
      var email_record_exist = $("#getPatientsData").val();
      if (typeof email_record_exist === "undefined" ){       
      }else {        
       showSearchTerms = event.event._def.extendedProps.patient_name.toLowerCase().indexOf(email_record_exist.toLowerCase()) >= 0 || event.event._def.extendedProps.patient_name.toLowerCase().indexOf(email_record_exist) >= 0;
      }
      
      if(showSearchTerms)
      {
        event.event.setProp('display','block')
      }else
      {
         event.event.setProp('display','none')
      }
      
      return showSearchTerms;
      
    }, //end: eventRender
  resourceLabelDidMount: function(arg) {
      arg.el.addEventListener('click', function() {
        console.log(arg)
      })
    },
    select: function (date,start, end, jsEvent) {
      var selectDate = date.start;
      var selectedDate = moment(selectDate).format('YYYY-MM-DD');
      showCreateAppointmentView(selectedDate);
      dashboardData(selectedDate);
    }
  })


  calendar.render();
  if(activeLanguage == ' de ')
  {
    calendar.setOption('locale','de');  
  }

  //Autocomplete features
  $('input[name="name"]').focus();
  $('#appointment_date').datepicker({
  
    dateFormat: 'yy-mm-dd',
    orientation: "bottom",
    autoclose: true,
    todayHighlight: true,
    startDate: new Date(),
    minDate: 0
  });


 $("#addAppbutton").click(function()
 {
   $('#s_button').removeClass('disabled');
 });

  // $('#frmAppointment').validator().on('submit', function (e) {
   
  //   $('#s_button').removeClass('disabled');
  //   if (!e.isDefaultPrevented()) {
  //     const $this = $(this);
  //     const action = $this.attr('action');
  //     const formData = new FormData($this[0]);
  //     $('#s_button').removeClass('disabled');
  //     $('#frmAppointment,.model-body').LoadingOverlay("show", {
  //       background: "rgba(165, 190, 100, 0.4)",
  //     });

  //     var patient_id = $("#patient_id").val();
  //     if(patient_id==undefined){
  //       patient_id = '';
  //     }
  //     formData.append('patient_id', patient_id);

  //     axios.post(action, formData)
  //       .then(function (response) {
  //         const resp = response.data;
  //         $('#s_button').removeClass('disabled');
  //         if (resp.status == 'success') {
  //           $this[0].reset();
  //           toastr.success(resp.msg);
  //           $('#frmAppointment,.model-body').LoadingOverlay("hide");
          
  //           $(".btnClosePopup").click();
  //           calendar.refetchEvents();
  //         }

  //         if (resp.status == 'error') {
  //           console.log(error);
  //           $('#frmAppointment,.model-body').LoadingOverlay("hide");
  //           toastr.error(resp.msg);
  //           $('#s_button').removeClass('disabled');
  //           const errorBag = resp.errors;

  //           $.each(errorBag, function (fieldName, value) {
  //             console.log(fieldName);
  //             $('.err_' + fieldName).closest('.form-group').addClass('has-error has-danger');
  //             $('.err_' + fieldName).text(value[0]).closest('span').show();
  //           })
  //         }
  //       })
  //       .catch(function (error) {
  //         console.log(error);
  //         $('#frmAppointment,.model-body').LoadingOverlay("hide");
  //         $('#s_button').removeClass('disabled');
  //         const errorBag = error.response.data.errors;

  //         $.each(errorBag, function (fieldName, value) {
  //           console.log(fieldName);
  //           $('.err_' + fieldName).closest('.form-group').addClass('has-error has-danger');
  //           $('.err_' + fieldName).text(value[0]).closest('span').show();
  //         })
  //       });
  //       $('#s_button').removeClass('disabled');
  //     return false;
  //    }
  // })

  $('#frmAppointment').validator().on('submit', function (e) 
  {
      const $this = $(this);
      const action = $this.attr('action');
      const formData = new FormData($this[0]);
      console.log(formData);
      $('#frmAppointment,.model-body').LoadingOverlay("show", {
        background: "rgba(165, 190, 100, 0.4)",
      });

      var patient_id = $("#patient_id").val();
      if(patient_id==undefined){
        patient_id = '';
      }
      formData.append('patient_id', patient_id);

      axios.post(action, formData)
        .then(function (response) {
          const resp = response.data;

          if (resp.status == 'success') {
            $this[0].reset();
            toastr.success(resp.msg);
            $('#frmAppointment,.model-body').LoadingOverlay("hide");           
            $(".btnClosePopup").click();
            calendar.refetchEvents();
          }

          if (resp.status == 'error') {

            $('#frmAppointment,.model-body').LoadingOverlay("hide");
            toastr.error(resp.msg);
            const errorBag = resp.errors;
            $.each(errorBag, function (fieldName, value) {
              $('.err_' + fieldName).closest('.form-group').addClass('has-error has-danger');
              $('.err_' + fieldName).text(value[0]).closest('span').show();
            })
          }

          if (resp.status == undefined) {
           
            $('#frmAppointment,.model-body').LoadingOverlay("hide");
            toastr.error(resp.msg);
            const errorBag = resp.errors;
            $.each(errorBag, function (fieldName, value) {
              $('.err_' + fieldName).closest('.form-group').addClass('has-error has-danger');
              $('.err_' + fieldName).text(value[0]).closest('span').show();
            })
          }
        })
        .catch(function (error) {
            $('#frmAppointment,.model-body').LoadingOverlay("hide", {
              background: "rgba(165, 190, 100, 0.4)",
            });

          $('#frmAppointment,.model-body').LoadingOverlay("hide");
          const errorBag = error.response.data.errors;
          $.each(errorBag, function (fieldName, value) {
             $('#frmAppointment,.model-body').LoadingOverlay("hide", {
              background: "rgba(165, 190, 100, 0.4)",
            });

            $('.err_' + fieldName).closest('.form-group').addClass('has-error has-danger');
            $('.err_' + fieldName).text(value[0]).closest('span').show();
          })
        });

      return false;
  })

  $("#patient-id").keyup(function () {
    
    var searchKey = $(this).val();
    if(searchKey==''){
      $("#suggesstion-box").empty();
    }else{

      $.ajax({
        type: "GET",
        url: ADMINURL + "/assistant-dashboard/patients",
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



  $("#doctor-id").keyup(function () {

    var searchKey = $(this).val();
    if(searchKey==''){
      $("#suggesstion-box1").empty();
    }else{
      $.ajax({
        type: "GET",
        url: ADMINURL + "/assistant-dashboard/doctors",
        data: 'keyword=' + searchKey, 
        success: function (response) {

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


  $("#deleteAppointmentModal").on("click", function () {
    var userId = $(this).attr('data-id');
    $.ajax({
      url: ADMINURL + "/assistant-dashboard/destroy/" + userId,
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

  $("#editAppointmentModal").on("click", function () {
      
      $("#appointmentModal").hide();
      // $('#addAppointmentModal').modal('show');

      const google_event_id = $(this).attr('data-id');
      const action = ADMINURL + "/assistant-dashboard/edit/" + google_event_id;

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
              url: ADMINURL + "/assistant-dashboard/patients",
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

  $("#redirectToPatient").on("click", function () {
      
      const google_event_id = $(this).attr('data-id');
      const action = ADMINURL + "/assistant-dashboard/redirect/" + google_event_id;


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

     


  $('#frmAppointmentEdit').on('submit', function (e) {
      if (!e.isDefaultPrevented()) {

        const $this = $(this);
        const action = $("#update_url").val();

        const formData = new FormData($this[0]);

        var patient_id = $("#patient_idedit").val();
        if(patient_id==undefined){
          patient_id = '';
        }
       
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


  /* Added By swati */
  selectDate = calendar.getDate();
  selectedDate = moment(selectDate).format('YYYY-MM-DD');
  dashboardData(selectedDate);

  $(".fc-today-button").click(function() {
    selectDate = calendar.getDate();
    selectedDate = moment(selectDate).format('YYYY-MM-DD');
    dashboardData(selectedDate);
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

  $("body").on('click','#cancel_notice',function()
  {
     $("#calender_notice").hide();
    $("#notice_edit_click").show();
    $(".button_section").hide();
    $("#err_contact_name").hide();
    $("#calender_notice").removeClass('custom_validation');
  });

  $("body").on('click',".click_me",function(){
      $('.click_me').prop('checked', false);
       $(this).prop('checked', true);
      $(".hide_type").css('display','none');
      
      $("#type"+$(this).attr('id')).css('display','block');    
  });

  $(document).on('click','#calender_notice',function()
  {
    $("#calender_notice").removeClass('custom_validation');
    $("#err_contact_name").css('display','none');
  });

  $(document).on('click','#close',function()
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
          url: ADMINURL + "/assistant-dashboard/addUpdateNotices",
          data: 'data=' + data+"&selectedDate="+selectedDate,      
          success: function (response) 
          {       
            $("#notice_edit_click").html($("#calender_notice").val());
            $("#notice_edit_click").show();
            $("#calender_notice").hide();
             $(".button_section").hide();
          }
        });
   

  });
  /* End */
});

// -------------------scroller--------------------------------------------------------


$(window).scroll(function() { //watches scroll of the window
  // var busy = false;
  // var limit = 20
  // var offset =20;

  busy = true;
  if($('.active').hasClass('refreshclass'))
  {

     // if($(window).scrollTop() + $(window).height() > $(document).height() - 100) 
    // if($(window).scrollTop() + $(window).height() > ($(document).height() - 100) / 2.5)
    // {
    //   // $('.content-wrapper').LoadingOverlay("show");    
    //   // displayRecords(limit, offset);
    //   // offset = limit + offset;
    // }
  }

  var sticky = $('.sticky'),
  scroll = $(window).scrollTop();
  if($(window).scrollTop() > 200)
  {
    sticky.addClass('fixed');
    //$(".fc-scrollgrid-section-sticky").addClass('fixed');
  }
  else 
  { 
    sticky.removeClass('fixed');
    //$(".fc-scrollgrid-section-sticky").removeClass('fixed');
  }

});

function displayRecords(lim, off) {
  $.ajax({
    type: "GET",
    async: false,
    url: ADMINURL + "/assistant-dashboard/todoList",
    data: "limit=" + lim + "&offset=" + off,
    cache: false,
    beforeSend: function() {
      $("#loader_message").html("").hide();
      $('#loader_image').show();
    },
    success: function(html) {
      $("#results").html('');
      $("#results").append(html);
      $('.content-wrapper').LoadingOverlay("hide");
      if(imp_patient_id!='')
      {
        // $('#sub_'+imp_patient_id).css('display','block');
        // $('#div_'+imp_patient_id).removeClass('collapsed-card');
        // $('.span_cls').show();
        // $('.old-appoinmant').show();
        // $('.new-patients').hide();
        // $('.new-patients-btn').hide();
        //toastr.success(finding_imp_suc);
      }
      // window.busy = false;
    }
  });
}

// $('#calendar').find('.fc-time-grid-event,.fc-event,.fc-start,.fc-end').addClass('fc-short');

  function gotoDate(element) {

    var currentDate = $(element).val();
    if(currentDate!=''){
      var currentDatesplit = currentDate.split('-');
      var date = new Date(currentDatesplit[2], (parseInt(currentDatesplit[1])-1), currentDatesplit[0]);
      calendar.gotoDate( date );
      calendar.changeView('resourceTimeGridDay');
    }
  }
 
function showCreateAppointmentView(selectDate){

  //console.log("showCreateAppointmentView");
    calendar.gotoDate( selectDate );
    calendar.changeView('resourceTimeGridDay');
    const action = ADMINURL + "/assistant-dashboard/create";
    axios.get(action)
    .then(response => {

      const resp = response.data;

       
        $("#frmAppointment").html(resp);

        $("#addAppbutton").click();
        $('.select2').select2();

        $('#appointment_date').datepicker({
          
          dateFormat: 'yy-mm-dd',
          orientation: "bottom",
          autoclose: true,
          todayHighlight: true,
          startDate: new Date(),
          minDate: 0
        });

        $('#birth_date').datepicker({ 
            dateFormat: 'dd-mm-yy',   
            changeMonth: true,
        changeYear: true, 
        yearRange: '1920:+0',
        startDate: new Date('1920-01-01')    
        });

        $('#search_birth_date').datepicker({ 
            dateFormat: 'dd-mm-yy',  
        });
        
        $('#birth_date').mask("99-99-9999");    
        $('#appointment_date').mask("9999-99-99");   
        $('#search_birth_date').mask("99-99-9999"); 

        if(selectDate!=''){
          $("#appointment_date").datepicker("setDate", selectDate);
        }

         $("body").on('keyup','#suggesstion_patient_id',function () {
    
          var searchKey = $(this).val();
           
          var birthdateKey = $("#search_birth_date").val();
          if(searchKey=='' && birthdateKey ==''){
            $("#suggesstion-box-patient").empty();
          }else{

            $.ajax({
              type: "GET",
              url: ADMINURL + "/assistant-dashboard/patients",
              data: 'keyword=' + searchKey+'&popup=1&birthdateKey='+birthdateKey, 
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
                  $("#suggesstion-box-patient").show();
                  $("#suggesstion-box-patient").html(response['data']); 
                  $("#suggesstion-box-patient").on('click','#patient_id',function()
                  {
                    $("#search_birth_date").val($("#patient_id option:selected").attr('title'));
                  })
                  $('#search_birth_date').datepicker({ 
                    dateFormat: 'dd-mm-yy',
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

        $("#search_birth_date").change(function () {
          //console.log('asdasd');
          var searchKey = $("#suggesstion_patient_id").val();
          var birthdateKey = $(this).val();
          if(searchKey=='' && birthdateKey ==''){
            $("#suggesstion-box-patient").empty();
          }else{

            $.ajax({
              type: "GET",
              url: ADMINURL + "/assistant-dashboard/patients",
             data: 'keyword=' + searchKey+'&popup=1&birthdateKey='+birthdateKey, 
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
                  $("#suggesstion-box-patient").show();
                  $("#suggesstion-box-patient").html(response['data']);
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

        $('#new_patient_chkbox').click(function(){
          if($(this).prop("checked") == true){
              $(".patient_details").show();
              $("#suggesstion_patient_div_id").hide();
              $("#search_birth_date_div").hide();
          }
          else if($(this).prop("checked") == false){
              $(".patient_details").hide();
              $("#suggesstion_patient_div_id").show();
              $("#search_birth_date_div").show();
          }
      });

    })
    .catch(error => {
      
    })
}


$("#app_reset").click(function()
{
  $("#select2-doctor_id-container").html('');
  $("#select2-appointment_type_id-container").html('');
  $("#suggesstion-box-patient").html('');
});


$('.content-wrapper').on("mouseover","#frmAppointment",function()
{
  $('#s_button').removeClass('disabled');
});

function assignValueToText()
{  
  $("#time_frame1").val($("#time_frame").val());
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
    }else
    {
      $(".active_status").show();
      $(".inactive_status").hide();
    }
  //return false;

  console.log(doctor_id);
  console.log(appointment_type_id);
  console.log(appointment_date);

  if (doctor_id != "" && appointment_type_id != "" && appointment_date != "") {
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
       
      })
      .catch(error => {
        $('.modal-content').LoadingOverlay("hide");
      })
  }

  return false;
}

  function GetServices(appointment_type_id,patient_id,a_id)
  {      
    $.ajax({
      type: "POST",
      headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      url: ADMINURL + '/appointment/getServices',
      data: 'appointment_type_id=' + appointment_type_id+'&patient_id='+patient_id+'&a_id='+a_id, 
      success: function (response) 
      {
       
        $(".appointment_type_services").html(response.services);
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
      url: ADMINURL + "/assistant-dashboard/getSpecificDateRecords",
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
function getDoctorDates() {
 
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

function getDoctorDutyRoster() {
  

  var doctor_id = $("#doctor_availability_id").val();
  var doctor_date = $("#doctor_dates_id").val();

  
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
        
        $("#doctorRosterData").html(resp.html);
        $('#doctorAvailabilityModal .modal-body').LoadingOverlay("hide");
      })
      .catch(error => {
        $('#doctorAvailabilityModal .modal-body').LoadingOverlay("hide");
      })
  }

  return false;
}


// processing message in german
$.extend( $.fn.dataTable.defaults, {
    language: {
        "processing": "Verarbeitung..."
    },
});

// Google calender heading date(title center) customer date add
$( document ).ready(function() 
{
  var mL = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
  var days = ['Sun', 'Mon', 'Tues', 'Wed', 'Thu', 'Fri', 'Sat'];
  var date = new Date()

  var d = date.getDate(),
    m = mL[date.getMonth()];
    y = date.getFullYear()
    n = days[date.getDay()];
   
    if(d>10)
    {
      var vday = '0'+d;
    }
    else
    {
      var vday = d;
    }

    $('.fc-center').html('<h2>'+n+', '+d+'.'+m+'.</h2>');

   

});



//});
 




function copyToClipboard(id) 
{
  // $('#collapseFinding_'+id).fadeOut();
  // $('#collapseNew_'+id).fadeOut();
  var id = 'input_'+id;
  var elem = document.getElementById(id)
  var targetId = "_hiddenCopyText_";
  var isInput = elem.tagName === "INPUT" || elem.tagName === "TEXTAREA";
  var origSelectionStart, origSelectionEnd;
  if (isInput) 
  {
    target = elem;
    origSelectionStart = elem.selectionStart;
    origSelectionEnd = elem.selectionEnd;
  } 
  else 
  {
    target = document.getElementById(targetId);
    if (!target) 
    {
        var target = document.createElement("textarea");
        target.style.position = "absolute";
        target.style.left = "-9999px";
        target.style.top = "0";
        target.id = targetId;
        document.body.appendChild(target);
    }
    target.textContent = elem.textContent;
  }
    // select the content
    var currentFocus = document.activeElement;
    target.focus();
    target.setSelectionRange(0, target.value.length);
    
    // copy the selection
    var succeed;
    try {
        succeed = document.execCommand("copy");
    } catch(e) {
        succeed = false;
    }
    // restore original focus
    if (currentFocus && typeof currentFocus.focus === "function") {
      
        currentFocus.focus();
    }
    
    if (isInput) {
        // restore prior selection
        elem.setSelectionRange(origSelectionStart, origSelectionEnd);
    } else {
        // clear temporary content
        target.textContent = "";
    }
    if(succeed == true)
    {
      toastr.success(copy_success_msg);
    }
    else
    {
      toastr.error(copy_error_msg);
    }
    // $('#div_'+id).addClass('collapsed-card');
    // $('#sub_'+id).css('display','none');
    //return false;
}

function copyTopatientDetails(type,id)
{
  var name = type+'_'+id;
  var copyText = document.getElementById(name);
  //console.log(name); console.log(copyText);
  copyText.select();
  copyText.setSelectionRange(0, 99999)
  document.execCommand("copy");
  toastr.success(copy_success_msg);
}

function completedNew(element,p_id,type,coll_type)
{
  var element = element;
  var $this = $(element);
  
  swal({
      title: deleteContent.title,
      text: warning_mesg,
      type: "warning",
      showCancelButton: true,
      cancelButtonText: deleteContent.cancel,
      confirmButtonText: warning_yes,
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
          url: ADMINURL + "/assistant-dashboard/clearTodoList",
          data: 
          {
            'p_id': p_id,
          },
          success: function (response) 
          {
            if(response!='')
            {
              if(coll_type =='new')
              {
                if(type == 'next')
                {
                  var btn_id = $this.closest('.newClass').nextAll('.clgtoggle').find('.btn_next_cls').first().attr('id');
                  if(typeof btn_id !== 'undefined')
                  {
                    $('#'+btn_id).trigger('click');
                  }
                  else
                  {
                    var btn_id = $this.closest('.updateClass').nextAll('.clgtoggle').find('.btn_next_cls').first().attr('id');
                    if(typeof btn_id !== 'undefined')
                    {
                      $('#'+btn_id).trigger('click');
                    }
                    else
                    {
                      var btn_id = $this.closest('.findClass').nextAll('.clgtoggle').find('.btn_next_cls').first().attr('id');
                      $('#'+btn_id).trigger('click');
                    }
                  }
                }
                $('#collapseNew_'+p_id).fadeOut();
                $('#collapseFinding_'+p_id).fadeOut(); 
                $('#collapseUpdate_'+p_id).fadeOut();
              }
              else if(coll_type =='update')
              {
                
                if(type == 'next')
                {
                  var btn_id = $this.closest('.collapse').nextAll('.clgtoggle').find('.btn_next_cls').first().attr('id');
                  if(typeof btn_id !== 'undefined')
                  {
                    $('#'+btn_id).trigger('click');
                  }
                  else
                  {
                    var btn_id = $this.closest('.newClass').nextAll('.clgtoggle').find('.btn_next_cls').first().attr('id');
                    if(typeof btn_id !== 'undefined')
                    {
                      $('#'+btn_id).trigger('click');
                    }
                    else
                    {
                      var btn_id = $this.closest('.findClass').nextAll('.clgtoggle').find('.btn_next_cls').first().attr('id');
                      $('#'+btn_id).trigger('click');
                    }
                  }
                  
                }
                $('#collapseNew_'+p_id).fadeOut();
                $('#collapseFinding_'+p_id).fadeOut();
                $('#collapseUpdate_'+p_id).fadeOut();
              }
              else
              {
                if(type == 'next')
                {
                  var btn_id = $this.closest('.findClass').nextAll('.clgtoggle').find('.btn_next_cls').first().attr('id');
                  if(typeof btn_id !== 'undefined')
                  {
                    $('#'+btn_id).trigger('click');
                  }
                  else
                  {
                    var btn_id = $this.closest('.newClass').nextAll('.clgtoggle').find('.btn_next_cls').first().attr('id');
                    //$('#'+btn_id).trigger('click');
                    if(typeof btn_id !== 'undefined')
                    {
                      $('#'+btn_id).trigger('click');
                    }
                    else
                    {
                      var btn_id = $this.closest('.updateClass').nextAll('.clgtoggle').find('.btn_next_cls').first().attr('id');
                      $('#'+btn_id).trigger('click');
                    }
                  }
                  
                }
                $('#collapseNew_'+p_id).fadeOut();
                $('#collapseFinding_'+p_id).fadeOut();
                $('#collapseUpdate_'+p_id).fadeOut();
              }
              $('#main_'+p_id).remove();
         
              
            }
            else
            {
              toastr.error(completed_not_msg);
            }
          }
        });
    });
}

function CancelNew(element,id)
{
  $('#collapseNew_'+id).fadeOut();
  $('#collapseFinding_'+id).fadeOut();
  $('#collapseUpdate_'+id).fadeOut();
  $('#collapseUpdate_'+id).removeClass('show');
}

function getPatientsDiv(ganymed_id,id,type)
{
  if(type == 1)
  {
    if(!$('#collapseNew_'+id).is(':visible'))
    {
      $('#collapseNew_'+id).fadeIn();
      $('#collapseFinding_'+id).fadeOut();
       $('#collapseUpdate_'+id).fadeOut();
      $('#collapseNew_'+id).css('display','block')
      //$('#collapseFinding_'+id).css('display','none')
    }
    else
    {
      $('#collapseNew_'+id).fadeOut();
      $('#collapseFinding_'+id).fadeOut();
       $('#collapseUpdate_'+id).fadeOut();
      $('#collapseNew_'+id).css('display','none')
      //$('#collapseFinding_'+id).css('display','none')
    }
   
   
  }
  else if(type == 2)
  {
    if(!$('#collapseFinding_'+id).is(':visible'))
    {
      $('#collapseNew_'+id).fadeOut();
      $('#collapseUpdate_'+id).fadeOut();
      $('#collapseFinding_'+id).fadeIn();
      $('#collapseFinding_'+id).css('display','block')
    }
    else
    {
      
      $('#collapseNew_'+id).fadeOut();
       $('#collapseUpdate_'+id).fadeOut();
      $('#collapseFinding_'+id).fadeOut();
      $('#collapseFinding_'+id).css('display','none')
    } 
    
  }
  else if(type == 3)
  {
    $.ajax({
        headers: 
        {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        },
        type: "POST",
        url: ADMINURL + "/assistant-dashboard/checkRecordWithGanymed",
        data: 
        {
          'ganymed_id': ganymed_id,
          'id': id,
        },
        success: function (response) 
        {
         
          if(response !='')
          {
            $('.updateRec_'+ganymed_id).html(response);
          }
          else
          {
            $('.updateRec_'+ganymed_id).html('<h5>'+err_something_wrong+'</h5>');
            //$('.updateRec').html(response);
          }
        }
      });
    
      if(!$('#collapseUpdate_'+id).is(':visible'))
      {
        $('#collapseUpdate_'+id).fadeIn();
        $('#collapseNew_'+id).fadeOut();
        $('#collapseFinding_'+id).fadeOut();
        $('#collapseUpdate_'+id).css('display','block')
      }
      else
      {
        $('#collapseNew_'+id).fadeOut();
        $('#collapseUpdate_'+id).fadeOut();
        $('#collapseUpdate_'+id).css('display','none')
        $('#collapseFinding_'+id).fadeOut();
      
      }
   
    
  }
}

function showImportaFinding(element,id,p_id,date)
{
  $('#old_date_id').val(id);
  $('#hd_patient_id').val(p_id);
  $('#hd_date').val(date);

  $( "#btn-import-finding" ).trigger( "click" );
}

function removeClass(id,p_id)
{
    $('#main_'+p_id).remove();
    var url = ADMINURL + '/assistant-dashboard/viewPatientDetails/'+id
    window.open(url, '_blank');
 
 
}
function showSendFinding(element,type,id,old_id,send_type)
{
  console.log(type);
  if(type == 0)
  {
    //ON
    var patient_name = $('#first_name_'+id).val()+' '+$('#family_name_'+id).val();
    if($('#email_'+id).val() == 'undefined')
    {
      var email = '';
    }
    else
    {
      var email = $('#email_'+id).val();
    }
    
    // swal({
    // title: deleteContent.title,
    // text: msg_finding_via_mail,
    // type: title_warning,
    // showCancelButton: true,
    // cancelButtonText: deleteContent.cancel,
    // confirmButtonText: 'Ok',
    // confirmButtonClass: "btn-danger",
    // closeOnConfirm: true,
    // showLoaderOnConfirm: true
    // },
    // function ()
    // {
      
      $.ajax({
        headers: 
        {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        },
        type: "POST",
        url: ADMINURL + "/assistant-dashboard/getOldAppoinmant",
        data: 
        {
          'old_id': old_id,
        },
        success: function (response) 
        {
          $('#old_appoinment_date').html(response);
          
          if(send_type == 2)
          {
            $('#hd_noties_div').show();
            $('#hd_notes').val($('#notes').val());
          }
          else
          {
            $('#hd_finding_old_id').val(old_id);
          }
          $('#patient_name').val(patient_name);
          $('#to').val(email);
          $('#hd_finding_patient_id').val(id);
          $( "#btn-send-finding-via-email" ).trigger( "click" );

        }
      });
    // });
  }
  else
  {
    //OFF
    if(send_type == 2)
    {
      var old_id = old_id;
      var notes  = $('#notes').val();
    }
    else
    {
      var old_id = old_id;
      var notes  = null;
    }
    
    $.ajax({
      headers: 
      {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
      },
      type: "POST",
      url: ADMINURL + "/assistant-dashboard/pushNotificationForPetient",
      data: 
      {
        'p_id': id,
        'send_type':send_type,
        'old_id':old_id,
        'notes':notes,
      },
      success: function (response) 
      {
        
        if(response == 'true')
        {
          $(element).closest('.list-wrappper').next('.list-wrappper').children('div').removeClass('collapsed-card');
          $('#div_'+id).parent('.list-wrappper').remove()
          toastr.success(msg_msg_finding_push_notification);
        }
        else if(response == 'false')
        {
          toastr.error(err_something_wrong);
        }
        else
        {
          toastr.error(response);
        }
      }
    });

  }

}

function hideDiv(id)
{
  $('#collapseNew_'+id).fadeOut();
  $('#collapseFinding_'+id).fadeOut();
}



function dismissalDone(id)
{
 
  // $('.dismissal_done').on('click', function (e) 
  // {
    // const $this = $('#frm_'+$(this).attr('lang'));
    const $this = $('#frm_'+id);

    // const formData = $('#frm_'+$(this).attr('lang')).serializeArray();
    const formData = $('#frm_'+id).serializeArray();
   
    $.ajax({

      headers: 
      {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
      },
      type: "POST",
      url: ADMINURL + "/assistant-dashboard/dismissalDone",
      data: formData,
      success: function (response) 
      {
       
        var res = response.split("****");
        //if(response!='')
        //{
          toastr.success(success_msg);
          $('#dismissal_list').html(res[0]);
          $('#btn_dismissal_cnt').html(res[1]);
        // }
        // else
        // {
        //   toastr.error(error_msg);
        // }
      }

    });

  // }); 
}
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
  

$(window).ready(function()
{
  console.log("asdasd");

  setTimeout(function(){
   $("#myTab").find('.nav-link').removeClass('disabled');    
  },1000);

  
});