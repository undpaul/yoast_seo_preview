<?php

namespace Drupal\yoast_seo_preview\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'metatag_empty_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "yoast_seo_preview_empty_formatter",
 *   module = "yoast_seo_preview",
 *   label = @Translation("Empty formatter"),
 *   field_types = {
 *     "yoast_seo_preview"
 *   }
 * )
 */
class YoastSeoPreviewEmptyFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Does not actually output anything.
    return [];
  }

}
