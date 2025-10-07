<?php

namespace App\Facades;

use Illuminate\Routing\ResourceRegistrar as OriginalRegistrar;
use Illuminate\Routing\Route;


class ResourceRegistrar extends OriginalRegistrar
{
    // add data to the array
    /**
     * The default actions for a resourceful controller.
     *
     * @var array
     */
    protected $resourceDefaults = ['index', 'create', 'store', 'show', 'update', 'destroy', 'status', 'data'];

    protected function addResourceData($name, $base, $controller, $options) : Route
    {
        $uri = $this->getResourceUri($name).'/datatable';
        $action = $this->getResourceAction($name, $controller, 'data', $options);
        return $this->router->post($uri, $action);
    }
    protected function addResourceStatus($name, $base, $controller, $options) : Route
    {
        $uri = $this->getResourceUri($name).'/{'.$base.'}/status';
        $action = $this->getResourceAction($name, $controller, 'status', $options);
        return $this->router->post($uri, $action);
    }

    protected function addResourceShow($name, $base, $controller, $options) : Route
    {
        $uri = $this->getResourceUri($name).'/{'.$base.'}';
        $action = $this->getResourceAction($name, $controller, 'show', $options);
        return $this->router->get($uri, $action)->where($base, '[0-9]+');
    }

    protected function addResourceStore($name, $base, $controller, $options) : Route
    {
        $uri = $this->getResourceUri($name).'/create';
        $action = $this->getResourceAction($name, $controller, 'store', $options);
        return $this->router->post($uri, $action)->where($base, '[0-9]+');
    }
}
