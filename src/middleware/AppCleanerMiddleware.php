<?php

class AppCleanerMiddleware {
    public function process($request, $response, $next) {
        // clean property
        $next->unsetProperty('routes');

        $response = $next->process($request, $response, $next);

        $next->unsetProperty('class');
        $next->unsetProperty('classList');
        $next->unsetProperty('pathList');
        $next->unsetProperty('cache');
        $next->unsetProperty('pathListCache');

        return $response;
    }
}