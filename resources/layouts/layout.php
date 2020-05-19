<!DOCTYPE html>
<html lang="en">
<head>
<?= $this->header('header'); ?>
<?php
if (isset($bag['style']) && is_string($bag['style']))
{
    echo "<style>" . $bag['style'] . "</style>";
}
?>
</head>

<body>
    <?= $this->section('navbar', [
        'links' => config('NAV', ['Home' => ['link' => 'home/']])
    ]);
    ?>
    <?= $this->content; ?>
    <?= $this->footer('footer-min'); ?>
</body>

</html>