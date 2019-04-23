<?php

namespace Drupal\sp_create\EntityClone;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_clone\EntityClone\Content\TaxonomyTermEntityClone;

/**
 * Class TaxonomyTermEntityClone.
 */
class SpCreateTaxonomyTermEntityClone extends TaxonomyTermEntityClone {

  /**
   * Sets the cloned entity's label.
   *
   * This is overridden to stop adding '- Cloned' to a title. This can cause
   * titles that overrun the 255 character limit.
   *
   * @param \Drupal\Core\Entity\EntityInterface $original_entity
   *   The original entity.
   * @param \Drupal\Core\Entity\EntityInterface $cloned_entity
   *   The entity cloned from the original.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function setClonedEntityLabel(EntityInterface $original_entity, EntityInterface $cloned_entity) {
    $label_key = $this->entityTypeManager->getDefinition($this->entityTypeId)->getKey('label');
    if ($label_key && $cloned_entity->hasField($label_key)) {
      $cloned_entity->set($label_key, $original_entity->label());
    }
  }

}
