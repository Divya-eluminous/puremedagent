<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Download PDF on Button Click</title>
</head>
<body>

<!-- <a href="https://puregyn.puremed.biz/storage/tenancy/tenants/9b15a68114c94800aa29355b4d3c9944/check_list_pdf/Varg_VERH_16-01-24.pdf" download="https://puregyn.puremed.biz/storage/tenancy/tenants/9b15a68114c94800aa29355b4d3c9944/check_list_pdf/Varg_VERH_16-01-24.pdf">Download PDF</a> -->

<a href="{{ route('downladPdf') }}">download Pdf</a>

<form id="pdfForm" method="post"  action="{{ route('uploadpdf') }}" enctype="multipart/form-data">
  @csrf
 <input 
        type="file" 
        name="pdf" 
        class="form-control"  
        maxlength="250"        
    >

     <button type="submit" id="btn_sub" class="btn btn-success">@lang('admin.TITLE_SAVE_BUTTON')</button>
  </form>

</body>
</html>




<!-- <!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Download PDF on Button Click</title>
</head>
<body>

<button onclick="downloadPDF()">Download PDF</button>

<script>
function downloadPDF() {
  // Replace 'your_pdf_file.pdf' with the actual file path or URL
  var pdfFile = 'https://puregyn.puremed.biz/storage/tenancy/tenants/9b15a68114c94800aa29355b4d3c9944/check_list_pdf/Varg_VERH_16-01-24.pdf';
  
  // Append a timestamp to the URL to prevent caching
  pdfFile += '?timestamp=' + new Date().getTime();
  
  // Create an invisible link element
  var link = document.createElement('a');
  link.href = pdfFile;
  link.download = 'https://puregyn.puremed.biz/storage/tenancy/tenants/9b15a68114c94800aa29355b4d3c9944/check_list_pdf/Varg_VERH_16-01-24.pdf'; // Change the downloaded file name if needed
  document.body.appendChild(link);

  // Trigger the click event on the link
  link.click();
  console.log(link);

  // Remove the link from the DOM
  document.body.removeChild(link);
}
</script>

</body>
</html>
 -->

