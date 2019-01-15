<?php

namespace Drupal\migration_glue\Form;

use Drupal\migration_glue\MigrationGlueManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AddMigrationForm.
 */
class AddMigrationForm extends FormBase {

  const REQUIRED_MIGRATION_KEYS = [
    'id',
    'source',
    'process',
    'destination',
  ];

  /**
   * Migration glue manager.
   *
   * @var \Drupal\migration_glue\MigrationGlueManager
   */
  protected $migrationGlueManager;

  /**
   * AddMigrationForm constructor.
   *
   * @param \Drupal\migration_glue\MigrationGlueManager $glue_manager
   *   Migration glue manager.
   */
  public function __construct(MigrationGlueManager $glue_manager) {
    $this->migrationGlueManager = $glue_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('migration_glue.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_migration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['migration'] = [
      '#title' => $this->t('Paste your migration config YML here.'),
      '#type' => 'textarea',
      '#rows' => 24,
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create'),
    ];

    $form['back_link'] = [
      '#title' => $this->t('Go Back'),
      '#type' => 'link',
      '#url' => Url::fromRoute('migration_glue.list_migration', ['migration_group' => 'default']),
      '#attributes' => [
        'class' => ['button']
      ]
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $value = $form_state->getValue('migration');
    $yml_data = Yaml::decode($value);

    if (!is_array($yml_data)) {
      $form_state->setErrorByName('migration', $this->t('Invalid migration YAML.'));
    }

    if ($yml_data) {
      $contains_all_required_keys = !array_diff(self::REQUIRED_MIGRATION_KEYS, array_keys($yml_data));
      // If Config not contains required keys.
      if (!$contains_all_required_keys) {
        $form_state->setErrorByName('migration', $this->t('Yml config not contains all the required keys: <strong>@keys</strong>.', [
          '@keys' => implode(', ', self::REQUIRED_MIGRATION_KEYS),
        ]));
      }
    }

    // Get existing migrations.
    $existing_migrations = $this->migrationGlueManager->getMigrationList();
    // If migration with same id already exists.
    if (array_key_exists($yml_data['id'], $existing_migrations)) {
      $form_state->setErrorByName('migration', $this->t('Migration with the id: @id already exists. Please change the id.', [
        '@id' => $yml_data['id'],
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $yml_data = Yaml::decode($form_state->getValue('migration'));
    $this->migrationGlueManager->registerMigration($yml_data);
    $this->messenger()->addMessage($this->t('Migration is created successfully.'));
  }

}
