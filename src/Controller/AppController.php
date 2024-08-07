<?php

namespace App\Controller;

use Survos\PixieBundle\Model\Config;
use Survos\PixieBundle\Service\PixieService;
use Survos\WikiBundle\Service\WikiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Yaml\Yaml;
use Wikidata\Property;

#[Route('/{_locale}')]
class AppController extends AbstractController
{

    #[Route('/denormalize', name: 'app_denormalize')]
    public function denormalize(PixieService $pixieService, DenormalizerInterface $denormalizer): Response
    {
        $configData = Yaml::parseFile($pixieService->getConfigFilename('moma'));
        $config = $denormalizer->denormalize($configData, Config::class);
        dd($config, $configData);

    }

    #[Route('/', name: 'app_homepage')]
    public function index(PixieService $pixieService): Response
    {
        $configs = $pixieService->getConfigFiles();
        return $this->render('app/index.html.twig', [
            'dir' => $pixieService->getConfigDir(),
            'configs' => $configs,

        ]);
    }

    /**
     * Iterate through moma and update the database with a description and image
     *
     * @return Response
     */
    #[Route('/wiki', name: 'app_wiki')]
    public function wiki(
        PixieService $pixieService,
        WikiService $wikiService
    ): Response
    {
        $kv = $pixieService->getStorageBox('moma');
        $kv->select('artist');
        foreach ($kv->iterate(where: [
//            'wiki_qid'=>
        ]) as $artist) {
            if ($wikiId  = $artist['wiki_qid']) {
                $info = $wikiService->fetchWikidataPage($wikiId);
                /** @var Property $property */
                foreach ($info->properties as $property) {
                    foreach ($property->values as $value) {

//                        dd($value, $property->values);

                    }
                }
                return $this->render('app/wiki.html.twig', [
                    'info' => $info,
                ]);

                foreach ($info->properties as $property) {
                    dd($property);
                }

                // in the wiki bundle, we should set the properties we want to save
                dd($info);
                assert($code = $info->title);
                dd($info);
                $pCodes = [];
                foreach ($info->claims as $pCode => $claimList) {
                    foreach ($claimList as $claim) {
                        dd($claim);
                    }

                    $wikiData = $wikiService->fetchWikidataPage($wikiId);
                    foreach ($wikiData->properties as $property) {

                        dd($property);
                    }
                    dd($wikiData, $artist);
                }

            }

        }
        return $this->render('app/index.html.twig', [
        ]);
    }
}
