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

use Certy\Actor\Examiner;
use Certy\Actor\Student;
use Certy\Certification\Exam;
use Certy\Certification\Question\Loader\DelegatingLoader;
use Certy\Certification\Question\QuestionFactory;
use Certy\Certification\Question\QuestionInterface;
use Certy\Reward\SimpleReward;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Class StartCommand
 *
 * This is the command to start a new questions set
 *
 * @author Vincent Composieux <vincent.composieux@gmail.com>
 */
class TestCommand extends Command
{
    /**
     * @var integer
     */
    const WORDWRAP_NUMBER = 80;

    protected $rewards = array(
        0  => 'You... SUCK!',
        1  => 'Come on now, even my grandma can do better!',
        2  => 'Is this your first time with a dark screen and monospaced font?',
        3  => 'You may as well have rolled your head on the keyboard there...',
        4  => 'You got a lot to learn about Symfony buddy!',
        5  => 'Hmmm, why don\'t you read the cookbook some more? http://symfony.com/doc/master/cookbook/index.html!',
        6  => 'Okay, not spectacular, but you\'re getting there!',
        7  => 'Hey! That\'t actually pretty good!',
        8  => 'Alright, we get it... You\'re pretty good at this Symfony-stuff, huh?',
        9  => 'Uhm... I don\'t think you need any more training!',
        10 => 'PERFECT! Is your last name Potencier perhaps?',
    );

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('test');
        $this->setDescription('Starts a new certificationy exam');
        $this->addArgument('exam', InputArgument::OPTIONAL, 'Path to the exam you want to use', __DIR__ . '/../Resources/exams/certificationy.yml');
        $this->addOption('student-name', 's', InputOption::VALUE_OPTIONAL, 'Name of the student that will perform the exam', 'Unknown student');
        $this->addOption('show-multiple-choice', null, InputOption::VALUE_OPTIONAL, 'Use this option to show indicators when a question is multiple-choice', true);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->isInteractive() !== true) {
            throw new \RuntimeException('This command must be run interactively');
        }

        $pathToExam       = $input->getArgument('exam');
        $delegatingLoader = new DelegatingLoader(new QuestionFactory());
        $student          = new Student($input->getOption('student-name'));
        $reward           = new SimpleReward($this->rewards);
        $questionSet      = $delegatingLoader->load($pathToExam);
        $exam             = new Exam($student, $questionSet);
        $exam->start();
        while ($question = $exam->run()) {
            $this->askQuestion($question, $input, $output);
        }
        $examiner = new Examiner();
        $points   = $examiner->evaluate($exam);
        $prize    = $reward->reward($points);
        $this->displayResults($exam, $points, $prize, $output);
    }

    /**
     * Asks the user the given question
     *
     * @param QuestionInterface $question The quetion to ask the user
     * @param InputInterface    $input    A Symfony Console input instance
     * @param OutputInterface   $output   A Symfony Console output instance
     */
    protected function askQuestion(QuestionInterface $question, InputInterface $input, OutputInterface $output)
    {
        /** @var QuestionHelper $questionHelper */
        $questionHelper     = $this->getHelper('question');
        $showMultipleChoice = $input->getOption('show-multiple-choice');
        $choiceQuestion     = new ChoiceQuestion(
            sprintf(
                'Question <comment>#%d</comment> [<info>%s</info>] %s' .
                ($showMultipleChoice === true ? "\n" . 'This question <comment>' . ($question->isMultipleChoice() === true ? 'IS' : 'IS NOT') . "</comment> multiple choice." : ""),
                $question->getQuestion()
            ),
            $question->getAnswerSet()->getPossibleAnswers()
        );

        $multiSelect = $showMultipleChoice === true ? $question->isMultipleChoice() : true;
        $choiceQuestion->setMultiselect($multiSelect);
        $choiceQuestion->setErrorMessage('Answer %s is invalid.');

        $answer  = $questionHelper->ask($input, $output, $choiceQuestion);
        $answers = true === $multiSelect ? $answer : array($answer);
        $answer  = true === $multiSelect ? implode(', ', $answer) : $answer;

        $question->answer($answers);

        $output->writeln('<comment>✎ Your answer</comment>: ' . $answer . "\n");
    }

    /**
     * Displays results through the output
     *
     * @param Exam            $exam   The exam that was evaluated
     * @param int             $points The points that have been awarded, as a number between 0 and 100
     * @param string          $reward The reward for this score (a cool text :-P)
     * @param OutputInterface $output A Symfony Console output instance
     */
    protected function displayResults(Exam $exam, $points, $reward, OutputInterface $output)
    {
        $results = array();

        $questionCount = 1;

        foreach ($exam->getQuestions() as $key => $question) {
            $isCorrect = $question->isCorrect($key);
            $label     = wordwrap($question->getQuestion(), self::WORDWRAP_NUMBER, "\n");

            $results[] = array(
                sprintf('<comment>#%d</comment> %s', $questionCount++, $label),
                wordwrap(implode(', ', $question->getAnswerSet()->getCorrectAnswers()), self::WORDWRAP_NUMBER, "\n"),
                $isCorrect ? '<info>✔</info>' : '<error>✗</error>'
            );
        }

        if ($results) {
            $tableHelper = $this->getHelper('table');
            $tableHelper
                ->setHeaders(array('Question', 'Correct answer', 'Result'))
                ->setRows($results);

            $tableHelper->render($output);
        }
        $output->writeln('<comment>Total score:</comment>');
        $output->writeln(
            sprintf('<info>Points: %d</info> - <comment>Reward: %s</comment>', $points, $reward)
        );
    }
}
