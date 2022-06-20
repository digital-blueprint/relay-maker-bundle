<?php

declare(strict_types=1);

namespace Dbp\Relay\MakerBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class MakeBundleCommand extends Command
{
    protected static $defaultName = 'dbp:relay:maker:make:bundle';
    /** @var string */
    private $projectRoot;

    public function __construct(ParameterBagInterface $containerBag)
    {
        parent::__construct();

        $this->projectRoot = (string) $containerBag->get('kernel.project_dir');
    }

    protected function configure()
    {
        $this->setDescription('Create a new bundle');
        $this->addOption('vendor', null, InputOption::VALUE_REQUIRED, 'Vendor', 'dbp');
        $this->addOption('category', null, InputOption::VALUE_REQUIRED, 'Category', 'relay');
        $this->addOption('unique-name', null, InputOption::VALUE_REQUIRED, 'Unique Name');
        $this->addOption('friendly-name', null, InputOption::VALUE_REQUIRED, 'Friendly Name');
        $this->addOption('example-entity', null, InputOption::VALUE_REQUIRED, 'Example Entity');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dry Run');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dryRun = $input->getOption('dry-run');
        $vendor = $input->getOption('vendor');
        $category = $input->getOption('category');
        $uniqueName = $input->getOption('unique-name');
        if ($uniqueName === null) {
            throw new \RuntimeException('--unique-name needs be passed');
        }
        $friendlyName = $input->getOption('friendly-name');
        if ($friendlyName === null) {
            throw new \RuntimeException('--friendly-name needs be passed');
        }
        $exampleEntity = $input->getOption('example-entity');
        if ($exampleEntity === null) {
            throw new \RuntimeException('--example-entity needs be passed');
        }

        if ($dryRun) {
            return Command::SUCCESS;
        }

        $progressBar = new ProgressBar($output);
        $progressBar->setFormatDefinition('custom', ' %current%/%max% [%bar%] %percent:3s%% -- %message%');
        $progressBar->setFormat('custom');
        $progressBar->setMessage('Creating a new bundle...');
        $progressBar->start(5);

        // Create the bundle directory
        $progressBar->setMessage('Create the bundle directory...');
        $filesystem = new Filesystem();
        $bundles = $this->projectRoot.'/bundles';
        $filesystem->mkdir($bundles);
        $dirName = "$vendor-$category-$uniqueName";
        $cloneDir = $bundles.'/'.$dirName;
        $progressBar->advance();

        if ($filesystem->exists($cloneDir)) {
            throw new \Exception("'$cloneDir' already exists. aborting.");
        }

        // Clone the bundle template
        $progressBar->setMessage('Clone the bundle template...');
        $process = new Process([
            'git',
            'clone',
            'https://gitlab.tugraz.at/dbp/relay/dbp-relay-template-bundle',
            $cloneDir,
        ], $this->projectRoot);
        $process->mustRun();
        $filesystem->remove($cloneDir.'/.git');
        $progressBar->advance();

        // Rename the template
        $progressBar->setMessage('Rename the template...');
        $process = new Process([
            './.bundle-rename',
            '--vendor='.$vendor,
            '--category='.$category,
            '--unique-name='.$uniqueName,
            '--friendly-name='.$friendlyName,
            '--example-entity='.$exampleEntity,
        ], $cloneDir);
        $process->mustRun();
        $progressBar->advance();

        // Register the package with composer
        $progressBar->setMessage('Register the package with composer...');
        $process = new Process([
            'composer', 'config', "repositories.$dirName",  'path', "./bundles/$dirName",
        ], $this->projectRoot);
        $process->mustRun();
        $progressBar->advance();

        // Install the package with composer
        $progressBar->setMessage('Install the package with composer...');
        $packageName = "$vendor/$category-$uniqueName-bundle";
        $process = new Process([
            'composer', 'require', "$packageName=@dev",
        ], $this->projectRoot);
        $process->mustRun();
        $progressBar->advance();

        $progressBar->finish();
        $output->writeln("\n");
        $output->writeln("* The package '$packageName' was created under '$cloneDir'");
        $output->writeln('* The package was added to your composer.json and installed');
        $output->writeln('* The containing bundle was registered with your application');

        return Command::SUCCESS;
    }
}
