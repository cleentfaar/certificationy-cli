<?php

/*
 * This file is part of the Certificationy CLI application.
 *
 * (c) Vincent Composieux <vincent.composieux@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Certificationy\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class TestCommand
 *
 * This is the command to start a new questions set
 *
 * @author Vincent Composieux <vincent.composieux@gmail.com>
 */
class ListCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('list');
        $this->setDescription('Lists all available categories');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $questionSets = $this->loadQuestionSets(array(), $input);
        $output->writeln('The following categories are available:');
        $results = array();
        foreach ($questionSets as $questionSet) {
            $results[] = array($questionSet->getCategory(), $questionSet->getDescription());
            $output->write('- ' . $questionSet->getCategory());
        }

        $tableHelper = $this->getHelper('table');
        $tableHelper
            ->setHeaders(array('Category', 'Description', 'Result'))
            ->setRows($results);

        $tableHelper->render($output);
    }
}
