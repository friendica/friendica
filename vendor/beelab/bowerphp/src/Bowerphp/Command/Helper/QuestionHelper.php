<?php

/*
 * This file is part of Bowerphp.
 *
 * (c) Massimiliano Arione <massimiliano.arione@bee-lab.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bowerphp\Command\Helper;

use Symfony\Component\Console\Helper\QuestionHelper as BaseQuestionHelper;
use Symfony\Component\Console\Question\Question;

/**
 * Copied by Composer https://github.com/composer/composer
 */
class QuestionHelper extends BaseQuestionHelper
{
    /**
     * Build text for asking a question. For example:
     *
     *  "Do you want to continue [yes]:"
     *
     * @param string $question The question you want to ask
     * @param mixed  $default  Default value to add to message, if false no default will be shown
     * @param string $sep      Separation char for between message and user input
     *
     * @return string
     */
    public function getQuestion($question, $default = null, $sep = ':')
    {
        return null !== $default ?
                new Question(sprintf('<info>%s</info> [<comment>%s</comment>]%s ', $question, $default, $sep)) :
                new Question(sprintf('<info>%s</info>%s ', $question, $sep));
    }
}
