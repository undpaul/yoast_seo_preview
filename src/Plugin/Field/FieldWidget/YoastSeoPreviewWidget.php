<?php

namespace Drupal\yoast_seo_preview\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\yoast_seo_preview\Form\YoastSeoPreviewFormHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Advanced widget for yoast_seo_preview field.
 *
 * @FieldWidget(
 *   id = "yoast_seo_preview_widget",
 *   label = @Translation("Yoast SEO keyword form."),
 *   field_types = {
 *     "yoast_seo_preview"
 *   }
 * )
 */
class YoastSeoPreviewWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // @phpstan-ignore-next-line
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $form['#yoast_settings'] = $this->getSettings();

    // Attach libraries and default settings.
    // @todo Add values to settings.
    $keyword = $element + [
      '#type' => 'textfield',
      // @phpstan-ignore-next-line
      '#default_value' => $items[$delta]->value,
    ];

    $element = [
      '#type' => 'container',
      '#attached' => [
        'library' => [
          'yoast_seo_preview/seo_preview',
        ],
        'drupalSettings' => [
          'yoast_seo_preview' => [
            'body' => '',
            'pageTitle' => '',
          ],
        ],
      ],
    ];

    // Keyword field used by yoast_seo_preview.
    $element['keyword'] = $keyword;

    // Add preview button.
    $target_type = $this->fieldDefinition->getTargetEntityTypeId();
    if ($this->entityTypeManager->hasHandler($target_type, 'yoast_seo_preview_form')) {
      $form_handler = $this->entityTypeManager->getHandler($target_type, 'yoast_seo_preview_form');

      if ($form_handler instanceof YoastSeoPreviewFormHandler) {
        $form_handler->addPreviewSubmit($element, $form_state);
      }
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$value) {
      $value = $value['keyword'];
    }
    return $values;
  }

}
