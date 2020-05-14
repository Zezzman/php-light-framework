<!DOCTYPE html>
<html lang="en">
<head>
<?php $this->header('header'); ?>
<?php
if (isset($bag['style']) && is_string($bag['style']))
{
    echo "<style>" . $bag['style'] . "</style>";
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