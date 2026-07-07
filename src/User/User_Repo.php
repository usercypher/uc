<?php

class User_Repo extends Shared_Lib_DatabaseHelper {
    private $app;
    private $database;
    private $castStandard;
    private $castDb;

    public function args($args) {
        list(
            $this->app,
            $this->database,
            $this->castStandard,
            $this->castDb
        ) = $args;

        $db = $this->app->getEnv('DB', array());
        $name = 'DEFAULT';

        parent::setTable('user');
        parent::setDb(
            $this->database,
            $this->database->connect(array(
                'dsn' => isset($db[$name]['DSN']) ? $db[$name]['DSN'] : null,
                'user' => isset($db[$name]['USER']) ? $db[$name]['USER'] : null,
                'pass' => isset($db[$name]['PASS']) ? $db[$name]['PASS'] : null,
                'query' => isset($db[$name]['QUERY']) ? $db[$name]['QUERY'] : null,
            ), $name)
        );
    }

    public function getSchema($action, $context = array()) {
        $std = $this->castStandard;
        $db = $this->castDb;
        $s = array();

        if (in_array($action, array('update', 'delete'))) {
            $s['id'] = array(
                $std->toInt(),
                $std->required(),
                $db->exists($this->table, 'id')
            );
        }

        if (in_array($action, array('insert', 'update'))) {
            $accountFields = array(
                'username' => array(
                    $std->toString(),
                    $std->required(),
                    $std->lengthMin(3),
                    $std->lengthMax(50),
                ),
                'email' => array(
                    $std->toString(),
                    $std->lengthMax(100),
                    $std->emptyToNull()
                ),
                'first_name' => array(
                    $std->toString(),
                    $std->lengthMax(255)
                ),
                'last_name' => array(
                    $std->toString()
                ),
                'role' => array(
                    $std->toString(),
                    $std->defaultValue('user'),
                    $std->value('user', empty($context['is_session_user_role_root'])),
                    $std->enum($context['user_roles'])
                ),
            );

            $passwordField = array(
                'password' => array(
                    $std->toString(),
                    $std->required(),
                    $std->lengthMin(8),
                    $std->lengthMax(72),
                    $std->passwordHash()
                ),
            );
        }

        if ($action === 'insert') {
            $s += $accountFields + $passwordField;

            $s['username'][] = $db->unique($this->table, 'username');
            $s['email'][] = $db->unique($this->table, 'email');
        }

        if ($action === 'update') {
            if (isset($context['update_account'])) {
                $s += $accountFields;
                $old = $context['user_old'];
                $s['username'][] = $db->unique($this->table, 'username', $old['username']);
                $s['email'][] = $db->unique($this->table, 'email', $old['email']);
            }

            if (isset($context['update_password'])) {
                $s += $passwordField;
            }
        }

        return $s;
    }

    function passwordVerify($input, $current, $message, $meta) {
        if (!password_verify($input, $current)) {
            return array(
                'data' => array(
                    'content' => $message
                ) + $meta
            );
        }
    }
}
?>
