<?php

namespace Drupal\search_api\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\EventSubscriber\AjaxResponseSubscriber;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\SearchApiException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides route responses for search indexes.
 */
class IndexController extends ControllerBase {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack|null
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var static $controller */
    $controller = parent::create($container);

    $controller->setRequestStack($container->get('request_stack'));

    return $controller;
  }

  /**
   * Retrieves the request stack.
   *
   * @return \Symfony\Component\HttpFoundation\RequestStack
   *   The request stack.
   */
  public function getRequestStack() {
    return $this->requestStack ?: \Drupal::service('request_stack');
  }

  /**
   * Retrieves the current request.
   *
   * @return \Symfony\Component\HttpFoundation\Request|null
   */
  public function getRequest() {
    return $this->getRequestStack()->getCurrentRequest();
  }

  /**
   * Sets the request stack.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The new request stack.
   *
   * @return $this
   */
  public function setRequestStack(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
    return $this;
  }

  /**
   * Displays information about a search index.
   *
   * @param \Drupal\search_api\IndexInterface $search_api_index
   *   The index to display.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function page(IndexInterface $search_api_index) {
    // Build the search index information.
    $render = array(
      'view' => array(
        '#theme' => 'search_api_index',
        '#index' => $search_api_index,
      ),
    );
    // Check if the index is enabled and can be written to.
    if ($search_api_index->status() && !$search_api_index->isReadOnly()) {
      // Attach the index status form.
      $render['form'] = $this->formBuilder()->getForm('Drupal\search_api\Form\IndexStatusForm', $search_api_index);
    }
    return $render;
  }

  /**
   * Returns the page title for an index's "View" tab.
   *
   * @param \Drupal\search_api\IndexInterface $search_api_index
   *   The index that is displayed.
   *
   * @return string
   *   The page title.
   */
  public function pageTitle(IndexInterface $search_api_index) {
    return new FormattableMarkup('@title', array('@title' => $search_api_index->label()));
  }

  /**
   * Enables a search index without a confirmation form.
   *
   * @param \Drupal\search_api\IndexInterface $search_api_index
   *   The index to be enabled.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response to send to the browser.
   */
  public function indexBypassEnable(IndexInterface $search_api_index) {
    // Enable the index.
    $search_api_index->setStatus(TRUE)->save();

    // \Drupal\search_api\Entity\Index::preSave() doesn't allow an index to be
    // enabled if its server is not set or disabled.
    if ($search_api_index->status()) {
      // Notify the user about the status change.
      drupal_set_message($this->t('The search index %name has been enabled.', array('%name' => $search_api_index->label())));
    }
    else {
      // Notify the user that the status change did not succeed.
      drupal_set_message($this->t('The search index %name could not be enabled. Check if its server is set and enabled.', array('%name' => $search_api_index->label())));
    }

    // Redirect to the index's "View" page.
    $url = $search_api_index->toUrl('canonical');
    return $this->redirect($url->getRouteName(), $url->getRouteParameters());
  }

  /**
   * Removes a field from a search index.
   *
   * @param \Drupal\search_api\IndexInterface $search_api_index
   *   The search index.
   * @param string $field_id
   *   The ID of the field to remove.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response to send to the browser.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown when the field was not found.
   */
  public function removeField(IndexInterface $search_api_index, $field_id) {
    $fields = $search_api_index->getFields();
    $success = FALSE;
    if (isset($fields[$field_id])) {
      try {
        $search_api_index->removeField($field_id);
        $search_api_index->save();
        $success = TRUE;
      }
      catch (SearchApiException $e) {
        $args['%field'] = $fields[$field_id]->getLabel();
        drupal_set_message($this->t('The field %field is locked and cannot be removed.', $args), 'error');
      }
    }
    else {
      throw new NotFoundHttpException();
    }

    // If this is an AJAX request, just remove the row in question.
    if ($success && $this->getRequest()->request->get(AjaxResponseSubscriber::AJAX_REQUEST_PARAMETER)) {
      $response = new AjaxResponse();
      $response->addCommand(new RemoveCommand("tr[data-field-row-id='$field_id']"));
      return $response;
    }
    // Redirect to the index's "Fields" page.
    $url = $search_api_index->toUrl('fields');
    return $this->redirect($url->getRouteName(), $url->getRouteParameters());
  }

}
