<!--
 |
 | In $plugin you'll find an instance of Plugin class.
 | If you'd like can pass variable to this view, for example:
 |
 | return PluginClassName()->view( 'dashboard.index', [ 'var' => 'value' ] );
 |
-->

<div class="wpx-cron-manager-light wrap">
  <h2>Crons</h2>

  <div>
    <form method="post">
      <?php
      $table->prepare_items();
      $table->display(); ?>
    </form>
  </div>
</div>