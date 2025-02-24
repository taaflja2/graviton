<?php
/**
 * HttpLoaderTest
 */

namespace Graviton\ProxyBundle\Tests\Definition\Loader;

use Graviton\ProxyBundle\Definition\Loader\HttpLoader;
use GuzzleHttp\Psr7\Response;
use Laminas\Diactoros\CallbackStream;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\CacheItem;

/**
 * tests for the HttpLoader class
 *
 * @author  List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    http://swisscom.ch
 */
class HttpLoaderTest extends TestCase
{
    /**
     * @var HttpLoader
     */
    private $sut;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * setup
     *
     * @return void
     */
    public function setup() : void
    {
        $content = new CallbackStream(
            function () {
                return "{ 'test': 'bablaba' }";
            }
        );

        $response = new Response();
        $response = $response->withBody($content);

        $client = $this->getMockBuilder('GuzzleHttp\Client')->getMock();
        $client->expects($this->any())
                ->method("send")
                ->withAnyParameters()
                ->willReturn($response);

        $validator = $this->getMockForAbstractClass('Symfony\Component\Validator\Validator\ValidatorInterface');
        $this->logger = $this->createMock('Psr\Log\LoggerInterface');

        $this->sut = new HttpLoader($validator, $client, $this->logger);
    }

    /**
     * test the support method
     *
     * @return void
     */
    public function testSupports()
    {
        $client = $this->getMockBuilder('GuzzleHttp\Client')->getMock();
        $validator = $this->getMockBuilder('Symfony\Component\Validator\Validator\ValidatorInterface')
                          ->getMockForAbstractClass();
        $validator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(array());

        $sut = new HttpLoader($validator, $client, $this->logger);
        $this->assertTrue($sut->supports("test/test.json"));

        $validatorFail = $this->getMockBuilder('Symfony\Component\Validator\Validator\ValidatorInterface')
            ->getMockForAbstractClass();
        $validatorFail
            ->expects($this->once())
            ->method('validate')
            ->willReturn(array("error text"));

        $sut = new HttpLoader($validatorFail, $client, $this->logger);
        $this->assertFalse($sut->supports("test/again.json"));
    }

    /**
     * load method return null
     *
     * @return HttpLoader
     */
    public function testLoadShallNotReturnNull()
    {
        $url = "http://localhost/test.json";
        $this->assertNotNull($this->sut->load($url));

        $mock = $this->getMockBuilder('Graviton\ProxyBundle\Definition\Loader\DispersalStrategy\SwaggerStrategy')
            ->disableOriginalConstructor()
            ->getMock();
        $mock
            ->expects($this->once())
            ->method("supports")
            ->willReturn(false);

        $this->sut->setDispersalStrategy($mock);
        $this->assertNotNull($this->sut->load($url));
    }

    /**
     * test the load method
     *
     * @depends testLoadShallNotReturnNull
     *
     * @return void
     */
    public function testLoad()
    {
        $apiDefinition = $this->getMockBuilder('Graviton\ProxyBundle\Definition\ApiDefinition')->getMock();

        $mock = $this->getMockBuilder('Graviton\ProxyBundle\Definition\Loader\DispersalStrategy\SwaggerStrategy')
            ->disableOriginalConstructor()
            ->getMock();
        $mock
            ->expects($this->once())
            ->method("supports")
            ->willReturn(true);
        $mock
            ->expects($this->once())
            ->method("process")
            ->willReturn($apiDefinition);

        $this->sut->setDispersalStrategy($mock);
        $loadedContent = $this->sut->load("http://localhost/test/url/blub");
        $this->assertInstanceOf('Graviton\ProxyBundle\Definition\ApiDefinition', $loadedContent);
    }

    /**
     * test a load with cached content
     *
     * @return void
     */
    public function testLoadWithCache()
    {
        $storeKey = 'testSwagger';
        $storeKeyDef = 'testSwagger-def';
        $apiDefinition = $this->createMock('Graviton\ProxyBundle\Definition\ApiDefinition');

        $mock = $this->getMockBuilder('Graviton\ProxyBundle\Definition\Loader\DispersalStrategy\SwaggerStrategy')
            ->disableOriginalConstructor()
            ->getMock();
        $mock->expects($this->never())
            ->method("supports");
        $mock->expects($this->never())
            ->method("process");
        $this->sut->setDispersalStrategy($mock);

        $cacheMockItem = new CacheItem();
        $cacheMockItem->set($apiDefinition);

        $cacheMock = $this->getMockBuilder(ArrayAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $cacheMock->expects($this->exactly(1))
            ->method('hasItem')
            ->with($storeKeyDef)
            ->willReturn(1);
        $cacheMock->expects($this->exactly(1))
            ->method('getItem')
            ->with($storeKeyDef)
            ->willReturn($cacheMockItem);
        $this->sut->setCache($cacheMock, 1234);
        $this->sut->setOptions(['prefix' => $storeKey]);

        $content = $this->sut->load("http://localhost/test/blablabla");
        $this->assertInstanceOf('Graviton\ProxyBundle\Definition\ApiDefinition', $content);
    }
}
