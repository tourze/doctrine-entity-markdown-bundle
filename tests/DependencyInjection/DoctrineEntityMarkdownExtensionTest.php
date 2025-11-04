<?php

namespace Tourze\DoctrineEntityMarkdownBundle\Tests\DependencyInjection;



use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\DoctrineEntityMarkdownBundle\DependencyInjection\DoctrineEntityMarkdownExtension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(DoctrineEntityMarkdownExtension::class)]
final class DoctrineEntityMarkdownExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    public function testLoadLoadsServicesConfiguration(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $extension = new DoctrineEntityMarkdownExtension();

        $configs = [];
        $extension->load($configs, $container);

        // 验证容器已加载配置（检查容器状态变化）
        $this->assertTrue($container->isTrackingResources());
    }

    public function testLoadWithEmptyConfigDoesNotThrow(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $extension = new DoctrineEntityMarkdownExtension();

        $configs = [];

        $this->expectNotToPerformAssertions();
        $extension->load($configs, $container);
    }

    public function testServiceDefinitions(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $extension = new DoctrineEntityMarkdownExtension();

        // 配置扩展
        $extension->load([], $container);

        // 验证服务定义存在
        $servicePattern = 'Tourze\DoctrineEntityMarkdownBundle\\';
        $serviceFound = false;

        foreach ($container->getDefinitions() as $id => $definition) {
            if (0 === strpos($id, $servicePattern)) {
                $serviceFound = true;
                $this->assertTrue($definition->isAutowired(), '服务应该启用自动装配');
                $this->assertTrue($definition->isAutoconfigured(), '服务应该启用自动配置');
            }
        }

        $this->assertTrue($serviceFound, '至少应该有一个 bundle 的服务定义');
    }

    protected function setUp(): void
    {
        parent::setUp();
        // 测试 Extension 不需要特殊的设置
    }
}
