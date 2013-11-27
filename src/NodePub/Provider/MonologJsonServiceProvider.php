<?php

namespace NodePub\Provider;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use NodePub\Monolog\JsonFormatter;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Configures Monolog to log messages as json objects
 */
class MonologJsonServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['json_logger'] = function() use ($app) {
            return $app['monolog.json'];
        };
        
        if (class_exists('Symfony\Bridge\Monolog\Logger')) {
            $app['monolog.json.logger.class'] = 'Symfony\Bridge\Monolog\Logger';
        } else {
            $app['monolog.json.logger.class'] = 'Monolog\Logger';
        }
        
        $app['monolog.json.formatter.class'] = 'NodePub\Monolog\JsonFormatter';

        $app['monolog.json'] = $app->share(function ($app) {
            $log = new $app['monolog.json.logger.class']($app['monolog.json.name']);
            $log->pushHandler($app['monolog.json.handler']);

            return $log;
        });

        $app['monolog.json.handler'] = function() use ($app) {
            $handler = new StreamHandler($app['monolog.json.logfile'], $app['monolog.json.level']);
            $handler->setFormatter(new $app['monolog.json.formatter.class']());

            return $handler;
        };

        $app['monolog.json.level'] = function() {
            return Logger::DEBUG;
        };

        $app['monolog.json.name'] = 'myapp';
    }

    public function boot(Application $app)
    {
        $app->before(function(Request $request) use ($app) {
            $app['monolog.json']->addInfo(
                'request',
                $this->getRequestProperties($request)
            );
        });

        $app->error(function(\Exception $e, $code) use ($app) {
            $app['monolog.json']->addError(
                'error',
                array_merge(
                    $this->getRequestProperties($app['request']),
                    array(
                        'error_code'     => $code,
                        'error_message'  => $e->getMessage(),
                    )
                );
            );
        });
    }

    protected function getRequestProperties(Request $request)
    {
        return array(
            'host'           => $request->getHost(),
            'request_method' => $request->getMethod(),
            'request_uri'    => $request->getRequestUri(),
            'ip_address'     => $request->getClientIp()
        );
    }
}
