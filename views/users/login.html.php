<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2010, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

$this->title('Login');
?>
<div class="sli-users sli-users-login users-<?php echo $configKey;?>">
<h3>Login</h3>
<?php
	echo $this->form->create(null, array('action' => 'login'));
	foreach ($fields as $field => $options):
		if (is_numeric($field)):
			$field = $options;
			$options = array();
		endif;
		if (!$options):
			if (strpos($field, 'password') !== false):
				$options['type'] = 'password';
			endif;
		endif;
		echo $this->form->field($field, $options);
	endforeach;
	if ($persist):
		echo $this->form->field('remember_me', array('type' => 'checkbox'));
		echo '<br>';
	endif;
	echo $this->form->submit('Login');
	if ($passwordReset):
		echo '<br>';
		echo $this->html->link('Forgot your password?', array('action' => 'password_reset'));
	endif;
	if ($register):
		echo '<br>';
		echo $this->html->link('Create Account', array('action' => 'register'));
	endif;
	echo '<br>';
	echo $this->form->end();
?>
</div>