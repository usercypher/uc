<?php

class Lib_Standard {
    function levenshteinUtf8($s1, $s2, $cost_ins = 1, $cost_rep = 1, $cost_del = 1) {
        if (strlen($s1) > 255 || strlen($s2) > 255) {
            return -1;
        }
        $c = $s1 . $s2;
        $len = strlen($c);
        $map = array();
        $i = 0;
        $counter = 0;
        while ($len > $i) {
            $byte = $c[$i];
            if ("\x80" > $byte) {
                $char = $c[$i];
                $i++;
            } elseif ("\xE0" > $byte) {
                $char = $c[$i] . $c[$i+1];
                $i += 2;
            } elseif ("\xF0" > $byte) {
                $char = $c[$i] . $c[$i+1] . $c[$i+2];
                $i += 3;
            } else {
                $char = $c[$i] . $c[$i+1] . $c[$i+2] . $c[$i+3];
                $i += 4;
            }
    
            if (!isset($map[$char]) && 256 > $counter) {
                $map[$char] = chr($counter++);
            }
        }
        $s1 = strtr($s1, $map);
        $s2 = strtr($s2, $map);
        return levenshtein($s1, $s2, $cost_ins, $cost_rep, $cost_del);
    }
}