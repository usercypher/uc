<?php

class Shared_Lib_Standard {
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

    function pseudoword($syllables = 2, $opts = array()) {
        $o = array(
            'capitalize' => false,
            'blocked'    => array(),
            'min_length' => 3,
            'max_tries'  => 300,
            'vowels'     => array('a','e','i','o','u'),
            'consonants' => array('b','c','d','f','g','k','l','m','n','p','r','s','t','z'),
            'clusters'   => array('br','cr','dr','fr','gr','pr','tr','sc','sk','sl','sm','sn','sp','st','sw','shr','spl','spr','str','scr','ch','sh','th','wh','ph','bl','cl','fl','gl','pl'),
        );

        foreach ($opts as $k => $v) {
            if (isset($o[$k])) {
                $o[$k] = $v;
            }
        }

        $blocked = array();

        foreach ($o['blocked'] as $b) {
            $blocked[] = strtolower($b);
        }

        $syllables = $syllables > 1 ? (int)$syllables : 1;
        $vLen = count($o['vowels']) - 1;
        $cLen = count($o['consonants']) - 1;
        $clLen = count($o['clusters']) - 1;
        $vowelSet = array();

        foreach ($o['vowels'] as $v) {
            $vowelSet[$v] = 1;
        }

        for ($t = 0, $maxTries = $o['max_tries']; $maxTries > $t; $t++) {
            $word = '';
            $sylCount = 0;

            while ($syllables > $sylCount) {
                $last = ($word === '') ? '' : $word[strlen($word) - 1];

                if ($last === '') {
                    if (mt_rand(0, 2) === 0) {
                        $word .= $o['vowels'][mt_rand(0, $vLen)];
                        $sylCount++;

                        if (mt_rand(0, 1) === 0) {
                            $word .= $o['consonants'][mt_rand(0, $cLen)];
                        }
                    } else {
                        if ($clLen >= 0 && mt_rand(0, 3) === 0) {
                            $word .= $o['clusters'][mt_rand(0, $clLen)];
                        } else {
                            $word .= $o['consonants'][mt_rand(0, $cLen)];
                        }

                        $word .= $o['vowels'][mt_rand(0, $vLen)];
                        $sylCount++;

                        if (mt_rand(0, 1) === 0) {
                            $word .= $o['consonants'][mt_rand(0, $cLen)];
                        }
                    }
                } elseif (isset($vowelSet[$last])) {
                    if ($clLen >= 0 && mt_rand(0, 3) === 0) {
                        $word .= $o['clusters'][mt_rand(0, $clLen)];
                    } else {
                        $word .= $o['consonants'][mt_rand(0, $cLen)];
                    }

                    $word .= $o['vowels'][mt_rand(0, $vLen)];
                    $sylCount++;

                    if (mt_rand(0, 1) === 0) {
                        $word .= $o['consonants'][mt_rand(0, $cLen)];
                    }
                } else {
                    $word .= $o['vowels'][mt_rand(0, $vLen)];
                    $sylCount++;
                }
            }

            if ($o['min_length'] > strlen($word) || in_array(strtolower($word), $blocked)) {
                continue;
            }

            return $o['capitalize'] ? ucfirst($word) : $word;
        }

        return '';
    }

    function timeid($extra = 0) {
        static $base32 = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
        $extra = max(0, (int) $extra);
        $widths = array(4, 4, 2, 4, 4, 2);
        $mt = str_pad(time(), 12, '0', STR_PAD_LEFT) . substr(microtime(), 2, 3);
        $values = array((int)substr($mt, 0, 6), (int)substr($mt, 6, 6), (int)substr($mt, 12, 3), mt_rand(0, 1048575), mt_rand(0, 1048575), mt_rand(0, 1023));

        for ($i = 0, $chunk = 1; $extra > $i; $i++, $chunk++) {
            if (!($extra > $i + 1) || $chunk === 4) {
                $widths[] = $chunk;
                $values[] = mt_rand(0, pow(32, $chunk) - 1);
                $chunk = 0;
            }
        }

        $id = '';
        foreach ($values as $i => $num) {
            $encoded = '';
            while ($num > 0) {
                $encoded = $base32[$num % 32] . $encoded;
                $num = floor($num / 32);
            }
            $id .= str_pad($encoded, $widths[$i], '0', STR_PAD_LEFT);
        }

        return $id;
    }
}