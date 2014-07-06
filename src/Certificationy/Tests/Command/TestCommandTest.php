<?php

/*
 * This file is part of the Certificationy CLI application.
 *
 * (c) Vincent Composieux <vincent.composieux@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Certificationy\Cli\Tests\Command;

use Certificationy\Command\TestCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * TestCommandTest
 *
 * @author Vincent Composieux <vincent.composieux@gmail.com>
 */
class TestCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TestCommand
     */
    private $command;

    public function setUp()
    {
        $app = new Application();
        $app->add(new TestCommand());
        $this->command = $app->find('test');
    }

    public function testCanListCategories()
    {
        $this->markTestIncomplete('Not yet ready, need to make separate list command for this');
        /**
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(array(
            'command' => $this->command->getName(),
            '-l' => true
        ));

        $output = $commandTester->getDisplay();
        $this->assertRegExp('/Twig/', $output);
        $this->assertCount(count(Loader::getCategories()) + 1, explode("\n", $output));
        */
    }

    public function testCanGetQuestions()
    {
        $helper = $this->command->getHelper('question');
        $helper->setInputStream($this->getInputStream(str_repeat("0\n", 20)));

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(array(
            'command' => $this->command->getName(),
        ));

        $output = $commandTester->getDisplay();
        $this->assertRegExp('/Twig/', $output);
        $this->assertRegExp('/Starting a new set of 20 questions/', $commandTester->getDisplay());
    }

    protected function getInputStream($input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fputs($stream, $input);
        rewind($stream);

        return $stream;
    }

}
