<div class="row">
    <div class="col-10 m-auto">
        <div class="row mt-3">
            <div class="col-12">
                <form action="" method="get" class="row">
                    <div class="col-10">
                        <input class="form-control" type="text" name="url" id="url-input" placeholder="file url" value="<?= \System\Helpers\HTTPHelper::get('url'); ?>" required>
                    </div>
                    <div class="col-2">
                        <button type="submit" class="btn btn-primary px-4">Submit</button>
                    </div>
                </form>
            </div>
            <div class="col-12 mt-1">
                <form action="" method="post" enctype="multipart/form-data">
                    <input type="file" name="file" id="path-input" placeholder="file path" required>
                    <button type="submit" class="btn btn-primary px-4">Submit</button>
                </form>
            </div>
        </div>
        <?php if (! empty($bag['image'])) { ?>
        <div class="row mt-3">
            <div class="col-12" id="image-data">
                <?= ($bag['image'] ?? ''); ?>
            </div>
        </div>
        <?php } ?>
    </div>
</div>