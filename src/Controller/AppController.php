<?php

namespace App\Controller;

use App\Entity\Source;
use App\Repository\ArticleRepository;
use App\Repository\SourceRepository;
use App\Service\NewsService;
use Doctrine\ORM\EntityManagerInterface;
use Jefs42\LibreTranslate;
use Survos\KeyValueBundle\Service\KeyValueService;
use Survos\KeyValueBundle\StorageBox;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use jcobhams\NewsApi\NewsApi;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AppController extends AbstractController
{
    private NewsApi $newsApi;
    const KV_FILENAME='news.db';
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CacheInterface $cache,
        private KeyValueService $keyValueService,
        private LibreTranslate $libreTranslate
    )
    {
        $key = 'd3dec04bf26d4e678a8d02c458537ad2';
        $this->newsApi = new NewsApi($key);

    }

    #[Route('/', 'app_landing_page')]
    public function landing(Request $request): Response
    {
        return $this->redirectToRoute('app_homepage', [
            '_locale' => $request->getLocale(),
        ]);

    }

    public function getStorageBox(): StorageBox
    {
        $fn = self::KV_FILENAME;
        unlink($fn);

        // https://dexie.org/docs/Tutorial/Understanding-the-basics
        // ++ is auto-increment, & is unique
        $kv = $this->keyValueService->getStorageBox($fn, [
            'sources'=>'id|TEXT,category,language,country',
            'headlines'=>''
        ]);
//        $kv = $keyValueService->getStringBox($fn = 'x.db', [
//            'trans'=>'locale',
//        ]);
        return $kv;
        $kv->inspectSchema($fn);
        dd(realpath($fn));


    }
    #[Route('/{_locale}/home/{language}', name: 'app_homepage')]
    public function home(
        LibreTranslate $libreTranslate,
        CacheInterface $cache,
        SourceRepository $sourceRepository,
        ArticleRepository $articleRepository,
        KeyValueService $keyValueService,
        string         $language = null): Response
    {
        return $this->render('app/index.html.twig', [
            'sources' => $sourceRepository->findBy($language ? [
                'language' => $language
            ]:[],[], 40),

            'articles' => $articleRepository->findBy($language ? [
                'language' => $language
            ]:[],[], 50),

//            'headlines' => $data,
//            'translations' => $translations,
            'languages' => $this->newsApi->getLanguages()
        ]);
    }

    #[Route('/load/{language}', name: 'app_load')]
    public function load(Request $request,
                         $language,
                         NewsService $newsService,
                         HttpClientInterface $client): Response
    {
        $sources = $newsService->getSources($language);
//        $sources = $this->newsApi->getSources(language: $language);
        $kv = $this->getStorageBox();
        foreach ($sources as $source) {

        }
        dd($sources);
        $articles = $newsService->loadArticles($language, $request->get('q', 'tobacco'));

        return $this->redirectToRoute('app_homepage', ['language' => $language]);
    }

//        dd($sources);
//        foreach (['en','es'] as $language) {
//            $url = $request->getSchemeAndHttpHost();
//            $articles = $client->request('GET', "$url/{$language}", [
//                'body' => [
//                    'q' => $request->get('q', 'microsoft'),
//                ]
//            ]);

    #[Cache(public: true, maxage: 3600*24, mustRevalidate: true)]
    #[Route('/sources/{language}', name: 'app_sources')]
    public function sources(Request $request, string $language): Response
    {
        $newsApi = $this->newsApi;
        $langauges = $newsApi->getLanguages();
        $sources = $newsApi->getSources(language: $language);
        return new JsonResponse($sources);
    }

    #[Cache(public: true, maxage: 3600*24, mustRevalidate: true)]
    #[Route('/search/{language}', name: 'app_search')]
    public function search(Request $request, $language): Response
    {

        $newsApi = $this->newsApi;
        $langauges = $newsApi->getLanguages();
        foreach ($newsApi->getLanguages() as $langauge) {
            $sources = $newsApi->getSources(language: $langauge);
        }

        $q = $request->get('q','microsoft');
        $results = $this->newsApi->getEverything($q, language: $language);
        dd($q, $language, $results);
        return new JsonResponse($results);
        dd($results);
        return $this->render('blog/index.html.twig', []);
    }



}
