<?php

class sfSimpleCMSAdminActions extends autosfSimpleCMSAdminActions
{
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

  public function preExecute()
  {
    $this->checkEditorCredential();
  }
  
  public function executeEditPage()
  {
    $page = Doctrine_Manager::getInstance()->getTable('sfSimpleCMSPage')->find($this->getRequestParameter('id'));
    $this->redirect(sfSimpleCMSTools::urlForPage($page->getSlug(), 'edit=true', $this->getUser()->getCulture()));
  }
  
  public function executeTogglePublish()
  {
    $page = Doctrine_Manager::getInstance()->getTable('sfSimpleCMSPage')->find($this->getRequestParameter('id'));
    $this->forward404Unless($page);
    
    $this->checkPublisherCredential();
    
    $page->setIsPublished(!$page->getIsPublished());
    $page->save();
    
    $this->redirect('sfSimpleCMSAdmin/list?page='.$this->getRequestParameter('page', 1));
  }
  
  public function executeAddPage()
  {
    if(!$this->getRequestParameter('slug'))
    {
      throw new Exception('Attempting to create a page with no slug. Please make sure you enter a slug in the form before submitting it.');
    }
    $relative_page = Doctrine_Manager::getInstance()->getTable('sfSimpleCMSPage')->findBySlug($this->getRequestParameter('position'));
    $positionType = $this->getRequestParameter('position_type');
    if($relative_page && $relative_page->getNode()->isRoot() && $positionType != 'under')
    {
      throw new Exception('Attempting to create a page at the same level as the home page. Please make sure select a position under the root node.');
    }
    
    $page = new sfSimpleCMSPage();
    $page->setSlug($this->getRequestParameter('slug'));
    $page->setTemplate($this->getRequestParameter('template'));
    if($positionType == 'under')
    {
      $page->getNode()->insertAsFirstChildOf($relative_page);
    }
    else
    {
      $page->getNode()->insertAsNextSiblingOf($relative_page);
    }   
    $page->save();

    $this->redirect('sfSimpleCMSAdmin/list?page='.$this->getRequestParameter('page', 1));
  }

  public function executeCreateRootPage()
  {
    if(!$this->getRequestParameter('slug'))
    {
      throw new Exception('Attempting to create a page with no slug. Please make sure you enter a slug in the form before submitting it.');
    }
    
    $page = new sfSimpleCMSPage();
    $page->setSlug($this->getRequestParameter('slug'));
    $page->setTemplate($this->getRequestParameter('template'));
    Doctrine_Manager::getInstance()->getTable('sfSimpleCMSPage')->getTree()->createRoot($page);


    $this->redirect('sfSimpleCMSAdmin/list?page='.$this->getRequestParameter('page', 1));
  }
  
  public function executeList()
  {
    parent::executeList();
    $this->getRequest()->setAttribute('page_names', Doctrine_Manager::getInstance()->getTable('sfSimpleCMSPage')->getAllPagesWithLevel());
  }
}
