<?php

class Shared_Lib_Curl {
    var $headerRaw = array();

    function send($url, $options = array()) {
        $ch = $this->createHandle($url, $options);

        $result = new stdClass();
        $result->header = array();
        $result->content = curl_exec($ch);
        $result->code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $result->error = curl_error($ch);
        $result->info = curl_getinfo($ch);

        unset($this->headerRaw[(int)$ch]);

        return $result;
    }

    function sendBatch($requests) {
        $mh = curl_multi_init();
        $handles = array();
        $results = array();

        foreach ($requests as $key => $req) {
            $url = isset($req['url']) ? $req['url'] : '';
            $options = isset($req['options']) ? $req['options'] : array();

            $ch = $this->createHandle($url, $options);
            curl_multi_add_handle($mh, $ch);

            $handles[(int)$ch] = array(
                'key' => $key,
                'ch'  => $ch
            );

            $results[$key] = new stdClass();
            $results[$key]->header = array();
        }

        $active = null;
        do {
            $status = curl_multi_exec($mh, $active);
            if ($active) {
                curl_multi_select($mh);
            }
        } while ($active && $status == CURLM_OK);

        foreach ($handles as $id => $info) {
            $ch = $info['ch'];
            $key = $info['key'];

            $results[$key]->content = curl_multi_getcontent($ch);
            $results[$key]->code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $results[$key]->error   = curl_multi_info_read($mh) ? '' : curl_error($ch); // simple mapping
            $results[$key]->info    = curl_getinfo($ch);

            unset($this->headerRaw[$id]);
            curl_multi_remove_handle($mh, $ch);
        }

        curl_multi_close($mh);
        return $results;
    }

    function createHandle($url, $options = array()) {
        $ch = curl_init();

        $method = isset($options['method']) ? strtoupper($options['method']) : 'GET';
        $header = isset($options['header']) ? $options['header'] : array();
        $content = isset($options['content']) ? $options['content'] : '';
        $timeout = isset($options['timeout']) ? (int) $options['timeout'] : 30;

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($content !== '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        }

        $verify_ssl = isset($options['verify_ssl']) ? $options['verify_ssl'] : true;
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verify_ssl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $verify_ssl ? 2 : 0);

        if (isset($options['ca_info'])) {
            curl_setopt($ch, CURLOPT_CAINFO, $options['ca_info']);
        }

        if (!empty($header)) {
            $parsedHeaders = array();
            foreach ($header as $key => $value) {
                $parsedHeaders[] = $key . ':' . $value;
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $parsedHeaders);
        }

        $this->headerRaw[(int)$ch] = array('raw' => array(), 'result' => new stdClass());

        $that = &$this;
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($that, 'header'));

        return $ch;
    }

    function header($ch, $headerLine) {
        $id = (int)$ch;
        $trimmed = trim($headerLine);

        if ($trimmed === '') {
            if (isset($this->headerRaw[$id])) {
                $this->headerRaw[$id]['result']->header = $this->parseHeader($this->headerRaw[$id]['raw']);
                $this->headerRaw[$id]['raw'] = array();
            }
        } else {
            $this->headerRaw[$id]['raw'][] = $headerLine;
        }

        return strlen($headerLine);
    }

    function parseHeader($headerLines) {
        $header = array();
        foreach ($headerLines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $key = strtolower(trim($key));
                $value = trim($value);
                if (!isset($header[$key])) {
                    $header[$key] = $value;
                } else {
                    if (is_array($header[$key])) {
                        $header[$key][] = $value;
                    } else {
                        $header[$key] = array($header[$key], $value);
                    }
                }
            }
        }
        return $header;
    }
}