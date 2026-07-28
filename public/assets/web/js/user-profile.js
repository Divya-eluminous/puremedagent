$("#birth_date").mask("99-99-9999");

$("#birth_date").datepicker({
    // changeMonth: true,
    // changeYear: true,
    dateFormat: "dd-mm-yy",
    changeMonth: true,
    changeYear: true,
    yearRange: "1920:+0",
    startDate: new Date("1920-01-01"),
    maxDate: 0,
});

// document.addEventListener('DOMContentLoaded',
// function(){
//             checkCountryCode();
//         });

// function checkCountryCode() {
//     $(".err_format").text('');
//     const inputCountryCode = $("#format").val();
//     if (inputCountryCode == "0" || inputCountryCode == "00") {
//         $(".err_format").text('Ungültige Länder-Vorwahl eingegeben.').show();
//     }
// }


$("#userProfile")
    .validator()
    .on("submit", function (e) {
        if (!e.isDefaultPrevented()) {
            const $this = $(this);
            const action = $this.attr("action");
            const formData = new FormData($this[0]);
            // console.log(action);
            //  console.log(formData);
            // return false;
            $.LoadingOverlay("show", {
                background: "rgba(165, 190, 100, 0)",
            });

            axios
                .post(action, formData)
                .then(function (response) {
                    const resp = response.data;
                    $.LoadingOverlay("hide");
                    if (resp.status == "success") {
                        // $this[0].reset();

                        toastr.success(resp.msg);
                        setTimeout(function () {
                            window.location.href = resp.url;
                        }, 5000);
                    }

                    if (resp.status == "error") {
                        toastr.error(resp.msg);
                    }
                })
                .catch(function (error) {
                    $.LoadingOverlay("hide");

                    const errorBag = error.response.data.errors;

                    $.each(errorBag, function (fieldName, value) {
                        $(".err_" + fieldName)
                            .closest("div")
                            .addClass("has-error has-danger");
                        $(".err_" + fieldName)
                            .text(value[0])
                            .closest("span")
                            .show();
                    });
                });

            return false;
        }
    });

//Added by Roshani for chnage value after click on dropdown value
// document.addEventListener('DOMContentLoaded', function() {
//     const countryCodeSelect = document.getElementById('country_code');
//     const formatInput = document.getElementById('format');
//     const loginButton = document.getElementById('login-submit-btn'); // Corrected ID selectorfunction
//     function validateInput() 
//     {
//         const pattern = new RegExp(formatInput.pattern);
//         const isValid = pattern.test(formatInput.value);
//         if (isValid) 
//         {
//             $("#format").click(); // Trigger click event on element with ID 'format'
//             $("#format").blur();  // Trigger blur event on element with ID 'format'
//         } else {
//         }
//     }
//     countryCodeSelect.addEventListener('change', function() {
//         formatInput.value = this.value;
//         validateInput();
//     });
//     formatInput.addEventListener('input', validateInput);
//     validateInput(); // Initial validation on load 
// });
//Added by Roshani for chnage value after click on dropdown value
 function handleCountrySelect(el) {
      var input = document.getElementById('format');
      if (el.value === 'other') {
          input.value = '';
          input.focus();
      } else {
          input.value = el.value;
      }
  }