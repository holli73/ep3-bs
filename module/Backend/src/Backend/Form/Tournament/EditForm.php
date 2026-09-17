<?php

namespace Backend\Form\Tournament;

use Tournament\Entity\Tournament;
use Zend\Form\Form;
use Zend\InputFilter\Factory;

class EditForm extends Form
{

    public function init()
    {
        $this->setName('tf');

        $this->add(array(
            'name' => 'tf-name',
            'type' => 'Text',
            'attributes' => array(
                'id' => 'tf-name',
                'style' => 'width: 320px',
            ),
            'options' => array(
                'label' => 'Name',
            ),
        ));

        $this->add(array(
            'name' => 'tf-description',
            'type' => 'Textarea',
            'attributes' => array(
                'id' => 'tf-description',
                'style' => 'width: 320px; height: 100px;',
            ),
            'options' => array(
                'label' => 'Description',
            ),
        ));

        $this->add(array(
            'name' => 'tf-date-start',
            'type' => 'Text',
            'attributes' => array(
                'id' => 'tf-date-start',
                'class' => 'datepicker',
                'style' => 'width: 110px;',
            ),
            'options' => array(
                'label' => 'Date (Start)',
            ),
        ));

        $this->add(array(
            'name' => 'tf-date-end',
            'type' => 'Text',
            'attributes' => array(
                'id' => 'tf-date-end',
                'class' => 'datepicker',
                'style' => 'width: 110px;',
            ),
            'options' => array(
                'label' => 'Date (End)',
            ),
        ));

        $this->add(array(
            'name' => 'tf-registration-deadline',
            'type' => 'Text',
            'attributes' => array(
                'id' => 'tf-registration-deadline',
                'class' => 'datepicker',
                'style' => 'width: 110px;',
            ),
            'options' => array(
                'label' => 'Registration deadline',
                'notes' => 'Optional. Leave empty for no deadline.',
            ),
        ));

        $this->add(array(
            'name' => 'tf-status',
            'type' => 'Select',
            'attributes' => array(
                'id' => 'tf-status',
                'style' => 'width: 200px',
            ),
            'options' => array(
                'label' => 'Status',
                'value_options' => Tournament::$statusOptions,
            ),
        ));

        $this->add(array(
            'name' => 'tf-male-group-size',
            'type' => 'Text',
            'attributes' => array(
                'id' => 'tf-male-group-size',
                'style' => 'width: 60px;',
            ),
            'options' => array(
                'label' => 'Group size (male)',
                'notes' => 'Target number of players per group',
            ),
        ));

        $this->add(array(
            'name' => 'tf-female-group-size',
            'type' => 'Text',
            'attributes' => array(
                'id' => 'tf-female-group-size',
                'style' => 'width: 60px;',
            ),
            'options' => array(
                'label' => 'Group size (female)',
                'notes' => 'Target number of players per group',
            ),
        ));

        $this->add(array(
            'name' => 'tf-submit',
            'type' => 'Submit',
            'attributes' => array(
                'value' => 'Save',
                'id' => 'tf-submit',
                'class' => 'default-button',
                'style' => 'width: 200px;',
            ),
        ));

        /* Input filters */

        $factory = new Factory();

        $dateValidators = array(
            array(
                'name' => 'Callback',
                'options' => array(
                    'callback' => function ($value) {
                        try {
                            new \DateTime($value);

                            return true;
                        } catch (\Exception $e) {
                            return false;
                        }
                    },
                    'message' => 'Invalid date',
                ),
            ),
        );

        $this->setInputFilter($factory->createInputFilter(array(
            'tf-name' => array(
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
            'tf-description' => array(
                'required' => false,
                'filters' => array(
                    array('name' => 'StringTrim'),
                ),
            ),
            'tf-date-start' => array(
                'filters' => array(
                    array('name' => 'StringTrim'),
                ),
                'validators' => array_merge(array(
                    array(
                        'name' => 'NotEmpty',
                        'options' => array(
                            'message' => 'Please type something here',
                        ),
                        'break_chain_on_failure' => true,
                    ),
                ), $dateValidators),
            ),
            'tf-date-end' => array(
                'filters' => array(
                    array('name' => 'StringTrim'),
                ),
                'validators' => array_merge(array(
                    array(
                        'name' => 'NotEmpty',
                        'options' => array(
                            'message' => 'Please type something here',
                        ),
                        'break_chain_on_failure' => true,
                    ),
                ), $dateValidators),
            ),
            'tf-registration-deadline' => array(
                'required' => false,
                'filters' => array(
                    array('name' => 'StringTrim'),
                ),
                'validators' => $dateValidators,
            ),
            'tf-male-group-size' => array(
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
                    array(
                        'name' => 'Digits',
                        'options' => array(
                            'message' => 'Please type a number here',
                        ),
                    ),
                ),
            ),
            'tf-female-group-size' => array(
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
                    array(
                        'name' => 'Digits',
                        'options' => array(
                            'message' => 'Please type a number here',
                        ),
                    ),
                ),
            ),
        )));
    }

}
