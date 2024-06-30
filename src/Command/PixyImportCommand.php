<?php

namespace App\Command;

use League\Csv\Reader;
use Psr\Log\LoggerInterface;
use Survos\KeyValueBundle\Service\KeyValueService;
use Survos\KeyValueBundle\Service\PixyImportService;
use Survos\KeyValueBundle\StorageBox;
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
        private LoggerInterface       $logger,
        private ParameterBagInterface $bag)
    {

        parent::__construct();
    }

    public function __invoke(
        IO                                                                      $io,
        KeyValueService                                                         $keyValueService,
        PixyImportService                                                       $pixyImportService,
        #[Argument(description: '(string)')] string                             $dirOrFilename = '',
        #[Option(description: 'conf filename, default to directory name of first argument, or pixy.conf', shortcut: 'c')]
        string                                                                  $config = 'pixy.conf',
        #[Option(description: "max number of records per table to import")] int $limit = 0,
        #[Option(description: "Batch size for commit")] int                     $batch = 500
    ): void
    {

        // idea: if conf doesn't exist, require a directory name and create it, a la rector

        // pixy databases go in datadir, not with their source? Or defined in the config
        if (!is_dir($dirOrFilename) && !$config) {
            $io->error("set the directory in config pass it as the first argument");
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
            $pixyDbName = pathinfo($config, PATHINFO_FILENAME) . '.pixy';
        } else {
            $pixyDbName = $dirOrFilename . "/" . u(pathinfo($dirOrFilename, PATHINFO_FILENAME))->snake() . '.pixy';
        }


        $finder = new Finder();

        $map = [];
        $fileMap = []; // from a csv file to a specific table format.

        $pixyImportService->import($configData, $pixyDbName, limit: $limit,
            callback: function ($row, $idx, StorageBox $kv) use ($batch) {
                if (($idx % $batch) == 0) {
                    $this->logger->info("Saving $batch, now at $idx");
                    $kv->commit();
                    $kv->beginTransaction();
                };
                return true;
            });
        $io->success('pixy:import success ' . $pixyDbName);
    }
}
