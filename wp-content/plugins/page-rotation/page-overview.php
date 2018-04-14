<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 23.7.2017
 * Time: 14:24
 */


function page_rotation_overview_help () {
	$screen = get_current_screen();
	
	// Add my_help_tab if current screen is My Admin Page
	$screen->add_help_tab( array(
		'id'	=> 'my_help_tab',
		'title'	=> __('My Help Tab'),
		'content'	=> '<p>' . __( 'Descriptive content that will show in My Help Tab-body goes here.' ) . '</p>',
	) );
}

function page_rotation_overview_page() {
	include('Page_Overview_List.php');
	
	
	
	
	?>
    <div id="dialog-delete" title="Zmazať záznam?">
        <p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>Ste si istý, že chcete zmazať tento záznam? Krok sa nedá vrátiť naspäť.</p>
    </div>
    <div class="wrap">
	    <h1 style="display: inline-block">Prehľad stránok</h1>
        <a href="post-new.php?post_type=page"" class="page-title-action">Pridať novú</a>
    </div>
<?php
	
	$table = new Page_Overview_List();
	$table->views();
	$table->prepare_items();
	
	?>
    <form method="post" action="">
	<?php
        $table->display();
    ?>
    </form>
    <?php
}

