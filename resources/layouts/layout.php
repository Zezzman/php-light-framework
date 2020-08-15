<!DOCTYPE html>
<html lang="en">
<head>
<?= $this->header('header', $bag); ?>
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