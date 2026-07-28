var disabledDates = [];
var datepicker_index = '';
var sessionSlots = {}; // tracks current selected slots per row index
function clearDateChanged(ev){
    console.log('clearDateChanged');
}
function dateChanged(ev) 
{
    console.log('dateChanged');
    
    var datepicker_id = ev.target.id;
    var datepicker_arr = datepicker_id.split('_');
    datepicker_index = datepicker_arr[1];

    var latest_rec  = [];
    tmpDisabledDates_multi[datepicker_index] = [];
    for (var i = 0; i < ev.dates.length; i++) 
    {
        var string = $.datepicker.formatDate('yy-mm-dd', ev.dates[i]);
        if($.inArray(string, tmpDisabledDates_multi[datepicker_index])==-1)
        {
            tmpDisabledDates_multi[datepicker_index][i] = string;
        }
    }
    //console.log('tmpDisabledDates_multi', tmpDisabledDates_multi);
    $('.date').datepicker('update');
   
}

function selectWeekOnSameDate(index) 
{
    var start_date = $("#start_date_"+index).val();
    var end_date = $("#end_date_"+index).val();

    if(start_date!='' && end_date!='' && start_date==end_date){
        var d = new Date(end_date);
        $("#week_day_id_"+index).val(d.getDay());
    }
}


$(document).ready(function ()  
{
    // adding focus event to first field 
    $('input[name="name"]').focus();  
    
    $('.date').datepicker({ 
            dateFormat: 'yy-mm-dd',
           // multidate: true,
            startDate: new Date(),
             minDate: 0
        });

    $('.date').mask("9999-99-99");
    //Datepicker
    //if(tmpDisabledDates_multi.length==0){ 
        /*//for Multiple Add
        $('.date').datepicker({ 
            format: 'yyyy-mm-dd',
            multidate: true,
            startDate: new Date(),
            beforeShowDay: function (date) {
                disabledDates = [];
                $(tmpDisabledDates_multi).each(function(key,value){
                    if(0!=key){
                        $(value).each(function(key1,value1){
                            if ($.inArray(value1, disabledDates) == -1){
                              disabledDates.push(value1);
                            }
                        });

                    }
                });
               
                var string = $.datepicker.formatDate('yy-mm-dd', date);
                if ($.inArray(string, disabledDates) != -1){
                   return {
                      classes: 'disabled'
                   };
                }
                return;
                
            } 
        })
        .on('changeDate', dateChanged);
       // .on('clearDate', clearDateChanged);*/

       

    //}else{
        
        /*//for Edit
        $('.date').each(function()
        {
            var datepicker_id = this.id;
            var datepicker_arr = datepicker_id.split('_');
            var id = datepicker_arr[1];

            $('#dates_'+id).datepicker({ 
                format: 'yyyy-mm-dd',
                multidate: true,
                startDate: new Date(),
                beforeShowDay: function (date) {
                    
                    disabledDates = [];
                    $(tmpDisabledDates_multi).each(function(key,value){
                        if(id!=key){
                            $(value).each(function(key1,value1){
                                if ($.inArray(value1, disabledDates) == -1){
                                  disabledDates.push(value1);
                                }
                            });

                        }
                    });
                   
                    var string = $.datepicker.formatDate('yy-mm-dd', date);
                    if ($.inArray(string, disabledDates) != -1){
                       return {
                          classes: 'disabled'
                       };
                    }
                    return;
                    
                } 
            })
            .on('changeDate', dateChanged);
           // .on('clearDate', clearDateChanged);
            
        });*/

    //}
    

    $('.timepicker').timepicker({
        // minuteStep: 1,
        defaultTime:'',
        timeFormat: 'H:i',
        showInputs: false,
        showMeridian: false //24hr mode
    });

    // bootstrap-timepicker is inconsistent about firing the native `change` event when
    // the value is changed via the popup arrow buttons — particularly on decrement and
    // across older plugin versions. Inline onchange="..." therefore misses those updates.
    //
    // Bind via document delegation (so it also catches rows added later by addPlan)
    // to multiple events:
    //   - changeTime.timepicker  : the plugin's own event, fires on every internal update
    //   - focusout               : guaranteed catch-all when user finishes editing
    //   - change                 : redundant safety net
    // showTimeFrames is idempotent (same inputs => same outputs), so duplicate firings
    // are harmless.
    $(document).on('changeTime.timepicker change focusout', '.timepicker', function() {
        var id  = $(this).attr('id');                  // e.g. "from_time_3"
        var idx = id.substring(id.lastIndexOf('_') + 1);
        showTimeFrames(idx, '');
    });

})
 
// Check for overlapping time ranges across rows within the same form.
// Two time ranges overlap when: start_A < end_B AND end_A > start_B
// Returns error string or empty string if no overlap.
function checkOverlappingRows()
{
    var rows = [];
    $('.plan').each(function() {
        var $row      = $(this);
        var weekday   = $row.find('.weekdays').val();
        var startDate = $row.find('input[name*="[start_date]"]').val();
        var endDate   = $row.find('input[name*="[end_date]"]').val();
        var fromTime  = $row.find('input[name*="[from_time]"]').val();
        var toTime    = $row.find('input[name*="[to_time]"]').val();

        if (!weekday || !startDate || !endDate || !fromTime || !toTime) return;

        rows.push({
            weekday:   weekday,
            startDate: startDate,
            endDate:   endDate,
            fromTime:  fromTime.length === 4 ? '0' + fromTime : fromTime,  // normalise "1:00" → "01:00"
            toTime:    toTime.length === 4 ? '0' + toTime : toTime
        });
    });

    for (var i = 0; i < rows.length; i++) {
        for (var j = i + 1; j < rows.length; j++) {
            var a = rows[i], b = rows[j];
            // Same weekday?
            if (a.weekday !== b.weekday) continue;
            // Date ranges overlap? (string comparison works for yyyy-mm-dd)
            if (a.startDate > b.endDate || a.endDate < b.startDate) continue;
            // Time ranges overlap?
            if (a.fromTime < b.toTime && a.toTime > b.fromTime) {
                return 'Row ' + (i + 1) + ' and Row ' + (j + 1) + ': time range overlaps (' +
                       a.fromTime + '–' + a.toTime + ' vs ' + b.fromTime + '–' + b.toTime +
                       ') on the same weekday with overlapping dates.';
            }
        }
    }
    return '';
}

// submitting form after validation
$('#rosterForm').validator().on('submit', function (e)
{
    if (!e.isDefaultPrevented()) {

        // Client-side overlap check before sending to server
        var overlapError = checkOverlappingRows();
        if (overlapError) {
            toastr.error(overlapError);
            return false;
        }

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
                    <input type="hidden" id="start_date_hidden_${counter}" value="">
                    <input type="hidden" id="end_date_hidden_${counter}" value="">
                        <div class="form-group"> 
                        <select 
                            class="form-control weekdays" 
                            name="date_data[${counter}][week_day_id]"
                            id="week_day_id_${counter}"
                            required
                            data-error="${weekDayText}" 
                        >
                            ${plan_options}
                        </select>
                        <span class="help-block invalid-feedback with-errors">
                            <ul class="list-unstyled">
                                <li class="err_weekday[${counter}][week_day_id][] err_weekday"></li>
                            </ul>
                        </span>
                        </div>
                    </td>
                    <td>
                        <div class="form-group">
                            <input 
                                type="text" 
                                name="date_data[${counter}][start_date]" 
                                class="form-control date"
                                id="start_date_${counter}" 
                                onchange="selectWeekOnSameDate(${counter})" 
                                autocomplete="off"
                                required
                                data-error="${dateText}" 
                            >
                            <span class="help-block invalid-feedback with-errors">
                                <ul class="list-unstyled">
                                    <li class="err_start_date"></li>
                                </ul>
                            </span>
                        </div>
                    </td>
                    <td>
                        <div class="form-group">
                            <input 
                                type="text" 
                                name="date_data[${counter}][end_date]" 
                                class="form-control date"
                                id="end_date_${counter}" 
                                onchange="selectWeekOnSameDate(${counter})" 
                                autocomplete="off"
                                required
                                data-error="${dateText}" 
                            >
                            <span class="help-block invalid-feedback with-errors">
                                <ul class="list-unstyled">
                                    <li class="err_end_date"></li>
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
                                onchange="showTimeFrames(${counter},'new')" 
                                data-error="${fromText}" 
                                >
                                <input type="hidden" id="from_time_hidden_${counter}" value="">
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
                                onchange="showTimeFrames(${counter},'new')" 
                                required 
                                data-error="${toText}" 
                                >
                                <input type="hidden" id="to_time_hidden_${counter}" value="">
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
                            data-error="${timeText}"
                            class="form-control select2" 
                            id="time_frames_${counter}"
                            multiple="multiple" 
                            data-placeholder="${selectText}"
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
     /*$('.date').datepicker({ 
        format: 'yyyy-mm-dd',
         multidate: true,
         startDate: new Date(),
         beforeShowDay: function (date) {

            disabledDates = [];
            $(tmpDisabledDates_multi).each(function(key,value){
                if(counter!=key){
                    $(value).each(function(key1,value1){
                        if ($.inArray(value1, disabledDates) == -1){
                          disabledDates.push(value1);
                        }
                    });
                }
            });
           
            var string = $.datepicker.formatDate('yy-mm-dd', date);

            if ($.inArray(string, disabledDates) != -1){
               return {
                  classes: 'disabled'
               };
            }
            return;
            
        } 
    }).on('changeDate', dateChanged);*/
    $('.date').datepicker({ 
            dateFormat: 'yy-mm-dd',
           // multidate: true,
            startDate: new Date(),
             minDate: 0
        });
    $('.date').mask("9999-99-99");
     $('.select2').select2();
}

function deletePlan(element)
{
    var index =  $(element).attr('id');

    //to delete the disable  dates for removed index
    //delete tmpDisabledDates_multi[index];
   // $('.date').datepicker('update');

    $(element).closest('.add_plan_area').remove();
    //$(element).closest('.add_plan_area').find('*').attr('disabled', true);
    //$(element).closest('.add_plan_area').hide();
}

// function deletePlan(element)
// {
//     // var index =  $(element).attr('id');
//     // alert(index);
//     var $this = $(element);
//     var action = $this.attr('data-href');
  
//     if (action != '') {
//         swal({
//           title: deleteContent.title,
//           text: deleteContent.text,
//           type: "warning",
//           showCancelButton: true,
//           cancelButtonText: deleteContent.cancel,
//           confirmButtonText: deleteContent.confirm,
//           confirmButtonClass: "btn-danger",
//           closeOnConfirm: false,
//           showLoaderOnConfirm: true
//         },
//         function () {
//             axios.delete(action)
//             .then(function (response) {
//                 if (response.data.status === 'success') {
//                     swal("Success", response.data.msg, 'success');
//                 }

//                 if (response.data.status === 'error') {
//                 }

//             })
//           .catch(function (error) {
//             // swal("Error",error.response.data.msg,'error');
//           });
//       });
//     }
//     //to delete the disable  dates for removed index
//     //delete tmpDisabledDates_multi[index];
//     // $('.date').datepicker('update');
//     $(element).closest('.add_plan_area').remove();
//     //$(element).closest('.add_plan_area').find('*').attr('disabled', true);
//     //$(element).closest('.add_plan_area').hide();
// }
function setduration()
{
    // var oldDuration = parseInt($("#default_time_duration").val());
    // $("#oldTimeDuration").val($("#default_time_duration").val());
    // console.log('setduration oldDuration:'+  $("#oldTimeDuration").val);
    $("#oldTimeDuration").val($("#default_time_duration").val());
}
function showTimeFrames_old(index, newtimeslot)
{
    var from_time = $("#from_time_" + index).val();
    var to_time   = $("#to_time_" + index).val();

    if (from_time == "" || to_time == "") return;

    var from_hour = 0, from_min = 0, to_hour = 0, to_min = 0;

    if (from_time.length > 0) {
        let split = from_time.split(":");
        from_hour = split[0];
        from_min  = split[1];
    }
    if (to_time.length > 0) {
        let split = to_time.split(":");
        to_hour = split[0];
        to_min  = split[1];
    }

    var duration   = parseInt($("#default_time_duration").val());
    var oldDuration = (typeof oldDurations !== 'undefined' && oldDurations[index])
                        ? parseInt(oldDurations[index])
                        : parseInt($("#oldTimeDuration").val());

    // Get the originally saved from/to hours (what was loaded when page opened)
    var defFromTime    = $("#from_time_hidden_" + index).val();
    var defToTime      = $("#to_time_hidden_" + index).val();
    var defFromTimeHour = '';
    var defToTimeHour   = '';

    if (defFromTime != "" && defFromTime != "undefined") {
        let split = defFromTime.split(":");
        defFromTimeHour = split[0];
        if (Number(defFromTimeHour) < 10) {
            defFromTimeHour = defFromTimeHour.replace(/^0+/, '');
        }
    }
    if (defToTime != "" && defToTime != "undefined") {
        let split = defToTime.split(":");
        defToTimeHour = split[0];
        if (Number(defToTimeHour) < 10) {
            defToTimeHour = defToTimeHour.replace(/^0+/, '');
        }
    }

    var hasSavedFrames = (typeof parsedJson !== 'undefined' 
                          && parsedJson != "" 
                          && parsedJson[index] 
                          && parsedJson[index].length > 0);

    var timeRangeUnchanged = (defFromTimeHour == from_hour && defToTimeHour == to_hour);

    // CASE 1: Time range not changed, no field trigger, saved data exists
    // -> Restore exactly what was saved (respects deletions)
    if (hasSavedFrames && timeRangeUnchanged && !newtimeslot) {
        var result = '';
        $.each(parsedJson[index], function(key, value) {
            result += '<option selected value="' + value + '">' + value + '</option>';
        });
        $("#time_frames_" + index).html(result);
        return; // stop here
    }

    // CASE 2: Time range changed OR new slot triggered
    // -> Regenerate full slot list from new time range
    var bindResult = timeArr(index, duration, from_hour, to_hour, from_min, to_min);
    $("#time_frames_" + index).html(bindResult);

    if (newtimeslot) {
        // Brand new row - select all slots
        $('#time_frames_' + index + ' option').prop('selected', true);

    } else if (hasSavedFrames && !timeRangeUnchanged) {
        // Time range changed on existing row
        // Select only slots that existed in saved data (preserve deletions within old range)
        // For NEW slots beyond old range, select them by default
        var savedSlots = parsedJson[index]; // e.g. ["01:00","01:20","01:40",...]

        $('#time_frames_' + index + ' option').each(function() {
            var slotVal = $(this).val();

            // Check if this slot falls within the OLD time range
            var slotHour = parseInt(slotVal.split(":")[0]);
            var oldToHour = parseInt(defToTimeHour);
            var fromHourInt = parseInt(from_hour);

            if (slotHour >= oldToHour) {
                // This slot is BEYOND the old end time -> NEW slot, select it
                $(this).prop('selected', true);
            } else {
                // This slot was in the old range -> only select if it was saved (not deleted)
                $(this).prop('selected', savedSlots.indexOf(slotVal) !== -1);
            }
        });

    } else {
        // No saved data at all - select everything
        $('#time_frames_' + index + ' option').prop('selected', true);
    }
}

function showTimeFrames(index, newtimeslot)
{
    var from_time  = $("#from_time_" + index).val();
    var to_time    = $("#to_time_" + index).val();
    var start_date = $("#start_date_" + index).val();
    var end_date   = $("#end_date_" + index).val();

    if (from_time == "" || to_time == "") return;

    var from_hour = 0, from_min = 0, to_hour = 0, to_min = 0;

    if (from_time.length > 0) {
        let split = from_time.split(":");
        from_hour = parseInt(split[0]);
        from_min  = parseInt(split[1]);
    }
    if (to_time.length > 0) {
        let split = to_time.split(":");
        to_hour = parseInt(split[0]);
        to_min  = parseInt(split[1]);
    }

    var duration = parseInt($("#default_time_duration").val());

    // The duration that was in effect when this row was originally saved. This is needed
    // to detect "duration changed since save" so we can regenerate slots at the new step
    // without mistaking newly-introduced slots for "previously deleted" ones.
    var savedDuration = (typeof oldDurations !== 'undefined' && oldDurations[index])
                        ? parseInt(oldDurations[index])
                        : duration;
    var durationChanged = (savedDuration > 0 && savedDuration !== duration);

    // Originally saved from/to time (from hidden fields set on page load)
    var defFromTime     = $("#from_time_hidden_" + index).val();
    var defToTime       = $("#to_time_hidden_" + index).val();
    var defFromTimeHour = -1, defFromTimeMin = 0;
    var defToTimeHour   = -1, defToTimeMin   = 0;

    if (defFromTime != "" && defFromTime != "undefined") {
        let split       = defFromTime.split(":");
        defFromTimeHour = parseInt(split[0]);
        defFromTimeMin  = parseInt(split[1]);
    }
    if (defToTime != "" && defToTime != "undefined") {
        let split     = defToTime.split(":");
        defToTimeHour = parseInt(split[0]);
        defToTimeMin  = parseInt(split[1]);
    }

    // Originally saved start/end date
    var defStartDate = $("#start_date_hidden_" + index).val() || "";
    var defEndDate   = $("#end_date_hidden_" + index).val()   || "";

    var hasSavedFrames = (typeof parsedJson !== 'undefined'
                          && parsedJson != ""
                          && parsedJson[index]
                          && parsedJson[index].length > 0);

    var timeRangeUnchanged = (defFromTimeHour === from_hour
                              && defFromTimeMin  === from_min
                              && defToTimeHour   === to_hour
                              && defToTimeMin    === to_min);

    // Check if date is back to original
    var dateBackToOriginal = (defStartDate === start_date && defEndDate === end_date);

    var $select = $("#time_frames_" + index);

    // Build the full slot list for the current time range at the CURRENT duration.
    var allSlots = buildSlotList(duration, from_hour, to_hour, from_min, to_min);

    // CASE 1: New row added via Add button -> select all generated slots
    if (newtimeslot) {
        renderSlots($select, allSlots, allSlots);
        sessionSlots[index] = allSlots.slice();
        return;
    }

    // CASE 2: Time range unchanged AND date back to original
    // -> restore parsedJson exactly (fast path; preserves user's original deletions verbatim).
    //
    // This intentionally fires regardless of `durationChanged`. Reason: this branch runs
    // on page load (the timepicker init fires changeTime.timepicker, which calls us with
    // no real user change) and whenever the user reverts edits back to the original state.
    // In both cases the user has NOT actively touched the time/date, so we must NOT
    // regenerate slots at the new duration — we must show the slots exactly as they were
    // saved. The new duration only takes effect once the user actually edits time or date,
    // at which point execution falls through to CASE 3 below.
    if (timeRangeUnchanged && dateBackToOriginal && hasSavedFrames) {
        renderSlots($select, parsedJson[index], parsedJson[index]);
        sessionSlots[index] = parsedJson[index].slice();
        return;
    }

    // CASE 2b: Time range unchanged, date extended beyond original, no saved data to honor
    // -> select everything in the (newly-stepped) slot list.
    if (timeRangeUnchanged && !dateBackToOriginal && !hasSavedFrames) {
        renderSlots($select, allSlots, allSlots);
        sessionSlots[index] = allSlots.slice();
        return;
    }

    // CASE 3 (general): regenerate slots at the current duration and decide selection per-slot.
    //
    // Selection rules when we have saved data:
    //   a) Slot is OUTSIDE the old time range (before old start OR beyond old end)
    //      => brand new from a range extension => select.
    //   b) Slot value is in savedSlots
    //      => was kept on the previous save => select.
    //   c) Slot is INSIDE the old time range, NOT in savedSlots:
    //        - If it WOULD have existed at the old duration (i.e. it lines up on the old
    //          step from the old start time), it was a "deleted" slot => skip.
    //        - Otherwise it's a brand-new slot introduced by the smaller/changed duration
    //          => select.
    //
    // No saved data => select everything.
    var selected;
    if (hasSavedFrames) {
        var savedSlots = parsedJson[index];

        // Build the set of slots that would have existed at the OLD duration within the
        // OLD time range. Any slot inside the old range that does NOT appear here was
        // never "saveable" at the old step, so its absence from savedSlots can't mean
        // "user deleted it" — it just didn't exist back then.
        var oldSlotSet = {};
        if (durationChanged && savedDuration > 0
            && defFromTimeHour >= 0 && defToTimeHour >= 0) {
            var oldGen = buildSlotList(savedDuration,
                                       defFromTimeHour, defToTimeHour,
                                       defFromTimeMin, defToTimeMin);
            for (var j = 0; j < oldGen.length; j++) {
                oldSlotSet[oldGen[j]] = true;
            }
        }

        selected = [];
        for (var i = 0; i < allSlots.length; i++) {
            var slotVal   = allSlots[i];
            var slotParts = slotVal.split(":");
            var slotHour  = parseInt(slotParts[0]);
            var slotMin   = parseInt(slotParts[1]);

            var beyondOldEnd = (slotHour > defToTimeHour)
                            || (slotHour === defToTimeHour && slotMin >= defToTimeMin);

            var beforeOldStart = (slotHour < defFromTimeHour)
                              || (slotHour === defFromTimeHour && slotMin < defFromTimeMin);

            if (beyondOldEnd || beforeOldStart) {
                // (a) outside old range — new from extension
                selected.push(slotVal);
            } else if (savedSlots.indexOf(slotVal) !== -1) {
                // (b) was previously saved
                selected.push(slotVal);
            } else if (durationChanged && !oldSlotSet[slotVal]) {
                // (c) inside old range, not saved, but wouldn't have existed at the old
                // duration either — so it's a new slot introduced by the duration change.
                selected.push(slotVal);
            }
            // else: inside old range, would have existed at old duration, but isn't in
            // savedSlots — that's a genuine deletion, leave it unselected.
        }
    } else {
        selected = allSlots.slice();
    }

    renderSlots($select, allSlots, selected);
    sessionSlots[index] = selected.slice();
}

// Build the array of "HH:MM" strings between (hourStart:minuteStart) and (hourEnd:minuteEnd),
// stepping by `interval` minutes. Mirrors timeArr() but returns a plain array (no <option> markup),
// which is what we need for the select2-friendly .val([...]) flow.
function buildSlotList(interval, hourStart, hourEnd, minuteStart, minuteEnd)
{
    var slots = [];
    if (interval === "" || interval <= 0) return slots;

    var start = new Date(1, 1, 1, parseInt(hourStart), parseInt(minuteStart));
    var end   = new Date(1, 1, 1, parseInt(hourEnd),   parseInt(minuteEnd));

    for (var d = start; d < end; d.setMinutes(d.getMinutes() + interval)) {
        slots.push(format(d));
    }
    return slots;
}

// Render a fresh option set into a select2-bound <select> and set the selected values.
// We empty the select, append the new options, and use .val([...]).trigger('change')
// which is select2's documented API for programmatic selection. We then trigger
// 'change.select2' as well, which forces select2 to redraw its tag UI even on
// versions where the standard 'change' event isn't enough after option replacement.
function renderSlots($select, allOptions, selectedValues)
{
    $select.empty();
    for (var i = 0; i < allOptions.length; i++) {
        var opt = new Option(allOptions[i], allOptions[i], false, false);
        $select.append(opt);
    }

    // If select2 isn't yet bound to this element (e.g. a brand-new row whose .select2()
    // call may not have run yet), bind it now.
    if (!$select.hasClass('select2-hidden-accessible')) {
        $select.select2();
    }

    $select.val(selectedValues).trigger('change');
    // Force select2's internal redraw — needed on some versions when the option set
    // has shrunk (decrease case) and select2 still has stale tags cached.
    $select.trigger('change.select2');
}
function timeArr_old(index,interval,hourStart,hourEnd,minuteStart,minuteEnd)
{
    //0-10-9-18-00-00
    // console.log(index,interval,hourStart,hourEnd,minuteStart,minuteEnd);
    // var result = '<select class="form-control timeFrames" name="weekday[0][timeFrames]" id="timeFrames_0" required data-error="Time Frame field is required.">';
    var result = '';
    var start = new Date(1,1,1,hourStart,minuteStart);
    var end = new Date(1,1,1,hourEnd,minuteEnd);
    
    //result.push('<select class="form-control timeFrames" name="weekday[0][timeFrames]" id="timeFrames_0" required data-error="Time Frame field is required.">');
    // console.log("test1221212");
    if(interval!="" && interval>0){
        // for (var d = start; d <=end; d.setMinutes(d.getMinutes() + interval)) { //Commented by Shyam 12-01-22
        for (var d = start; d < end; d.setMinutes(d.getMinutes() + interval)) { //Added by Shyam 12-01-22
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
function timeArr(index, interval, hourStart, hourEnd, minuteStart, minuteEnd)
{
    var result = '';
    // Parse as integers to avoid string comparison issues
    var start = new Date(1, 1, 1, parseInt(hourStart), parseInt(minuteStart));
    var end   = new Date(1, 1, 1, parseInt(hourEnd),   parseInt(minuteEnd));

    if (interval != "" && interval > 0) {
        for (var d = start; d < end; d.setMinutes(d.getMinutes() + interval)) {
            var hourMinute = format(d);
            var selected_text = "";
            if (parsedJson != "") {
                $.each(parsedJson[index], function(key, value) {
                    if (value === hourMinute) {
                        selected_text = "selected";
                        return false;
                    }
                });
            }
            result += '<option ' + selected_text + ' value="' + hourMinute + '">' + hourMinute + '</option>';
        }
    }
    return result;
}
function format(inputDate)
{
    var hours = inputDate.getHours();
    var minutes = inputDate.getMinutes();
    //var ampm = hours < 12? "AM" : (hours=hours%12,"PM");
    // Pad with leading zero — including hour 0 (midnight should be "00", NOT "12",
    // otherwise generated slots won't match parsedJson values produced by PHP date("H:i").
    hours = hours < 10 ? ("0" + hours) : hours;
    minutes = minutes < 10 ? ("0" + minutes) : minutes;
    return hours + ":" + minutes;
    // return hours + ":" + minutes + " " + ampm;
   // return '<option value="'+hours + ":" + minutes+'">'+hours + ":" + minutes+'</option>';
}

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


