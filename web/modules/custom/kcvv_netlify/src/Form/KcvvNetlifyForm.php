<?php

namespace Drupal\kcvv_netlify\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class KcvvNetlifyForm.
 */
class KcvvNetlifyForm extends ConfigFormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new KcvvNetlifyForm object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'kcvv_netlify_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('kcvv_netlify.kcvvnetlify');
    $form['kcvv_frontend_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('KCVV Elewijt front-end URL'),
      '#description' => $this->t('The URL the actual website is hosted on.'),
      '#maxlength' => 255,
      '#size' => 255,
      '#default_value' => $config->get('kcvv_frontend_url'),
    ];

    $form['kcvv_netlify_build_hook_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Netlify Build Hook URL'),
      '#description' => $this->t('The build hook URL which triggers a new deploy in Netlify.'),
      '#maxlength' => 255,
      '#size' => 255,
      '#default_value' => $config->get('kcvv_netlify_build_hook_url'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('kcvv_netlify.kcvvnetlify')
      ->set('kcvv_netlify_build_hook_url', $form_state->getValue('kcvv_netlify_build_hook_url'))
      ->save();

    $this->config('kcvv_netlify.kcvvnetlify')
      ->set('kcvv_frontend_url', $form_state->getValue('kcvv_frontend_url'))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'kcvv_netlify.kcvvnetlify',
    ];
  }

}
