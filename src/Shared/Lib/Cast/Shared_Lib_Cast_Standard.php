<?php

class Shared_Lib_Cast_Standard {
    var $t;

    function args($args) {
        list(
            $translator
        ) = $args;

        $this->t = $translator->get('shared');
    }

    public function required() {
        $o = new Shared_Lib_Cast_Standard_Required;
        $o->t = $this->t;
        return $o;
    }

    public function trim() {
        return new Shared_Lib_Cast_Standard_Trim;
    }

    public function email() {
        $o = new Shared_Lib_Cast_Standard_Email;
        $o->t = $this->t;
        return $o;
    }

    public function lengthMin($min) {
        $o = new Shared_Lib_Cast_Standard_LengthMin;
        $o->t = $this->t;
        $o->min = $min;
        return $o;
    }

    public function lengthMax($max) {
        $o = new Shared_Lib_Cast_Standard_LengthMax;
        $o->t = $this->t;
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
        $o->t = $this->t;
        $o->pattern = $pattern;
        $o->error = $error;
        return $o;
    }

    public function enum($allowed) {
        $o = new Shared_Lib_Cast_Standard_Enum;
        $o->t = $this->t;
        $o->allowed = $allowed;
        return $o;
    }

    public function value($value, $override = true) {
        $o = new Shared_Lib_Cast_Standard_Value;
        $o->value = $value;
        $o->override = $override;
        return $o;
    }

    public function defaultValue($defaultValue) {
        $o = new Shared_Lib_Cast_Standard_DefaultValue;
        $o->defaultValue = $defaultValue;
        return $o;
    }

    public function toDate() {
        $o = new Shared_Lib_Cast_Standard_ToDate;
        $o->t = $this->t;
        return $o;
    }

    public function toDateTime() {
        $o = new Shared_Lib_Cast_Standard_ToDateTime;
        $o->t = $this->t;
        return $o;
    }

    public function range($min, $max) {
        $o = new Shared_Lib_Cast_Standard_Range;
        $o->t = $this->t;
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
    public function equalTo($value, $message = null) {
        $o = new Shared_Lib_Cast_Standard_EqualTo;
        $o->t = $this->t;
        $o->value = $value;
        $o->message = $message;
        return $o;
    }
}

class Shared_Lib_Cast_Standard_Required {
    var $t;
    function call($value) {
        if (empty($value)) {
            return array($value, $this->t->t('lib_cast_standard_required'));
        }
        return array($value, null);
    }
}

class Shared_Lib_Cast_Standard_Trim {
    function call($value) {
        return array(is_string($value) ? trim($value) : $value, null);
    }
}

class Shared_Lib_Cast_Standard_Email {
    var $t;
    function call($value) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return array($value, $this->t->t('lib_cast_standard_invalid_email'));
        }
        return array($value, null);
    }
}

class Shared_Lib_Cast_Standard_LengthMin {
    var $t;
    var $min;

    function call($value) {
        $min = $this->min;
        if ($min > strlen($value)) {
            return array($value, $this->t->t('lib_cast_standard_length_min', array(
                ':min' => $min,
            )));
        }
        return array($value, null);
    }
}

class Shared_Lib_Cast_Standard_LengthMax {
    var $t;
    var $max;

    function call($value) {
        $max = $this->max;
        if (strlen($value) > $max) {
            return array($value, $this->t->t('lib_cast_standard_length_max', array(
                ':max' => $max,
            )));
        }
        return array($value, null);
    }
}

class Shared_Lib_Cast_Standard_ToString {
    function call($value) {
        return array((string)$value, null);
    }
}

class Shared_Lib_Cast_Standard_ToInt {
    function call($value) {
        return array((int)$value, null);
    }
}

class Shared_Lib_Cast_Standard_ToFloat {
    function call($value) {
        return array((float)$value, null);
    }
}

class Shared_Lib_Cast_Standard_ToBool {
    function call($value) {
        return array(filter_var($value, FILTER_VALIDATE_BOOLEAN), null);
    }
}

class Shared_Lib_Cast_Standard_Regex {
    var $t;
    var $pattern, $error;

    function call($value) {
        if (!preg_match($this->pattern, $value)) {
            return array($value, isset($this->error) ? $this->error : $this->t->t('lib_cast_standard_invalid_format'));
        }
        return array($value, null);
    }
}

class Shared_Lib_Cast_Standard_Enum {
    var $t;
    var $allowed;

    function call($value) {
        $allowed = $this->allowed;
        if (!in_array($value, $allowed)) {
            return array($value, $this->t->t('lib_cast_standard_enum', array(
                ':allowed' => implode(', ', $allowed),
            )));
        }
        return array($value, null);
    }
}

class Shared_Lib_Cast_Standard_Value {
    var $value;
    var $override;

    function call($value) {
        return array($this->override ? $this->value : $value, null);
    }
}

class Shared_Lib_Cast_Standard_DefaultValue {
    var $defaultValue;

    function call($value) {
        $default = $this->defaultValue;
        if ($value === null || $value === '') {
            return array($default, null);
        }
        return array($value, null);
    }
}

class Shared_Lib_Cast_Standard_ToDate {
    var $t;
    function call($value) {
        if (!$value) return array(null, null);
        
        $timestamp = strtotime($value);
        if (!$timestamp) {
            return array($value, $this->t->t('lib_cast_standard_invalid_date'));
        }
        return array(date('Y-m-d', $timestamp), null);
    }
}

class Shared_Lib_Cast_Standard_ToDateTime {
    var $t;
    function call($value) {
        if (!$value) return array(null, null);

        $timestamp = strtotime($value);
        if (!$timestamp) {
            return array($value, $this->t->t('lib_cast_standard_invalid_datetime'));
        }
        return array(date('Y-m-d H:i:s', $timestamp), null);
    }
}

class Shared_Lib_Cast_Standard_Range {
    var $t;
    var $min, $max;

    function call($value) {
        $min = $this->min;
        $max = $this->max;

        if ($min > $value || $value > $max) {
            return array($value, $this->t->t('lib_cast_standard_range', array(
                ':min' => $min,
                ':max' => $max
            )));
        }
        return array($value, null);
    }
}

class Shared_Lib_Cast_Standard_EmptyToNull {
    function call($value) {
        if (is_string($value) && trim($value) === '') {
            return array(null, null);
        }
        return array($value, null);
    }
}

class Shared_Lib_Cast_Standard_PasswordHash {
    var $algo;

    function call($value) {
        if (!$value) return array($value, null);

        return array(password_hash($value, $this->algo), null);
    }
}

class Shared_Lib_Cast_Standard_EqualTo {
    var $t;
    var $value;
    var $message;

    function call($value) {
        if ($value !== $this->value) {
            return array($value, isset($this->message) ? $this->message : $this->t->t('lib_cast_standard_not_equal'));
        } 
        return array($value, null);
    }
}
