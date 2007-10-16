<?php
use_helper('Form', 'Javascript', 'I18N');

function include_editor_tools($page)
{
  if (sfContext::getInstance()->getRequest()->getParameter('edit') == 'true' && !sfConfig::get('app_sfSimpleCMS_disable_editor_toolbar', false))
  {
    use_stylesheet('/sfSimpleCMSPlugin/css/CMSEditorTools.css');
    include_component('sfSimpleCMS', 'editorTools', array('page' => $page));
  }
}

function sf_simple_cms_has_slot($page, $slot_name)
{
  $request = sfContext::getInstance()->getRequest();
  if ($request->getParameter('edit') == 'true' && !$request->getParameter('preview'))
  {
    return true;
  }
  $slot_value = $page->getSlotValue($slot_name);
  if($slot_value)
  {
    return true;
  }
  return false;
}

function sf_simple_cms_slot($page, $slot, $default_text = null, $default_type = 'Text')
{
  $context = sfContext::getInstance();
  $request = $context->getRequest();
  
  $culture = $request->getAttribute('culture');
  $slot_object = $page->getSlot($slot);
  if(!$slot_object)
  {
    $slot_object = new sfSimpleCMSSlot();
    $slot_object->setType($default_type);
    $slot_object->setCulture($culture);
  }
  $slot_value = $slot_object->getValue();
  $slot_type_name = $slot_object->getType();
  
  $slot_type_class = 'sfSimpleCMSSlot'.$slot_type_name;
  $slot_type = new $slot_type_class();
    
  if ($request->getParameter('edit') == 'true' && !$request->getParameter('preview'))
  {
    echo '<div id="locking_'.$slot.'" class="locking-error" style="display:none">While you were editing this page was locked by <span class="user-name">user_name</span>, therefore your changes could not be saved</div>';
    echo '<div class="editable_slot" title="'.__('Double-click to edit').'" id="slot_'.$slot.'" onDblClick="Element.show(\'edit_'.$slot.'\');Element.hide(\'slot_'.$slot.'\');">';
    
    if($slot_value)
    {
      // Get slot value from the slot type object
      echo $slot_type->getSlotValue($slot_object);
    }
    else
    {
      // default text
      echo $default_text ? $default_text : sfConfig::get('app_sfSimpleCMS_default_text', __('[add text here]'));
    }
    
    echo '</div>';
/*    echo '<form id="edit_'.$slot.'" class="edit_slot" method="post" action="'.sfContext::getInstance()->getController()->genUrl('sfSimpleCMS/updateSlot').'" onsubmit="new Ajax.Updater(\'slot_'.$slot.'\', \''.sfContext::getInstance()->getController()->genUrl('sfSimpleCMS/updateSlot').'\', 
    {
      asynchronous:true, 
      evalScripts:true, 
      onSuccess:function(request, json) {
        Element.show(\'slot_'.$slot.'\');
        Element.hide(\'edit_'.$slot.'\'); 
        new Effect.Highlight(\'slot_'.$slot.'\', {});
      },
      on409
      parameters:Form.serialize(this)
    }); return false;" style="display: none;">'*/
    echo form_remote_tag(array(
      'url'         => 'sfSimpleCMS/updateSlot',
      'script'      => 'true',
      'update'      => 'slot_'.$slot,
      'success'     => 'Element.show(\'slot_'.$slot.'\');
                        Element.hide(\'edit_'.$slot.'\');
                       '.visual_effect('highlight', 'slot_'.$slot),
      '409'       => 'Element.show(\'locking_'.$slot.'\');
                        var affected = document.getElementsByClassName(\'user-name\');
                        for (var i = 0; i < affected.length; i++)
                        {
                          affected[i].update(json.username);
                        }'
      ), array(
        'class'     => 'edit_slot',
        'id'        => 'edit_'.$slot,
        'style'     => 'display:none'
    ));
    echo input_hidden_tag('slug', $page->getSlug(), 'id=edit_path'.$slot);
    echo input_hidden_tag('slot', $slot);
    if(sfConfig::get('app_sfSimpleCMS_use_l10n', false))
    {
      echo input_hidden_tag('sf_culture', $slot_object->getCulture());
    }
    
    // Get slot editor from the slot type object
    echo $slot_type->getSlotEditor($slot_object);
    
    echo label_for('slot_type', __('Type: '));
    echo select_tag(
      'slot_type', 
      options_for_select(
        sfConfig::get('app_sfSimpleCMS_slot_types', array(
          'Text'     => __('Simple Text'),
          'RichText' => __('Rich text'),
          'Php'      => __('PHP code'),
          'Image'    => __('Image'),
          'Modular'  => __('List of components'))),
        $slot_type_name
      )
    );
    if($rich = sfConfig::get('app_sfSimpleCMS_rich_editing', false))
    {
      // activate rich text if global rich_editing is true and is the current slot is RichText
      $rich = ($slot_type_name == 'RichText');
    }    
    echo submit_tag('update', array(
      'onclick' => ($rich ? 'tinymceDeactivate()'.';submitForm(\'edit_'. $slot .'\'); return false' : ''),
      'class' => 'submit_tag'
    ));
    echo button_to_function('cancel', 'Element.hide(\'edit_'.$slot.'\');Element.show(\'slot_'.$slot.'\');', 'class=submit_tag');
    
    echo '</form>';
  }
  else
  {
    echo $slot_type->getSlotValue($slot_object, ESC_RAW);
  }
}