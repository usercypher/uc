<?php

class Cli_Pipe_Unit_Info {
    var $app;

    function args($args) {
        list($this->app) = $args;
    }

    function call($input, $output) {
        $success = true;
        $message = '';

        if (empty($input->param['name'])) {
            $message .= 'Error: Missing required parameters.' . "\n";
            $message .= 'Usage: php [file] unit info [name]' . "\n";
            $output->content = $message;
            $output->code = 1;
            $success = false;
            return array($input, $output, $success);
        }

        $name = $input->param['name'];

        if (!isset($this->app->unit[$name])) {
            $message .= 'Error: Unit not found.' . "\n";
            $output->content = $message;
            $output->code = 1;
            $success = false;
            return array($input, $output, $success);
        }

        $path = $this->app->unit[$name][APP_UNIT_PATH];
        $file = $this->app->unit[$name][APP_UNIT_FILE];
        $load = $this->app->unit[$name][APP_UNIT_LOAD];
        $args = $this->app->unit[$name][APP_UNIT_ARGS];
        $base = $this->app->unit[$name][APP_UNIT_BASE];
        $bind = $this->app->unit[$name][APP_UNIT_BIND];
        $inst_cache  = $this->app->unit[$name][APP_UNIT_INST_CACHE];

        $loadTpl = '';
        foreach ($load as $i => $unit) {
            $loadTpl .= "    # $i  " . $this->app->unitList[$unit] . "\n";
        }

        $argsTpl = '';
        foreach ($args as $i => $tv) {
            list($type, $val) = $tv;
            if ($type === APP_UNIT_ARGS_TYP_UNIT) {
                $val = 'unit:' . $this->app->unitList[$val];
            } else {
                ob_start();
                print_r($val);
                $val = 'data:' . ob_get_contents();
                ob_end_clean();
            }
            $argsTpl .= "    # $i  " . str_replace("\n", "\n    ", trim($val)) . "\n";
        }

        $message .= 'UNIT INFO' . "\n";
        $message .= '  unit       : ' . $name . "\n";
        $message .= '  path       : ' . $this->app->pathList[$path] . "\n";
        $message .= '  file       : ' . $file . "\n";
        $message .= '  load       : ' . "\n" . $loadTpl;
        $message .= '  args       : ' . "\n" . $argsTpl;
        $message .= '  base       : ' . $this->app->unitList[$base] . "\n";
        $message .= '  bind       : ' . $this->app->unitList[$bind] . "\n";
        $message .= '  inst_cache : ' . $inst_cache . "\n";

        $output->content = $message;

        return array($input, $output, $success);
    }
}
