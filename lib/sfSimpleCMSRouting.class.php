<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package    symfony
 * @subpackage plugin
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id: sfGuardRouting.class.php 7636 2008-02-27 18:50:43Z fabien $
 */
class sfSimpleCMSRouting
{
  /**
   * Listens to the routing.load_configuration event.
   *
   * @param sfEvent An sfEvent instance
   */
  static public function listenToRoutingLoadConfigurationEvent(sfEvent $event)
  {
    $r = $event->getSubject();

    // preprend our routes
    $r->prependRoute('sf_cms_delete', new sfRoute('/cms_delete/:sf_culture/:slug', array('module' => 'sfSimpleCMS', 'action' => 'delete'), array('slug' => '.*')));
    $r->prependRoute('sf_cms_toggle_publish', new sfRoute('/cms_publish/:slug', array('module' => 'sfSimpleCMS', 'action' => 'togglePublish'), array('slug' => '.*')));
    if(sfConfig::get('app_sfSimpleCMS_use_l10n', false))
    {
      $r->prependRoute('sf_cms_show', new sfRoute('/cms/:sf_default_culture/:slug', array('module' => 'sfSimpleCMS', 'action' => 'show'), array('slug' => '.*')));
    }
    else
    {
      $r->prependRoute('sf_cms_show', new sfRoute('/cms/:slug', array('module' => 'sfSimpleCMS', 'action' => 'show'), array('slug' => '.*')));
    }
  }
}