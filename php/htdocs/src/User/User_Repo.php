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

        parent::setTable('user');
        parent::setDb(
            $this->database,
            $this->database->connect(array(
                'host' => $this->app->getEnv('DB_HOST'),
                'port' => $this->app->getEnv('DB_PORT'),
                'name' => $this->app->getEnv('DB_NAME'),
                'user' => $this->app->getEnv('DB_USER'),
                'pass' => $this->app->getEnv('DB_PASS'),
                'time' => $this->app->getEnv('DB_TIME', '+00:00'),
            ))
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

        $accountFields = array(
            'username' => array(
                $std->toString(),
                $std->required(),
                $std->lengthMax(50)
            ),
            'email' => array(
                $std->toString(),
                $std->lengthMax(100)
            ),
        );

        $profileFields = array(
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

        if ($action === 'insert') {
            $s += $accountFields + $profileFields + $passwordField;

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

            if (isset($context['update_profile'])) {
                $s += $profileFields;
            }

            if (isset($context['update_password'])) {
                $s += $passwordField;
            }
        }

        return $s;
    }
}
?>
