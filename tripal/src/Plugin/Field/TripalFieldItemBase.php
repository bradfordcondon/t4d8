<?php

namespace Drupal\tripal\Plugin\Field;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\Messenger\MessengerTrait;


/**
 * A Tripal-based entity field item.
 *
 * Entity field items making use of this base class have to implement
 * the static method propertyDefinitions().
 *
 */

abstract class TripalFieldItemBase extends FieldItemBase {
  use MessengerTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    $settings = [
      // -- Define the Vocabulary.
      // The short name for the vocabulary (e.g. shcema, SO, GO, PATO, etc.).
      'term_vocabulary' => 'schema',
      // The full name of the vocabulary.
      'vocab_name' => 'schema',
      // The description of the vocabulary.
      'vocab_description' => 'A set of types, each associated with a set of properties. The types are arranged in a hierarchy.',

      // -- Define the Vocabulary Term.
      // The name of the term.
      'term_name' => 'Thing',
      // The unique ID (i.e. accession) of the term.
      'term_accession' => 'Thing',
      // The definition of the term.
      'term_definition' => 'The most generic type of item.',

      // -- Additional Settings.
      // Set to TRUE if the site admin is not allowed to change the term
      // type, otherwise the admin can change the term mapped to a field.
      'term_fixed' => FALSE,
      // Set to TRUE if the field should be automatically attached to an entity
      // when it is loaded. Otherwise, the callee must attach the field
      // manually.  This is useful to prevent really large fields from slowing
      // down page loads.  However, if the content type display is set to
      // "Hide empty fields" then this has no effect as all fields must be
      // attached to determine which are empty.  It should always work with
      // web services.
      'auto_attach' => TRUE,
    ];
    return $settings;
  }

  /**
   * Save the default term associated with this field.
   */
  public function saveDefaultTerm() {
    $settings = $this->getSettings();

    $term = tripal_get_term_details($settings['term_vocabulary'], $settings['term_accession']);
    if (is_array($term) AND isset($term['TripalTerm'])) {
      return $term['TripalTerm'];
    }
    else {
      $vocab = \Drupal\tripal\Entity\TripalVocab::create();
      $vocab->setLabel($settings['term_vocabulary']);
      $vocab->setName($settings['vocab_name']);
      $vocab->setDescription($settings['vocab_description']);
      $vocab->save();
      $vocab_id = $vocab->id();
      if (!$vocab_id) {
        return FALSE;
      }

      $term = \Drupal\tripal\Entity\TripalTerm::create();
      $term->setVocabID($vocab->id());
      $term->setAccession($settings['term_accession']);
      $term->setName($settings['term_name']);
      $term->setDefinition($settings['term_definition']);
      $term->save();
      return $term;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $settings = $this->getSettings();
    $term_fixed = $settings['term_fixed'];

    // Get the term for this instance.
    $term = $this->saveDefaultTerm();
    $vocab = $term->getVocab();
    if (!is_object($term) OR !is_object($vocab)) {
      // TODO: Don't do the rest of the form!!!
    }

    // Construct a table for the vocabulary information.
    $headers = [];
    $rows = [];
    $rows[] = [
      [
        'data' => 'Vocabulary',
        'header' => TRUE,
        'width' => '20%',
      ],
      $vocab->getName() . ' (' . $vocab->getLabel() . ') ' . $vocab->getDescription(),
    ];
    $rows[] = [
      [
        'data' => 'Term',
        'header' => TRUE,
        'width' => '20%',
      ],
      $vocab->getLabel() . ':' . $term->getAccession(),
    ];
    $rows[] = [
      [
        'data' => 'Name',
        'header' => TRUE,
        'width' => '20%',
      ],
      $term->getName(),
    ];
    $rows[] = [
      [
        'data' => 'Definition',
        'header' => TRUE,
        'width' => '20%',
      ],
      $term->getDefinition(),
    ];

    $element['term_vocabulary'] = [
      '#type' => 'value',
      '#value' => $vocab->getLabel(),
    ];
    $element['term_name'] = [
      '#type' => 'value',
      '#value' => $term->getName(),
    ];
    $element['term_accession'] = [
      '#type' => 'value',
      '#value' => $term->getAccession(),
    ];
    $description = t('All fields attached to a Tripal-based content type must ' .
        'be associated with a controlled vocabulary term.  Please use caution ' .
        'when changing the term for this field as other sites may expect this term ' .
        'when querying web services.');
    if ($term_fixed) {
      $description = t('All fields attached to a Tripal-based content type must ' .
          'be associated with a controlled vocabulary term. This field mapping is ' .
          'required and cannot be changed');
    }
    $element['field_term'] = [
      '#type' => 'fieldset',
      '#title' => 'Controlled Vocabulary Term',
      '#description' => $description,
      '#description_display' => 'before',
      '#prefix' => '<div id = "tripal-field-term-fieldset">',
      '#suffix' => '</div>',
      '#weight' => 1000,
    ];
    $element['field_term']['details'] = [
      '#type' => 'table',
      '#title' => 'Current Term',
      '#header' => $headers,
      '#rows' => $rows
    ];

    // If this field mapping is fixed then don't let the user change it.
    if ($term_fixed != TRUE) {
      $element['field_term']['new_name'] = [
        '#type' => 'textfield',
        '#title' => 'Change the term',
        // TODO: This autocomplete path should not use Chado.
        //'#autocomplete_path' => "admin/tripal/storage/chado/auto_name/cvterm/",
      ];
      $element['field_term']['select_button'] = [
        '#type' => 'button',
        '#value' => t('Lookup Term'),
        '#name' => 'select_cvterm',
        '#ajax' => [
          'callback' => "tripal_fields_select_term_form_ajax_callback",
          'wrapper' => "tripal-field-term-fieldset",
          'effect' => 'fade',
          'method' => 'replace',
        ],
      ];
    }
    // @TODO: We don't yet have the term lookup working.
/*
    // If a new term name has been specified by the user then give some extra
    // fields to clarify the term.
    $term_name = '';
    if (array_key_exists('values', $form_state) and array_key_exists('new_name', $form_state['values'])) {
      $term_name = array_key_exists('values', $form_state) ? $form_state['values']['new_name'] : '';
    }
    if (array_key_exists('input', $form_state) and array_key_exists('new_name', $form_state['input'])) {
      $term_name = array_key_exists('input', $form_state) ? $form_state['input']['new_name'] : '';
    }
    if ($term_name) {
      $element['field_term']['instructions'] = [
        '#type' => 'item',
        '#title' => 'Matching terms',
        '#markup' => t('Please select the term the best matches the ' .
          'content type you want to associate with this field. If the same term exists in ' .
          'multiple vocabularies you will see more than one option below.'),
      ];
      $match = [
        'name' => $term_name,
      ];
      $terms = chado_generate_var('cvterm', $match, ['return_array' => TRUE]);
      $terms = chado_expand_var($terms, 'field', 'cvterm.definition');
      $num_terms = 0;
      foreach ($terms as $term) {
        // Save the user a click, by setting the default value as 1 if there's
        // only one matching term.
        $default = FALSE;
        $attrs = [];
        if ($num_terms == 0 and count($terms) == 1) {
          $default = TRUE;
          $attrs = ['checked' => 'checked'];
        }
        $element['field_term']['term-' . $term->cvterm_id] = [
          '#type' => 'checkbox',
          '#title' => $term->name,
          '#default_value' => $default,
          '#attributes' => $attrs,
          '#description' => '<b>Vocabulary:</b> ' . $term->cv_id->name . ' (' . $term->dbxref_id->db_id->name . ') ' . $term->cv_id->definition .
          '<br><b>Term: </b> ' . $term->dbxref_id->db_id->name . ':' . $term->dbxref_id->accession . '.  ' .
          '<br><b>Definition:</b>  ' . $term->definition,
        ];
        $num_terms++;
      }
      if ($num_terms == 0) {
        $element['field_term']['none'] = [
          '#type' => 'item',
          '#markup' => '<i>' . t('There is no term that matches the entered text.') . '</i>',
        ];
      }
    }
    */
    $element['#element_validate'][] = 'tripal_field_instance_settings_form_alter_validate';
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'max_length' => 255,
      'tripalstorage' => 'drupalonly',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = [];

    $element['max_length'] = [
      '#type' => 'number',
      '#title' => t('Maximum length'),
      '#default_value' => $this->getSetting('max_length'),
      '#required' => TRUE,
      '#description' => t('The maximum length of the field in characters.'),
      '#min' => 1,
      '#disabled' => $has_data,
    ];

    $type = \Drupal::service('plugin.manager.tripalstorage');
    $plugin_definitions = $type->getDefinitions();
    $tripalstorage_options = [];
    foreach ($plugin_definitions as $id => $defn) {
      $tripalstorage_options[ $id ] = $defn['label']->get();
    }
    $element['tripalstorage'] = [
      '#type' => 'select',
      '#title' => 'Tripal Storage',
      '#description' => 'Choose where data will be stored. It is not recommended to change this setting unless specifically told to.',
      '#options' => $tripalstorage_options,
      '#default_value' => $this->getSetting('tripalstorage'),
      '#disabled' => $has_data,
    ];

    $element += parent::storageSettingsForm($form, $form_state, $has_data);
    return $element;
  }
}
