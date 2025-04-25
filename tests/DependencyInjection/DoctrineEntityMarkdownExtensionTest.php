<?php

namespace Tourze\DoctrineEntityMarkdownBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\DoctrineEntityMarkdownBundle\DependencyInjection\DoctrineEntityMarkdownExtension;

class DoctrineEntityMarkdownExtensionTest extends TestCase
{
    private ContainerBuilder $container;
    private DoctrineEntityMarkdownExtension $extension;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->extension = new DoctrineEntityMarkdownExtension();
    }

    public function testServiceDefinitions(): void
    {
        // 配置扩展
        $this->extension->load([], $this->container);

        // 验证服务定义存在，不再验证标签
        $servicePattern = 'Tourze\DoctrineEntityMarkdownBundle\\';
        $serviceFound = false;

        foreach ($this->container->getDefinitions() as $id => $definition) {
            if (strpos($id, $servicePattern) === 0) {
                $serviceFound = true;
                $this->assertTrue($definition->isAutowired(), '服务应该启用自动装配');
                $this->assertTrue($definition->isAutoconfigured(), '服务应该启用自动配置');
            }
        }

        $this->assertTrue($serviceFound, '至少应该有一个 bundle 的服务定义');
    }
}
