<!DOCTYPE html>
<html lang="en">
<head>
<?php $this->header('header', config('APP')); ?>
<?php
if (! empty($style = \System\Helpers\FileHelper::loadFile('../public/assets/css/' . basename(__FILE__, '.php') . '.css')))
{
    echo "<style>$style</style>";
}
?>
</head>
<?php
$this->section('navbar', [
    'links' => config('NAV', ['Home' => ['link' => 'home/']])
]);
?>

<body>
    <?= $this->content; ?>
</body>

<?php $this->footer('footer-min'); ?>

</html>