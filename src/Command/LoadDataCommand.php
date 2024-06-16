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
use Symfony\Component\Intl\Languages;
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
            ->addOption('source', 's', InputOption::VALUE_OPTIONAL, 'comma-delimited source languages', 'en')
            ->addOption('target', 't', InputOption::VALUE_OPTIONAL, 'comma-delimited target languages')
            ->addOption('load', null, InputOption::VALUE_NONE, 'load the sources first')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $q = $input->getArgument('q');
        $source = explode(',', $input->getOption('source'));

        if ($input->getOption('load')) {
            foreach ($source as $language) {
                $languageName = Languages::getName($language);
                $io->title("Loading $language $languageName searching for $q");
                $this->newsService->loadSources($language);
                // q is tied to language, only all if brand name
                $this->newsService->loadArticles($language, $q);
//                break;
            }
        }

        if (empty($target = $input->getOption('target'))) {
            $target = $source;
        } else {
            $target = explode(',', $target);
        }
        $this->newsService->translateEntities(Article::class, $target);
//        $this->newsService->translateSources(Source::class);
        $io->success('load/translations complete');

        return Command::SUCCESS;
    }
}
