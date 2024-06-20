<?php

namespace App\Tests;

use PHPUnit\Framework\Attributes\Test;
use Survos\KeyValueBundle\Service\KeyValueService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use PHPUnit\Framework\Attributes\TestWithJson;

class PixyWriterTest extends KernelTestCase
{
    public function testSomething(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        // $routerService = static::getContainer()->get('router');
         $kvService = static::getContainer()->get(KeyValueService::class);
         // @todo: define where dbs are kept
        $filename = 'test.db';
        if (file_exists($filename)) {
            unlink($filename);
        }
        $kv = $kvService->getStorageBox($filename);
        $this->assertSame(0, count($kv->getTables()));



        $table = $kv->createTable('movie', '&&id|int,year|integer');


    }

//    #[Test]
//    #[TestWithJson('{"a": 1}')]
    #[TestWithJson('[2]')]
    public function jsonTest(int $a): void
    {
        $this->assertSame($a, 1);
        $this->assertSame($a, 2);
        $kernel = self::bootKernel();
        $kvService = static::getContainer()->get(KeyValueService::class);
        $kv = $kvService->getStorageBox('movie');
//        $kv->getIndexDefinitions()
    }

}
