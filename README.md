Authentication Module for Safan Framework
===============

REQUIREMENTS
------------
```
PHP > 5.4.0
gap-orm/gap >= 1.*
```

SETUP
------------

Enable "memcache" from Safan main config
If you're using [Composer](http://getcomposer.org/) for your project's dependencies, add the following to your "composer.json":

```
"require": {
    "safan-lab/auth": "1.*"
}
```

Update Modules Config List - safan-framework-standard/application/Settings/modules.config.php
```
<?php
return [
    // Safan Framework default modules route
    'Safan'         => 'vendor/safan-lab/safan/Safan',
    'SafanResponse' => 'vendor/safan-lab/safan/SafanResponse',
    // Write created or installed modules route here ... e.g. 'FirstModule' => 'application/Modules/FirstModule'
    'GapOrm'         => 'vendor/gap-db/orm/GapOrm',
    'Authentication' => 'vendor/safan-lab/auth/Authentication',
];
```

Add Configuration - safan-framework-standard/application/Settings/main.config.php
```
<?php
'init' => [
    ...
    'auth' => [
        'class'  => 'Authentication\AuthManager',
        'method' => 'init',
        'params' => [
            'token'       => 'any_token',
            'driver'      => 'memcacheAuth',
            'crossDomain' => false
        ]
    ],
    ...
]
```
