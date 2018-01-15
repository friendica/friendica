<?php

/*
 * This file is part of Bowerphp.
 *
 * (c) Massimiliano Arione <massimiliano.arione@bee-lab.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bowerphp\Console;

use Bowerphp\Command;
use Bowerphp\Command\Helper\QuestionHelper;
use Bowerphp\Util\ErrorHandler;
use PackageVersions\Versions;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The console application that handles the commands
 * Inspired by Composer https://github.com/composer/composer
 */
class Application extends BaseApplication
{
    /**
     * @var \Bowerphp\Bowerphp
     */
    protected $bowerphp;

    private static $logo = '    ____                                __
   / __ )____ _      _____  _________  / /_  ____
  / __  / __ \ | /| / / _ \/ ___/ __ \/ __ \/ __ \
 / /_/ / /_/ / |/ |/ /  __/ /  / /_/ / / / / /_/ /
/_____/\____/|__/|__/\___/_/  / .___/_/ /_/ .___/
                             /_/         /_/
';

    /**
     * Constructor
     */
    public function __construct()
    {
        ErrorHandler::register();
        $version = Versions::getVersion('beelab/bowerphp');
        parent::__construct('Bowerphp', $version . ' Powered by BeeLab (github.com/bee-lab)');
    }

    /**
     * {@inheritdoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        if ($input->hasParameterOption('--profile')) {
            $startTime = microtime(true);
        }

        if ($newWorkDir = $this->getNewWorkingDir($input)) {
            $oldWorkingDir = getcwd();
            chdir($newWorkDir);
        }

        $result = $this->symfonyDoRun($input, $output);

        if (isset($oldWorkingDir)) {
            chdir($oldWorkingDir);
        }

        if (isset($startTime)) {
            $output->writeln('<info>Memory usage: ' . round(memory_get_usage() / 1024 / 1024, 2) . 'MB (peak: ' . round(memory_get_peak_usage() / 1024 / 1024, 2) . 'MB), time: ' . round(microtime(true) - $startTime, 2) . 's');
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getHelp()
    {
        return self::$logo . parent::getHelp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultCommands()
    {
        return [
            new Command\HelpCommand(),
            new Command\CommandListCommand(),
            new Command\HomeCommand(),
            new Command\InfoCommand(),
            new Command\InitCommand(),
            new Command\InstallCommand(),
            new Command\ListCommand(),
            new Command\LookupCommand(),
            new Command\SearchCommand(),
            new Command\UpdateCommand(),
            new Command\UninstallCommand(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultInputDefinition()
    {
        $definition = parent::getDefaultInputDefinition();
        $definition->addOption(new InputOption('--profile', null, InputOption::VALUE_NONE, 'Display timing and memory usage information'));
        $definition->addOption(new InputOption('--working-dir', '-d', InputOption::VALUE_REQUIRED, 'If specified, use the given directory as working directory.'));

        return $definition;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultHelperSet()
    {
        $helperSet = parent::getDefaultHelperSet();
        $helperSet->set(new QuestionHelper());

        return $helperSet;
    }

    /**
     * @param  InputInterface    $input
     * @return string
     * @throws \RuntimeException
     */
    private function getNewWorkingDir(InputInterface $input)
    {
        $workingDir = $input->getParameterOption(['--working-dir', '-d']);
        if (false !== $workingDir && !is_dir($workingDir)) {
            throw new \RuntimeException('Invalid working directory specified.');
        }

        return $workingDir;
    }

    /**
     * Copy of original Symfony doRun, to allow a default command
     *
     * @param InputInterface  $input   An Input instance
     * @param OutputInterface $output  An Output instance
     * @param string          $default Default command to execute
     *
     * @return int 0 if everything went fine, or an error code
     * @codeCoverageIgnore
     */
    private function symfonyDoRun(InputInterface $input, OutputInterface $output, $default = 'list-commands')
    {
        if (true === $input->hasParameterOption(['--version', '-V'])) {
            $output->writeln($this->getLongVersion());

            return 0;
        }

        $name = $this->getCommandName($input);

        if (true === $input->hasParameterOption(['--help', '-h'])) {
            if (!$name) {
                $name = 'help';
                $input = new ArrayInput(['command' => 'help']);
            } else {
                // TODO $wantHelps is private in parent class
                $this->wantHelps = true;
            }
        }

        if (!$name) {
            $name = $default;
            $input = new ArrayInput(['command' => $default]);
        }

        // the command name MUST be the first element of the input
        $command = $this->find($name);

        $this->runningCommand = $command;
        $exitCode = $this->doRunCommand($command, $input, $output);
        $this->runningCommand = null;

        return $exitCode;
    }
}
