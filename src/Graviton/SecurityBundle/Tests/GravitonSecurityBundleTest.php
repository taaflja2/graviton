<?php
/**
 * test for bundle base class
 */

namespace Graviton\SecurityBundle;

/**
 * Class GravitonSecurityBundleTest
 *
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class GravitonSecurityBundleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Verifies the correct behavior of getBundles()
     *
     * @return void
     */
    public function testGetBundles()
    {
        $bundle = new GravitonSecurityBundle();

        $this->assertEmpty($bundle->getBundles());
    }

    /**
     * Verifies the correct behavior of build()
     *
     * @return void
     */
    public function testBuild()
    {
        $containerDouble = $this->getMockBuilder('\Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->setMethods(array('addCompilerPass'))
            ->getMock();
        $containerDouble
            ->expects($this->once())
            ->method('addCompilerPass')
            ->with(
                $this->isInstanceOf('\Graviton\SecurityBundle\DependencyInjection\Compiler\AuthenticationKeyFinderPass')
            );

        $bundle = new GravitonSecurityBundle();
        $bundle->build($containerDouble);
    }
}
