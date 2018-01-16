<?php

/*
 * This file is part of Bowerphp.
 *
 * (c) Massimiliano Arione <massimiliano.arione@bee-lab.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bowerphp\Command;

use Bowerphp\Bowerphp;
use Bowerphp\Config\Config;
use Bowerphp\Output\BowerphpConsoleOutput;
use Bowerphp\Repository\GithubRepository;
use Bowerphp\Util\Filesystem;
use Github\Client;
use Guzzle\Log\ClosureLogAdapter;
use Guzzle\Log\MessageFormatter;
use Guzzle\Plugin\Log\LogPlugin;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Base class for Bowerphp commands
 * Inspired by Composer https://github.com/composer/composer
 */
abstract class Command extends BaseCommand
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Client
     */
    protected $githubClient;

    /**
     * @var BowerphpConsoleOutput
     */
    protected $consoleOutput;

    /**
     * Debug HTTP interactions
     *
     * @param Client          $client
     * @param OutputInterface $output
     */
    protected function logHttp(Client $client, OutputInterface $output)
    {
        $guzzle = $client->getHttpClient();
        if (OutputInterface::VERBOSITY_DEBUG <= $output->getVerbosity()) {
            $logger = function ($message) use ($output) {
                $finfo = new \finfo(FILEINFO_MIME);
                $msg = ('text' == substr($finfo->buffer($message), 0, 4)) ? $message : '(binary string)';
                $output->writeln('<info>Guzzle</info> ' . $msg);
            };
            $logAdapter = new ClosureLogAdapter($logger);
            $logPlugin = new LogPlugin($logAdapter, MessageFormatter::DEBUG_FORMAT);
            $guzzle->addSubscriber($logPlugin);
        }
    }

    /**
     * Set oauth token (to increase API limit to 5000 per hour, instead of default 60)
     *
     * @param Client $client
     */
    protected function setToken(Client $client)
    {
        $token = getenv('BOWERPHP_TOKEN');
        if (!empty($token)) {
            $client->authenticate($token, null, Client::AUTH_HTTP_TOKEN);
        }
    }

    /**
     * @param OutputInterface $output
     */
    protected function setGithubToken(OutputInterface $output)
    {
        $this->filesystem = new Filesystem();
        $this->githubClient = new Client();
        $this->config = new Config($this->filesystem);
        $this->logHttp($this->githubClient, $output);
        $this->setToken($this->githubClient);
    }

    /**
     * @param  OutputInterface $output
     * @return Bowerphp
     */
    protected function getBowerphp(OutputInterface $output)
    {
        $this->consoleOutput = new BowerphpConsoleOutput($output);

        return new Bowerphp($this->config, $this->filesystem, $this->githubClient, new GithubRepository(), $this->consoleOutput);
    }
}
