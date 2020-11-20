<?php

namespace xPDO\Helpers;

class EmptyUserInteractionHandler implements UserInteractionHandler
{
    public function __construct()
    {

    }

    /**
     * @param string $question
     * @param bool $default
     * @return bool
     */
    public function promptConfirm(string $question, $default=true)
    {
        return $default;
    }

    /**
     * @param string $question
     * @param string|mixed $default
     * @return string|mixed ~ user input
     */
    public function promptInput(string $question, $default)
    {
        return $default;
    }

    /**
     * @param string $question
     * @param string|mixed $default
     * @param array $options ~ ex: ['Value1, 'Value2',... ] or ['Option1' => 'value', 'Option2' => 'value2', ...]
     * @param string $error_message ~ ex: 'Color %s is invalid.'
     * @return mixed ~ selected value
     */
    public function promptSelectOneOption(string $question, $default, $options=[], $error_message='%s is an invalid choice.')
    {
        return $default;
    }

    /**
     * @param string $question
     * @param string|mixed $default ~ comma sep
     * @param array $options ~ ex: ['Value1, 'Value2',... ] or ['Option1' => 'value', 'Option2' => 'value2', ...]
     * @param string $error_message ~ ex: 'Color %s is invalid.'
     * @return array ~ array of selected values
     */
    public function promptSelectMultipleOptions(string $question, $default, $options=[], $error_message='%s is an invalid choice.')
    {
        return $default;
    }

    /**
     * @param string $question
     * @param string|mixed $default
     * @return string|mixed ~ user input
     */
    public function promptHiddenInput(string $question, $default)
    {
        return $default;
    }

    /**
     * @param string $string
     * @param int $type UserInteractionHandler::MASSAGE_STRING, MASSAGE_SUCCESS, MASSAGE_WARNING or MASSAGE_ERROR
     * @return void
     */
    public function tellUser(string $string, int $type)
    {
        echo $string.PHP_EOL;
    }
}