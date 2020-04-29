<?php 
echo \System\Helpers\FileHelper::loadScripts((array) config('LAYOUT.FOOTER.SCRIPTS'));
// Print appending scripts
echo \System\Helpers\FileHelper::loadScripts($viewData->bag['scripts'] ?? []);
?>