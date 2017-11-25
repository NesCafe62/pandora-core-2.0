<h2><?= ($form->isUpdate) ? '_modelName_ edit' : '_modelName_ add' ?><h2>
<form method="post" action="<?= $app->uri ?>">
<?php
	// $form->input('title', ['label' => 'Title']);
	// $form->input('count', ['label' => 'Count']);
	if ($form->isUpdate) {
		$form->hidden('id');
	}

	$form->buttonSubmit(($form->isUpdate) ? 'Save' : 'Add');
	$form->buttonLink('Cancel', $uriBack);
?>
</form>