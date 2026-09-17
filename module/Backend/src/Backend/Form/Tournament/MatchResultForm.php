<?php

namespace Backend\Form\Tournament;

use Zend\Form\Form;
use Zend\InputFilter\Factory;

class MatchResultForm extends Form
{

    public function init()
    {
        $this->setName('mrf');

        for ($i = 1; $i <= 3; $i++) {
            $this->add(array(
                'name' => "mrf-set{$i}-a",
                'type' => 'Text',
                'attributes' => array(
                    'id' => "mrf-set{$i}-a",
                    'style' => 'width: 40px;',
                ),
                'options' => array(
                    'label' => sprintf('Set %d', $i),
                ),
            ));

            $this->add(array(
                'name' => "mrf-set{$i}-b",
                'type' => 'Text',
                'attributes' => array(
                    'id' => "mrf-set{$i}-b",
                    'style' => 'width: 40px;',
                ),
            ));
        }

        $this->add(array(
            'name' => 'mrf-set3-tiebreak',
            'type' => 'Checkbox',
            'attributes' => array(
                'id' => 'mrf-set3-tiebreak',
            ),
            'options' => array(
                'label' => 'Third set is a match tiebreak',
                'notes' => 'e.g. entered as 10-8',
            ),
        ));

        $this->add(array(
            'name' => 'mrf-submit',
            'type' => 'Submit',
            'attributes' => array(
                'value' => 'Save result',
                'id' => 'mrf-submit',
                'class' => 'default-button',
            ),
        ));

        /* Input filters */

        $factory = new Factory();

        $digitsValidator = array(
            array(
                'name' => 'Digits',
                'options' => array(
                    'message' => 'Please type a number here',
                ),
            ),
        );

        $this->setInputFilter($factory->createInputFilter(array(
            'mrf-set1-a' => array(
                'filters' => array(array('name' => 'StringTrim')),
                'validators' => array_merge(array(
                    array(
                        'name' => 'NotEmpty',
                        'options' => array('message' => 'Please type something here'),
                        'break_chain_on_failure' => true,
                    ),
                ), $digitsValidator),
            ),
            'mrf-set1-b' => array(
                'filters' => array(array('name' => 'StringTrim')),
                'validators' => array_merge(array(
                    array(
                        'name' => 'NotEmpty',
                        'options' => array('message' => 'Please type something here'),
                        'break_chain_on_failure' => true,
                    ),
                ), $digitsValidator),
            ),
            'mrf-set2-a' => array(
                'required' => false,
                'filters' => array(array('name' => 'StringTrim')),
                'validators' => $digitsValidator,
            ),
            'mrf-set2-b' => array(
                'required' => false,
                'filters' => array(array('name' => 'StringTrim')),
                'validators' => $digitsValidator,
            ),
            'mrf-set3-a' => array(
                'required' => false,
                'filters' => array(array('name' => 'StringTrim')),
                'validators' => $digitsValidator,
            ),
            'mrf-set3-b' => array(
                'required' => false,
                'filters' => array(array('name' => 'StringTrim')),
                'validators' => $digitsValidator,
            ),
            'mrf-set3-tiebreak' => array(
                'required' => false,
            ),
        )));
    }

}
