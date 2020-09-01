<?php
/**
 * Generates Navbar from $bag
 */
if (empty($bag['links'])) return;

$uri = $this->controller->getRequest()->uri;
$links = $bag['links'];
$left = '';
$right = '';
foreach ($links as $name => $link) {
    if (($link['hide'] ?? true) === false) continue;
    $navItem = $link;
    $navItem['name'] = $link['name'] ?? $name;
    if (isset($link['target'])) $navItem['target'] = 'target="'. $link['target'] . '"';
    if (isset($link['data']))
    {
        $data = '';
        foreach ($link['data'] as $name => $value) {
            $data .= "data-$name=\"$value\" ";
        }
        $navItem['data'] = $data;
    }
    if (isset($link['link'])) {
        $style = '<li class="nav-item '. (($link['link'] === $uri) ? 'active' : '').
            '"><a class="nav-link {class}" href="{link}" {target} {data}>{prepend}{name}{append}</a></li>';
    } elseif (isset($link['button'])) {
        $style = '<li class="nav-item"><button class="btn nav-link nav-button {class}" '.
            'id="{button}" style="{style}" {data}>{prepend}{name}{append}</button></li>';
    }
    $item = System\Helpers\QueryHelper::scanCodes($navItem, $style ?? '');
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
if (! empty($left)) $left = '<ul class="navbar-nav mr-auto">'. $left . '</ul>';
if (! empty($right)) $right = '<ul class="navbar-nav ml-auto">' . $right . '</ul>';

if (! empty($navBrand = ($bag['BRAND'] ?? false)))
    $brand = '<a class="navbar-brand" href="' . $navBrand['link'] . '">' . $navBrand['name'] . '</a>';
if (! empty($left) || ! empty($right))
    $entries = '<div class="collapse navbar-collapse" id="navbar-collapse-div">' . $left . $right . '</div>';

$this->card(config('LAYOUT.NAV'), ['brand' => ($brand ?? ''), 'entries' => ($entries ?? '')]);