<?php
/*
* This file is part of the sfDoctrineSimpleCMS package, based on the 
* sfSimpleCMS package.
* (c) 2007 François Zaninotto <francois.zaninotto@symfony-project.com>
* (c) 2007 Magnus Nordlander, smiling plants <magnus@smilingplants.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
 
/**
 *
 * @package sfDoctrineSimpleCMS
 * @author  François Zaninotto <francois.zaninotto@symfony-project.com>
 * @author  Magnus Nordlander, smiling plants <magnus@smilingplants.com>
 */
abstract class PluginsfSimpleCMSPage extends BasesfSimpleCMSPage
{
  protected $localizations = null,
            $title         = '',
            $slots = array();
  //Done
  public function __toString($culture = '')
  {
    $title = $this->getTitle($culture);
    return $title ? $title : '['.$this->slug.']';
  }
  //Done
  public function getSlots($culture = null)
  {
    if(!$this->slots)
    {
      $this->populateSlots($culture);
    }
    return $this->slots;
  }

  //Done
  public function populateSlots($culture)
  {
    $related_slots = Doctrine_Query::create()->from('sfSimpleCMSSlot s')->
                     where('s.culture = ? AND s.page_id = ?', array($culture, $this->id))->
                     execute();
    $slots = array();
    foreach($related_slots as $slot)
    {
      $slots[$slot->name] = $slot;
    }
    $this->slots = $slots;          
  }

  //Done
  public function hasSlots($culture)
  {
    return count($this->getSlots($culture));
  }
  //Done
  public function getSlot($name, $culture = null)
  {
    $slots = $this->getSlots($culture);
    return isset($slots[$name]) ? $slots[$name] : null;
  }
  //Done
  public function getSlotValue($name, $culture = null)
  {
    $slot = $this->getSlot($name, $culture);

    return $slot ? $slot->value : null;
  }
  
  //Done
  public function getTitle($culture = null)
  {
    if(!$this->title)
    {
      $this->title = $this->getSlotValue('title', $culture);
    }
    return $this->title;
  }
  
  public function getTitleType($culture = null)
  {
    $slot = $this->getSlot('title', $culture);
    
    return $slot != null ? $slot->type : 'Text';
  }

  //Done
  public function setTitle($title)
  {
    $this->title = $title;
  }
  
  public function setSlot($name, $culture, $type, $value)
  {
    $slot = $this->getSlot($name, $culture);
    if(!$slot)
    {
      $slot = new sfSimpleCMSSlot();
      $slot->setName($name);
      $slot->setPageId($this->getId());
      $slot->setCulture($culture);
    }
    $slot->setType($type);
    $slot->setValue($value);
    $slot->save();
    
    $this->slots[$name] = $slot;
    
    return $slot; 
  }

  public function getSlugWithLevel()
  {
    return str_repeat(' - ', $this->getLevel()).$this->getSlug();
  }
  
  public function hasLocalization($culture)
  {
    $localizations = $this->getLocalizations();
    return in_array($culture, $localizations);
  }
  
  public function getLocalizations()
  {
    if($this->localizations === null)
    {
      $locs = Doctrine_Query::create()->
              from('sfSimpleCMSSlot s')->
              where('s.page_id = ?', $this->getId())->
              groupby('s.culture')->execute();
           
      if($locs->count() == 0) return array();
      $localizations = array();
      foreach ($locs as $set)
      {
        $localizations[] = $set->culture;
      }
      
      $this->localizations = $localizations;
    }
    
    return $this->localizations;
  }
  
  public function isLockedForUserId($id)
  {
    if ($this->locked_by == NULL || $this->locked_by == $id)
    {
      return false;
    }
    else
    {
      return true;
    }
  }
  
  public function lockForUserId($id)
  {
    $this->locked_by = $id;
  }
  
  public function breakLock()
  {
    $this->locked_by = NULL;
  }
  
  public function getChildrenWithTitles($include_unpublished_pages = false, $culture = null)
  {
    $q = new Doctrine_RawSql();
    
    if ($culture != null)
    {
      $cond = "(s.culture = '$culture' AND s.name = 'title')";
    }
    else
    {
      $cond = "(s.name = 'title')";
    }
  
    $q->select('{p.*}, {s.*}')->
        from("sf_simple_cms_page p LEFT JOIN sf_simple_cms_slot s ON p.id = s.page_id AND $cond")->
        where('p.level = ? AND p.lft > ? AND p.rgt < ?', array($this->level + 1, $this->lft, $this->rgt));
  
    if (!$include_unpublished_pages)
    {
      $q->addWhere('p.is_published = 1');
    }
  
    $res = $q->orderby('p.lft')->
           addComponent('p', 'sfSimpleCMSPage p')->
           addComponent('s', 'p.sfSimpleCMSSlots s')->
           execute();
  
    foreach($res as $r)
    {
      if ($r->sfSimpleCMSSlots->count() == 1)
      {
        $r->setTitle($r->sfSimpleCMSSlots->getFirst()->value);
      }
    }
  
    return $res;
  }
}
