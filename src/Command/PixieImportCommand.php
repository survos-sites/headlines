<?php

namespace App\Command;

use Psr\Log\LoggerInterface;
use Survos\PixieBundle\Model\Config;
use Survos\PixieBundle\Service\PixieService;
use Survos\PixieBundle\Service\PixieImportService;
use Survos\PixieBundle\StorageBox;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\ProgressBar;
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

#[AsCommand('pixie:import', 'Import csv to Pixie, a file or directory of files"')]
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
        PixieService                                                         $pixieService,
        PixieImportService                                                       $pixieImportService,
        #[Argument(description: '(string)')] string                             $dirOrFilename = '',
        #[Option(shortcut: 'c', description: 'conf filename, default to directory name of first argument')]
        string                                                                  $configCode=null,
        #[Option(description: "max number of records per table to import")] int $limit = 0,
        #[Option(description: "Batch size for commit")] int                     $batch = 500,
    ): int
    {

        // idea: if conf doesn't exist, require a directory name and create it, a la rector
        if (empty($config)) {
            $configCode = pathinfo($dirOrFilename, PATHINFO_BASENAME);
        }

        $configFilename = $pixieService->getConfigDir() . "/$configCode.yaml";

        if (!file_exists($configFilename)) {
            // prompt for init to create it
            dd($configFilename);
        }

        $config = new Config($configFilename);
        $configData = Yaml::parseFile($configFilename);
        if (empty($dirOrFilename)) {
            $dirOrFilename = $config->getDataDirectory();
        }

//        dump($configData, $config->getVersion());
//        dd($dirOrFilename, $config, $configFilename, $pixieService->getPixieFilename($configCode));

        // Pixie databases go in datadir, not with their source? Or defined in the config
        if (!is_dir($dirOrFilename)) {
            $io->error("set the directory in config or pass it as the first argument");
            return self::FAILURE;
        }

//        if (!file_exists($configFilename) && (file_exists($configWithCsv = $dirOrFilename . "/$config"))) {
//            $config = $configWithCsv;
//        }
//
//        if (!file_exists($config) && (file_exists($configInPackages = $this->bag->get('kernel.project_dir') . "/config/packages/Pixie/$config"))) {
//            $config = $configInPackages;
//        }
//
//        if (!file_exists($config)) {
//            $config = $dirOrFilename . "/$config";
//        }

        $pixieDbName = $pixieService->getPixieFilename($configCode);
        if (!file_exists($dirName = pathinfo($pixieDbName, PATHINFO_DIRNAME))) {
            mkdir($dirName, 0777, true);
        }

        $progressBar = new ProgressBar($io->output());

        $pixieImportService->import($configData, $pixieDbName, limit: $limit,
            callback: function ($row, $idx, StorageBox $kv) use ($batch, $progressBar) {
            $progressBar->advance();
                if (($idx % $batch) == 0) {
                    $this->logger->info("Saving $batch, now at $idx");
                    $kv->commit();
                    $kv->beginTransaction();
                };
                return true;
            });
        $io->success('Pixie:import success ' . $pixieDbName);
        return self::SUCCESS;
    }
}
