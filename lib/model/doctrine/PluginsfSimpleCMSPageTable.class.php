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
class PluginsfSimpleCMSPageTable extends Doctrine_Table
{
  public function findPublicBySlug($slug, $culture = null, $con = null)
  {
    $page = $this->createQuery()->where('slug = ? AND is_published = true', $slug)->execute()->getFirst();
    
    if($page && $culture)
    {
      // populate the slots for the given culture
      $slots = $page->getSlots($culture);
      if(!$slots)
      {
        // a page with no slot is not displayed in the frontend
        return null;
      }
    }

    return $page;
  }
  
  public function findBySlug($slug, $culture = null, $con = null)
  {
    $page = $this->createQuery()->where('slug = ?', $slug)->execute()->getFirst();
    
    if ($page && $culture) 
    {
      // populate the slots for the given culture
      $page->populateSlots($culture);
    }

    return $page;
  }

  public function getAllOrderBySlug($con = null)
  {
    return $this->createQuery()->orderby('slug')->execute();
  }

  public function getAllOrderByTree($con = null)
  {
    return $this->createQuery()->orderby('lft')->execute();
  }

  public function getRoot()
  {
    return $this->createQuery()->where('level = 0')->execute()->getFirst();
  }

  public function getLevel1($include_unpublished_pages = false, $culture = null)
  {
    $q = new Doctrine_RawSql();
    
    $q->select('{p.*}, {s.*}')->
        from("sf_simple_cms_page p LEFT JOIN sf_simple_cms_slot s ON p.id = s.page_id AND (s.culture = '$culture' AND s.name = 'title')")->
        where('p.level = 1');
        
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

  public function getLatest($max = 5, $include_unpublished_pages = false)
  {
    $query = $this->createQuery()->
             from('sfSimpleCMSPage p')->
             leftJoin('p.sfSimpleCMSSlots s WITH s.name = ?', 
               "title");
             
    if (!$include_unpublished_pages)
    {
      $query->where('p.is_published = true');
    }
    
    return $query->orderby('p.updated_at')->limit($max)->execute();
  }

  public function getAllPagesWithLevel($indent_string = ' - ')
  {
    $tree = $this->getTree()->fetchTree();
    $page_names = array();
    if ($tree)
    {
      foreach ($tree as $page) {
        $page_names[$page->slug] = str_repeat($indent_string, $page->level).$page->slug;
      }
    }
    return $page_names;
  }

}
