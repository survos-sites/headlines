<?php

namespace App\Controller;

use App\Entity\Tax;
use Survos\PixieBundle\Service\PixieService;
use Survos\WikiBundle\Service\WikiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Wikidata\Property;

class AppController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function index(): Response
    {
        return $this->render('app/index.html.twig', [
        ]);
    }

    /**
     * Iterate through moma and update the database with a description and image
     *
     * @return Response
     */
    #[Route('/wiki', name: 'app_wiki')]
    public function wiki(
        PixieService $PixieService,
        WikiService $wikiService
    ): Response
    {
        $kv = $PixieService->getStorageBox('moma.Pixie');
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
