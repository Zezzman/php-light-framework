<?php
if (is_array($bag) && ! empty($bag)) {
    $defaults = [
        'id' => '',
        'class' => 'col-11 mx-auto my-0',
    ];
    if (isset($bag['message'])) {
        $alert['type'] = 'alert ' . ($alert['type'] ?? 'alert-warning');
        $style = $bag['style'] ?? '<div id="{id}" class="{class} {type}">{message}</div>';
        echo System\Helpers\QueryHelper::scanCodes($bag, $style, $defaults);
    } else {
        foreach ($bag as $key => $alert) {
            if (isset($alert['message'])) {
                $alert['type'] = 'alert ' . ($alert['type'] ?? 'alert-warning');
                $style = $alert['style'] ?? '<div id="{id}" class="{class} {type}">{message}</div>';
                echo System\Helpers\QueryHelper::scanCodes($alert, $style, $defaults);
            }
        }
    }
    
}
?>