<!DOCTYPE html>
<html lang="en">
<?php 
$this->header('header');

$links = config('NAV', [
    'Home' => ['link' => 'home/'],
]);
$this->section('navbar', [
    'links' => config('NAV', ['Home' => ['link' => 'home/']])
]);
?>

<body>
    <?= $this->content; ?>
</body>

<?php $this->footer('footer-min'); ?>

</html>