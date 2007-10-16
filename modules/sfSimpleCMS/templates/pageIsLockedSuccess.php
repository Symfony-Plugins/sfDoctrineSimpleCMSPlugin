<div class="sfTMessageContainer sfTLock"> 
  <?php echo image_tag('/sf/sf_default/images/icons/lock48.png', array('alt' => 'page is locked', 'class' => 'sfTMessageIcon', 'size' => '48x48')) ?>
  <div class="sfTMessageWrap">
    <h1>Page is locked for editing</h1>
    <h5>This page has been locked for editing by <?php echo $page->lockingUser ?></h5>
  </div>
</div>
<dl class="sfTMessageInfo">
  <dt><?php echo $page->lockingUser ?> has locked this page for editing</dt>
  <dd>This means that <?php echo $page->lockingUser ?> is the only one who can edit this page. This is usually done in order to prevent people from overwriting each other's changes.</dd>

  <dt>Breaking the lock</dt>
  <dd>If you think that <?php echo $page->lockingUser ?> has locked this page and forgotten to unlock it, you can <?php echo link_to('break the lock', 'sfSimpleCMS/breakLock?slug='.$page->slug) ?>, however be advised that if <?php echo $page->lockingUser ?> is still editing the page this can result in you overwriting each other's changes</dd>
</dl>
