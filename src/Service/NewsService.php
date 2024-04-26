<?php

// load and translate news

declare(strict_types=1);

namespace App\Service;

use App\Entity\Source;
use App\Repository\SourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use jcobhams\NewsApi\NewsApi;
use Jefs42\LibreTranslate;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\CacheInterface;

class NewsService
{
    private NewsApi $newsApi;
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CacheInterface $cache,
        private LibreTranslate $libreTranslate,
        private SourceRepository $sourceRepository,
        private LoggerInterface $logger
    )
    {
        $key = 'd3dec04bf26d4e678a8d02c458537ad2';
        $this->newsApi = new NewsApi($key);
    }

    public function loadSources(string $language)
    {
        $sources = $this->cache->get('sources_' . $language,
            fn(CacheItem $item) => $this->newsApi->getSources(language: $language));

        foreach ($sources->sources as $data) {
            $data = (array)$data;
            $origLanguage = $data['language'];
            if (!$source = $this->entityManager->getRepository(Source::class)
                ->findOneBy(['code' => $data['id']])) {
                $source = (new Source())
                    ->setCode($data['id']);
                $this->entityManager->persist($source);
            }
            // set the originals, not really translations
            $source->setDefaultLocale($origLanguage);
            $source
                ->translate($origLanguage)->setDescription($data['description']);
            $source
                ->translate($origLanguage)->setName($data['name']);
            $source
                ->setUrl($data['url'])
                ->setCountry($data['country'])
                ->setLanguage($data['language']);
        }
        $this->entityManager->flush();

    }

    public function translateSources()
    {
        foreach ($this->sourceRepository->findAll() as $source) {
            foreach (['en','es','fr'] as $locale) {
                if ($source->getLanguage() === $locale) {
                    continue;
                }
                foreach (['name','description'] as $field) {
                    $method = 'get' . ucfirst($field);
                    if ($text = $source->$method())
                    {
                        $t = $this->libreTranslate->Translate(
                            $text,
                            source: $source->getLanguage(),
                            target: $locale);
                        $method = 'set' . ucfirst($field);
                        $source->translate($locale)->$method($t);
                        $this->logger->info("$locale $t");
                    }
                }
            }
            $source->mergeNewTranslations();
        }
        $this->entityManager->flush();



    }

}
