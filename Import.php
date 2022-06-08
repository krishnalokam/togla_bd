<h1> Data Import </h1>

<div class="row">
    <div class="col-md-12">
        <div style="text-align:right">
            <input type="hidden" name="execute" value="1">
            <input class="btn btn-success btn-lg bold block" type="button" id="execute" value="Execute" />
        </div>
        <p id="start"> </p>
        <p id="end"> </p>
    </div>
</div>

<script>
$(document).ready(function() {

    $("#execute").click(function() {

        swal({
            type: "warning",
            title: "Are you sure",
            html: "to Import the data",
            showCancelButton: true,
            showConfirmButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: "Yes",
            cancelButtonText: "No"
        }, function(isConfirm) {

            if (isConfirm) {
                $("#execute").attr('disabled', true);
                $("#start").text("Data import process started");

                $.ajax({
                    url: "/api/widget/html/get/data_import_execute",
                    type: "POST",
                    data: {
                        execute: "1",
                    },
                    success: function(data) {
                        console.log("Data import successful");
                        $("#end").text("Data import process completed");
                    },
                });
            }
        });





    });

});
</script>