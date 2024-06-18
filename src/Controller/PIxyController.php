<?php

namespace App\Controller;

use League\Csv\Reader;
use Survos\KeyValueBundle\Service\KeyValueService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Yaml\Yaml;

#[Route('/moma')]
class PIxyController extends AbstractController
{

    public function __construct(private KeyValueService $keyValueService) {

    }
    private function getPixyDbName(): string
    {
        $pixyDbName = __DIR__ . '/../../moma.db';
        return $pixyDbName;
    }
    #[Route('/', name: 'moma_homepage')]
    public function home(): Response
    {
        $pixyDbName = $this->getPixyDbName();


        return $this->render('moma/index.html.twig', [
            'kv' => $this->keyValueService->getStorageBox($pixyDbName)
        ]);

    }
    #[Route('/moma', name: 'moma_import')]
    public function index(KeyValueService $keyValueService): Response
    {
        // cache wget "https://github.com/MuseumofModernArt/collection/raw/main/Artists.csv"   ?
        $artistsTableName = 'artist';
        $artworkTableName = 'artwork';
        $pixyDbName = $this->getPixyDbName();

        foreach ($config = [
            $artworkTableName => [
                'indexes' => 'id|INTEGER,department,classification|department|year', // dexie format,
                'rules' => [
        '/ObjectID/'=>'id',
                    '/BeginDate/' => 'artistBirthYear',
                    '/EndDate/' => 'artistDeathYear',
        '/^Date$/'=>'art_year',
                    '/ConstituentID/' => 'artist_id'
    ],
            ],
         $artistsTableName => [
            'indexes' => ['id|INTEGER', 'nationality',
                'birth_year|INTEGER',
                'gender','qid'],
             'rules' => [
                 '/ConstituentID/' => 'id',
                 '/BeginDate/' => 'birthYear',
                 '/EndDate/' => 'deathYear'
             ]
        ]
        ] as $tableName => $tableData) {
            $tablesToCreate[$tableName] = $tableData['indexes'];

    }
        file_put_contents($x = str_replace('.db', '.conf', $pixyDbName), (Yaml::dump($config, 4)));
        dd($x);
        $kv = $keyValueService->getStorageBox($pixyDbName, $tablesToCreate);

        foreach ($config as $tableName => $tableData) {
        $kv->map($tableData['rules'], [$tableName]);
        $kv->select($tableName);

            $fn = __DIR__ . '/../../' . ucfirst($tableName) . 's.csv';
            assert(file_exists($fn), $fn);
            // pixydb? phixy.db?
            $csv = Reader::createFromPath($fn, 'r');
            $csv->setHeaderOffset(0); //set the CSV header offset

            $headers = $kv->mapHeader($csv->getHeader());
            $kv->beginTransaction();
            assert(count($headers) == count(array_unique($headers)), json_encode($headers));
            foreach ($csv->getRecords($headers) as $idx => $row) {
                $kv->set($row['id'], $row);
                if ($idx > 100) break;
//            dd($kv->get($row['id']));
//            dump($row); break;
            }
            $kv->commit();
            foreach ($kv->iterate() as $key => $row) {
                dump($key, $row); break;
            }
        }

        return $this->redirectToRoute('moma_homepage');
    }
}
