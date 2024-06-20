<?php


use Survos\KeyValueBundle\Service\KeyValueService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PixyIndexTest extends KernelTestCase
{
    public function testSomething(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        // $routerService = static::getContainer()->get('router');
         $kvService = static::getContainer()->get(KeyValueService::class);
        $kv = $kvService->getStorageBox('test.db');
        $this->assertSame(0, count($kv->getTables()));

        $table = $kv->createTable('movie', '&&id|int,year|integer');


    }


}
