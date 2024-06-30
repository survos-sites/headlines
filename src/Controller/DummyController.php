<?php

namespace App\Controller;

use Survos\PixieBundle\Service\PixieService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dummy', name: 'dummy_')]
class DummyController extends AbstractController
{
    const Pixie_NAME='dummy';

    public function __construct(
        #[Autowire('%kernel.project_dir%/data/')] private string $dataDir,
        private PixieService $pixieService
    )
    {

    }
    #[Route('/import', name: 'import')]
    public function import(

    ): Response
    {
        $projectCode='dummy';
        dd($projectCode);
        if (!file_exists($fn = $this->dataDir.'/'.$projectCode.'/products.json')) {
            file_put_contents($fn, file_get_contents('https://dummyjson.com/products'));
        }
        $products = json_decode(file_get_contents($fn), true);
        dd($products);

        $kv = $this->pixieService->getStorageBox('dummy.Pixie', [
            'products' => 'sku,brand,category' // first key is text primary key by default
        ]);
        dd($kv, $products);

        $kv->select('products'); // so that we don't have to pass it each time.

        foreach ($products['products'] as $product) {
            $kv->set($product); // because they key is in the data.
        }
        assert($kv->get($id));
        assert($kv->has($id));
        assert(json_decode($kv->get($id)) == $data);

        dd($this->dataDir, $products);
        return $this->render('dummy/index.html.twig', [
            'controller_name' => 'DummyController',
        ]);
    }

    #[Route('/', name: 'index')]
    public function index(
    ): Response
    {
        return $this->render('dummy/index.html.twig', [
            'kv' => $this->pixieService->getStorageBox(self::Pixie_NAME)
        ]);
    }

    }
