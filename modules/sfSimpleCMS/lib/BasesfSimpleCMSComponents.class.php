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

class BasesfSimpleCMSComponents extends sfComponents
{
  public function executeEditorTools()
  {
    $this->page_names = Doctrine::getTable('sfSimpleCMSPage')->getAllPagesWithLevel();
    $this->slug = $this->getRequestParameter('slug');
    $this->culture = $this->getRequestParameter('sf_culture', sfConfig::get('app_sfSimpleCMS_default_culture', 'en'));
    $publisher_credentials = sfConfig::get('app_sfSimpleCMS_publisher_credential', false);
    $this->is_publisher = (!$publisher_credentials  || $this->getUser()->hasCredential($publisher_credentials));
  }
  
  public function executeMainNavigation()
  {
    $include_unpublished_pages = $this->getRequestParameter('edit') == 'true';
    $this->culture = $this->getCulture();
    $this->level1_nodes = Doctrine::getTable('sfSimpleCMSPage')->getlevel1($include_unpublished_pages, $this->culture);
  }

  public function executeBreadcrumb()
  {
    $this->pages = $this->page->getNode()->getAncestors();
  }
  
  public function executeLatestPages()
  {
    $include_unpublished_pages = $this->getRequestParameter('edit') == 'true';
    $this->pages = Doctrine::getTable('sfSimpleCMSPage')->getLatest(sfConfig::get('app_sfSimpleCMS_max_pages_in_list', 5), $include_unpublished_pages);
    $this->culture = $this->getCulture();
  }
    
  protected function getCulture()
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
  
  protected function checkEditorCredential()
  {
    $editor_credentials = sfConfig::get('app_sfSimpleCMS_editor_credential', false);
    return $editor_credentials ? $this->getUser()->hasCredential($editor_credentials) : true;
  }
    
  public function executeEmbed()
  {
    $culture = $this->getCulture();
    
    if($this->getRequestParameter('edit') == 'true')
    {
      if(!$this->checkEditorCredential())
      {
        $this->CMS_error_msg = 'You need the editor credential to edit this content';
        return;
      }
      $page = Doctrine::getTable('sfSimpleCMSPage')->findBySlug($this->slug, $culture);
    }
    else
    {
      $page = Doctrine::getTable('sfSimpleCMSPage')->findPublicBySlug($this->slug, $culture);
    }

    if (!$page)
    {
      $this->CMS_error_msg = sprintf('The page %s does not exist in culture %c', $this->slug, $culture);
      return;
    }
    
    $this->page = $page;
    $this->culture = $culture;
    $this->getRequest()->setAttribute('culture', $culture);
    $this->templatePath = sfProjectConfiguration::getActive()->getTemplatePath('sfSimpleCMS', $this->page->getTemplate().'Template.php');
    sfConfig::set('app_sfSimpleCMS_disable_editor_toolbar', true);
  }
}
