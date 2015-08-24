<?php

return array(
    // Authentication cli routes
    '/^(auth):([a-z0-9-]+):([a-z0-9-]+)$/i' => array(
        'type'       => 'RegExp',
        'module'     => 'Authentication',
        'controller' => '',
        'action'     => '',
        'matches'    => ['', '', 'controller', 'action'],
    ),
);