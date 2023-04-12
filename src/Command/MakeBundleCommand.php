<?php

declare(strict_types=1);

namespace Dbp\Relay\MakerBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
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
        $this->addOption('vendor', null, InputOption::VALUE_REQUIRED, 'Vendor');
        $this->addOption('category', null, InputOption::VALUE_REQUIRED, 'Category', 'relay');
        $this->addOption('unique-name', null, InputOption::VALUE_REQUIRED, 'Unique Name');
        $this->addOption('friendly-name', null, InputOption::VALUE_REQUIRED, 'Friendly Name');
        $this->addOption('example-entity', null, InputOption::VALUE_REQUIRED, 'Example Entity');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dry Run');
        $this->addOption('no-confirm', null, InputOption::VALUE_NONE, 'Bypass all confirmation questions, for automation');
    }

    protected function showPreview(OutputInterface $output, string $vendor, string $category, string $uniqueName, string $friendlyName, string $exampleEntity)
    {
        $right = function (string $in): string {
            return sprintf('%25s', $in);
        };

        $composerPackageName = NamingUtils::kebap($vendor).'/'.NamingUtils::kebap($category).'-'.NamingUtils::kebap($uniqueName).'-bundle';
        $output->writeln($right('Composer Package Name').": <info>$composerPackageName</info>");

        $phpNamespace = NamingUtils::pascal($vendor).'\\'.NamingUtils::pascal($category).'\\'.NamingUtils::pascal($uniqueName).'Bundle';
        $output->writeln($right('PHP Namespace').": <info>$phpNamespace</info>");

        $symfonyBundleBaseName = NamingUtils::pascal($vendor).NamingUtils::pascal($category).NamingUtils::pascal($uniqueName);
        $symfonyBundleName = $symfonyBundleBaseName.'Bundle';
        $output->writeln($right('Symfony Bundle Name').": <info>$symfonyBundleName</info>");

        $bundleConfigKey = NamingUtils::snake($vendor.' '.$category.' '.$uniqueName);
        $output->writeln($right('Bundle Config Key').": <info>$bundleConfigKey</info>");

        $phpClassName = NamingUtils::pascal($exampleEntity);
        $output->writeln($right('PHP Class Name').": <info>$phpClassName</info>");

        $apiPlatformShortName = NamingUtils::pascal($uniqueName).NamingUtils::pascal($exampleEntity);
        $output->writeln($right('API-Platform Short Name').": <info>$apiPlatformShortName</info>");

        $resourcePath = '/'.NamingUtils::kebap($uniqueName).'/'.NamingUtils::kebap(NamingUtils::plural($exampleEntity));
        $output->writeln($right('Resource Path').": <info>$resourcePath</info>");

        $serializationGroup = NamingUtils::pascal($uniqueName).NamingUtils::pascal($exampleEntity).':some-group';
        $output->writeln($right('Serialization Group').": <info>$serializationGroup</info>");

        $openAPITag = $friendlyName;
        $output->writeln($right('Open API Tag').": <info>$openAPITag</info>");

        $gitRepositoryName = NamingUtils::kebap($vendor).'-'.NamingUtils::kebap($category).'-'.NamingUtils::kebap($uniqueName).'-bundle';
        $output->writeln($right('GIT Repository Name').": <info>$gitRepositoryName</info>");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $noConfirm = $input->getOption('no-confirm');
        $dryRun = $input->getOption('dry-run');
        $vendor = $input->getOption('vendor');
        $category = $input->getOption('category');
        $uniqueName = $input->getOption('unique-name');
        if ($vendor === null) {
            throw new \RuntimeException('--vendor needs be passed');
        }
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

        $this->showPreview($output, $vendor, $category, $uniqueName, $friendlyName, $exampleEntity);
        if (!$noConfirm) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Continue? (y/n)', false);
            if (!$helper->ask($input, $output, $question)) {
                $output->writeln('aborting');

                return Command::SUCCESS;
            }
        }

        $progressBar = new ProgressBar($output);
        $progressBar->setFormatDefinition('custom', ' %current%/%max% [%bar%] %percent:3s%% -- %message%');
        $progressBar->setFormat('custom');
        $progressBar->setMessage('Creating a new bundle...');
        $progressBar->start(5);

        $dirName = NamingUtils::kebap($vendor).'-'.NamingUtils::kebap($category).'-'.NamingUtils::kebap($uniqueName);
        $packageName = NamingUtils::kebap($vendor).'/'.NamingUtils::kebap($category).'-'.NamingUtils::kebap($uniqueName).'-bundle';
        $bundles = $this->projectRoot.'/bundles';
        $cloneDir = $bundles.'/'.$dirName;

        // Create the bundle directory
        $progressBar->setMessage('Create the bundle directory...');
        if (!$dryRun) {
            $filesystem = new Filesystem();
            $filesystem->mkdir($bundles);
            if ($filesystem->exists($cloneDir)) {
                throw new \Exception("'$cloneDir' already exists. aborting.");
            }
        }
        $progressBar->advance();

        // Clone the bundle template
        $progressBar->setMessage('Clone the bundle template...');
        if (!$dryRun) {
            $process = new Process([
                'git',
                'clone',
                'https://github.com/digital-blueprint/relay-template-bundle',
                $cloneDir,
            ], $this->projectRoot);
            $process->mustRun();
            $filesystem->remove($cloneDir.'/.git');
        }
        $progressBar->advance();

        // Rename the template
        $progressBar->setMessage('Rename the template...');
        if (!$dryRun) {
            $process = new Process([
                './.bundle-rename',
                '--vendor='.$vendor,
                '--category='.$category,
                '--unique-name='.$uniqueName,
                '--friendly-name='.$friendlyName,
                '--example-entity='.$exampleEntity,
            ], $cloneDir);
            $process->mustRun();
        }
        $progressBar->advance();

        // Register the package with composer
        $progressBar->setMessage('Register the package with composer...');
        if (!$dryRun) {
            $process = new Process([
                'composer', 'config', "repositories.$dirName",  'path', "./bundles/$dirName",
            ], $this->projectRoot);
            $process->mustRun();
        }
        $progressBar->advance();

        // Install the package with composer
        $progressBar->setMessage('Install the package with composer...');
        if (!$dryRun) {
            $process = new Process([
                'composer', 'require', "$packageName=@dev",
            ], $this->projectRoot);
            $process->mustRun();
        }
        $progressBar->advance();

        $progressBar->finish();
        $output->writeln("\n");
        $output->writeln("* The package '$packageName' was created under '$cloneDir'");
        $output->writeln('* The package was added to your composer.json and installed');
        $output->writeln('* The containing bundle was registered with your application');

        return Command::SUCCESS;
    }
}
