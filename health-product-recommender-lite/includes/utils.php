<?php
if (!defined('ABSPATH')) exit;

function hprl_cartesian_product($arrays) {
    $result = array(array());
    foreach ($arrays as $property => $values) {
        $tmp = array();
        foreach ($result as $result_item) {
            foreach ($values as $value) {
                $tmp[] = array_merge($result_item, array($value));
            }
        }
        $result = $tmp;
    }
    return $result;
}

function hprl_generate_all_combos($questions) {
    $choices = array();
    foreach ($questions as $q) {
        if (!empty($q['answers'])) {
            $choices[] = array_keys($q['answers']);
        } else {
            $choices[] = array('');
        }
    }
    $perms = hprl_cartesian_product($choices);
    $combos = array();
    foreach ($perms as $p) {
        $answers = array();
        foreach ($p as $idx) {
            $answers[] = array($idx);
        }
        $combos[] = array('answers' => $answers, 'cheap' => '', 'premium' => '', 'note' => '');
    }
    return $combos;
}
