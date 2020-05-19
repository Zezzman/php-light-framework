<div class="container-fluid container-response container-<?= $model->responseCode; ?> h-100 overflow-auto">
    <div class="row text-center h-100">
        <div class="col-12 col-md-8 col-xl-4 mx-md-auto">
            <div class="row h-100 py-0">
                <div class="col-12 my-md-auto">
                    <h1><?= $model->responseCode; ?></h1>
                    <p class="lead"><?= $model->responseTitle; ?></p>
                    <p><?= $model->messages(); ?></p>
                    <p><?= $model->exception(); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>