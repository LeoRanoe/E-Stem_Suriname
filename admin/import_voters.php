<?php
include_once '../include/header.php';
include_once '../include/nav.php';
?>

<div class="container">
    <h1>Voter Import</h1>
    <form action="import_voters.php" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="csvFile">CSV File:</label>
            <input type="file" class="form-control-file" id="csvFile" name="csvFile">
        </div>
        <button type="submit" class="btn btn-primary" name="import">Import</button>
    </form>
</div>

<?php
include_once '../include/footer.php';
?>