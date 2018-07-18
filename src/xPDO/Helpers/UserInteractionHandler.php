<?php

namespace xPDO\Helpers;


interface UserInteractionHandler
{
    const MASSAGE_STRING = 1;
    const MASSAGE_SUCCESS = 2;
    const MASSAGE_WARNING = 3;
    const MASSAGE_ERROR = 4;

    /**
     * @param string $question
     * @param bool $default
     * @return bool
     */
    public function promptConfirm(string $question, $default);

    /**
     * @param string $question
     * @param string|mixed $default
     * @return string|mixed ~ user input
     */
    public function promptInput(string $question, $default);

    /**
     * @param string $question
     * @param string|mixed $default
     * @param array $options ~ ex: ['Option1' => 'value', 'Option2' => 'value2', ...]
     * @return mixed ~ selected value
     */
    public function promptSelectOneOption(string $question, $default, $options=[]);

    /**
     * @param string $question
     * @param string|mixed $default
     * @param array $options ~ ex: ['Option1' => 'value', 'Option2' => 'value2', ...]
     * @return array ~ array of selected values
     */
    public function promptSelectMultipleOptions(string $question, $default, $options=[]);

    /**
     * @param string $question
     * @param string|mixed $default
     * @return string|mixed ~ user input
     */
    public function promptHiddenInput(string $question, $default);

    /**
     * @param string $string
     * @param int $type UserInteractionHandler::MASSAGE_STRING, MASSAGE_SUCCESS, MASSAGE_WARNING or MASSAGE_ERROR
     * @return void
     */
    public function tellUser(string $string, int $type);

}