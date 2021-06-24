<?php


namespace App\Controller;

use Exception;

class Rules
{
    public string $operator;

    public $conditions;

    public $effects;

    function __construct()
    {
        if (is_file('../config/rules.json')) {
            $rules = file_get_contents('../config/rules.json');
        } else {
            throw new Exception('Не открывается файл rules.json');
        }

        $rules = json_decode($rules, false);
        $this->operator = $rules->rules[0]->operator;
        $this->conditions = $rules->rules[0]->conditions;
        $this->effects = $rules->rules[0]->effects;
    }

    public function compareWithCondition($first, $second, $condition)
    {
        switch ($condition) {
            case 'equal':
                return $first === $second;
            case 'inArray':
                return in_array($second, $first);
            case 'moreThan':
                return $first > $second;
            case 'lessThan':
                return $first < $second;
            default:
                return "error";
        }
    }

    public function operatorResult($first, $second)
    {
        if ($this->operator === 'and') {
            return $first and $second;
        } elseif ($this->operator === 'or') {
            return $first or $second;
        } else {
            return "error";
        }
    }

    public function operatorInitial()
    {
        if ($this->operator === 'and') {
            return true;
        } elseif ($this->operator === 'or') {
            return false;
        } else {
            return "error";
        }
    }

    public function isConditionsTrue($project)
    {
        return array_reduce($this->conditions, function ($acc, $condition) use ($project) {

            $conditionKey = $condition->key;
            $conditionVal = $condition->val;
            $conditionCondition = $condition->condition;

            return $this->operatorResult($acc, $this->compareWithCondition($project->$conditionKey, $conditionVal, $conditionCondition));

        }, $this->operatorInitial());
    }

//    public function getPlacefolders($projects, $effect) {
//        $result = [];
//        foreach ($projects as $project) {
//            foreach ($effect as $key => $value) {
//                array_push($result, [$key, $project=>$value]);
//            }
//        }
//        return $result;
//    }


}