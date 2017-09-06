<?php

namespace Drupal\yoast_seo_preview\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'yoast_seo' field type.
 *
 * @FieldType(
 *   id = "yoast_seo_preview",
 *   label = @Translation("SEO keyword"),
 *   module = "yoast_seo_preview",
 *   description = @Translation("Provides seo widget and keyword storage."),
 *   default_widget = "yoast_seo_preview_widget",
 *   default_formatter = "yoast_seo_preview_empty_formatter",
 * )
 */
class YoastSeoPreviewItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => 256,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Keyword'))
      ->setRequired(TRUE);

    return $properties;
  }

}
