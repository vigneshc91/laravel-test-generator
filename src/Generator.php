<?php

namespace Vigneshc91\LaravelTestGenerator;

use ReflectionMethod;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;
use Illuminate\Foundation\Http\FormRequest;
use Vigneshc91\LaravelTestGenerator\TestCaseGenerator;

class Generator
{
    protected $routeFilter;

    protected $originalUri;

    protected $action;

    protected $config;

    protected $testCaseGenerator;

    protected $formatter;

    protected $directory;

    protected $sync;

    /**
     * Initiate the global parameters
     *
     * @param array $options
     */
    public function __construct($options)
    {
        $this->directory = $options['directory'];
        $this->routeFilter = $options['filter'];
        $this->sync = $options['sync'];
        $this->testCaseGenerator = new  TestCaseGenerator();
        $this->formatter = new  Formatter($this->directory, $this->sync);
    }

    /**
     * Generate the route methods and write to the file
     *
     * @return void
     */
    public function generate()
    {
        $this->getRouteMethods();
        // $this->formatter->generate();
    }

    /**
     * Get the route detail and generate the test cases
     *
     * @return void
     */
    protected function getRouteMethods()
    {
        foreach ($this->getAppRoutes() as $route) {
            $this->originalUri = $uri = $this->getRouteUri($route);
            $this->uri = $this->strip_optional_char($uri);

            if ($this->routeFilter && !preg_match('/^' . preg_quote($this->routeFilter, '/') . '/', $this->uri)) {
                continue;
            }   

            $action = $route->getAction('uses');
            $methods = $route->methods();
            $actionName = $this->getActionName($route->getActionName());
            
            $controllerName = $this->getControllerName($route->getActionName());

            foreach ($methods as $method) {
                $this->method = strtoupper($method);
                
                if (in_array($this->method, ['HEAD'])) continue;
                
                $rules = $this->getFormRules($action);
                if(empty($rules)) {
                    $rules = [];
                }
                $case = $this->testCaseGenerator->generate($rules);
                $hasAuth = $this->isAuthorizationExist($route->middleware());
                $this->formatter->format($case, $this->uri, $this->method, $controllerName, $actionName, $hasAuth);
                
            }
        }
    }

    /**
     * Check authorization middleware is exist
     *
     * @param array $middlewares
     * @return boolean
     */
    protected function isAuthorizationExist($middlewares)
    {
        $hasAuth = array_filter($middlewares, function ($var) { 
            return (strpos($var, 'auth') > -1); 
        });

        return $hasAuth;
    }

    /**
     * Replace the optional params from the URL
     *
     * @param string $uri
     * @return string
     */
    protected function strip_optional_char($uri)
    {
        return str_replace('?', '', $uri);
    }

    /**
     * Get the routes of the application
     *
     * @return array
     */
    protected function getAppRoutes()
    {
        return app('router')->getRoutes();
    }

    /**
     * Get the URI of the route
     *
     * @param Route $route
     * @return string
     */
    protected function getRouteUri(Route $route)
    {
        $uri = $route->uri();

        if (!starts_with($uri, '/')) {
            $uri = '/' . $uri;
        }

        return $uri;
    }

    /**
     * Get the form rules for creating the parameters
     *
     * @param [type] $action
     * @return array
     */
    protected function getFormRules($action)
    {
        if (!is_string($action)) return false;
        
        $parsedAction = Str::parseCallback($action);
        
        $reflector = (new ReflectionMethod($parsedAction[0], $parsedAction[1]));
        $parameters = $reflector->getParameters();
        
        foreach ($parameters as $parameter) {
            $class = (string) $parameter->getType();
            
            if (is_subclass_of($class, FormRequest::class)) {
                return (new $class)->rules();
            }
        }
    }

    /**
     * Return's the controller name
     *
     * @param string $controller
     * @return string
     */
    protected function getControllerName($controller)
    {
        $namespaceReplaced = substr($controller, strrpos($controller, '\\')+1);
        $actionNameReplaced = substr($namespaceReplaced, 0, strpos($namespaceReplaced, '@'));
        $controllerReplaced = str_replace('Controller', '', $actionNameReplaced);
        $controllerNameArray = preg_split('/(?=[A-Z])/', $controllerReplaced);
        $controllerName = trim(implode(' ', $controllerNameArray));

        return $controllerName;
    }

    /**
     * Return's the action name
     *
     * @param string $actionName
     * @return string
     */
    protected function getActionName($actionName)
    {
        $actionNameSubString = substr($actionName, strpos($actionName, '@')+1);
        $actionNameArray = preg_split('/(?=[A-Z])/', ucfirst($actionNameSubString));
        $actionName = trim(implode('', $actionNameArray));

        return $actionName;
    }
}