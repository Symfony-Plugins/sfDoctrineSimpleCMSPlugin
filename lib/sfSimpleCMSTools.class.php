<?php

class sfSimpleCMSTools
{
  public static function urlForPage($slug, $query_string = '', $culture = '')
  {
    if(sfConfig::get('app_sfSimpleCMS_use_l10n', false))
    {
      $culture_parameter = $culture ? $culture : sfContext::getInstance()->getRequest()->getParameter('sf_default_culture');
      $culture_query = '&sf_default_culture='.$culture_parameter;
    }
    else
    {
      $culture_query = '';
    }
    
    //Make Google love us!
    if (sfConfig::get('app_sfSimpleCMS_default_page', 'home') == $slug && $culture_query == '' && $query_string == '')
    {
      return sfContext::getInstance()->getController()->genUrl('sfSimpleCMS/show', sfConfig::get('app_sfSimpleCMS_absolute_urls', true));
    }
    
    $routed_url = sfContext::getInstance()->getController()->genUrl('sfSimpleCMS/show?slug=-PLACEHOLDER-'.$culture_query, sfConfig::get('app_sfSimpleCMS_absolute_urls', true));
    return str_replace('-PLACEHOLDER-', $slug, $routed_url).($query_string ? '?'.$query_string : '');
  }
}