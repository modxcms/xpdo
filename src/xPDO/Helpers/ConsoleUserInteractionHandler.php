<?php

namespace xPDO\Helpers;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;

class ConsoleUserInteractionHandler implements UserInteractionHandler
{
    /** @var \Symfony\Component\Console\Input\InputInterface */
    protected $input;

    /** @var \Symfony\Component\Console\Output\OutputInterface */
    protected $output;

    /** @var \Symfony\Component\Console\Command\Command; */
    protected $command;

    /** @var \Symfony\Component\Console\Helper\QuestionHelper */
    protected $questionHelper;

    /**
     * CliUserInteractionHandler constructor.
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * @param \Symfony\Component\Console\Command\Command $command
     */
    public function setCommandObject(Command $command)
    {
        $this->command = $command;
        // @see: https://symfony.com/doc/3.4/components/console/helpers/questionhelper.html
        $this->questionHelper = $this->command->getHelper('question');
    }

    /**
     * @param string $question
     * @param bool $default
     * @return bool
     */
    public function promptConfirm(string $question, $default=true)
    {
        $confirmationQuestion = new ConfirmationQuestion($question.' <comment>(Y/N)</comment> ', $default);
        return $this->questionHelper->ask($this->input, $this->output, $confirmationQuestion);
    }

    /**
     * @param string $question
     * @param string|mixed $default
     * @return string|mixed ~ user input
     */
    public function promptInput(string $question, $default=null)
    {
        $questionInput = new Question($question, $default);
        return $this->questionHelper->ask($this->input, $this->output, $questionInput);
    }

    /**
     * @param string $question
     * @param string|mixed $default
     * @param array $options ~ ex: ['Value1, 'Value2',... ] or ['Option1' => 'value', 'Option2' => 'value2', ...]
     * @param string $error_message ~ ex: 'Color %s is invalid.'
     * @return mixed ~ selected value
     */
    public function promptSelectOneOption(string $question, $default=null, $options=[], $error_message='%s is an invalid choice.')
    {
        $pretty_options = $this->getPrettyOptions($options);

        /** @var \Symfony\Component\Console\Question\ChoiceQuestion $question */
        $choiceQuestion = new ChoiceQuestion(
            $question,
            $pretty_options['choices'],
            $default
        );
        $choiceQuestion->setErrorMessage($error_message);

        return $pretty_options['map'][$this->questionHelper->ask($this->input, $this->output, $question)];
    }

    /**
     * @param string $question
     * @param string|mixed $default ~ comma sep
     * @param array $options ~ ex: ['Value1, 'Value2',... ] or ['Option1' => 'value', 'Option2' => 'value2', ...]
     * @param string $error_message ~ ex: 'Color %s is invalid.'
     * @return array ~ array of selected values
     */
    public function promptSelectMultipleOptions(string $question, $default=null, $options=[], $error_message='%s is an invalid choice.')
    {
        $pretty_options = $this->getPrettyOptions($options);

        /** @var \Symfony\Component\Console\Question\ChoiceQuestion $question */
        $choiceQuestion = new ChoiceQuestion(
            $question,
            $pretty_options['choices'],
            $default
        );
        $choiceQuestion
            ->setMultiselect(true)
            ->setErrorMessage($error_message);

        return $pretty_options['map'][$this->questionHelper->ask($this->input, $this->output, $question)];
    }

    /**
     * @param string $question
     * @param string|mixed $default
     * @return string|mixed ~ user input
     */
    public function promptHiddenInput(string $question, $default=null)
    {
        $questionInput = new Question($question, $default);
        $questionInput
            ->setHidden(true)
            ->setHiddenFallback(false);

        return $this->questionHelper->ask($this->input, $this->output, $questionInput);
    }

    /**
     * @param string $string
     * @param int $type UserInteractionHandler::MASSAGE_STRING, MASSAGE_SUCCESS, MASSAGE_WARNING or MASSAGE_ERROR
     * @return void
     */
    public function tellUser(string $string, int $type)
    {
        switch ($type) {
            case self::MASSAGE_STRING:
                // no break
            default:
                $this->output->writeln($string);
                break;
        }
    }

    /**
     * @param array $options
     * @return array
     */
    protected function getPrettyOptions($options = [])
    {
        $pretty_options = [
            'choices' => [],
            'map' => []
        ];
        // https://stackoverflow.com/questions/173400/how-to-check-if-php-array-is-associative-or-sequential#173479
        if (array_keys($options) !== range(0, count($options) - 1)) {
            foreach ($options as $option => $value) {
                $pretty_options['choices'][] = $option . '(' . $value . ')';
                $pretty_options['choices'][$option . '(' . $value . ')'] = $value;
            }

        } else {
            foreach ($options as $option) {
                $pretty_options['choices'][] = $option;
                $pretty_options['choices'][$option] = $option;
            }
        }

        return $pretty_options;
    }
}