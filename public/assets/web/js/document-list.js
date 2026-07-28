
function submitFrm()
{
  $('#btn-sub').prop('disabled', true);
  $('#checkListForm').submit() ;
  // $('#loader_image').show();
  $.LoadingOverlay("show", {
           background  : "rgba(165, 190, 100, 0)",
        });
}

function getDocument(doc_id)
{
	console.log(WEBURL);
	$.ajax({
	    url: WEBURL + '/online-appointment/generate-single-document',
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
	       console.log(responce);
	       $('#getSpecilistbtn').trigger('click');
	       $('#document_page').html(responce);
	    }
	}); 
}

function CancelDoc()
{
	var doc_id = $('#hd_doc_id').val();
	console.log(doc_id);
	$('input:checkbox[value="' +doc_id+ '"]').prop('checked', false);
}

