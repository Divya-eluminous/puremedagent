// var disabledDates = ["2020-02-03","2020-02-04","2020-02-05"];
var disabledDates = [];
function dateChanged(ev) {
    console.log('dateChanged');
    console.log(ev);
    console.log($(this));
  /*  $(this).datepicker('hide');
    if ($('#startdate').val() != '' && $('#enddate').val() != '') {
        $('#period').text(diffInDays() + ' d.');
    } else {
        $('#period').text("-");
    }*/
}
$(document).ready(function ()  
{
    // adding focus event to first field 
    $('input[name="name"]').focus();  

    //Datepicker
    /*$('.date').datepicker({ 
        format: 'yyyy-mm-dd',
         multidate: true 
    });*/

    $('.date').datepicker({ 
        format: 'yyyy-mm-dd',
        multidate: true,
        beforeShowDay: function (date) {
            var day = date.getDay();
            var string = $.datepicker.formatDate('yy-mm-dd', date);
            // var string = '2020-02-02';
            // console.log(string);

            if ($.inArray(string, disabledDates) != -1){
               return {
                  classes: 'disabled'
               };
            }
            return;
            
        } 
    });
    //.on('changeDate', dateChanged);

    //$("#date123").datepicker("setDate", ['2020-01-07','2020-01-06']);

    //Timepicker
    // $('.timepicker').datetimepicker({
    //    format: 'HH:mm',
    // }); 
    $('.timepicker').timepicker({
        // minuteStep: 1, 
        defaultTime:'',
        timeFormat: 'H:i',
        showInputs: false,
        showMeridian: false //24hr mode 
    });

}) 
 
// submitting form after validation   
$('#rosterForm').validator().on('submit', function (e)     
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

function addPlan() 
{
    // var counter = $(".plan").length;    
    var items = parseInt($("#total_items").val()) + 1;
    $("#total_items").val(items);
    var counter = items;    
    var plan_area = `<tr class="inner-td add_plan_area plan">                    
                    <td>
                        <div class="form-group"> 
                        <input 
                            type="text" 
                            name="date_data[${counter}][dates]" 
                            class="form-control date"
                            id="dates_${counter}" 
                            autocomplete="off"
                            required
                            data-error="Date field is required." 
                        >
                        <span class="help-block invalid-feedback with-errors">
                            <ul class="list-unstyled">
                                <li class="err_dates[${counter}]"></li>
                            </ul>
                        </span>
                    </div>
                    </td>
                    <td>
                        <div class="form-group">
                            <input 
                                type="text" 
                                class="form-control timepicker" 
                                name="date_data[${counter}][from_time]"
                                required 
                                id="from_time_${counter}" 
                                onchange="showTimeFrames(${counter})" 
                                data-error="@lang('admin.ERR_TIME_FROM_REQUIRED')." 
                                >
                            <span class="help-block invalid-feedback with-errors">
                                <ul class="list-unstyled">
                                    <li class="err_from_time[${counter}]"></li>
                                </ul>
                            </span>
                        </div>
                    </td>
                    <td>
                        <div class="form-group">
                            <input 
                                type="text" 
                                class="form-control timepicker"
                                name="date_data[${counter}][to_time]"
                               id="to_time_${counter}" 
                                onchange="showTimeFrames(${counter})" 
                                required 
                                data-error="@lang('admin.ERR_TIME_TO_REQUIRED')." 
                                >
                          
                            <span class="help-block invalid-feedback with-errors">
                                <ul class="list-unstyled">
                                    <li class="err_to_time[${counter}]"></li>
                                </ul>
                            </span>
                        </div>
                    </td>
                    <td>
                     <div class="form-group time_frames">
                        <select 
                            name="date_data[${counter}][time_frames][]"
                            required
                            data-error="Time Frame field is required."
                            class="form-control select2" 
                            id="time_frames_${counter}"
                            multiple="multiple" 
                            data-placeholder="Select Time Frames"
                            style="width: 100%;"
                            >
                        </select> 
                        <span class="help-block invalid-feedback with-errors">
                            <ul class="list-unstyled">
                                <li class="err_time_frames"></li>
                            </ul>
                        </span>
                    </div> 
                    </td>
                    <td>
                    <p class="m-0 red bold deletebtn" style="display:block;cursor:pointer" onclick="return deletePlan(this)"  id="${counter}" style="cursor:pointer">${removeText}</p>              </td>
                    </tr>`;
    // $(plan_area).insertAfter($(".add_plan_area:last"));    
    if($("#plan-table tr").length > 1){     
        $(plan_area).insertAfter($(".add_plan_area:last")); 
    } else {        
        $(plan_area).insertAfter($(".heading-tr:last"));    
    }
    //$('.select2').select2();
    $(".add_plan_area").validator();
    $('.timepicker').timepicker({
        // minuteStep: 1, 
        defaultTime:'',
        timeFormat: 'H:i',
        showInputs: false,
        showMeridian: false //24hr mode 
    });

    //to Disable the selected dates
    $('.date').each(function(){

        var date_values = $(this).val();

        if(date_values!='')
        {
            // console.log('Value exist');
            var date_value_string = date_values.split(',');
            for (var i = 0; i < date_value_string.length; i++) {
                if(date_value_string[i]){
                    // console.log(date_value_string[i]);
                    // console.log(disabledDates);
                    // console.log($.inArray(date_value_string[i], disabledDates));
                    if($.inArray(date_value_string[i], disabledDates)==-1){
                        disabledDates.push(date_value_string[i]);     //console.log(date_value_string[i]);

                    }
                }
               
            }

        }

   })
    //Datepicker
    $('#dates_'+counter).datepicker({ 
        format: 'yyyy-mm-dd',
         multidate: true,
         beforeShowDay: function (date) {
            var day = date.getDay();
            var string = $.datepicker.formatDate('yy-mm-dd', date);
            // var string = '2020-02-02';
            // console.log(string);

            if ($.inArray(string, disabledDates) != -1){
               return {
                  classes: 'disabled'
               };
            }
            return;
            
        } 
    });
     $('.select2').select2();
}

function deletePlan(element)
{
    // console.log($(element).attr('id'))
    var index =  $(element).attr('id');
    //$(element).closest('.add_plan_area').find('*').attr('disabled', true);
    //$(element).closest('.add_plan_area').hide();

    var deletedDates = $("#dates_"+index).val();
    if(deletedDates!=""){
        var dd_arr = deletedDates.split(",");

        for (var i = 0; i < disabledDates.length; i++) {
            for (var j = 0; j < dd_arr.length; j++) {
                if(disabledDates[i]==dd_arr[j])
                  delete disabledDates[i];
            }
           
        }
        console.log('ddDates');
        console.log(deletedDates);
        // console.log(disabledDates);

        /*if ($.inArray(string, disabledDates) != -1){
             
        }*/

    }

    $(element).closest('.add_plan_area').remove();
}

function showTimeFrames(index){
     
    var from_time = $("#from_time_"+index).val();
    var to_time   =   $("#to_time_"+index).val();
    //var date_data_id = $("#week_day_id_"+index).val();

    var from_hour = 0;
    var from_min = 0;
    var to_hour = 0;
    var to_min = 0;
    if(from_time!="" && from_time.length>0){
        let split = from_time.split(":");
        from_hour = split[0];
        from_min = split[1];
    }
    if(to_time!="" && to_time.length>0){
        let split = to_time.split(":");
        to_hour = split[0];
        to_min = split[1];
    }
    // console.log('showTimeFrames:'+index);
    // // console.log(weekday_id,duration,from_hour,to_hour,from_min,to_min);
    //  return false;
    var duration = $("#appointment_type_id option:selected").data('duration');

    if(from_time!=""){
        var bindResult = timeArr(index,duration,from_hour,to_hour,from_min,to_min);
        $("#time_frames_"+index).html(bindResult);
    }

    /*$('#time_frames_'+index+' option').filter(function() { 
        //console.log($(this).text());
        return ($(this).text() == '01:00'); //To select Blue
    }).prop('selected', true);*/
}


function timeArr(index,interval,hourStart,hourEnd,minuteStart,minuteEnd)
{
    // console.log('timeArr:');
    // console.log(index,interval,hourStart,hourEnd,minuteStart,minuteEnd);
    // var result = '<select class="form-control timeFrames" name="weekday[0][timeFrames]" id="timeFrames_0" required data-error="Time Frame field is required.">';
    var result = '';
    var start = new Date(1,1,1,hourStart,0);
    var end = new Date(1,1,1,hourEnd,0);
    
    //result.push('<select class="form-control timeFrames" name="weekday[0][timeFrames]" id="timeFrames_0" required data-error="Time Frame field is required.">');
    // console.log("test1221212");
    if(interval!="" && interval>0){
        for (var d = start; d <=end; d.setMinutes(d.getMinutes() + interval)) {
          var hourMinute = format(d);
          var selected_text = "";
          if(parsedJson!=""){
               $.each(parsedJson[index], function(key, value) {
                 if(value===hourMinute){
                    selected_text = "selected" ;
                   return;
                 }
              });
          }
          
          result += '<option '+selected_text+' value="'+hourMinute+'">'+hourMinute+'</option>';
      }
    }
   //result += '</select';
  return result;
}

function format(inputDate)
{
    var hours = inputDate.getHours();
    var minutes = inputDate.getMinutes();
    //var ampm = hours < 12? "AM" : (hours=hours%12,"PM");
    hours =  hours == 0? 12 : hours < 10? ("0" + hours) : hours;
    minutes = minutes < 10 ? ("0" + minutes) : minutes;
    return hours + ":" + minutes;
    // return hours + ":" + minutes + " " + ampm;
   // return '<option value="'+hours + ":" + minutes+'">'+hours + ":" + minutes+'</option>';
}

// timeArr(30,2,14,10,10);

// console.log(timeArr(30));
/*function timegenerate(starth,startm,endh,endm,interval)
{
    times=[]
    size= endh>starth ? endh-starth+1 : starth-endh+1
    hours=[...Array(size).keys()].map(i => i + starth);
    for (hour in hours)
    {
        for (min = startm; min < 60; min += interval) 
        {
            startm=0
            if ((hours.slice(-1)[0] === hours[hour]) && (min > endm))
            {
                break;
            }
            if (hours[hour] > 11 && hours[hour] !== 24 )
            {
                times.push(('0' + (hours[hour]%12 === 0 ? '12': hours[hour]%12)).slice(-2) + ':' + ('0' + min).slice(-2) + " " + 'PM');
            }
            else
            {
                times.push(('0' +  (hours[hour]%12 === 0 ? '12': hours[hour]%12)).slice(-2) + ':' + ('0' + min).slice(-2) + " " + 'AM');
            }
        }
    }
    return times;
}*/