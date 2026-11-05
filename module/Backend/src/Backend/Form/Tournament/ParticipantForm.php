<?php

namespace Backend\Form\Tournament;

use Zend\Form\Form;
use Zend\InputFilter\Factory;

class ParticipantForm extends Form
{

    public function init()
    {
        $this->setName('puf');

        $this->add(array(
            'name' => 'puf-user',
            'type' => 'Text',
            'attributes' => array(
                'id' => 'puf-user',
                'style' => 'width: 250px',
            ),
            'options' => array(
                'label' => 'User',
                'notes' => 'Start typing a name to search, then pick from the list',
            ),
        ));

        $this->add(array(
            'name' => 'puf-submit',
            'type' => 'Submit',
            'attributes' => array(
                'value' => 'Add participant',
                'id' => 'puf-submit',
                'class' => 'default-button',
            ),
        ));

        /* Input filters */

        $factory = new Factory();

        $this->setInputFilter($factory->createInputFilter(array(
            'puf-user' => array(
                'filters' => array(
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'NotEmpty',
                        'options' => array(
                            'message' => 'Please type something here',
                        ),
                        'break_chain_on_failure' => true,
                    ),
                ),
            ),
        )));
    }

}
