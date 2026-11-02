<?php

class Cli_Pipe_Unit_Refs {
    var $app;

    function args($args) {
        list($this->app) = $args;
    }

    function call($input, $output) {
        $success = true;
        $message = '';

        if (empty($input->param['name'])) {
            $message .= 'Error: Missing required parameters.' . "\n";
            $message .= 'Usage: php [file] unit refs [name]' . "\n";
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

        $result = array(
            'args' => array(),
            'load' => array(),
            'base' => array(),
            'bind' => array(),
        );

        foreach ($this->app->unit as $u) {
            if ($u[APP_UNIT_LOAD]) {
                foreach ($u[APP_UNIT_LOAD] as $il => $l) {
                    if ($name === $this->app->unitList[$l]) {
                        $result['load'][] = '# ' . $il . '  ' . $this->app->unitList[$u[APP_UNIT_LIST]];
                    }
                }
            }

            if ($u[APP_UNIT_ARGS]) {
                foreach ($u[APP_UNIT_ARGS] as $ia => $a) {
                    list($atype, $aval) = $a;
                    if ($atype === APP_UNIT_ARGS_TYP_UNIT && $name === $this->app->unitList[$aval]) {
                        $result['args'][] = '# ' . $ia . '  ' . $this->app->unitList[$u[APP_UNIT_LIST]];
                    }
                }
            }

            if ($u[APP_UNIT_BASE] && $name === $this->app->unitList[$u[APP_UNIT_BASE]]) {
                $result['base'][] = $this->app->unitList[$u[APP_UNIT_LIST]];
            }

            if ($u[APP_UNIT_BIND] && $name === $this->app->unitList[$u[APP_UNIT_BIND]]) {
                $result['bind'][] = $this->app->unitList[$u[APP_UNIT_LIST]];
            }
        }
        $loadTpl = '';
        foreach ($load as $i => $unit) {
            $loadTpl .= "    # $i  " . $this->app->unitList[$unit] . "\n";
        }

        $message .= 'UNIT REFS' . "\n";
        $message .= '  load : ' . "\n    " . implode("\n    ", $result['load']) . "\n";
        $message .= '  args : ' . "\n    " . implode("\n    ", $result['args']) . "\n";
        $message .= '  base : ' . "\n    " . implode("\n    ", $result['base']) . "\n";
        $message .= '  bind : ' . "\n    " . implode("\n    ", $result['bind']) . "\n";

        $output->content = $message;

        return array($input, $output, $success);
    }
}
