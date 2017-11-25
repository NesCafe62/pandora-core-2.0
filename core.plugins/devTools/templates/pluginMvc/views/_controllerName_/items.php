<h2>_modelName_s</h2>
<div class="_modelName_s">
	<?php foreach ($_modelName_s as $_modelName_): ?>
		<div class="_modelName_">
			<a href="/_modelName_s/<?= $_modelName_->id ?>/update">#<?= $_modelName_->id ?></a>
			<?php /* <div class="title"><?= $_modelName_->title ?></div> */ ?>
			<?php /* <div class="count">count: <?= $_modelName_->count ?></div> */ ?>
		</div>
	<?php endforeach; ?>
</div>
