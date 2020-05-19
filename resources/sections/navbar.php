<?php
/**
 * Generates Navbar from $bag
 */
if (isset($bag['links'])) {
    $uri = $this->controller->getRequest()->uri;
    $links = $bag['links'];
    $left = '';
    $right = '';
    foreach ($links as $name => $link) {
        if (! isset($link['hide']) || $link['hide'] === false) {
            if (isset($link['link'])) {
                $linkObj = [
                    'name' => $name,
                    'link' => $link['link'],
                    'active' => ($link['link'] === $uri) ? 'active' : '',
                    'prepend' => $link['prepend'] ?? '',
                    'append' => $link['append'] ?? '',
                ];
                if (isset($link['target']))
                {
                    $linkObj['target'] = 'target="'. $link['target'] . '"';
                }
                $item = System\Helpers\QueryHelper::scanCodes($linkObj, '<li class="nav-item {active}"><a class="nav-link" href="{link}" {target}>{prepend}{name}{append}</a></li>');
                if (isset($link['align'])) {
                    if ($link['align'] === 'right') {
                        $right .= $item;
                    } else {
                        $left .= $item;
                    }
                } else {
                    $left .= $item;
                }
            } elseif (isset($link['mail'])) {
                $linkObj = [
                    'name' => $name,
                    'mail' => $link['mail'],
                    'prepend' => $link['prepend'] ?? '',
                    'append' => $link['append'] ?? '',
                ];
                $item = System\Helpers\QueryHelper::scanCodes($linkObj, '<li class="nav-item"><a class="nav-link nav-mail" href="mailto:{mail}">{prepend}{name}{append}</a></li>');
                if (isset($link['align'])) {
                    if ($link['align'] === 'right') {
                        $right .= $item;
                    } else {
                        $left .= $item;
                    }
                } else {
                    $left .= $item;
                }
            }  elseif (isset($link['button'])) {
                $linkObj = [
                    'name' => $name,
                    'id' => $link['button'],
                    'style' => $link['style'] ?? '',
                    'class' => $link['class'] ?? '',
                    'prepend' => $link['prepend'] ?? '',
                    'append' => $link['append'] ?? '',
                ];
                if (isset($link['data']))
                {
                    $linkObj['data'] = '';
                    foreach ($link['data'] as $name => $value) {
                        $linkObj['data'] .= "data-$name=\"$value\" ";
                    }
                }
                $item = System\Helpers\QueryHelper::scanCodes($linkObj, '<li class="nav-item"><button class="btn nav-link nav-button {class}" id="{id}" style="{style}" {data}>{prepend}{name}{append}</button></li>');
                if (isset($link['align'])) {
                    if ($link['align'] === 'right') {
                        $right .= $item;
                    } else {
                        $left .= $item;
                    }
                } else {
                    $left .= $item;
                }
            }
        }
    }
    if (! empty($left))
    {
        $left = '<ul class="navbar-nav mr-auto">'. $left . '</ul>';
    }
    if (! empty($right))
    {
        $right = '<ul class="navbar-nav ml-auto">' . $right . '</ul>';
    }
    $brand = '';
    $entries = '';
    if (! empty($navBrand = ($bag['BRAND'] ?? false)))
    {
        $brand = '<a class="navbar-brand" href="' . $navBrand['link'] . '">' . $navBrand['name'] . '</a>';
    }
    if (! empty($left) || ! empty($right))
    {
        $entries = '<div class="collapse navbar-collapse" id="navbar-collapse-div">' . $left . $right . '</div>';
    }
    $this->card(config('LAYOUT.NAV'), ['brand' => $brand, 'entries' => $entries]);
}
?>