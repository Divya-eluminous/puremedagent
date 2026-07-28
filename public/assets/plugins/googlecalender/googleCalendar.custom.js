$.fn.eventCalendar = function (e) 
{

    // default settings
    var l = $.extend({}, $.fn.eventCalendar.defaults, e);
    var v = {
            wrap: "",
            directionLeftMove: "300",
            eventsJson: {}
        };

    function t(e, t) 
    {
        return e.date.toLowerCase() > t.date.toLowerCase() ? 1 : -1
    }

    function a(e, t, a)
    {
        var s = $("<div class='col-sm-12 col-md-12 eventsCalendar-slider'><div id='loading' style='position: absolute; margin: 140px 0px 0px 225px; z-index: 2;display:none;'><img src='" + ADMINURL + "/../assets/admin/images/icons/loader3.gif' /></div><div id='popup_box' style='display:none;' class='popup_box'></div></div>"),
            r = $("<div class='eventsCalendar-monthWrap responsive-calendar'></div>"),
            d = $("<div class='eventsCalendar-currentTitle controls'><h4 class='monthTitle'></h4></div>"),
            i = $("<div class='arrow-wrapper'><a href='#' class='arrow prev pull-left mnt-arr'><div class='btn prev-mnt'>" + l.txt_prev + "</div></a><a href='#' class='arrow next pull-right mnt-arrs'><div class='btn next-mnt'>" + l.txt_next + "</div></a></div>");

        if ($eventsCalendarDaysList = $("<div class='eventsCalendar-daysList day-headers'></div>"), date = new Date(CURRENTTIME), $myeventsDays = $('<div class="days days_listing" data-group="days"></div>'), v.wrap.find(".eventsCalendar-slider").length ? v.wrap.find(".eventsCalendar-slider").append(r) : (v.wrap.prepend(s), s.append(r)), v.wrap.find(".responsive-calendar.currentMonth").removeClass("currentMonth").addClass("oldMonth"), r.addClass("currentMonth").append(d, $eventsCalendarDaysList, $myeventsDays), "current" === e) day = date.getDate(), s.prepend(i);
        else
        {
            date = new Date(v.wrap.attr("data-current-year"), v.wrap.attr("data-current-month"), 1, 0, 0, 0), day = 0, moveOfMonth = 1, "prev" === e && (moveOfMonth = -1), date.setMonth(date.getMonth() + moveOfMonth);
            var o = new Date;
            date.getMonth() === o.getMonth() && (day = o.getDate())
        }

        var t = date.getFullYear(),
            c = (new Date).getFullYear(),
            a = date.getMonth();

        "current" != e && n(l.eventsLimit, t, a, !1, e), v.wrap.attr("data-current-month", a).attr("data-current-year", t), d.find(".monthTitle").html(l.monthNames[a] + " " + t);

        var p = 32 - new Date(t, a, 32).getDate(),
            u = [],
            h = [];

        if (l.showDayAsWeeks) {
            if ($eventsCalendarDaysList.addClass("showAsWeek"), l.showDayNameInCalendar) {
                $eventsCalendarDaysList.addClass("showDayNames");
                var f = 0;
                for (l.startWeekOnMonday && (f = 1); 7 > f; f++) u.push('<div class="eventsCalendar-day-header day header">' + l.dayNamesShort[f] + "</div>"), 6 === f && l.startWeekOnMonday && u.push('<div class="eventsCalendar-day-header day header">' + l.dayNamesShort[0] + "</div>")
            }
            dt = new Date(t, a, 1);
            var y = dt.getDay();
            for (l.startWeekOnMonday && (y = dt.getDay() - 1), 0 > y && (y = 6), console.log("V: " + f + " M: " + y + " P: " + p), f = y; f > 0; f--) h.push('<div class="day not-current"></div>')
        }

        for (dayCount = 1; dayCount <= p; dayCount++) {
            var m = "";
           // if (day > 0 && dayCount === day && t === c && (m = "today1"), dayCount < day) {
               // var w = '<span style="color:#E1E5F1">' + dayCount + "</span>";
            //}
           // else {
                var w = '<a class="showEvent" data-day="' + dayCount + '" href="javascript:void(0)">' + dayCount + "</a>";
            //}

            h.push('<div id="dayList_' + dayCount + '" rel="' + dayCount + '" class="eventsCalendar-day day ' + m + '">' + w + "</div>")
           
        }
         h.push('<div class="btn-wrap"><button type="button" onclick="getEventsByType(`google`)" class="green-btn">Important Events</button>')
          h.push('<button type="button" onclick="getEventsByType(`lectures`)" class="orange-btn">Live Lectures</button></div>')

        $eventsCalendarDaysList.append(u.join("")), $myeventsDays.append(h.join(""))
       // $eventsCalendarDaysList.append('<button type="submit" class="blue-btn bold text-uppercase btn-normal-h-30 disabled">send</button>');
    }

    function n(e, t, a, n, r)
    {
        var e = e || 0,
            t = t || "",
            n = n || "";
        if ("undefined" != typeof a) var a = a;
        else var a = "";
        if (v.wrap.find(".eventsCalendar-loading").fadeIn(), l.jsonData) l.cacheJson = !0, v.eventsJson = l.jsonData, s(v.eventsJson, e, t, a, n, r);
        else if (l.cacheJson && r) s(v.eventsJson, e, t, a, n, r);
        else {
            var d = (new Date).getTimezoneOffset();

            // console.log("testgoogle");
            // console.log(l.eventsjson.split("?"));

            $.getJSON(l.eventsjson + "&limit=" + e + "&year=" + t + "&month=" + a + "&day=" + n + "&offset=" + d, function (d) 
            {
                v.eventsJson = d, s(v.eventsJson, e, t, a, n, r)
            })
        }
    }

    function s(e, a, n, s, r, i)
    {
        directionLeftMove = "-=" + v.directionLeftMove,
            eventContentHeight = "auto",
            subtitle = v.wrap.find(".eventsCalendar-list-wrap .eventsCalendar-subtitle"),
            i ? ("" != r ? (d = new Date(n, s, r),
                dd = d.getDay(),
                subtitle.html(l.dayNames[dd] + ",  " + l.txt_SpecificEvents_prev + l.shortmonthNames[s] + " " + r + ", " + l.currentYear)) : subtitle.html(l.txt_SpecificEvents_prev + l.shortmonthNames[s] + "  " + l.currentYear), "prev" === i ? directionLeftMove = "+=" + v.directionLeftMove : ("day" === i || "month" === i) && (directionLeftMove = "+=0", eventContentHeight = 0)) : (subtitle.html(l.txt_NextEvents), eventContentHeight = "auto", directionLeftMove = "-=0"),

            v.wrap.find(".eventsCalendar-list").animate(
                {
                    opacity: l.moveOpacity,
                    height: eventContentHeight
                },

                l.moveSpeed, function () {
                    v.wrap.find(".eventsCalendar-list").css(
                        {
                            left: 0,
                            height: "auto"
                        }).hide();

                    var d = [];

                    if (e = $(e).sort(t), e.length) {

                        var i = "";
                        l.showDescription || (i = "hidden");
                        var o = "_self";
                        l.openEventInNewWindow && (o = "_target");
                        var c = 0;
                        var todayEvents='';
                        $.each(e, function (e, t) 
                        {
                            // console.log("hi" + t);
                            // console.log(t);
                            var o = new Date(parseInt(t.date)),
                                u = o.getFullYear(),
                                h = o.getMonth(),
                                f = o.getDate(),
                                y = o.getHours(),
                                m = o.getMinutes();

                            var itemclass="orange";
                            if(t.event_type=="google"){
                                itemclass="green";
                            }

                           // console.log(h + "/" + f + "/" + u)
                           // console.log(u + "/" + (h+1) + "/" + f)

                            if (parseInt(m) <= 9 && (m = "0" + parseInt(m)), (0 === a || a > c) && !(s !== !1 && s != h || "" != r && r != f || "" != n && n != u)) {
                                // adding html
                                eventStringDate = moment(o).format(l.dateFormat);

                               
                                var C = ADMINURL + "/../assets/admin/images/icons/eye.svg",

                                    _ = '<h4 class="eventTitle" id="event_date_'+f+'"><a href="javascript:void(0)">' + t.title + "</a><span class='bold theme-blue f-12'> "+t.obj_time +"</span></h4>";


                                  // console.log("aa");
                                // console.log(DATE);
                                // console.log(u + "/" + h + "/" + f)
                                // console.log(DATE.split("-"));

                                var currDate=TODAYDATE.split("-");
                                 // console.log("DATE");
                                 // console.log(currDate);
                                
        
        
                                //if(currDate[0]==u && currDate[1]==(h+1) && currDate[2]==f){

                                    d.push(`<li id="${e}" class="${t.type}"> <div class="course-sec-desc no-image ${itemclass}"> ${_} </div> </li>`);
                                    c++
                               // }

                                

                            }
                            
                            var currDate=TODAYDATE.split("-");
                            console.log('currDate');
                            
                            if(currDate[0]==u && currDate[1]==(h+1) && currDate[2]==f){
                               console.log(currDate);

                                    todayEvents+='<div class="course-sec-desc no-image '+itemclass+'"><h4 class="eventTitle" id="event_date_'+f+'"><a href="javascript:void(0)">'+ t.title +'</a><span class="bold theme-blue f-12"> '+t.obj_time +'</span></h4> </div>';
                                    
                               
                                v.wrap.find(".currentMonth .days_listing #dayList_" + parseInt(f)).addClass("currentDate")
                            }else if(t.event_type=='google'){
                                //console.log('google');
                                u == v.wrap.attr("data-current-year") && h == v.wrap.attr("data-current-month") && v.wrap.find(".currentMonth .days_listing #dayList_" + parseInt(f)).addClass("googleEvent")

                            }else  if(t.event_type=='livelecture'){
                               // console.log('livelecture');
                                u == v.wrap.attr("data-current-year") && h == v.wrap.attr("data-current-month") && v.wrap.find(".currentMonth .days_listing #dayList_" + parseInt(f)).addClass("dayWithEvents")
                                //active
                            }else{
                               // console.log('googleDayWithEvent');
                                u == v.wrap.attr("data-current-year") && h == v.wrap.attr("data-current-month") && v.wrap.find(".currentMonth .days_listing #dayList_" + parseInt(f)).addClass("googleDayWithEvent");
                            } 
                           

                           // u == v.wrap.attr("data-current-year") && h == v.wrap.attr("data-current-month") && v.wrap.find(".currentMonth .days_listing #dayList_" + parseInt(f)).addClass("dayWithEvents active")
                        })

                        setTimeout(function(){
                            $(".googleDayWithEvent").removeClass('googleEvent');
                            
                            var currDate=TODAYDATE.split("-");

                            var month = (parseInt(v.wrap.attr("data-current-month"))+1);
                            var year  = v.wrap.attr("data-current-year");
                            var impevent= $(".green-btn").hasClass('selected');
                            var livelecture= $(".orange-btn").hasClass('selected');
                            if(currDate[0]==year && currDate[1]==month && impevent==false && livelecture==false){
                                $(".eventsCalendar-subtitle").html("TODAY'S EVENTS:")
                                $(".eventsCalendar-list").html(todayEvents);
                            }/*else{
                                 $(".days days_listing,.eventsCalendar-day").removeClass('currentDate');
                                 
                            }*/
                        },100);

                        $('.calendar-wrp').LoadingOverlay("hide");
                    }else{
                        $('.calendar-wrp').LoadingOverlay("hide");
                    }

                    d.length || d.push('<li class="eventsCalendar-noEvents"><p>' + l.txt_noEvents + "</p></li>"),

                        v.wrap.find(".eventsCalendar-loading").hide(),
                        v.wrap.find(".eventsCalendar-list").html(d.join(""));

                    v.wrap.find(".eventsCalendar-list").animate({
                        opacity: 1,
                        height: "show"
                    }, l.moveSpeed)

                }), o()
    }

    function r() 
    {

        v.wrap.find(".arrow").click(function (e) {

            var impevent= $(".green-btn").hasClass('selected');
            var livelecture= $(".orange-btn").hasClass('selected');

            if(impevent || livelecture){
                //load all records again if impevent or livelecture button is activated
                getCalenderRecords('all');
            }

            
            $('.calendar-wrp').LoadingOverlay("show", {
                background: "rgba(165, 190, 100, 0)",
            });

            if ($("#eventModal").hide() , e.preventDefault(), $(this).hasClass("next")) {
                a("next");
                "-=" + v.directionLeftMove
            }
            else {
                a("prev");
                "+=" + v.directionLeftMove
            }
            
            $('.calendar-wrp').LoadingOverlay("hide");
            v.wrap.find(".responsive-calendar.oldMonth").animate({}, l.moveSpeed, function () {
                v.wrap.find(".responsive-calendar.oldMonth").remove()
            })
        })
    }

    // set loading error
    function i(e) 
    {
        v.wrap.find(".eventsCalendar-list-wrap").html("<span class='eventsCalendar-loading error'>" + e + " " + l.eventsjson + "</span>")
    }

    // directions
    function o() 
    {
        v.directionLeftMove = v.wrap.width();
    }

    // each calender events
    this.each(function () 
    {
        v.wrap = $(this);

        v.wrap.addClass("eventCalendar-wrap").append("<div class='col-sm-12 col-md-12 eventsCalendar-list-wrap'><div class='row'><div class='course-wrp alert fade in'><a class='close-btn close-cal' title='close' aria-label='close' href='javascript:void(0)'>x</a><h1 class='eventsCalendar-subtitle heading'></h1><span class='eventsCalendar-loading'>loading...</span><div class='eventsCalendar-list-content'><ul class='list-unstyled course-list eventsCalendar-list'></ul></div></div></div></div>"),

        l.eventsScrollable && v.wrap.find(".eventsCalendar-list-content").addClass("scrollable"), 

        o(),
       
        $(window).on('resize', function () 
        {
            o()
        }),

        a("current"), n(l.eventsLimit, !1, !1, !1, !1), r(), 

        v.wrap.on("click", ".eventsCalendar-day a", function (e) 
        {
            var month = (parseInt(v.wrap.attr("data-current-month"))+1);
            var year  = v.wrap.attr("data-current-year");
            var day   = $(this).parent().attr("rel");

            var impevent= $(".green-btn").hasClass('selected');
            var livelecture= $(".orange-btn").hasClass('selected');

            var type='all';
            if(impevent){
                var type='google';
            }else if(livelecture){
                var type='lectures';
            }

           // var start_date= year+'-'+month+'-'+day;

            getEventsOnSelectedDate(type,year,month,day);
          
            //console.log($(this).parent().attr("rel"));
           /* $("#popup_box").hide(), e.preventDefault();

            var t = v.wrap.attr("data-current-year"),
                a = v.wrap.attr("data-current-month"),
                s = $(this).parent().attr("rel");

            n(!1, t, a, s, "day")*/
        }), 
        
        v.wrap.on("click", ".monthTitle", function (e) 
        {
           /* e.preventDefault();

            var t = v.wrap.attr("data-current-year"),
                a = v.wrap.attr("data-current-month");

            n(l.eventsLimit, t, a, !1, "month")*/
        })
    })    
};

// setting
$.fn.eventCalendar.defaults =
{
    eventsjson: "js/events.json",
    eventsLimit: 4,
    monthNames: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
    shortmonthNames: ["Jan", "Feb", "Mar", "April", "May", "June", "July", "Aug", "Sept", "Oct", "Nov", "Dec"],
    dayNames: ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
    dayNamesMed: ["Sunday", "Mon", "Tues", "Wednes", "Thurs", "Fri", "Satur"],
    dayNamesShort: ["SUN", "MON", "TUE", "WED", "THU", "FRI", "SAT"],
    currentYear: (new Date).getFullYear(),
    txt_noEvents: "There are no events currently listed for this date.",
    txt_SpecificEvents_prev: "",
    txt_SpecificEvents_after: "events:",
    txt_next: "next",
    txt_prev: "prev",
    txt_NextEvents: "TODAY'S EVENTS:",
    txt_GoToEventUrl: "See the event",
    showDayAsWeeks: !0,
    startWeekOnMonday: !0,
    showDayNameInCalendar: !0,
    showDescription: !1,
    onlyOneDescription: !0,
    openEventInNewWindow: !1,
    eventsScrollable: !1,
    dateFormat: "DD/MM/YYYY",
    jsonDateFormat: "timestamp",
    moveSpeed: 500,
    moveOpacity: .15,
    jsonData: "",
    cacheJson: !0
};