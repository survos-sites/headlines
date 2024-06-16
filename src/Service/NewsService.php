<?php

// load and translate news

declare(strict_types=1);

namespace App\Service;

use App\Entity\Article;
use App\Entity\Source;
use App\Repository\ArticleRepository;
use App\Repository\SourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use jcobhams\NewsApi\NewsApi;
use Jefs42\LibreTranslate;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Contracts\Cache\CacheInterface;

class NewsService
{
    private NewsApi $newsApi;
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CacheInterface $cache,
        private LibreTranslate $libreTranslate,
        private SourceRepository $sourceRepository,
        private ArticleRepository $articleRepository,
        private LoggerInterface $logger
    )
    {
        $key = 'd3dec04bf26d4e678a8d02c458537ad2';
        $this->newsApi = new NewsApi($key);
    }

    public function getSources(string $language): array
    {
        $sources = $this->cache->get('sources_' . $language,
            fn(CacheItem $item) => $this->newsApi->getSources(language: $language));
        return $sources->sources;
    }

    // loads as doctrine entities.  @todo: move to StorageBox for testing
    public function loadSources(string $language)
    {
        $sources = $this->getSources($language);
        foreach ($sources as $data) {
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

    public function translateEntities(string $class, array $targetLanguages=['en','es','fr','de'])
    {
        $repo = $this->entityManager->getRepository($class);
        $fields = match($class) {
            Article::class => ['title','description'],
            Source::class => ['name','description'],
        };
        foreach ($repo->findAll() as $translatableEntity)
        {
            $original = $translatableEntity->translate($translatableEntity->getLanguage());
            $actualLocale = $translatableEntity->getLanguage();
            foreach ($targetLanguages as $locale)
            {
                if ($actualLocale === $locale) {
//                    $this->logger->warning("skipping $locale in " . $translatableEntity->getTitle());
                    continue;
                }
                $translatedEntity = $translatableEntity->translate($locale, fallbackToDefault: false);
                $this->entityManager->persist($translatedEntity);

                /** @var Article $translatableEntity */
//                $translatableEntity->setCurrentLocale($locale); // no!
                foreach ($fields as $field) {
                    $getMethod = 'get' . ucfirst($field);
                    assert(method_exists($class, $getMethod), "missing $getMethod in $class");

                    if ($text = $original->$getMethod())
                    {
                        dump(actualLocale: $actualLocale, text: $text);
                        $trans = $translatableEntity->getTranslations()->get($locale);
//                            dd($locale, $trans);

                        // first, check if we have it.
                        if (true)  { // || !$existing =  $translatableEntity->translate($locale)->$method()) {
                            $t = $this->libreTranslate->Translate(
                                $text,
                                source: $actualLocale,
                                target: $locale);
//                            assert($text <> $t, "no translation $actualLocale => $locale for $text");
                            $this->logger->info("$actualLocale->$locale: $text");
//                        dd($text, $t);
                            $setMethod = 'set' . ucfirst($field);
//                            dump($locale, $method, $t);
                            $translatedEntity->$setMethod($t);
                            assert($t = $translatableEntity->translate($locale)->$getMethod());
//                            dump("$actualLocale->$locale: $text", $t);
                        } else {
                            $this->logger->info("existing: $actualLocale->$locale: $method $text");
                        }
                    } else {
                        dd($getMethod, $translatableEntity::class);
                    }
                    $translatableEntity->mergeNewTranslations();
                }
            }
            foreach ($translatableEntity->getTranslations() as $translation) {
                $this->logger->info($translation->getLocale() . ': ' . $translation->getTitle());
            }
            $this->entityManager->flush();
        }
        $this->entityManager->flush();

    }

    public function loadArticles(string $language, string $q='tobacco')
    {
        $slugger = new AsciiSlugger();
        $key = sprintf("art_%s_%s", $language, $slugger->slug($q));
        $articles = $this->cache->get($key, fn(CacheItem $cacheItem) =>
            $this->newsApi->getEverything($q, language: $language)
        );
        foreach ($articles->articles as $idx => $a) {
            $s = $a->source;
            if (!$s->id) {
                continue;
            }
            dump('orig: ' . $language . '/' . $a->title);
            $source = $this->sourceRepository->findOneBy(['code' => $s->id]);
            if (!$source) {
                continue;
            }

            $article = $this->articleRepository->get($a->url);
            assert($source, $s->id);
//            $source->addArticle($article); // update count?
            $article->setSource($source);
            $article->setUrl($a->url)
                ->setAuthor($a->author)
                ->setPublishedAt(new \DateTimeImmutable($a->publishedAt))
                ->setLanguage($language)
                ->setDefaultLocale($language)
            ;
            $article
                ->translate($language)->setDescription($a->description)
            ;
            $article
                ->translate($language)->setTitle($a->title);
            $article->mergeNewTranslations();
        }
        $this->entityManager->flush();
    }

}
