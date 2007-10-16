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

class BasesfSimpleCMSActions extends sfActions
{  
  private function getCulture()
  {
    if(sfConfig::get('app_sfSimpleCMS_use_l10n', false))
    {
      return $this->getRequestParameter('sf_culture', sfConfig::get('app_sfSimpleCMS_default_culture', 'en'));
    }
    else
    {
      return sfConfig::get('app_sfSimpleCMS_default_culture', 'en');
    }
  }

  public function executeIndex()
  {
    $this->redirect(sfSimpleCMSTools::urlForPage(sfConfig::get('app_sfSimpleCMS_default_page', 'home'), '', $this->getCulture()));
  }
  
  private function checkEditorCredential()
  {
    $editor_credentials = sfConfig::get('app_sfSimpleCMS_editor_credential', false);
    if($editor_credentials && !$this->getUser()->hasCredential($editor_credentials))
    {
      $this->forward(sfConfig::get('sf_secure_module'), sfConfig::get('sf_secure_action'));
      throw new sfStopException();
    }
  }

  private function checkPublisherCredential()
  {
    $publisher_credentials = sfConfig::get('app_sfSimpleCMS_publisher_credential', false);
    if($publisher_credentials  && !$this->getUser()->hasCredential($publisher_credentials ))
    {
      $this->forward(sfConfig::get('sf_secure_module'), sfConfig::get('sf_secure_action'));
      throw new sfStopException();
    }
  }
  
  public function executeShow()
  {
    $culture = $this->getCulture();
    
    $editor_credentials = sfConfig::get('app_sfSimpleCMS_editor_credential', false);
    if($this->getRequestParameter('edit') == 'true')
    {
      $this->checkEditorCredential();
      $page = sfDoctrine::getTable('sfSimpleCMSPage')->findBySlug($this->getRequestParameter('slug', sfConfig::get('app_sfSimpleCMS_default_page', 'home')), $culture);
      if ($page->isLockedForUserId($this->getUser()->getGuardUser()->id))
      {
        $this->getRequest()->setAttribute('lockedPage', $page);
        $this->getRequest()->setAttribute('culture', $culture);
        $this->forward('sfSimpleCMS','pageIsLocked');
      }
    }
    else
    {
      $page = sfDoctrine::getTable('sfSimpleCMSPage')->findPublicBySlug($this->getRequestParameter('slug', sfConfig::get('app_sfSimpleCMS_default_page', 'home')), $culture);
    }

    $this->forward404Unless($page);
    
    $this->page = $page;
    $this->culture = $culture;
    $this->getRequest()->setAttribute('culture', $culture);
    $this->setTemplate($this->page->getTemplate());
    $this->getResponse()->setTitle(sprintf(sfConfig::get('app_sfSimpleCMS_title_format', '%s'), $this->page->getTitle()));
    if(sfConfig::get('app_sfSimpleCMS_use_bundled_layout', true))
    {
      $this->setLayout(sfLoader::getTemplateDir('sfSimpleCMS', 'layout.php').'/layout');
      if(sfConfig::get('app_sfSimpleCMS_use_bundled_stylesheet', true)) 
      {
        $this->getResponse()->addStylesheet('/sfSimpleCMSPlugin/css/CMSTemplates.css', 'last'); 
      }
    }

    return 'Template';
  }

  public function executeUpdateSlot()
  {
    $this->checkEditorCredential();
    $page = sfDoctrine::getTable('sfSimpleCMSPage')->findBySlug($this->getRequestParameter('slug'));
    $this->forward404Unless($page);
    
    if ($page->isLockedForUserId($this->getUser()->getGuardUser()->id))
    {
      $response = '{ "username": "'.$page->lockingUser.'" }';
      $this->getResponse()->setStatusCode(409, 'Page is locked');
      $this->getResponse()->setHttpHeader("X-JSON", '('.$response.')');
      return sfView::HEADER_ONLY;
    }

    $culture = $this->getCulture();
    $ret = '';
    
    $slot_name = $this->getRequestParameter('slot');
    $old_slot_object = $page->getSlot($slot_name, $culture);
    
    $slot_type_name = $this->getRequestParameter('slot_type', 'Text');
    $slot_type_class = 'sfSimpleCMSSlot'.$slot_type_name;
    $slot_type = new $slot_type_class();
    
    $slot_value = $slot_type->getSlotValueFromRequest($this->getRequest());

    if(($old_slot_object && $slot_type_name != $old_slot_object->getType()) 
      || 
      (!$old_slot_object && $slot_type_name != 'Text'))
    {
      // The slot type has changed, so we must reload the page to get the correct editor
      $ret .= '<script type="text/javascript">window.location.reload();</script>';
    }
    
    $slot = $page->setSlot($slot_name, $culture, $slot_type_name, $slot_value);
    $page->save();
    
    if($slot_value)
    {
      $ret .= $slot_type->getSlotValue($slot);
    }
    else
    {
      $ret .= sfConfig::get('app_sfSimpleCMS_default_text');
    }
    
    return $this->renderText($ret);
  }

  public function executeTogglePublish()
  {
    $this->checkPublisherCredential();
    
    $slug = $this->getRequestParameter('slug');
    $page = sfDoctrine::getTable('sfSimpleCMSPage')->findBySlug($slug);
    $this->forward404Unless($page);
    
    if ($page->isLockedForUserId($this->getUser()->getGuardUser()->id))
    {
      throw new Exception('This page is locked by ' . $page->lockingUser);
    }
    
    $this->checkPublisherCredential();
    
    $page->setIsPublished(!$page->getIsPublished());
    $page->save();

    $query_string = 'edit=true'.($this->getRequestParameter('preview', false) == 'true' ? '&preview=true' : '');
    $this->redirect(sfSimpleCMSTools::urlForPage($slug, $query_string, $this->getRequestParameter('sf_culture')));
  }
  
  public function executePageIsLocked()
  {
    $this->page = $this->getRequest()->getAttribute('lockedPage');
    $this->culture = $this->getRequest()->getAttribute('culture');
  }
  
  public function executeEdit()
  {
    $this->forward404Unless($this->getRequest()->getMethod() == sfRequest::POST);
    $this->checkEditorCredential();
    if(!$this->getRequestParameter('slug'))
    {
      throw new Exception('Attempting to edit/create a page with no slug. Please make sure you enter a slug in the form before submitting it.');
    }
    
    $page_id = $this->getRequestParameter('page_id');
    $culture = $this->getCulture();
    $relative_page = sfDoctrine::getTable('sfSimpleCMSPage')->findBySlug($this->getRequestParameter('position'));
    $positionType = $this->getRequestParameter('position_type');
    if($relative_page && $relative_page->getNode()->isRoot() && $positionType != 'under')
    {
      // FIXME: error message rather than exception
      throw new Exception('Attempting to move/create a page at the same level as the home page. Please make sure to select a position under the root node.');
    }
    
    if($page_id)
    {
      // update
      $page = sfDoctrine::getTable('sfSimpleCMSPage')->find($page_id);
      $this->forward404Unless($page);
      
      if ($page->isLockedForUserId($this->getUser()->getGuardUser()->id))
      {
        throw new Exception('This page is locked by ' . $page->lockingUser);
      }
      
      if($relative_page)
      {
        if($positionType == 'under')
        {
          $page->getNode()->moveAsFirstChildOf($relative_page);
        }
        else
        {
          $page->getNode()->moveAsNextSiblingOf($relative_page);
        }
      }
      if($title = $this->getRequestParameter('title'))
      {
        //We take care to not mess with the type of the title...
        $page->setSlot('title', $culture, $page->getTitleType($culture), $title);
      }
      $page->slug = $this->getRequestParameter('slug');
      $page->setTemplate($this->getRequestParameter('template'));
      $page->save();
    }
    else
    {
      // create
      $page = new sfSimpleCMSPage();
      $page->slug = $this->getRequestParameter('slug');
      $page->setTemplate($this->getRequestParameter('template'));
      if($positionType == 'under')
      {
        $page->getNode()->insertAsFirstChildOf($relative_page);
      }
      else
      {
        $page->getNode()->insertAsNextSiblingOf($relative_page);
      }
    }

    $this->redirect(sfSimpleCMSTools::urlForPage($page->getSlug(), 'edit=true', $culture));
  }

  public function executeDelete ()
  {
    $this->checkEditorCredential();

    $culture = $this->getCulture();
    $page = sfDoctrine::getTable('sfSimpleCMSPage')->findBySlug($this->getRequestParameter('slug'));
    $this->forward404Unless($page);
    
    if ($page->isLockedForUserId($this->getUser()->getGuardUser()->id))
    {
      throw new Exception('This page is locked by ' . $page->lockingUser);
    }

    $page->getNode()->delete();

    $this->redirect(sfSimpleCMSTools::urlForPage(sfConfig::get('app_sfSimpleCMS_default_page', 'home'), 'edit=true', $culture));
  }
  
  public function executeBreakLock()
  {
    $this->checkEditorCredential();

    $culture = $this->getCulture();
    $page = sfDoctrine::getTable('sfSimpleCMSPage')->findBySlug($this->getRequestParameter('slug'));
    $this->forward404Unless($page);
    
    $page->breakLock();
    
    $page->save();

    $this->redirect(sfSimpleCMSTools::urlForPage($page->getSlug(), 'edit=true', $culture));
  }
  
  public function executeDone()
  {
    $this->checkEditorCredential();

    $culture = $this->getCulture();
    $page = sfDoctrine::getTable('sfSimpleCMSPage')->findBySlug($this->getRequestParameter('slug'));
    $this->forward404Unless($page);

    $this->redirect(sfSimpleCMSTools::urlForPage($page->getSlug(), '', $culture));
  }
  
  public function executeAcquireLock()
  {
    $this->checkEditorCredential();
  
    $culture = $this->getCulture();
    $page = sfDoctrine::getTable('sfSimpleCMSPage')->findBySlug($this->getRequestParameter('slug'));
    $this->forward404Unless($page);
  
    if ($page->isLockedForUserId($this->getUser()->getGuardUser()->id))
    {
      throw new Exception('This page is locked by ' . $page->lockingUser);
    }
    else
    {
      $page->lockForUserId($this->getUser()->getGuardUser()->id);
      $page->save();
    }
  
    $this->redirect(sfSimpleCMSTools::urlForPage($page->getSlug(), 'edit=true', $culture));
  }
  
  public function executeReleaseLock()
  {
    $this->checkEditorCredential();
  
    $culture = $this->getCulture();
    $page = sfDoctrine::getTable('sfSimpleCMSPage')->findBySlug($this->getRequestParameter('slug'));
    $this->forward404Unless($page);
  
      if ($page->locked_by == $this->getUser()->getGuardUser()->id) {
      $page->breakLock();
      $page->save();
    }
  
    $this->redirect(sfSimpleCMSTools::urlForPage($page->getSlug(), 'edit=true', $culture));
  }

}
