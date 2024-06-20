<?php

namespace App\Controller;

use League\Csv\Reader;
use Survos\KeyValueBundle\Service\KeyValueService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Yaml\Yaml;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route('/pixy')]
class PixyController extends AbstractController
{

    public function __construct(
        private ParameterBagInterface $bag,
        #[Autowire('%data_dir%')] private string $dataDir,
        private KeyValueService $keyValueService) {

    }
    private function getPixyDbName(): string
    {

        //
//        return $this->dataDir .
        return $this->bag->get('data_dir') . '/moma/moma.pixy';
    }
    #[Route('/', name: 'pixy_homepage')]
    public function home(ChartBuilderInterface $chartBuilder): Response
    {
        $pixyDbName = $this->getPixyDbName();
        if (!file_exists($pixyDbName)) {
            dd("Import $pixyDbName first");
        }


        $kv = $this->keyValueService->getStorageBox($pixyDbName);
        foreach ($kv->getTables() as $tableName) {
            foreach ($kv->getIndexes($tableName) as $indexName) {
                $labels =  [];
                $values = [];
                $counts = $kv->getCounts($indexName, $tableName);
                foreach ($counts as $count) {
                    $labels[] = $count['value']; // the property name
                    $values[] = $count['count'];
                    $colors[] = sprintf('rgb(%d, %d, %d)',
                        rand(0,255),
                        rand(0,255),
                        rand(0,255)
                    );
                }
                $chart = $chartBuilder->createChart(
                    str_contains($indexName, 'year') ? Chart::TYPE_LINE :
                    Chart::TYPE_PIE
                );

                $chart->setData([
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'label' => $indexName . "/$tableName",
                            'backgroundColor' => $colors,
                            'borderColor' => 'rgb(255, 99, 132)',
                            'data' => $values,
                        ],
                    ],
                ]);

                $charts[$tableName][$indexName] = $chart;
            }
        }




        return $this->render('pixy/index.html.twig', [
            'kv' => $kv,
            'charts' => $charts,
        ]);

    }
    #[Route('/moma', name: 'pixy_import')]
    public function import(KeyValueService $keyValueService): Response
    {
        // cache wget "https://github.com/MuseumofModernArt/collection/raw/main/Artists.csv"   ?
        $pixyDbName = $this->getPixyDbName();
        $ext = pathinfo($pixyDbName, PATHINFO_EXTENSION);
        $configFilename = str_replace(".$ext", '.conf', $pixyDbName);
        $tables = Yaml::parseFile($configFilename)['tables'];
        foreach ($tables as $tableName => $tableData) {
            $tablesToCreate[$tableName] = $tableData['indexes'];
        }
        $kv = $keyValueService->getStorageBox($pixyDbName, $tablesToCreate);

        foreach ($tables as $tableName => $tableData) {
        $kv->map($tableData['rules'], [$tableName]);
        $kv->select($tableName);

            $fn = $this->dataDir . '/moma/' . ucfirst($tableName) . 's.csv';
            assert(file_exists($fn), $fn);
            // pixydb? phixy.db?
            $csv = Reader::createFromPath($fn, 'r');
            $csv->setHeaderOffset(0); //set the CSV header offset

            $headers = $kv->mapHeader($csv->getHeader());
            $kv->beginTransaction();
            assert(count($headers) == count(array_unique($headers)), json_encode($headers));
            foreach ($csv->getRecords($headers) as $idx => $row) {
                $kv->set($row);
//                if ($idx > 100) break;
//            dd($kv->get($row['id']));
//            dump($row); break;
            }
            $kv->commit();
            foreach ($kv->iterate() as $key => $row) {
                dump($key, $row); break;
            }
        }

        return $this->redirectToRoute('pixy_homepage');
    }
}
