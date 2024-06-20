<?php

namespace App\Command;

use League\Csv\Reader;
use Survos\KeyValueBundle\Service\KeyValueService;
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

#[AsCommand('pixy:import', 'Import csv to pixy, a file or directory of files"')]
final class PixyImportCommand extends InvokableServiceCommand
{
    use RunsCommands;
    use RunsProcesses;

    public function __construct(private ParameterBagInterface $bag)
    {

        parent::__construct();
    }
    public function __invoke(
        IO $io,
        KeyValueService $keyValueService,
        #[Argument(description: '(string)')]
        string $dirOrFilename = '',
        #[Option(description: 'conf filename, default to directory name of first argument, or pixy.conf', shortcut: 'c')]
        string $config = 'pixy.conf',
    ): void {

        if (!is_dir($dirOrFilename)) {
            $io->error("only dirs now!");
        }

        if (!file_exists($config)) {
            $config = $dirOrFilename . "/$config";
        }

        if (file_exists($config)) {
            $configData = Yaml::parseFile($config);
        }
        $pixyDbName = $dirOrFilename . "/" . pathinfo($dirOrFilename, PATHINFO_FILENAME) . '.pixy';

        $tables = $configData['tables'];
        foreach ($tables as $tableName => $tableData) {
            $tablesToCreate[$tableName] = $tableData['indexes'];
        }
        $kv = $keyValueService->getStorageBox($pixyDbName, $tablesToCreate);

        $finder = new Finder();

        foreach ($finder->in($dirOrFilename)->name('*.csv') as $splFile) {
            $fn = $splFile->getRealPath();
            dd($fn, $configData);
        }

        foreach ($tables as $tableName => $tableData) {
            $kv->map($tableData['rules'], [$tableName]);
            $kv->select($tableName);

            $csv = Reader::createFromPath($fn, 'r');
            $csv->setHeaderOffset(0); //set the CSV header offset

            $headers = $kv->mapHeader($csv->getHeader());
            $kv->beginTransaction();
            assert(count($headers) == count(array_unique($headers)), json_encode($headers));
            foreach ($csv->getRecords($headers) as $idx => $row) {
                $kv->set($row);
                dd($row);
//                if ($idx > 100) break;
//            dd($kv->get($row['id']));
//            dump($row); break;
            }
            $kv->commit();

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
