// function markAsDownloaded(apkId) {
//     $.ajax({
//         headers: {
//             'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
//         },
//         type: "POST",
//         url: ADMINURL + "/apks/mark-downloaded/" + apkId,
//         data: { id: apkId },  // Use an object for data
//         success: function(response) {
//             if (response.success) {  // Assuming the server response is structured with a 'success' field
//                 console.log("APK has been marked as downloaded.");

//                 // Remove the "New" badge after a successful update
//                 const badge = document.getElementById(`download-badge-${apkId}`);
//                 if (badge) {
//                     badge.remove();  // Remove the "New" badge
//                 }
//                 if(response.apk == false)
//                 {
//                     location.reload();
//                 }

//             } else {
//                 console.error("Failed to mark APK as downloaded.");
//             }
//         },
//         error: function(xhr, status, error) {
//             console.error("AJAX Error: ", error);
//         }
//     });
// }


function markAsDownloaded(apkId, filepath) {
    $.ajax({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        },
        type: "POST",
        url: ADMINURL + "/apks/mark-downloaded/" + apkId,
        data: { id: apkId, path: filepath },
        success: function(response) {
            // Remove the "New" badge after a successful update
            const badge = document.getElementById(`download-badge-${apkId}`);
            if (badge) {
                badge.remove();
            }
            // Reload the page or update the UI based on the response
            location.reload();
        },
        error: function(xhr, status, error) {
            console.error("An error occurred:", error);
        }
    });
}

