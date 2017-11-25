<?php

/** @var string $path_root */
/** @var array $error */
/** @var array $messages */

$title = '';

$msg = $error->getMessage(); // $error['message'] ?? '';
$file = $error->getFile(); // $error['file'] ?? '';
$line = $error->getLine(); // $error['line'] ?? '';
$type = $error->getCode(); // $error['type'] ?? '';
$typeLabel = ucfirst(debug::getErrorTypeName($type));
$file = '/'.trimLeft($file, $path_root.'/');

?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="robots" content="noindex">
		<title><?= $title ?></title>
		<style>
			body {
				margin: 0;
				font-family: Verdana,"Geneva CY","DejaVu Sans",sans-serif;
				font-size: 13px;
			}
			h1, p, pre {
				margin: 0;
			}
			pre {
				display: inline;
			}
			a {
				outline: none;
			}
			.page-wrap {
				margin: 0 auto;
				max-width: 1100px;
				padding-top: 100px;
			}
			.page-wrap h1 {
				color: #707070;
				font-size: 32px;
				margin-bottom: 20px;
				margin-left: -1px;
				margin-right: 40px;
			}
			.info {
				margin-bottom: 30px;
			}
			.info p {
				color: #777777;
				font-size: 15px;
				margin-bottom: 10px;
			}
			.info .error-message {
				color: #656565;
				line-height: 26px;
			}
			.messages {
				color: #454545;
			}
			.messages .message-row {
				line-height: 18px;
				margin-bottom: 7px;
			}
			.messages .message-row div {
				display: inline;
				margin-right: 6px;
			}
			b {
				color: #03769C;
				font-weight: normal;
			}
		</style>
	</head>

	<body>
		<div class="page-wrap">
			<h1 class="error">Внутренняя ошибка сервера</h1>
			<div class="info">
				<p class="error-message">
					<b><?= $typeLabel ?></b>: <?= $msg ?> in <b><?= $file ?></b> on line <b><?= $line ?></b>
				</p>
				<?php /* echo '<p class="link-back">Вы можете <a href="'.$back_url.'">вернуться на предыдущую страницу</a></p>'; */ ?>
			</div>
			<div class="messages">
				<?php foreach ($messages as $msg): ?>
					<div class="message-row">
						<?= self::_out_error($msg) ?>
					</div>
				<?php endforeach; ?>
			<div>
		</div>
	</body>
</html>
<?php
exit($error['type']);
