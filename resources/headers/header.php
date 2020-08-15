<meta charset="utf-8">
<title><?= ((isset($model) && ! empty($model->getTitle()))? $model->getTitle(): $bag['NAME'] ?? ''); ?></title>
<meta name="csrf-token" content="<?= \System\Providers\SessionProvider::token(); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="<?= $bag['DESCRIPTION'] ?? ''; ?>">
<meta name="author" content="<?= $bag['AUTHOR'] ?? ''; ?>">

<script>var remoteDomain = "<?= config('LINKS.PUBLIC'); ?>";</script>
<?= \System\Helpers\FileHelper::loadLinks(config('LAYOUT.HEADER.LINKS', [])); ?>
<?= \System\Helpers\FileHelper::loadScripts(config('LAYOUT.HEADER.SCRIPTS', [])); ?>
<?= (! empty(($bag['layout'] ?? ''))) ? "<style>" . (\System\Helpers\FileHelper::loadFile($bag['layout'])) . "</style>": ''; ?>