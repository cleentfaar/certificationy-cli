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
use Symfony\Component\Console\Input\InputOption;
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
        $this->addOption('questions-dir', 'd', InputOption::VALUE_OPTIONAL, 'Directory containing the YAML-files', __DIR__ . '/../Resources/questions');
        $this->setHelp(<<<EOT
The list command allows you to get an overview of all the available exam categories.

You can limit the categories used in your own tests by using one or more of these categories
in the --category[] option of the test command. For example:

<info>certificationy.phar test --category[]=cat1 --category[]=cat2</info>
EOT
        );
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
        }

        $tableHelper = $this->getHelper('table');
        $tableHelper
            ->setHeaders(array('Category', 'Description'))
            ->setRows($results);

        $tableHelper->render($output);
    }
}
