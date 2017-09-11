<?php

namespace Drupal\yoast_seo_preview\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for yoast_seo_preview form handlers.
 */
class YoastSeoPreviewFormHandler implements EntityHandlerInterface {

  use DependencySerializationTrait;

  /**
   * The type of the entity for whose form the yoast_seo_preview form is used.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * Information about the entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * SeoPreviewFormHandler constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer) {
    $this->entityTypeId = $entity_type->id();
    $this->entityType = $entity_type;
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * Renders a node preview.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node_preview
   *   The node preview.
   * @param string $view_mode_id
   *   (optional) The view mode that should be used to display the entity.
   *   Defaults to 'full'.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function preview(EntityInterface $node_preview, $view_mode_id = 'full') {
    $node_preview->preview_view_mode = $view_mode_id;

    // Building preview, see NodePreviewController.
    $build = $this->entityTypeManager
      ->getViewBuilder($node_preview->getEntityTypeId())
      ->view($node_preview, $view_mode_id);

    $build['#entity_type'] = $node_preview->getEntityTypeId();
    $build['#' . $build['#entity_type']] = $node_preview;
    $build['#attached']['library'][] = 'node/drupal.node.preview';

    // Don't render cache previews.
    unset($build['#cache']);

    return $this->renderer->render($build);
  }

  /**
   * Renders the title of a node in preview.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node_preview
   *   The current node.
   *
   * @return string
   *   The  title.
   */
  public function previewTitle(EntityInterface $node_preview) {
    return $node_preview->label();
  }

  /**
   * Ajax Callback for returning node preview to seo library.
   *
   * @param array $form
   *   The complete form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public function previewSubmitAjax(array &$form, FormStateInterface $form_state) {
    $node_preview = $form_state->getFormObject()->getEntity();
    $node_preview->in_preview = TRUE;

    $parents = $form_state->getTriggeringElement()['#parents'];
    array_splice($parents, -1, 1, 'keyword');
    $keyword = NestedArray::getValue($form_state->getUserInput(), $parents);

    $settings['yoast_seo_preview'] = [
      'baseURL' => rtrim(\Drupal::request()->getSchemeAndHttpHost() . base_path(), '/'),
      'urlPath' => \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $node_preview->id()),
      'title' => $this->previewTitle($node_preview),
      'text' => $this->preview($node_preview, 'full'),
      'keyword' => $keyword,
    ];

    // Markup for YoastSeo.js library output.
    // @todo: Add template.
    $markup = '<div id="yoast-seo-preview-snippet"></div><div id="yoast-seo-preview-output"></div>';

    $response = new AjaxResponse();
    $response->addCommand(new SettingsCommand($settings, TRUE));
    $response->addCommand(new OpenModalDialogCommand('Seo Preview', $markup, ['dialogClass' => 'yoast-seo-preview-dialog', 'minWidth' => 700]));
    $response->addCommand(new InvokeCommand('body', 'trigger', ['seoPreviewOpen', $settings['yoast_seo_preview']]));
    return $response;
  }

  /**
   * Adds yoast_seo_preview submit.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function addPreviewSubmit(array &$element, FormStateInterface $form_state) {
    $element['yoast_seo_preview_button'] = [
      '#type' => 'button',
      '#value' => t('Seo preview'),
      '#ajax' => [
        'callback' => [$this, 'previewSubmitAjax'],
      ],
    ];
  }

}
