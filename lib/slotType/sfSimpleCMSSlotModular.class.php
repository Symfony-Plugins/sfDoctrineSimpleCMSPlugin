<?php

/**
 * Modular slot class, to be used by the sfSimpleCMSHelper.
 * The slot must contain a valid list of components in YAML format.
 * Example:
 * <code>
 * // If the slot value is
 * mycomponent:
 *   component: mymodule/myaction
 *   foo:       bar
 * // Then the getSlotValue() method will return the result of a call to
 * get_component('mymodule', 'myaction', array('foo' => 'bar'));
 * </code>
 */
class sfSimpleCMSSlotModular extends sfSimpleCMSSlotText implements sfSimpleCMSSlotTypeInterface
{
  public function getSlotValue($slot)
  {
    sfLoader::loadHelpers(array('Partial'));
    $components = sfYaml::load($slot->getValue());
    $res = '';
    foreach($components as $name => $params)
    {
      if(!isset($params['component']))
      {
        return sprintf('<strong>Error</strong>: The value of slot %s in incorrect. Component %s has no \'component\' key.', $slot->getName(), $name);
      }
      list($module, $action) = split('/', $params['component']);
      unset($params['component']);
      $res .= get_component($module, $action, array_merge(array("slot" => $slot), $params));
    }
    
    return $res;
  }
}
