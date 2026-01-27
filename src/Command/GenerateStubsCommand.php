<?php

namespace App\Command;

use StubsGenerator\{StubsGenerator, Finder};
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'stubs:generate',
    description: 'Generates PHP stubs for Bitrix modules.'
)]
class GenerateStubsCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('stubs:generate')
            ->addArgument('root', InputArgument::REQUIRED, 'Path to the Bitrix project root')
            ->addOption('module', 'm', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Bitrix module(s) to include (e.g. main, sale)', []);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $root = rtrim($input->getArgument('root'), '/');

        if (!is_dir($root . '/bitrix/modules')) {
            $output->writeln("<error>Invalid root: {$root} — bitrix/modules not found.</error>");
            return Command::FAILURE;
        }

        $modules = $input->getOption('module');
        if (empty($modules)) {
            $modules = ['main', 'sale', 'catalog', 'iblock'];
        }

        $modulePaths = [];
        foreach ($modules as $module) {
            $path = $root . '/bitrix/modules/' . $module;
            if (!is_dir($path)) {
                $output->writeln("<comment>Warning: module directory not found: {$path}</comment>");
                continue;
            }
            $modulePaths[] = $path;
        }

        if (empty($modulePaths)) {
            $output->writeln('<error>No valid module directories found.</error>');
            return Command::FAILURE;
        }

        $finder = Finder::create()->in($modulePaths);

        $defaultExcludes = [
            'seo/lib/businesssuite/services/',
            'rest/lib/configuration/dataprovider/http/',
            'sender/lib/integration/crm/timeline',
            'disk/lib/copy/integration',
            'vendor/',
            'catalog/lib/integration/report/filter/',
            'catalog/lib/integration/report/view/',
            'catalog/lib/integration/report/handler/',
            'catalog/lib/v2/Tests/',
            'call/lib/Integration/AI/Task',
        ];

        foreach ($defaultExcludes as $exclude) {
            $finder->exclude($exclude);
        }

        $excludeFiles = [
            'disk/lib/integration/transformermanager.php',
            'disk/lib/integration/bizproc/result.php',
            'disk/lib/integration/bizproc/error.php',
            'security/lib/xscanresulttable.php',
            'security/meta/orm.php',
        ];

        foreach ($excludeFiles as $file) {
            $finder->notPath($file);
        }

        $generator = new StubsGenerator();
        $result = $generator->generate($finder);

        $outputFile = $root . '/' . 'stubs.php';


        file_put_contents($outputFile, $result->prettyPrint());

        $output->writeln("<info>✅ Stubs generated successfully:</info> <comment>{$outputFile}</comment>");
        return Command::SUCCESS;
    }
}