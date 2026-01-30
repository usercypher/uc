<?php

class Lib_Cast_Standard {
    public function required() {
        return new Lib_Cast_Standard_Required;
    }

    public function trim() {
        return new Lib_Cast_Standard_Trim;
    }

    public function email() {
        return new Lib_Cast_Standard_Email;
    }

    public function lengthMin($min) {
        $o = new Lib_Cast_Standard_LengthMin;
        $o->min = $min;
        return $o;
    }

    public function lengthMax($max) {
        $o = new Lib_Cast_Standard_LengthMax;
        $o->max = $max;
        return $o;
    }

    public function toString() {
        return new Lib_Cast_Standard_ToString;
    }

    public function toInt() {
        return new Lib_Cast_Standard_ToInt;
    }

    public function toFloat() {
        return new Lib_Cast_Standard_ToFloat;
    }

    public function toBool() {
        return new Lib_Cast_Standard_ToBool;
    }

    public function regex($pattern = '') {
        $o = new Lib_Cast_Standard_Regex;
        $o->pattern = $pattern;
        return $o;
    }

    public function enum($allowed) {
        $o = new Lib_Cast_Standard_Enum;
        $o->allowed = $allowed;
        return $o;
    }

    public function defaultValue($defaultValue) {
        $o = new Lib_Cast_Standard_DefaultValue;
        $o->defaultValue = $defaultValue;
        return $o;
    }

    public function toDate() {
        return new Lib_Cast_Standard_ToDate;
    }

    public function toDateTime() {
        return new Lib_Cast_Standard_ToDateTime;
    }

    public function range($min, $max) {
        $o = new Lib_Cast_Standard_Range;
        $o->min = $min;
        $o->max = $max;
        return $o;
    }
}

class Lib_Cast_Standard_Required {
    function process($value) {
        if (empty($value)) {
            return array('Field is required', 1);
        }
        return $value;
    }
}

class Lib_Cast_Standard_Trim {
    function process($value) {
        return is_string($value) ? trim($value) : $value;
    }
}

class Lib_Cast_Standard_Email {
    function process($value) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return array('Invalid email format', 1);
        }
        return $value;
    }
}

class Lib_Cast_Standard_LengthMin {
    var $min;

    function process($value) {
        $min = $this->min;
        if ($min > strlen($value)) {
            return array("Must be at least $min characters", 1);
        }
        return $value;
    }
}

class Lib_Cast_Standard_LengthMax {
    var $max;

    function process($value) {
        $max = $this->max;
        if (strlen($value) > $max) {
            return array("Must be at most $max characters", 1);
        }
        return $value;
    }
}

class Lib_Cast_Standard_ToString {
    function process($value) {
        return (string)$value;
    }
}

class Lib_Cast_Standard_ToInt {
    function process($value) {
        return (int)$value;
    }
}

class Lib_Cast_Standard_ToFloat {
    function process($value) {
        return (float)$value;
    }
}

class Lib_Cast_Standard_ToBool {
    function process($value) {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}

class Lib_Cast_Standard_Regex {
    var $pattern;

    function process($value) {
        if (!preg_match($params['pattern'], $value)) {
            return array(
                isset($params['error']) ? $params['error'] : "Invalid format", 
                1
            );
        }
        return $value;
    }
}

class Lib_Cast_Standard_Enum {
    var $allowed;

    function process($value) {
        $allowed = $this->allowed;
        if (!in_array($value, $allowed)) {
            return array("Value must be one of: " . implode(', ', $allowed), 1);
        }
        return $value;
    }
}

class Lib_Cast_Standard_DefaultValue {
    var $defaultValue;

    function process($value) {
        $default = $this->defaultValue;
        if ($value === null || $value === '') {
            return $default;
        }
        return $value;
    }
}

class Lib_Cast_Standard_ToDate {
    function process($value) {
        if (!$value) return null;
        
        $timestamp = strtotime($value);
        if (!$timestamp) {
            return array("Invalid date format", 1);
        }
        return date('Y-m-d', $timestamp);
    }
}

class Lib_Cast_Standard_ToDateTime {
    function process($value) {
        if (!$value) return null;

        $timestamp = strtotime($value);
        if (!$timestamp) {
            return array("Invalid date-time format", 1);
        }
        return date('Y-m-d H:i:s', $timestamp);
    }
}

class Lib_Cast_Standard_Range {
    var $min, $max;

    function process($value) {
        $min = $this->min;
        $max = $this->min;

        if ($min > $value || $value > $max) {
            return array("Value must be between $min and $max", 1);
        }
        return $value;
    }
}
