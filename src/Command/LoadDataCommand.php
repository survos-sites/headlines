<?php

namespace App\Command;

use App\Entity\Article;
use App\Entity\Source;
use App\Service\NewsService;
use jcobhams\NewsApi\NewsApi;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:load-data',
    description: 'Add a short description for your command',
)]
class LoadDataCommand extends Command
{
    public function __construct(
        private NewsService $newsService,
        private HttpClientInterface $client)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('q', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('language', 'l', InputOption::VALUE_OPTIONAL, 'source language', 'en')
            ->addOption('load', null, InputOption::VALUE_NONE, 'load the sources first')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $q = $input->getArgument('q');

        if ($input->getOption('load')) {
            foreach (['en','de','fr','es'] as $language) {
                $this->newsService->loadSources($language);
                // q is tied to language, only all if brand name
                $this->newsService->loadArticles($language, $q);
                break;
            }
        }
        $this->newsService->translateEntities(Article::class);
//        $this->newsService->translateSources(Source::class);
        $io->success('load/translations complete');

        return Command::SUCCESS;
    }
}
