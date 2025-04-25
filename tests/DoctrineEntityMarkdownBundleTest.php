<?php

namespace Tourze\DoctrineEntityMarkdownBundle\Tests;

use PHPUnit\Framework\TestCase;
use Tourze\DoctrineEntityMarkdownBundle\DoctrineEntityMarkdownBundle;

class DoctrineEntityMarkdownBundleTest extends TestCase
{
    public function testBundleCreation(): void
    {
        $bundle = new DoctrineEntityMarkdownBundle();
        $this->assertInstanceOf(DoctrineEntityMarkdownBundle::class, $bundle);
    }
}
