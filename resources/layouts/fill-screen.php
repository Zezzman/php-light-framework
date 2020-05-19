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

<body class="body-grid" style="height: 100vh">
    <div class="grid-cell">
        <?= $this->section('navbar', [
            'links' => config('NAV', ['Home' => ['link' => 'home/']])
        ]);
        ?>
    </div>
    <div class="grid-cell">
        <?= $this->content; ?>
    </div>
    <div class="grid-cell">
        <?= $this->footer('footer-min'); ?>
    </div>
</body>
</html>