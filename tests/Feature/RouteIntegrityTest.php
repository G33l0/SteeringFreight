<?php

namespace Tests\Feature;

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Guards against a route that points nowhere.
 *
 * A resource route registers seven actions whether or not the controller has
 * them, which is how a URL that answers with a 500 gets shipped without anyone
 * noticing: nothing links to it, so no page test covers it.
 */
class RouteIntegrityTest extends TestCase
{
    public function test_every_route_points_at_a_method_that_exists(): void
    {
        $missing = [];

        foreach (Route::getRoutes() as $route) {
            /** @var RoutingRoute $route */
            $action = $route->getActionName();

            if ($action === 'Closure') {
                continue;
            }

            [$class, $method] = array_pad(explode('@', $action, 2), 2, '__invoke');

            if (! class_exists($class) || ! method_exists($class, $method)) {
                $missing[] = $route->uri().' → '.$action;
            }
        }

        $this->assertSame([], $missing, 'Routes pointing at a missing controller method: '.implode(', ', $missing));
    }

    public function test_every_named_route_has_a_unique_name(): void
    {
        $names = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            if ($name !== null) {
                $names[] = $name;
            }
        }

        $this->assertSame(array_unique($names), $names, 'Duplicate route names would make route() ambiguous.');
    }
}
