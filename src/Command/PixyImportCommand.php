<?php

namespace App\Command;

use League\Csv\Reader;
use Psr\Log\LoggerInterface;
use Survos\KeyValueBundle\Service\KeyValueService;
use Survos\KeyValueBundle\Service\PixyImportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Yaml\Yaml;
use Zenstruck\Console\Attribute\Argument;
use Zenstruck\Console\Attribute\Option;
use Zenstruck\Console\ConfigureWithAttributes;
use Zenstruck\Console\InvokableServiceCommand;
use Zenstruck\Console\IO;
use Zenstruck\Console\RunsCommands;
use Zenstruck\Console\RunsProcesses;
use Symfony\Component\Finder\Finder;
use function Symfony\Component\String\u;

#[AsCommand('pixy:import', 'Import csv to pixy, a file or directory of files"')]
final class PixyImportCommand extends InvokableServiceCommand
{
    use RunsCommands;
    use RunsProcesses;

    public function __construct(
        private LoggerInterface $logger,
        private ParameterBagInterface $bag)
    {

        parent::__construct();
    }
    public function __invoke(
        IO $io,
        KeyValueService $keyValueService,
        PixyImportService $pixyImportService,
        #[Argument(description: '(string)')]
        string $dirOrFilename = '',
        #[Option(description: 'conf filename, default to directory name of first argument, or pixy.conf', shortcut: 'c')]
        string $config = 'pixy.conf',
    ): void {

        if (!is_dir($dirOrFilename)) {
            $io->error("only dirs now!");
        }

        if (!file_exists($config) && (file_exists($configWithCsv = $dirOrFilename . "/$config"))) {
            $config = $configWithCsv;
        }

        if (!file_exists($config) && (file_exists($configInPackages = $this->bag->get('kernel.project_dir') . "/config/packages/pixy/$config"))) {
            $config = $configInPackages;
        }

        if (!file_exists($config)) {
            $config = $dirOrFilename . "/$config";
        }

        assert(file_exists($config), "Missing $config");
        if (file_exists($config)) {
            $configData = Yaml::parseFile($config);
        }
        $pixyDbName = $dirOrFilename . "/" . u(pathinfo($dirOrFilename, PATHINFO_FILENAME))->snake() . '.pixy';


        $finder = new Finder();

        $map = [];
        $fileMap=[]; // from a csv file to a specific table format.


        if (0) {

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
        }


        $io->success('pixy:import success.');
    }
}
