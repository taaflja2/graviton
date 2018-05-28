<?php
/**
 * XDynamicKeyControllerTest class file
 */

namespace Graviton\CoreBundle\Tests\Controller;

use Graviton\TestBundle\Test\RestTestCase;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class XDynamicKeyControllerTest extends RestTestCase
{
    /**
     * load fixtures
     *
     * @return void
     */
    public function setUp()
    {
        if (!class_exists('GravitonDyn\TestCaseXDynamicKeyBundle\DataFixtures\MongoDB\LoadTestCaseXDynamicKeyData')) {
            $this->markTestSkipped('LoadTestCaseXDynamicKeyData definition is not loaded');
        }

        $this->loadFixturesLocal(
            [
                'GravitonDyn\TestCaseXDynamicKeyAppBundle\DataFixtures\MongoDB\LoadTestCaseXDynamicKeyAppData',
                'GravitonDyn\TestCaseXDynamicKeyBundle\DataFixtures\MongoDB\LoadTestCaseXDynamicKeyData'
            ]
        );
    }

    /**
     * test that XDynamicKey functionality transforms the output as expected
     *
     * @return void
     */
    public function testExpectedStructure()
    {
        $client = static::createRestClient();
        $client->request('GET', '/testcase/xdynamickey/?sort(+id)');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $result = $client->getResults();
        // two records
        $this->assertEquals(2, count($result));
        // check for the 'apps' keys - those are generated by x-dynamic-key!
        $this->assertObjectHasAttribute('someApp', $result[0]->apps);
        $this->assertObjectHasAttribute('someOtherApp', $result[0]->apps);
        $this->assertObjectHasAttribute('oneMoreApp', $result[0]->apps);

        $this->assertObjectHasAttribute('someOtherApp', $result[1]->apps);
        $this->assertObjectHasAttribute('oneMoreApp', $result[1]->apps);
        $this->assertObjectNotHasAttribute('someApp', $result[1]->apps);

        $this->assertEquals(
            'http://localhost/testcase/xdynamickey-app/someApp',
            $result[0]->apps->someApp->app->{'$ref'}
        );
        $this->assertEquals(
            'http://localhost/testcase/xdynamickey-app/someOtherApp',
            $result[0]->apps->someOtherApp->app->{'$ref'}
        );
        $this->assertEquals(
            'http://localhost/testcase/xdynamickey-app/oneMoreApp',
            $result[0]->apps->oneMoreApp->app->{'$ref'}
        );

        $this->assertEquals(
            'http://localhost/testcase/xdynamickey-app/someOtherApp',
            $result[1]->apps->someOtherApp->app->{'$ref'}
        );
        $this->assertEquals(
            'http://localhost/testcase/xdynamickey-app/oneMoreApp',
            $result[1]->apps->oneMoreApp->app->{'$ref'}
        );
    }
}
