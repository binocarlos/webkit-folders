<form id="<?= $data->arguments['id'] ?>" name="<?= $data->arguments['id'] ?>" method="POST" action="<?= Kohana::config('webkitfolders.form_controller_uri') ?>" enctype="multipart/form-data">
<input type="hidden" name="model" value="<?= $data->arguments['model'] ?>">
<input type="hidden" name="destination" value="<?= $data->arguments['destination'] ?>">
<input type="hidden" name="redirect" value="<?= $data->arguments['redirect'] ?>">
<input type="hidden" name="name_field" value="<?= $data->arguments['name_field'] ?>">
<input type="hidden" name="dbconfig" value="<?= $data->arguments['dbconfig'] ?>">
<?= $contents ?>
</form>