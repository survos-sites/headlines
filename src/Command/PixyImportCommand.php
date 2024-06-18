<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Yaml\Yaml;
use Zenstruck\Console\Attribute\Argument;
use Zenstruck\Console\Attribute\Option;
use Zenstruck\Console\ConfigureWithAttributes;
use Zenstruck\Console\InvokableServiceCommand;
use Zenstruck\Console\IO;
use Zenstruck\Console\RunsCommands;
use Zenstruck\Console\RunsProcesses;

#[AsCommand('pixy:import', 'Import csv to pixy, a file or directory of files"')]
final class PixyImportCommand extends InvokableServiceCommand
{
    use RunsCommands;
    use RunsProcesses;

    public function __invoke(
        IO $io,
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
            dd($configData);
        }
        assert(file_exists($config), $config);
        dd($config);


        $io->success('pixy:import success.');
    }
}
