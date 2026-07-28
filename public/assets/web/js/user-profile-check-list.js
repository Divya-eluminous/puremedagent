// Check Whitch Record Is exist check list or document
//console.log($('#hd_exam').val());

// start added on 26-dec-24 for 274 issue
document.addEventListener("DOMContentLoaded", function () {
  var intervalId = setInterval(function () {
    var skipExamination = document.querySelector(".skkip-examination-button");
    if (skipExamination) {
      console.log("Special button found:", skipExamination);
      var btn = document.getElementById("btn_examination");
      btn.setAttribute("data-cache-bust", new Date().getTime());
      $("#btn_examination").hide();

      console.log("in 11111111..");

      submitExamination(btn);
      clearInterval(intervalId); 
    } else {
      console.log("Special button not found.");
    }
  }, 100);
});
// end added on 26-dec-24 for 274 issue 


let lastSlideContent = null;
let lastPerformansContent = null;
let slideIndex1 = 0;


$(document).ready(function()
{
    var slideIndex = 1;
    showSlides(slideIndex);
    var current = 1,current_step,next_step,steps;

    
});

// added by vijay on 2/4/2024 - new process CR
document.addEventListener("DOMContentLoaded", function () {
    var inputs = document.querySelectorAll(
        '#examinationForm  input[type="hidden"]'
    );

    // Check if all inputs are hidden
    var allHidden = Array.from(inputs).every(function (input) {
        return input.type === "hidden";
    });
    var submitSection = document.getElementById("submitSection");
    var skip_btn = $("#skipBookBtn").val();
    if (allHidden && skip_btn == 1) {
        submitSection.style.display = "none";
              console.log("in 222222..");

        //submitExamination(document.getElementById("examinationForm"));
    }
});

steps = $("fieldset").length;
  $(document).on('click', '.continue', function(event){   
  //$(".continue").click(function(){
    console.log('in continue');

     $("html, body").animate({
          scrollTop: 0
    }, 1000); // 1000 milliseconds (1 second) for the animation 


     $.LoadingOverlay("show", {
           background  : "rgba(165, 190, 100, 0)",
     });

    current_step = $(this).parent();
     next_step = $(this).parent().next();

    // next_step = $(this).parent().next('fieldset'); //added on 20-dec-23
  

     console.log("current_step");
     console.log(current_step);
      console.log("next_step");
     console.log(next_step);

    var key = $(this).attr('key');
    var id = $(this).attr('id');

    var last_id = $("#last_id").val();
     
      if(id!=last_id)
      {
         console.log("in id is not same to last id");

         $.LoadingOverlay("hide", {
                 background  : "rgba(165, 190, 100, 0)",
              });

        next_step.show();
        current_step.hide();   

     }else
     {
         console.log("in else part");
       
          $.LoadingOverlay("hide", {
                 background  : "rgba(165, 190, 100, 0)",
              });

     }//else


  });

 
  var all_rows = [];
  var idarr=[];
  var html = '';
  $(document).on('click', '.book', function(event){   
 // $(".book").click(function()
  
     console.log('in book function');

      $("html, body").animate({
          scrollTop: 0
      }, 1000); // 1000 milliseconds (1 second) for the animation 

     
      $.LoadingOverlay("show", {
           background  : "rgba(165, 190, 100, 0)",
     });

    current_step = $(this).parent();
    next_step = $(this).parent().next();    
    var key = $(this).attr('key');
    var id = $(this).attr('id');

    idarr[key] = id;
    all_rows.push(idarr);

    html ='<input type="hidden"  class="form-check-input checkboxlength"  name="app_services['+key+']" value="'+id+'"  />';
    $("#examinationForm").append(html);
   
    var last_id = $("#last_id").val();

    if(id!=last_id)
    {
         $.LoadingOverlay("hide", {
                 background  : "rgba(165, 190, 100, 0)",
              });
       next_step.show();
       current_step.hide();   
    }
    else
    {
      //alert('end');
       $.LoadingOverlay("hide", {
                 background  : "rgba(165, 190, 100, 0)",
              });
    }//else
    
  });


if($('#hd_examination_flag').val() == 1)
{
    $('#main_div').append($('#hd_exam').val());
    $('#examination_div').removeClass('collapse');
    $('#examination_div').removeClass('show');
    $('#examination_div').hide();

    $('#main_div').append($('#hd_performance').val());
    $('#performance_div').addClass('show');
    $('#performance_div').show();
    $('#demo').removeClass('show');
    $('#demo').hide();
    $('#document').removeClass('show');
    $('#document').hide();
}
else if($('#hd_exam').val() != '')
{
    $('#main_div').append($('#hd_exam').val());
    $('#examination_div').addClass('show');
    $('#examination_div').show();
    $('#performance_div').removeClass('show');
    $('#performance_div').hide();
    $('#demo').removeClass('show');
    $('#demo').hide();
    $('#document').removeClass('show');
    $('#document').hide();
}

if (
    $("#hd_performance_checklist").val() == 1 &&
    $("#hd_examination_flag").val() != 1
) {
    $("#main_div").append($("#hd_performance").val());
    $("#examination_div").removeClass("show");
    $("#examination_div").hide();
    $("#performance_div").removeClass("show");
    $("#performance_div").hide();
    $("#demo").removeClass("show");
    $("#demo").hide();
    $("#document").removeClass("show");
    $("#document").hide();
}

if($('#hd_general_doc').val() == 1 && $('#hd_service_doc').val() ==1)
{
    $('#main_div').append($('#hd_document').val());
    $('#document').removeClass("show")
    // $('#document').css('display','block');
    $('#document').hide();
    $('#examination_div').removeClass('show');
    $('#examination_div').hide();
    $('#performance_div').removeClass('show');
    $('#performance_div').hide();
    $('#demo').removeClass('show');
    $('#demo').hide();
   
}
else if($('#hd_document').val() != '')
{
    $('#main_div').append($('#hd_document').val());
    $('#document').addClass("show")
    $('#document').css('display','block');
    $('#document').show();
    $('#examination_div').removeClass('show');
    $('#examination_div').hide();
    $('#performance_div').removeClass('show');
    $('#performance_div').hide();
    $('#demo').removeClass('show');
    $('#demo').hide();  
}


// if($('#hd_type').val() == 0)
// {
// 	$('#demo').addClass('show');
// 	$('#demo').show();
// 	$('#document').removeClass('show');
// 	$('#document').hide();
// }
// else
// {
// 	$('#document').addClass('show');
// 	$('#document').show();
// 	$('#demo').removeClass('show');
// 	$('#demo').hide();
// }
function submitFrm(element)
{
    const $this    = $(element).closest("form");
    const action   = $("#checkListForm").attr('action');
    const formData = new FormData($this[0]);

    $.LoadingOverlay("show", {
           background  : "rgba(165, 190, 100, 0)",
        });

    axios.post(action, formData)
    .then(function (response) {
        const resp = response.data;
        var getHtmlForPerformanceCheckList = resp.getHtmlForPerformanceCheckList;
        

        if (resp.status == 'success')
        {
        	    if(resp.exaination_html !='')
            	{
            		$('#main_div').append(resp.exaination_html);
            		$('#examination_div').addClass('show');
            		$('#examination_div').show();
            		$('#demo').removeClass('show');
            		$('#demo').hide();
            		$('#document').removeClass('show');
            		$('#document').hide();
            	}
            	else if(resp.getAllDocumentList.length>0)
            	{
            		$('#main_div').append(resp.document_html);
            		$('#examination_div').removeClass('show');
            		$('#examination_div').hide();
            		$('#performance_div').removeClass('show');
            		$('#performance_div').hide();
            		$('#demo').removeClass('show');
            		$('#demo').hide();
            		$('#document').addClass('show');
            		$('#document').show();
            	}
            	else
            	{
            		toastr.success(resp.msg);
		            $.LoadingOverlay("hide", {
			           background  : "rgba(165, 190, 100, 0)",
			        });

			        setTimeout(function () {
		               window.location.href = resp.url;
		            }, 2000)
            	}
    		
    		$.LoadingOverlay("hide", {
	           background  : "rgba(165, 190, 100, 0)",
	        });
        }
        else
        {
    		toastr.success(resp.msg);
            $.LoadingOverlay("hide", {
	           background  : "rgba(165, 190, 100, 0)",
	        });
	        setTimeout(function () {
               
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

function submitPerformanceFrm(element,index)
{
    console.log("in submitPerformanceFrm");
    console.log(index);
    
    const $this    = $(element).closest("form");
    const action   = $("#performancecheckListForm").attr('action');
    const formData = new FormData($this[0]);

      formData.append('index', index);


    $.LoadingOverlay("show", {
           background  : "rgba(165, 190, 100, 0)",
        });

    axios.post(action, formData)
    .then(function (response) {
        const resp = response.data;
        var getHtmlForPerformanceCheckList = resp.getHtmlForPerformanceCheckList;
        

        if (resp.status == 'success')
        {
            // if(resp.exaination_html !='')
            //     {
            //         $('#main_div').append(resp.exaination_html);
            //         $('#examination_div').addClass('show');
            //         $('#examination_div').show();
            //         $('#demo').removeClass('show');
            //         $('#demo').hide();
            //         $('#document').removeClass('show');
            //         $('#document').hide();
            //     }
            //     else 
                if(resp.getAllDocumentList.length>0)
                {
                    $('#main_div').append(resp.document_html);
                    $('#examination_div').removeClass('show');
                    $('#examination_div').hide();
                    $('#performance_div').removeClass('show');
                    $('#performance_div').hide();
                    $('#demo').removeClass('show');
                    $('#demo').hide();
                    $('#document').addClass('show');
                    $('#document').show();
                }
                else
                {
                    toastr.success(resp.msg);
                    $.LoadingOverlay("hide", {
                       background  : "rgba(165, 190, 100, 0)",
                    });

                    setTimeout(function () {
                       window.location.href = resp.url;
                    }, 2000)
                }
            
            $.LoadingOverlay("hide", {
               background  : "rgba(165, 190, 100, 0)",
            });
        }
        else
        {
            toastr.success(resp.msg);
            $.LoadingOverlay("hide", {
               background  : "rgba(165, 190, 100, 0)",
            });
            setTimeout(function () {
               
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
function submitExamination(element)
{
    var key = $(element).attr('key');
    var id = $(element).attr('id');
    var is_book_or_continue = $(element).attr('is_booked');

    const $this    = $(element).closest("form");
    const action   = $("#examinationForm").attr('action');
    const formData = new FormData($this[0]);

    if(key && id && is_book_or_continue==1)
    {
      var app_serviceid = "app_services["+key+"]";
      formData.append(app_serviceid, id);
    }
  
    for (let [key, value] of formData.entries()) {
     console.log(key, ':', value);
    }

    $.LoadingOverlay("show", {
           background  : "rgba(165, 190, 100, 0)",
        });

    axios.post(action, formData)
    .then(function (response) {
        const resp = response.data;
        var getHtmlForPerformanceCheckList = resp.getHtmlForPerformanceCheckList;
        
        console.log(resp);
        if (resp.status == 'success')
        {
        	if(getHtmlForPerformanceCheckList !='')
        	{
        		
        		$('#main_div').append(getHtmlForPerformanceCheckList);
        		$('#examination_div').removeClass('show');
        		$('#examination_div').hide();
        		$('#demo').removeClass('show');
        		$('#demo').hide();
        		$("#demo").remove();
        		$('#document').removeClass('show');
        		$('#document').hide();
        		$('#performance_div').addClass('show');
        		$('#performance_div').show();
                var slideIndex = 1;
                showPerformanceSlides(slideIndex);
                
        	}
        	else if(resp.getAllDocumentList.length>0)
        	{

        		// $('#main_div').append(resp.getAllDocumentList);
        		// $('#examination_div').removeClass('show');
        		// $('#examination_div').hide();
        		// $('#demo').removeClass('show');
        		// $('#demo').hide();
        		// $("#demo").remove();
        		// $('#performance_div').removeClass('show');
        		// $('#performance_div').hide();
        		// $('#document').addClass('show');
        		// $('#document').show();

                $("#main_div").append(resp.document_html);
                $("#examination_div").removeClass("show");
                $("#examination_div").hide();
                $("#performance_div").removeClass("show");
                $("#performance_div").hide();
                $("#demo").removeClass("show");
                $("#demo").hide();
                $("#document").addClass("show");
                $("#document").show();
        	}
            else
            {
                console.log("dfsdfhsdfd----->")
                toastr.success(resp.msg);
                $.LoadingOverlay("hide", {
                   background  : "rgba(165, 190, 100, 0)",
                });

                setTimeout(function () {
                   window.location.href = resp.url;
                }, 2000)
            }
    		
    		$.LoadingOverlay("hide", {
	           background  : "rgba(165, 190, 100, 0)",
	        });
        }
        else
        {
    		toastr.success(resp.msg);
            $.LoadingOverlay("hide", {
	           background  : "rgba(165, 190, 100, 0)",
	        });
	        setTimeout(function () {
               
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

        // const errorBag = error.response.data.errors;

        // $.each(errorBag, function (fieldName, value) {
            
        //     $('.err_' + fieldName).closest('.form-group').addClass('has-error has-danger');
        //     $('.err_' + fieldName).text(value[0]).closest('span').show();
        // })
    });

    return false;
}

// submitting form after validation
$('#checkListForm').validator().on('submit', function (e)  
{
    if (!e.isDefaultPrevented()) {
        const $this = $(this);
        const action = $this.attr('action');
        
        const formData = new FormData($this[0]);
      
        $.LoadingOverlay("show", {
           background  : "rgba(165, 190, 100, 0)",
        });

        axios.post(action, formData)
            .then(function (response) {
                const resp = response.data;
              
                var getAllDocumentList = resp.getAllDocumentList
            
                if (resp.status == 'success')
                {
                	if(resp.exaination_html !='')
                	{
                		$('#main_div').append(resp.exaination_html);
                		$('#examination_div').addClass('show');
	            		$('#examination_div').show();
                		$('#demo').removeClass('show');
	            		$('#demo').hide();
	            		$('#document').removeClass('show');
	            		$('#document').hide();
                	}
                	else if(resp.getAllDocumentList.length>0)
                	{
                		$('#examination_div').removeClass('show');
	            		$('#examination_div').hide();
                		$('#demo').removeClass('show');
	            		$('#demo').hide();
	            		$('#document').addClass('show');
	            		$('#document').show();
                	}
            		
            		$.LoadingOverlay("hide", {
			           background  : "rgba(165, 190, 100, 0)",
			        });
                }
                else
                {
            		toastr.success(resp.msg);
                    $.LoadingOverlay("hide", {
			           background  : "rgba(165, 190, 100, 0)",
			        });
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

$('#examinationForm').validator().on('submit', function (e)  
{
    if (!e.isDefaultPrevented()) {
        const $this = $(this);
        const action = $this.attr('action');
        
        const formData = new FormData($this[0]);
      
        
        $.LoadingOverlay("show", {
           background  : "rgba(165, 190, 100, 0)",
        });

        axios.post(action, formData)
            .then(function (response) {
                const resp = response.data;
               
                var getAllDocumentList = resp.getAllDocumentList
              
                if (resp.status == 'success')
                {
                	if(resp.exaination_html !='')
                	{
                		$('#main_div').append(resp.exaination_html);
                		$('#examination_div').addClass('show');
	            		$('#examination_div').show();
                		$('#demo').removeClass('show');
	            		$('#demo').hide();
	            		$('#document').removeClass('show');
	            		$('#document').hide();
                	}
                	else if(resp.getAllDocumentList.length>0)
                	{
                		$('#examination_div').removeClass('show');
	            		$('#examination_div').hide();
                		$('#demo').removeClass('show');
	            		$('#demo').hide();
	            		$('#document').addClass('show');
	            		$('#document').show();
                	}
            		
            		$.LoadingOverlay("hide", {
			           background  : "rgba(165, 190, 100, 0)",
			        });
                }
                else
                {
            	
            		toastr.success(resp.msg);
                    $.LoadingOverlay("hide", {
			           background  : "rgba(165, 190, 100, 0)",
			        });
			        setTimeout(function () {
                       
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

function getDocument(doc_id)
{
	$.ajax({
	    url: WEBURL + '/user-profile/generate-single-document',
	    type: "POST",
	    headers: {
	    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
	    },
	    data: 
	    {
	        'doc_id':doc_id, 
	    },
	    async:false,
	    success: function(responce)
	    {
	       $('#getSpecilistbtn').trigger('click');
	       $('#document_page').html(responce);
	    }
	}); 
}

function CancelDoc()
{
	var doc_id = $('#hd_doc_id').val();
	$('input:checkbox[value="' +doc_id+ '"]').prop('checked', false);
}


function getPerformanceCheckList(patient_id,appointment_id)
{
	 $.ajax({
              type: "GET",
              url: WEBURL + '/user-profile/getPerformanceChecklist',
              data: 'patient_id=' + patient_id+'appointment_id=' + appointment_id, 
              success: function (response) 
              {

              }
            });
}


// Slider code
var slideIndex = 1;

showSlides(slideIndex);

function plusSlides(n) {
  showSlides(slideIndex += n);
}

function currentSlide(n) {
  showSlides(slideIndex = n);
}

function currentPerformanceSlide(n) {
  showPerformanceSlides(slideIndex = n);
}

function plusPerformanceSlides(n) {

  var generalCheckListCount = document
    .getElementById("checklist-data")
    .getAttribute("data-count");
  console.log(generalCheckListCount);
  console.log("slideIndex", slideIndex);
  if (slideIndex == generalCheckListCount) {
    callPerformanceFunctionOnLastSlide();
  }  
    
  showPerformanceSlides(slideIndex += n);
}

function showSlides(n) {
  var i;
  var slides = document.getElementsByClassName("mySlides");
  var dots = document.getElementsByClassName("dot");
  if (n > slides.length) {slideIndex = 1}    
  if (n < 1) {slideIndex = slides.length}
  for (i = 0; i < slides.length; i++) {
      slides[i].style.display = "none";  
  }
  for (i = 0; i < dots.length; i++) {
      dots[i].className = dots[i].className.replace(" active", "");
  }
  console.log(slides[slideIndex-1]);
  // if(slides[slideIndex-1])
  // {
  //   console.log("lllll");
   var previouseSide = slides[slideIndex-1];
   if(typeof previouseSide !== 'undefined')
   {
     slides[slideIndex-1].style.display = "block"; 
   }
    
  //}
  

}

function showPerformanceSlides(n) {
  var i;

   
 
  var slides = document.getElementsByClassName("myPerformanceSlides");
  var dots = document.getElementsByClassName("dot");
  if (n > slides.length) {slideIndex = 1}    
  if (n < 1) {slideIndex = slides.length}
  for (i = 0; i < slides.length; i++) {
      slides[i].style.display = "none";  
  }

  for (i = 0; i < dots.length; i++) {
      dots[i].className = dots[i].className.replace(" active", "");
  }
  slides[slideIndex-1].style.display = "block"; 

}//





function submitChecklist(element, index) {
  const $this = $(element).closest("form");
  const action = $("#checkListForm").attr("action");
  const formData = new FormData();
  const checklistData = $this.find(`input[name^='check_list[${index}]']`);

  checklistData.each(function () {
    formData.append($(this).attr("name"), $(this).val());
  });

  formData.append("chk_type", $("#chk_type").val());
  showSlides1((slideIndex1 += 1));
  // Show loading overlay
  $.LoadingOverlay("show", {
    background: "rgba(165, 190, 100, 0)",
  });

  axios
    .post(action, formData)
    .then(function (response) {
      const resp = response.data;

      // Handle the success response
      if (resp.status == "success") {
        toastr.success(resp.msg);
        $.LoadingOverlay("hide", {
          background: "rgba(165, 190, 100, 0)",
        });

        lastSlideContent = {
          exaination_html: resp.exaination_html,
          document_html: resp.document_html,
          getAllDocumentList: resp.getAllDocumentList,
          url: resp.url,
          msg: resp.msg,
        };

      } else {
        toastr.success(resp.msg);
        $.LoadingOverlay("hide", {
          background: "rgba(165, 190, 100, 0)",
        });
        setTimeout(function () {}, 2000);
      }

      // Handle errors if any
      if (resp.status == "error") {
        $(".card-body").LoadingOverlay("hide");
        toastr.error(resp.msg);

        const errorBag = resp.errors;

        $.each(errorBag, function (fieldName, value) {
          $(".err_" + fieldName)
            .closest(".form-group")
            .addClass("has-error has-danger");
          $(".err_" + fieldName)
            .text(value[0])
            .closest("span")
            .show();
        });
      }
    })
    .catch(function (error) {
      $(".card-body").LoadingOverlay("hide");

      const errorBag = error.response.data.errors;

      $.each(errorBag, function (fieldName, value) {
        $(".err_" + fieldName)
          .closest(".form-group")
          .addClass("has-error has-danger");
        $(".err_" + fieldName)
          .text(value[0])
          .closest("span")
          .show();
      });
    });

  return false;
}

function submitPerformanceChecklist(element, index) {

   console.log("submitPerformanceChecklist index==>");
  console.log(index);

  
  const $this = $(element).closest("form");
  const action = $("#performancecheckListForm").attr("action");
  //const formData = new FormData();
 const formData = new FormData($this[0]);
  formData.append('index', index);
  //commented on 4-march-25 temp
 /* const checklistData = $this.find(`input[name^='check_list[${index}]']`);

  checklistData.each(function () {
    formData.append($(this).attr("name"), $(this).val());
  });

  formData.append("chk_type", $("#chk_type").val());*/


  showPerformanceSlides((slideIndex += 1));
  $.LoadingOverlay("show", {
    background: "rgba(165, 190, 100, 0)",
  });

  axios
    .post(action, formData)
    .then(function (response) {
      const resp = response.data;

      if (resp.status == "success") {

        lastPerformansContent = resp;

        console.log("lastPerformansContent==>");
        console.log(lastPerformansContent);
        
        toastr.success(resp.msg);
        $.LoadingOverlay("hide", {
          background: "rgba(165, 190, 100, 0)",
        });
      } else {
        toastr.success(resp.msg);
        $.LoadingOverlay("hide", {
          background: "rgba(165, 190, 100, 0)",
        });
        setTimeout(function () {}, 2000);
      }

      if (resp.status == "error") {
        $(".card-body").LoadingOverlay("hide");
        toastr.error(resp.msg);

        const errorBag = resp.errors;

        $.each(errorBag, function (fieldName, value) {
          $(".err_" + fieldName)
            .closest(".form-group")
            .addClass("has-error has-danger");
          $(".err_" + fieldName)
            .text(value[0])
            .closest("span")
            .show();
        });
      }
    })
    .catch(function (error) {
      $(".card-body").LoadingOverlay("hide");

      const errorBag = error.response.data.errors;

      $.each(errorBag, function (fieldName, value) {
        $(".err_" + fieldName)
          .closest(".form-group")
          .addClass("has-error has-danger");
        $(".err_" + fieldName)
          .text(value[0])
          .closest("span")
          .show();
      });
    });

  return false;
}


function showSlides1(index) {
  let slides = document.getElementsByClassName("mySlides");

  // Loop back to the first slide if we reach the end
  if (index >= slides.length) {
    slideIndex1 = 0; // Loop to the first slide
  }

  // Go to the last slide if we go backward
  if (index < 0) {
    slideIndex1 = slides.length - 1; // Go to the last slide
  }

  // Hide all slides
  for (let i = 0; i < slides.length; i++) {
    slides[i].style.display = "none";
  }

  // Show the current slide
  slides[slideIndex1].style.display = "block";
}

// Function to move between slides
function moveSlide(n) {
    console.log("in moveSlide..");
    
  var generalCheckListCount = document
    .getElementById("general-checklist-data")
    .getAttribute("data-count");
  console.log(generalCheckListCount);

  if (slideIndex1 == generalCheckListCount - 1) {
    callFunctionOnLastSlide();
  }
  console.log("slideIndex1>>", slideIndex1);
  showSlides1((slideIndex1 += n));
}


function callFunctionOnLastSlide() {



  const resp = lastSlideContent;
 console.log("resp", resp);
  if (resp) {
    if (resp.exaination_html != "") {
      $("#main_div").append(resp.exaination_html);
      $("#examination_div").addClass("show");
      $("#examination_div").show();
      $("#demo").removeClass("show");
      $("#demo").hide();
      $("#document").removeClass("show");
      $("#document").hide();
    } else if (resp.getAllDocumentList.length > 0) {
      $("#main_div").append(resp.document_html);
      $("#examination_div").removeClass("show");
      $("#examination_div").hide();
      $("#performance_div").removeClass("show");
      $("#performance_div").hide();
      $("#demo").removeClass("show");
      $("#demo").hide();
      $("#document").addClass("show");
      $("#document").show();
    }else{
      setTimeout(function () {
            window.location.href = resp.url;
          }, 2000);
    }
}else{
   toastr.error('Bitte reichen Sie mindestens eine Checkliste ein');
}
}

function callPerformanceFunctionOnLastSlide() {

    console.log("in callPerformanceFunctionOnLastSlide==>");

  var resp = lastPerformansContent;
  console.log("resp", resp);

  
  if (resp) {
  if (resp.getAllDocumentList.length > 0) {
    $("#main_div").append(resp.document_html);
    $("#examination_div").removeClass("show");
    $("#examination_div").hide();
    $("#performance_div").removeClass("show");
    $("#performance_div").hide();
    $("#demo").removeClass("show");
    $("#demo").hide();
    $("#document").addClass("show");
    $("#document").show();
  } else {
    toastr.success(resp.msg);
    $.LoadingOverlay("hide", {
      background: "rgba(165, 190, 100, 0)",
    });

    setTimeout(function () {
      window.location.href = resp.url;
    }, 2000);
  }
}else{
    toastr.error('Bitte reichen Sie mindestens eine Checkliste ein');
}
}


// Initially show the first slide
showSlides1(slideIndex1);