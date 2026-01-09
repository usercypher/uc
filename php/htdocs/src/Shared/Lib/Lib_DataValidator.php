<?php

class Lib_DataValidator {
    var $ruleObjects = array();

    function args($args) {
        foreach ($args as $arg) {
            $this->ruleObjects[$arg->name] = $arg;
        }
    }

    function validate($data, $validationRules) {
        $errors = array();
        foreach ($validationRules as $field => $rules) {
            foreach ($rules as $rule) {
                list($ruleName, $ruleMethod, $ruleArgs) = $rule;
                $error = $this->ruleObjects[$ruleName]->$ruleMethod(isset($data[$field]) ? $data[$field] : null, $ruleArgs);
                if ($error) {
                    list($msg, $signal) = $error;
                    $errors[$field][] = $msg;
                    if ($signal === -1) {
                        break;
                    }
                    if ($signal === -2) {
                        return $errors;
                    }
                }
            }
        }
        return empty($errors) ? null : $errors;
    }
}
