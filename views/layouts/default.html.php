<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2010, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */
?>
<!doctype html>
<html>
<head>
	<?php echo $this->html->charset();?>
	<title>Application > <?php echo $this->title(); ?></title>
	<?php echo $this->html->style(array('lithium', 'debug')); ?>
	<?php echo $this->scripts(); ?>
	<?php echo $this->html->link('Icon', null, array('type' => 'icon')); ?>
</head>
<body class="app slicedup">
	<div id="container">
		<div id="header">
			<h1>Slicedup Users</h1>
		</div>
		<div id="content">
			<?=$this->flashMessage->output();?>
			<?=$this->content;?>
		</div>
	</div>
</body>
</html>