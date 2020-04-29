<div class="container container-<?= $model->responseCode; ?>">
    <div class="row text-center vh-85">
        <div class="col-12 m-auto">
            <h1><?= $model->responseCode; ?></h1>
            <p class="lead"><?= $model->responseTitle; ?></p>
            <p><?= $model->messages(); ?></p>
            <p><?= $model->exception(); ?></p>
        </div>
    </div>
</div>