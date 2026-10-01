<?php

class Shared_Lib_Decimal {
    function add($a, $b, $scale = 0) {
        return bcadd($a, $b, $scale);
    }

    function sub($a, $b, $scale = 0) {
        return bcsub($a, $b, $scale);
    }

    function mul($a, $b, $scale = 0) {
        return bcmul($a, $b, $scale);
    }

    function div($a, $b, $scale = 0) {
        return bcdiv($a, $b, $scale);
    }

    function pow($a, $b, $scale = 0) {
        return bcpow($a, $b, $scale);
    }

    function sqrt($n, $scale = 10) {
        return bcsqrt($n, $scale);
    }

    function scale($scale) {
        return bcscale($scale);
    }

    function comp($a, $b, $scale = 0) {
        for ($iarr = array(&$a, &$b), $i = 0, $ilen = 2; $ilen > $i; $i++) {
            $s = $iarr[$i];
            $len = strlen($s);
            if ($len == 0) { continue; }

            $j = 0;
            $c = $s[0];
            if ($c == '-' || $c == '+') {
                $j = 1;
            }

            $z = true;
            $digit = false;
            $seen_dot = false;

            for (; $len > $j; $j++) {
                $c = $s[$j];
                if ($c == '.') { $seen_dot = true; $j++; break; }
                if ($c != '0') { $z = false; break; }
                $digit = true;
            }

            if ($z && $seen_dot) {
                $check = $scale > ($len - $j) ? ($len - $j) : $scale;
                $end   = $j + $check;
                for (; $end > $j; $j++) {
                    if ($s[$j] != '0') { $z = false; break; }
                    $digit = true;
                }
            }

            if ($z && $digit) { $iarr[$i] = '0'; }
        }

        return bccomp($a, $b, $scale);
    }

    function round($n, $precision = 2, $mode = 'ROUND_HALF_UP') {
        if (!in_array($mode, array(
            'ROUND_HALF_UP', 'ROUND_HALF_DOWN', 'ROUND_HALF_EVEN',
            'ROUND_HALF_ODD', 'ROUND_UP', 'ROUND_DOWN',
            'ROUND_CEILING', 'ROUND_FLOOR'
        ))) {
            user_error("Shared_Lib_Decimal::round(): unknown mode '$mode'", E_USER_WARNING);
            return null;
        }

        $n = (string) $n;
        $precision = (int) $precision;
        if ($precision < 0) { $precision = 0; }

        $neg = (strlen($n) > 0 && $n[0] == '-');
        $mag = $neg ? substr($n, 1) : $n;

        $dot  = strpos($mag, '.');
        $frac = ($dot === false) ? '' : substr($mag, $dot + 1);
        $flen = strlen($frac);

        $lastDigit = ($precision < $flen) ? (int) $frac[$precision] : 0;

        $tailNonZero = false;
        for ($k = $precision + 1; $k < $flen; $k++) {
            if ($frac[$k] != '0') { $tailNonZero = true; break; }
        }

        $anyNonZero = ($lastDigit != 0) || $tailNonZero;

        if (!$anyNonZero) {
            return $this->add($n, '0', $precision);
        }

        $truncated = $this->add($n, '0', $precision);

        $step = ($precision <= 0) ? '1' : '0.' . str_repeat('0', $precision - 1) . '1';

        $up   = $neg ? $this->sub($truncated, $step, $precision)
                     : $this->add($truncated, $step, $precision);
        $down = $neg ? $this->add($truncated, $step, $precision)
                     : $this->sub($truncated, $step, $precision);

        switch ($mode) {

            case 'ROUND_HALF_UP':
                if ($lastDigit >= 5 || ($lastDigit == 5 && $tailNonZero)) {
                    return $up;
                }
                if ($lastDigit > 5) { return $up; }
                return $truncated;

            case 'ROUND_HALF_DOWN':
                if ($lastDigit > 5 || ($lastDigit == 5 && $tailNonZero)) {
                    return $up;
                }
                return $truncated;

            case 'ROUND_HALF_EVEN':
                if ($lastDigit > 5 || ($lastDigit == 5 && $tailNonZero)) {
                    return $up;
                }
                if ($lastDigit < 5) {
                    return $truncated;
                }
                $keep = (int) substr($truncated, -1);
                return (($keep % 2) === 0) ? $truncated : $up;

            case 'ROUND_HALF_ODD':
                if ($lastDigit > 5 || ($lastDigit == 5 && $tailNonZero)) {
                    return $up;
                }
                if ($lastDigit < 5) {
                    return $truncated;
                }
                $keep = (int) substr($truncated, -1);
                return (($keep % 2) !== 0) ? $truncated : $up;

            case 'ROUND_UP':
                return $up;

            case 'ROUND_DOWN':
                return $truncated;

            case 'ROUND_CEILING':
                return (!$neg) ? $up : $truncated;

            case 'ROUND_FLOOR':
                return ($neg) ? $up : $truncated;
        }

        return $truncated;
    }

    function abs($a, $scale = 0) {
        return $this->comp($a, '0', $scale) < 0 ? $this->sub('0', $a, $scale) : $this->add($a, '0', $scale);
    }

    function min($a, $b, $scale = 0) {
        return $this->add(($this->comp($a, $b, $scale) < 0 ? $a : $b), '0', $scale);
    }

    function max($a, $b, $scale = 0) {
        return $this->add(($this->comp($a, $b, $scale) > 0 ? $a : $b), '0', $scale);
    }
}
