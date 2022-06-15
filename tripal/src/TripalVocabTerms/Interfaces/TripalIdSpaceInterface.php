<?php

namespace Drupal\tripal\TripalVocabTerms\Interfaces;

use Drupal\tripal\TripalVocabTerms\Interfaces\TripalCollectionPluginInterface;

/**
 * Defines an interface for tripal id space plugins.
 */
interface TripalIdSpaceInterface extends TripalCollectionPluginInterface {

  /**
   * Gets the parent of the given term. The given term must be a valid term for
   * this id space. If the given term is a root of this id space then NULL
   * is returned.
   *
   * @param Drupal\tripal\TripalVocabTerms\TripalTerm $child
   *   The given term.
   *
   * @return Drupal\tripal\TripalVocabTerms\TripalTerm|NULL
   *   The parent term or NULL.
   */
  public function getParent($child);

  /**
   * Gets the children terms of the given term. The given term must be a valid
   * term for this id space or NULL. If the given term is NULL then the root
   * children of this id space is returned.
   *
   * @param Drupal\tripal\TripalVocabTerms\TripalTerm|NULL $parent
   *   The given term or NULL.
   *
   * @return array
   *   An array of Drupal\tripal\TripalVocabTerms\TripalTerm children objects.
   */
  public function getChildren($parent = NULL);

  /**
   * Returns the term in this id space with the given accession. If no such term
   * exists then NULL is returned.
   *
   * @param string $accession
   *   The accession.
   *
   * @return Drupal\tripal\TripalVocabTerms\TripalTerm|NULL
   *   The term or NULL.
   */
  public function getTerm($accession);

  /**
   * Returns the terms in this id space whose names match the given name with
   * the given options array.
   *
   * The given options array has the following recognized keys:
   *
   * exact(boolean): True to only include exact matches else false to include
   * all substring matches. The default is false.
   *
   * @param string $name
   *   The name.
   *
   * @param array $options
   *   The options array.
   *
   * @return array
   *   Array of matching Drupal\tripal\TripalVocabTerms\TripalTerm instances.
   */
  public function getTerms($name, $options);

  /**
   * Sets the default vocabulary of this id space to the given vocabulary name.
   * 
   * Removes this id space from its previous default vocabulary if one is set
   * and then adds this id space to its new default vocabulary if the given name
   * is not NULL. It is still the responsibility of an implementation to
   * actually save changes to its default vocabulary.
   *
   * @param string name
   *   The vocabulary name.
   *   
   * @return bool
   *   True on success or false otherwise.
   */
  public function setDefaultVocabulary($name);

  /**
   * Returns this id space's default vocabulary name or NULL if no default has
   * been set.
   *
   * @return string
   *   The vocabulary name.
   */
  public function getDefaultVocabulary();

  /**
   * Saves a term to its ID space data store.
   * 
   * If a term is new in the ID space and has no parents then it will
   * be considered a "root" term for the vocabulary. If the term
   * has parents, use the `addParents()` function to add them before
   * calling this function.  If the term is not new and already exists
   * you only need to provide parents if you need to change the parentage.
   * If the `updateParent` option is True then all parents of an existing
   * term will be removed and will be updated to the parents provided.  If
   * `updateParent` is False and no parents are provided then no change
   * is made to the parent relationships.   
   *
   * The options array accepts the following recognized keys:
   *
   * failIfExists(boolean): True to force this method to fail if this term
   * already exists else false to update this term if it already exists. The
   * default is false.
   *
   * updateParent(boolean): True to update this term's parent to the one
   * given or false to not update this existing term's parent. If this term
   * is new this has no effect. The default is false.
   *
   * @param TripalTerm $term
   *   The TripalTerm object to save.
   * @param array $options
   *   The options array.
   *
   * @return bool
   *   True on success or false otherwise.
   */
  public function saveTerm(TripalTerm $term, $options);
    

  /**
   * Removes the term with the given accession from this id space. All children
   * terms are also removed.
   * !!!WARNING!!!
   * If the removed term in this id space is referenced by entities this could
   * break data integrity. This method must be used with extreme caution!
   *
   * @param string $accession
   *   The accession.
   *
   * @return bool
   *   True if the term was removed or false otherwise.
   */
  public function removeTerm($accession);

  /**
   * Returns the URL prefix of this id space.
   *
   * @return string
   *   The URL prefix.
   */
  public function getURLPrefix();

  /**
   * Sets the URL prefix of this id space to the given URL prefix.
   *
   * @param string $prefix
   *   The URL prefix.
   *   
   * @return bool
   *   True if the value was set or false otherwise.
   */
  public function setURLPrefix($prefix);
  
  
  /**
   * Returns the description of this id space.
   *
   * @return string
   *   The description.
   */
  public function getDescription();
  
  /**
   * Sets the description of this id space.
   *
   * @param string $description
   *   The description.
   *   
   * @return bool
   *   True if the value was set or false otherwise.
   */
  public function setDescription($description);

}
