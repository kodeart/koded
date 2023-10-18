<?php

return [
    'language' => 'English',
    'messages' => [
        'koded.handler.wrong.type' => '"type" must be an exception type',
        'koded.handler.missing' => 'Error handler must either be specified explicitly, or defined as a static method named "handle" that is a member of the given exception type',
        'koded.middleware.implements' => 'A middleware class {0} must implement {1}',

        'koded.router.noSlash' => 'URI template must begin with \'/\'',
        'koded.router.duplicateSlashes' => 'URI template has duplicate slashes',
        'koded.router.pcre.compilation' => 'PCRE compilation error. {0}',
        'koded.router.invalidRoute.title' => 'Invalid route. No regular expression provided',
        'koded.router.invalidRoute.detail' => 'Provide a proper PCRE regular expression',
        'koded.router.invalidParam.title' => "Invalid route parameter type '{0}'",
        'koded.router.invalidParam.detail' => 'Use one of the supported parameter types',
        'koded.router.duplicateRoute.title' => 'Duplicate route',
        'koded.router.duplicateRoute.detail' => 'Detected a multiple route definitions. The URI template for "%s" conflicts with an already registered route "%s".',
        'koded.router.multiPaths.title' => 'Invalid route. Multiple path parameters in the route template detected',
        'koded.router.multiPaths.detail' => 'Only one "path" type is allowed as URI parameter',
    ]
];
