<?php

namespace App\Tests;

use PHPUnit\Framework\Attributes\Test;
use Survos\PixieBundle\Service\PixieService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use PHPUnit\Framework\Attributes\TestWithJson;

class PixieWriterTest extends KernelTestCase
{

//    #[Test]
//    #[TestWithJson('{"a": 1}')]
    #[TestWithJson('[2]')]
    public function jsonTest(int $a): void
    {
        $this->assertSame($a, 1);
        $this->assertSame($a, 2);
        $kernel = self::bootKernel();
        $kvService = static::getContainer()->get(PixieService::class);
        $kv = $kvService->getStorageBox('movie');
//        $kv->getIndexDefinitions()
    }

}
