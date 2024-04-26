<?php

namespace App\Controller;

use App\Entity\Source;
use App\Repository\SourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Jefs42\LibreTranslate;
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
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CacheInterface $cache
    )
    {
        $key = 'd3dec04bf26d4e678a8d02c458537ad2';
        $this->newsApi = new NewsApi($key);

    }
    #[Route('/home/{language}', name: 'app_homepage')]
    public function home(
        LibreTranslate $libreTranslate,
        CacheInterface $cache,
        SourceRepository $sourceRepository,
        string         $language = 'es'): Response
    {

        return $this->render('app/index.html.twig', [
            'sources' => $sourceRepository->findBy([
                'language' => $language
            ],[], 40),
//            'headlines' => $data,
//            'translations' => $translations,
            'languages' => $this->newsApi->getLanguages()
        ]);
    }

    #[Route('/load/{language}', name: 'app_load')]
    public function load(Request $request, $language, HttpClientInterface $client): Response
    {
        $sources = $this->cache->get('sources_' . $language, fn(CacheItem $item) => $this->newsApi->getSources(language: $language));

        foreach ($sources->sources as $data) {
            $data = (array)$data;
            if (!$source = $this->entityManager->getRepository(Source::class)
                ->findOneBy(['code' => $data['id']])) {

                $source = (new Source())
                    ->setCode($data['id']);
                $this->entityManager->persist($source);
            }
            $source
                    ->setDescription($data['description'])
                    ->setName($data['name'])
                    ->setUrl($data['url'])
                    ->setCountry($data['country'])
                    ->setLanguage($data['language']);
            }
        $this->entityManager->flush();
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
