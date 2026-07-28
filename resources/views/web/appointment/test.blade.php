
<h1>test page</h1>
<button class="btn-test" id="btn-test">Test</button>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script type="text/javascript">
$( document ).ready(function() {
     $("#btn-test").on("click", function () 
  {
  	const WEBURL = $('meta[name="web-path"]').attr('content');
  	alert("test function");
    //var userId = $(this).attr('data-id');
    doc_id = 1;
    $.ajax({
	    url: 'https://puregyn.puremed.biz/testcall',
	    type: "GET",
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
	       // $('#getSpecilistbtn').trigger('click');
	       // $('#document_page').html(responce);
	    }
	}); 
  });
});
 
</script>
