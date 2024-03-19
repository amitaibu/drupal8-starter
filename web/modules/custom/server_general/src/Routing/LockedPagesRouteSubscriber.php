<?php

namespace Drupal\server_general\Routing;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\server_general\LockedPages;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
final class LockedPagesRouteSubscriber extends RouteSubscriberBase {

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The locked pages service.
   *
   * @var \Drupal\server_general\LockedPages
   */
  protected $lockedPagesService;

  /**
   * Constructs a new LockedPagesRouteSubscriber object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type Manager service.
   * @param \Drupal\server_general\LockedPages $locked_pages_service
   *   The locked pages service.
   */
  public function __construct(RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager, LockedPages $locked_pages_service) {
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->lockedPagesService = $locked_pages_service;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Check if the route exists and is for deleting a node.
    if ($route = $collection->get('entity.node.delete_form')) {
      $route->setRequirement('_custom_access', 'server_general.route_subscriber::access');
    }
  }

  /**
   * Checks if current node can be accessed.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    $node = $this->routeMatch->getParameter('node');
    // Get the list of bundles that can be restricted.
    $bundles = $this->lockedPagesService->getReferencedBundles();
    // Return in_array($node->id(), $restricted_entities);.
    if ($node instanceof NodeInterface && in_array($node->getType(), $bundles)) {
      $main_settings = $this->lockedPagesService->getMainSettings();
      $cache_tags = $main_settings->getCacheTags();
      if ($this->lockedPagesService->isNodeLocked($node)) {
        return AccessResult::forbidden()->addCacheableDependency($node)->addCacheTags($cache_tags);
      }
    }
    // Allow deletion if user has permission to delete any page.
    if ($account->hasPermission('delete any {$node->getType()} content')) {
      return AccessResult::allowed();
    }

    // If user is not anonymous, and has permission to delete own
    // content, allow it.
    if ($account->id() !== 0 && $node->getOwnerId() === $account->id() && $account->hasPermission('delete own {$node->getType()} content')) {
      return AccessResult::allowed();
    }

    return AccessResult::neutral();
  }

}
