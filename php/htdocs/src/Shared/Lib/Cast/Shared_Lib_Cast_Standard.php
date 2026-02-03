<?php

class Shared_Lib_Cast_Standard {
    public function required() {
        return new Shared_Lib_Cast_Standard_Required;
    }

    public function trim() {
        return new Shared_Lib_Cast_Standard_Trim;
    }

    public function email() {
        return new Shared_Lib_Cast_Standard_Email;
    }

    public function lengthMin($min) {
        $o = new Shared_Lib_Cast_Standard_LengthMin;
        $o->min = $min;
        return $o;
    }

    public function lengthMax($max) {
        $o = new Shared_Lib_Cast_Standard_LengthMax;
        $o->max = $max;
        return $o;
    }

    public function toString() {
        return new Shared_Lib_Cast_Standard_ToString;
    }

    public function toInt() {
        return new Shared_Lib_Cast_Standard_ToInt;
    }

    public function toFloat() {
        return new Shared_Lib_Cast_Standard_ToFloat;
    }

    public function toBool() {
        return new Shared_Lib_Cast_Standard_ToBool;
    }

    public function regex($pattern, $error = null) {
        $o = new Shared_Lib_Cast_Standard_Regex;
        $o->pattern = $pattern;
        $o->error = $error;
        return $o;
    }

    public function enum($allowed) {
        $o = new Shared_Lib_Cast_Standard_Enum;
        $o->allowed = $allowed;
        return $o;
    }

    public function defaultValue($defaultValue) {
        $o = new Shared_Lib_Cast_Standard_DefaultValue;
        $o->defaultValue = $defaultValue;
        return $o;
    }

    public function toDate() {
        return new Shared_Lib_Cast_Standard_ToDate;
    }

    public function toDateTime() {
        return new Shared_Lib_Cast_Standard_ToDateTime;
    }

    public function range($min, $max) {
        $o = new Shared_Lib_Cast_Standard_Range;
        $o->min = $min;
        $o->max = $max;
        return $o;
    }

    public function emptyToNull() {
        return new Shared_Lib_Cast_Standard_EmptyToNull;
    }

    public function passwordHash($algo = PASSWORD_DEFAULT) {
        $o = new Shared_Lib_Cast_Standard_PasswordHash;
        $o->algo = $algo;
        return $o;
    }
}

class Shared_Lib_Cast_Standard_Required {
    function process($value) {
        if (empty($value)) {
            return array($value, 'Field is required');
        }
        return array($value, null);
    }
}

class Shared_Lib_Cast_Standard_Trim {
    function process($value) {
        return array(is_string($value) ? trim($value) : $value, null);
    }
}

class Shared_Lib_Cast_Standard_Email {
    function process($value) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return array($value, 'Invalid email format');
        }
        return array($value, null);
    }
}

class Shared_Lib_Cast_Standard_LengthMin {
    var $min;

    function process($value) {
        $min = $this->min;
        if ($min > strlen($value)) {
            return array($value, "Must be at least $min characters");
        }
        return array($value, null);
    }
}

class Shared_Lib_Cast_Standard_LengthMax {
    var $max;

    function process($value) {
        $max = $this->max;
        if (strlen($value) > $max) {
            return array($value, "Must be at most $max characters");
        }
        return array($value, null);
    }
}

class Shared_Lib_Cast_Standard_ToString {
    function process($value) {
        return array((string)$value, null);
    }
}

class Shared_Lib_Cast_Standard_ToInt {
    function process($value) {
        return array((int)$value, null);
    }
}

class Shared_Lib_Cast_Standard_ToFloat {
    function process($value) {
        return array((float)$value, null);
    }
}

class Shared_Lib_Cast_Standard_ToBool {
    function process($value) {
        return array(filter_var($value, FILTER_VALIDATE_BOOLEAN), null);
    }
}

class Shared_Lib_Cast_Standard_Regex {
    var $pattern, $error;

    function process($value) {
        if (!preg_match($this->pattern, $value)) {
            return array($value, isset($this->error) ? $this->error : "Invalid format");
        }
        return array($value, null);
    }
}

class Shared_Lib_Cast_Standard_Enum {
    var $allowed;

    function process($value) {
        $allowed = $this->allowed;
        if (!in_array($value, $allowed)) {
            return array($value, "Value must be one of: " . implode(', ', $allowed));
        }
        return array($value, null);
    }
}

class Shared_Lib_Cast_Standard_DefaultValue {
    var $defaultValue;

    function process($value) {
        $default = $this->defaultValue;
        if ($value === null || $value === '') {
            return array($default, null);
        }
        return array($value, null);
    }
}

class Shared_Lib_Cast_Standard_ToDate {
    function process($value) {
        if (!$value) return array(null, null);
        
        $timestamp = strtotime($value);
        if (!$timestamp) {
            return array($value, "Invalid date format");
        }
        return array(date('Y-m-d', $timestamp), null);
    }
}

class Shared_Lib_Cast_Standard_ToDateTime {
    function process($value) {
        if (!$value) return array(null, null);

        $timestamp = strtotime($value);
        if (!$timestamp) {
            return array($value, "Invalid date-time format");
        }
        return array(date('Y-m-d H:i:s', $timestamp), null);
    }
}

class Shared_Lib_Cast_Standard_Range {
    var $min, $max;

    function process($value) {
        $min = $this->min;
        $max = $this->max;

        if ($min > $value || $value > $max) {
            return array($value, "Value must be between $min and $max");
        }
        return array($value, null);
    }
}

class Shared_Lib_Cast_Standard_EmptyToNull {
    function process($value) {
        if (is_string($value) && trim($value) === '') {
            return array(null, null);
        }
        return array($value, null);
    }
}

class Shared_Lib_Cast_Standard_PasswordHash {
    var $algo;

    function process($value) {
        if (!$value) return array($value, null);

        return array(password_hash($value, $this->algo), null);
    }
}
