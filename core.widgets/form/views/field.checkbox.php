<?php

/** @var string $name */
/** @var mixed $value */

$isChecked = ($value != '' || $value !== 0);

?>
<input type="checkbox" name="<?= $name ?>" value="on" <?= ($isChecked) ? 'checked="checked"' : '' ?>>



