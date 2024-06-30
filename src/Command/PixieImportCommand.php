<?php

namespace App\Command;

use Psr\Log\LoggerInterface;
use Survos\PixieBundle\Service\PixieService;
use Survos\PixieBundle\Service\PixieImportService;
use Survos\PixieBundle\StorageBox;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Yaml\Yaml;
use Zenstruck\Console\Attribute\Argument;
use Zenstruck\Console\Attribute\Option;
use Zenstruck\Console\InvokableServiceCommand;
use Zenstruck\Console\IO;
use Zenstruck\Console\RunsCommands;
use Zenstruck\Console\RunsProcesses;
use Symfony\Component\Finder\Finder;
use function Symfony\Component\String\u;

#[AsCommand('Pixie:import', 'Import csv to Pixie, a file or directory of files"')]
final class PixieImportCommand extends InvokableServiceCommand
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
        PixieService                                                         $PixieService,
        PixieImportService                                                       $PixieImportService,
        #[Argument(description: '(string)')] string                             $dirOrFilename = '',
        #[Option(shortcut: 'c', description: 'conf filename, default to directory name of first argument, or Pixie.conf')]
        string                                                                  $config = 'Pixie.conf',
        #[Option(description: "max number of records per table to import")] int $limit = 0,
        #[Option(description: "Batch size for commit")] int                     $batch = 500
    ): void
    {

        // idea: if conf doesn't exist, require a directory name and create it, a la rector
        dd($dirOrFilename, $config);

        // Pixie databases go in datadir, not with their source? Or defined in the config
        if (!is_dir($dirOrFilename) && !$config) {
            $io->error("set the directory in config pass it as the first argument");
        }


        if (!file_exists($config) && (file_exists($configWithCsv = $dirOrFilename . "/$config"))) {
            $config = $configWithCsv;
        }

        if (!file_exists($config) && (file_exists($configInPackages = $this->bag->get('kernel.project_dir') . "/config/packages/Pixie/$config"))) {
            $config = $configInPackages;
        }

        if (!file_exists($config)) {
            $config = $dirOrFilename . "/$config";
        }

        assert(file_exists($config), "Missing $config");
        if (file_exists($config)) {
            $configData = Yaml::parseFile($config);
            $PixieDbName = pathinfo($config, PATHINFO_FILENAME) . '.Pixie';
        } else {
            $PixieDbName = $dirOrFilename . "/" . u(pathinfo($dirOrFilename, PATHINFO_FILENAME))->snake() . '.Pixie';
        }


        $finder = new Finder();

        $map = [];
        $fileMap = []; // from a csv file to a specific table format.

        $PixieImportService->import($configData, $PixieDbName, limit: $limit,
            callback: function ($row, $idx, StorageBox $kv) use ($batch) {
                if (($idx % $batch) == 0) {
                    $this->logger->info("Saving $batch, now at $idx");
                    $kv->commit();
                    $kv->beginTransaction();
                };
                return true;
            });
        $io->success('Pixie:import success ' . $PixieDbName);
    }
}
