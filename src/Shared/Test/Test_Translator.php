<?php

class Test_Translator {
    var $app, $assert, $translator;

    function args($args) {
        list(
            $this->app,
            $this->assert,
            $this->translator,
        ) = $args;
    }

    function call() {
        $translator = $this->translator;
        $translator->set('key', array(
            // Basic string
            'hello' => 'Hello World',

            // Plural forms
            'apple' => array(
                'There is no apple',
                'There is :count apple',
                'There are :count apples'
            ),

            // With placeholders
            'welcome' => 'Welcome, :name!',
            'gift' => array(
                ':name has no gift', // :name custom placeholder
                ':name has :count gift', // :count use internally
                ':name has :count gifts'
            )
        ));

        $t = $translator->get('key');

        $this->assert->isEquals('Hello World', $t->t('hello'), 'Basic translation');
        $this->assert->isEquals('There is no apple', $t->nt('apple', 0, array(':count' => 0)), 'Plural (none)');
        $this->assert->isEquals('There is 1 apple', $t->nt('apple', 1, array(':count' => 1)), 'Plural (singular)');
        $this->assert->isEquals('There are 5 apples', $t->nt('apple', 5, array(':count' => 5)), 'Plural (plural)');
        $this->assert->isEquals('Welcome, Alice!', $t->t('welcome', array(':name' => 'Alice')), 'Placeholder replacement');
        $this->assert->isEquals('Bob has no gift', $t->nt('gift', 0, array(':count' => 0, ':name' => 'Bob')), 'Plural + placeholder (none)');
        $this->assert->isEquals('Bob has 1 gift', $t->nt('gift', 1, array(':count' => 1, ':name' => 'Bob')), 'Plural + placeholder (singular)');
        $this->assert->isEquals('Bob has 3 gifts', $t->nt('gift', 3, array(':count' => 3, ':name' => 'Bob')), 'Plural + placeholder (plural)');
        $this->assert->isEquals('not_exist', $t->t('not_exist'), 'Missing key fallback');        
    }
}