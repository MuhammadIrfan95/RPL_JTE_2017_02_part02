<?php if (isset($_GET['sidebar'])) { $sidebar_pos = htmlspecialchars($_GET['sidebar']); } else { $sidebar_pos = ot_get_option('shop_sidebar'); }  ?>
<aside class="sidebar woo small-12 medium-3 columns <?php if ($sidebar_pos == 'left') { echo 'medium-pull-9'; }?>">
	<?php 
	
		##############################################################################
		# belanja
		##############################################################################
	
	 	?>
	<?php dynamic_sidebar('belanja'); ?>
</aside>