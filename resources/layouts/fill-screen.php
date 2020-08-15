<!DOCTYPE html>
<html lang="en">
<head>
<?= $this->header('header', $bag); ?>
</head>

<body class="list-grid" style="height: 100vh">
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