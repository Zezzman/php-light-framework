<head>
	<meta charset="utf-8">
	<title><?= ((isset($model) && ! empty($model->getTitle()))? $model->getTitle(): config('APP.NAME')); ?></title>
	<meta name="csrf-token" content="<?= System\Providers\SessionProvider::token(); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="<?= config('APP.DESCRIPTION'); ?>">
	<meta name="author" content="<?= config('APP.AUTHOR'); ?>">

	<?= System\Helpers\FileHelper::loadLinks((array) config('LAYOUT.HEADER.LINKS')); ?>
	<?= System\Helpers\FileHelper::loadScripts((array) config('LAYOUT.HEADER.SCRIPTS')); ?>
</head>