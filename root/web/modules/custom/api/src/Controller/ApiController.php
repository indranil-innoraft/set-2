<?php

namespace Drupal\api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * This is the controller used to fetch data.
 */
final class ApiController extends ControllerBase {

  /**
   * This is store the data of all nodes of the product type.
   *
   * @var array
   */
  protected array $productNodes;

/**
   * This is store the file entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $file;

  /**
   * Constructs the CustomAPI object with the required depenency.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   This is the EntityTypeManagerInterface.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->productNodes = $entity_manager->getStorage('node')->loadByProperties([
      'type' => 'product',
    ]);
    $this->file = $entity_manager->getStorage('file');
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * This is to build the response for the api call.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A Json response contains all the products nodes details.
   */
  public function fetch() {
    foreach($this->productNodes as $product) {
      foreach ($product->get('field_category')->referencedEntities() as $tag) {
        $tags[] = $tag->label();
      }
      foreach ($product->field_images as $image) {
        $images[] = [
          'target_id' => $image->target_id,
          'alt' => $image->alt,
          'title' => $image->title,
          'width' => $image->width,
          'height' => $image->height,
          'url' => $this->file->load($image->target_id)->createFileUrl(),
        ];
      }
      $build[] = [
        'title' => $product->get('title')->value,
        'catagory' => $tags,
        'desctiption' => $product->get('field_description')->value,
        'images' => $images,
        'price' => $product->get('field_price')->value,
      ];
    }
    return new JsonResponse($build);
  }

}
