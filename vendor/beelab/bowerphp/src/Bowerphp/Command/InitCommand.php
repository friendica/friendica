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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Init
 */
class InitCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('init')
            ->setDescription('Initializes a bower.json file')
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command initializes a bower.json file in
the current directory.

  <info>php %command.full_name%</info>
EOT
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setGithubToken($output);

        $author = sprintf('%s <%s>', $this->getGitInfo(), $this->getGitInfo('user.email'));

        $params = ['name' => get_current_user(), 'author' => $author];

        // @codeCoverageIgnoreStart
        if ($input->isInteractive()) {
            $dialog = $this->getHelperSet()->get('question');

            $params['name'] = $dialog->ask(
                $input, $output, $dialog->getQuestion('Please specify a name for project', $params['name'])
            );

            $params['author'] = $dialog->ask(
                $input, $output, $dialog->getQuestion('Please specify an author', $params['author'])
            );
        }
        // @codeCoverageIgnoreEnd
        $bowerphp = $this->getBowerphp($output);
        $bowerphp->init($params);

        $output->writeln('');
    }

    /**
     * Get some info from local git
     *
     * @param  string      $info info type
     * @return string|null
     */
    private function getGitInfo($info = 'user.name')
    {
        $output = [];
        $return = 0;
        $info = exec("git config --get $info", $output, $return);

        if (0 === $return) {
            return $info;
        }
    }
}
