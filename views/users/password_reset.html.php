<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2010, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */
?>
<h3>Password Reset</h3>
<?php
	$this->title('Password Reset');
	if ($status == 'error'):
		echo '<div class="test-result test-result-fail">Email address not found.</div>';
	elseif ($status == 'success'):
		echo '<div class="test-result test-result-success">Please login to continue.</div>';
	else:
		echo '<div class="test-result test-result-exception">Please provide your email address so we can confirm your identity and reset your password.</div>';
	endif;
	echo $this->form->create(null, array('action' => 'password_reset'));
	foreach ($fields as $field => $options):
		if (is_numeric($field)):
			$field = $options;
			$options = array();
		endif;
		echo $this->form->field($field, $options);
	endforeach;
	echo $this->form->submit('Submit');
	echo '<br />';
	echo $this->html->link('Login', array('action' => 'login'));
	echo $this->form->end();