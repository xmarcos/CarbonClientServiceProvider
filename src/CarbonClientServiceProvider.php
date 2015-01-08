<?php
namespace xmarcos\Silex;

use ErrorException;
use Silex\Application;
use xmarcos\Carbon\Client;
use InvalidArgumentException;
use Silex\ServiceProviderInterface;

class CarbonClientServiceProvider implements ServiceProviderInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @param string $name Name used to register the service in Silex.
     */
    public function __construct($name = 'carbon')
    {
        if (empty($name) || false === is_string($name)) {
            throw new InvalidArgumentException(
                sprintf('$name must be a non-empty string, "%s" given', gettype($name))
            );
        }

        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $name       = $this->name;
        $key        = sprintf('%s.params', $name);
        $app[$key]  = isset($app[$key]) ? $app[$key] : [];
        $app[$name] = $app->share(function (Application $app) use ($key) {
            return $this->createClient($app[$key]);
        });
    }

    private function createClient(array $params = [])
    {
        $defaults = [
            'host'      => '127.0.0.1',
            'port'      => 2003,
            'transport' => 'udp',
            'namespace' => '',
            'stream'    => null,
        ];

        $args = array_replace_recursive(
            $defaults,
            array_intersect_key($params, $defaults)
        );

        $stream    = $args['stream'];
        $exception = null;

        if (!is_resource($stream)) {
            set_error_handler(function ($code, $message, $file = null, $line = 0) use (&$exception) {
                $exception = new ErrorException($message, $code, null, $file, $line);
            });
            $address = sprintf('%s://%s:%d', $args['transport'], $args['host'], $args['port']);
            $stream  = stream_socket_client($address);
            restore_error_handler();
        }

        try {
            $carbon = new Client($stream);
            $carbon->setNamespace($args['namespace']);
        } catch (InvalidArgumentException $e) {
            $carbon = new Client(fopen('php://memory', 'r'));
            $carbon->setNamespace($args['namespace']);
        }

        return $carbon;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function boot(Application $app)
    {
    }
}
