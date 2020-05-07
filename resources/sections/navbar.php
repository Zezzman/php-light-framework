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
                ];
                if (isset($link['target']))
                {
                    $linkObj['target'] = 'target="'. $link['target'] . '"';
                }
                $item = System\Helpers\QueryHelper::scanCodes($linkObj, '<li class="nav-item {active}"><a class="nav-link" href="{link}" {target}>{name}</a></li>');
                if (isset($link['align'])) {
                    if ($link['align'] === 'right') {
                        $right .= $item;
                    }
                } else {
                    $left .= $item;
                }
            } elseif (isset($link['mail'])) {
                $linkObj = [
                    'name' => $name,
                    'mail' => $link['mail'],
                ];
                $item = System\Helpers\QueryHelper::scanCodes($linkObj, '<li class="nav-item"><a class="nav-link" href="mailto:{mail}">{name}</a></li>');
                if (isset($link['align'])) {
                    if ($link['align'] === 'right') {
                        $right .= $item;
                    }
                } else {
                    $left .= $item;
                }
            }
        }
    }
    if ($left != '')
    {
        $left = '<ul class="navbar-nav mr-auto">'. $left . '</ul>';
    }
    if ($right != '')
    {
        $right = '<ul class="navbar-nav ml-auto">' . $right . '</ul>';
    }
    $entries = '<div class="collapse navbar-collapse" id="navbar-collapse-div">' . $left . $right . '</div>';
    $this->card(config('LAYOUT.NAV'), ['nav-entries' => $entries]);
}
?>