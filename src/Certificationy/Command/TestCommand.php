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

use Certy\Component\Core\Actor\Examiner;
use Certy\Component\Core\Actor\Student;
use Certy\Component\Core\Exam\Exam;
use Certy\Component\Core\Exam\Question\QuestionFactory;
use Certy\Component\Core\Exam\Question\QuestionInterface;
use Certy\Component\Core\Exam\Question\QuestionSet;
use Certy\Component\Core\Reward\SimpleReward;
use Certy\Component\Loader\YamlLoader;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class TestCommand
 *
 * This is the command to start a new questions set
 *
 * @author Vincent Composieux <vincent.composieux@gmail.com>
 */
class TestCommand extends AbstractCommand
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
        $this->addArgument('category', InputArgument::IS_ARRAY, 'Categories to include in the exam, leave empty to include all categories');
        $this->addOption('questions-dir', 'd', InputOption::VALUE_OPTIONAL, 'Directory containing the YAML-files', __DIR__ . '/../Resources/questions');
        $this->addOption('student-name', 's', InputOption::VALUE_OPTIONAL, 'Name of the student that will perform the exam', 'Unknown student');
        $this->addOption('randomized', 'r', InputOption::VALUE_REQUIRED, 'Whether questions and answers should be randomized to avoid students remembering them in earlier attempts', true);
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

        $i    = 0;
        $exam = $this->createExam($input);
        $exam->start();
        while ($questionSet = $exam->run()) {
            $output->writeln(sprintf('<info>Category:</info> <comment>%s</comment>', $questionSet->getCategory()));
            foreach ($questionSet->all() as $question) {
                $this->askQuestion(++$i, $question, $input, $output);
            }
        }
        $this->displayResults($exam, $output);

        return;
    }

    /**
     * @param InputInterface $input
     *
     * @return Exam
     *
     * @throws \InvalidArgumentException
     */
    protected function createExam(InputInterface $input)
    {
        $categories   = $input->getArgument('category');
        $questionSets = $this->loadQuestionSets($categories, $input);
        if (empty($questionSets)) {
            throw new \InvalidArgumentException('No questions found in any of the given categories');
        }
        $student      = new Student($input->getOption('student-name'));
        $exam         = new Exam($student, $questionSets);

        return $exam;
    }

    /**
     * Asks the user the given question
     *
     * @param int               $iteration The position of this question in the exam
     * @param QuestionInterface $question  The quetion to ask the user
     * @param InputInterface    $input     A Symfony Console input instance
     * @param OutputInterface   $output    A Symfony Console output instance
     */
    protected function askQuestion($iteration, QuestionInterface $question, InputInterface $input, OutputInterface $output)
    {
        /** @var QuestionHelper $questionHelper */
        $questionHelper     = $this->getHelper('question');
        $showMultipleChoice = $input->getOption('show-multiple-choice');
        $answers            = $question->getAnswerSet()->getPossibleAnswers();
        $choiceQuestion     = new ChoiceQuestion(
            sprintf(
                'Question <comment>#%d</comment> %s' .
                ($showMultipleChoice === true ? "\n" . 'This question <comment>' . ($question->isMultipleChoice() === true ? 'IS' : 'IS NOT') . "</comment> multiple choice." : ""),
                $iteration,
                $question->getQuestion()
            ),
            array_combine(range(1, count($answers)), $answers)
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
     * @param OutputInterface $output A Symfony Console output instance
     */
    protected function displayResults(Exam $exam, OutputInterface $output)
    {
        $examiner      = new Examiner();
        $reward        = new SimpleReward();
        $results       = array();
        $questionCount = 1;
        $reward->setRewards($this->rewards);

        foreach ($exam->getQuestionSets() as $key => $questionSet) {
            foreach ($questionSet->all() as $question) {
                $isCorrect = $question->isCorrect($key);
                $label     = wordwrap($question->getQuestion(), self::WORDWRAP_NUMBER, "\n");

                $results[] = array(
                    sprintf('<comment>#%d</comment> %s', $questionCount++, $label),
                    wordwrap(implode(', ', $question->getAnswerSet()->getCorrectAnswers()), self::WORDWRAP_NUMBER, "\n"),
                    $isCorrect ? '<info>✔</info>' : '<error>✗</error>'
                );
            }
        }

        if ($results) {
            $tableHelper = $this->getHelper('table');
            $tableHelper
                ->setHeaders(array('Question', 'Correct answer', 'Result'))
                ->setRows($results);

            $tableHelper->render($output);
        }
        $points = $examiner->evaluate($exam);
        $output->writeln('<comment>Total score:</comment>');
        $output->writeln(
            sprintf('<info>Points: %d</info> - <comment>Reward: %s</comment>', $points, $reward->reward($points))
        );
    }

    /**
     * @param array          $categories
     * @param InputInterface $input
     *
     * @return QuestionSet[]
     */
    protected function loadQuestionSets(array $categories = array(), InputInterface $input)
    {
        $loader       = new YamlLoader(new QuestionFactory());
        $questionsDir = $input->getOption('questions-dir');
        $finder       = new Finder();
        $yamlFiles    = $finder->files()->in($questionsDir)->name('*.yml');
        $questionSets = array();
        foreach ($yamlFiles->getIterator() as $file) {
            /** @var SplFileInfo $file */
            $questionSet = $loader->load($file->getRealPath(), $input->getOption('randomized'));
            if (empty($categories) || in_array($questionSet->getCategory(), $categories)) {
                $questionSets[] = $questionSet;
            }
        }

        return $questionSets;
    }
}
