<?php
namespace xmarcos\Silex;

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
            'port'      => 2004,
            'transport' => 'udp',
            'namespace' => '',
            'stream'    => null,
        ];

        $args = array_replace_recursive(
            $defaults,
            array_intersect_key($params, $defaults)
        );

        if (!empty($args['stream'])) {
            $stream = $args['stream'];
        } else {
            $address = sprintf('%s://%s:%d', $args['transport'], $args['host'], $args['port']);
            $stream  = @stream_socket_client($address);
        }

        try {
            $carbon = new Client($stream);
            $carbon->setNamespace($args['namespace']);

            return $carbon;
        } catch (InvalidArgumentException $e) {
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function boot(Application $app)
    {
    }
}
