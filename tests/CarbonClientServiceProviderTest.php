<?php
namespace xmarcos\Silex;

use ReflectionClass;
use Silex\Application;
use xmarcos\Carbon\Client;
use InvalidArgumentException;
use PHPUnit_Framework_TestCase;

class CarbonClientServiceProviderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Application
     */
    private $app;
    /**
     * @var resource
     */
    private $stream;

    protected function setUp()
    {
        $this->app    = new Application();
        $this->stream = fopen('php://memory', 'r+');
    }

    public function testRegisterWithName()
    {
        $this->app->register(new CarbonClientServiceProvider('metrics'));
        $this->assertTrue($this->app['metrics'] instanceof Client);
    }

    public function testRegisterWithoutName()
    {
        $this->app->register(new CarbonClientServiceProvider());
        $this->assertTrue($this->app['carbon'] instanceof Client);
    }

    /**
     * @expectedException InvalidArgumentException
     * @dataProvider providerWrongNames
     */
    public function testRegisterThrowsExeption($name)
    {
        $this->app->register(new CarbonClientServiceProvider($name));
    }

    public function providerWrongNames()
    {
        return [
            [true],
            [null],
            [[]],
            [mt_rand()],
            [new \stdClass()]
        ];
    }

    public function testRegisterWithStream()
    {
        $this->app->register(new CarbonClientServiceProvider(), [
            'carbon.params' => [
                'stream' => $this->stream,
            ]
        ]);
        $this->assertTrue($this->app['carbon'] instanceof Client);

        $carbon_stream = (new ReflectionClass($this->app['carbon']))->getProperty('stream');
        $carbon_stream->setAccessible(true);

        $this->assertSame($this->stream, $carbon_stream->getValue($this->app['carbon']));
    }

    public function testRegisterWithNamespace()
    {
        $this->app->register(new CarbonClientServiceProvider(), [
            'carbon.params' => [
                'namespace' => 'some.namespace',
            ]
        ]);

        $this->assertTrue($this->app['carbon'] instanceof Client);
        $this->assertEquals('some.namespace', $this->app['carbon']->getNamespace());
    }

    /**
     * @dataProvider providerWrongParams
     */
    public function testRegisterWithInvalidStreamCreatesFallbackMemoryStream($params)
    {
        $this->app->register(new CarbonClientServiceProvider(), [
            'carbon.params' => $params
        ]);
        $carbon_stream = (new ReflectionClass($this->app['carbon']))->getProperty('stream');
        $carbon_stream->setAccessible(true);

        $type = stream_get_meta_data($carbon_stream->getValue($this->app['carbon']))['stream_type'];
        $this->assertEquals('MEMORY', $type);
    }

    public function providerWrongParams()
    {
        return [
            [['transport' => 'invalid_transport']],
            [
                [
                    'transport' => 'tcp',
                    'host'      => 'localhost',
                    'port'      => 25,
                ]
            ],
        ];
    }
}
