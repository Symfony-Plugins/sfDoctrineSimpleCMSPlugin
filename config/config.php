<?php

if (sfConfig::get('app_sfSimpleCMS_routes_register', true) && in_array('sfSimpleCMS', sfConfig::get('sf_enabled_modules')))
{
  $this->dispatcher->connect('routing.load_configuration', array('sfSimpleCMSRouting', 'listenToRoutingLoadConfigurationEvent'));
}
