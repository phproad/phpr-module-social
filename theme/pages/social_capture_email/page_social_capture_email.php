<div id="email_confirmation">
	<?= form_open() ?>
		<input type="hidden" name="redirect" value="<?= root_url('') ?>" />
		<input type="hidden" name="social_email_confirmation" value="1" />
		
		<?= flash_message() ?>

		<div class="page-header">
			<h2><?=__('Confirm email')?></h2>
			<p><?=__("You're almost done. Please confirm your email address.")?></p>
		</div>

		<fieldset class="form-horizontal">
			<div class="control-group">
				<label for="signup_email" class="control-label">Email</label>
				<div class="controls">
					<input id="signup_email" type="text" name="email" value="<? post('email', '') ?>" class="text" />
				</div>
			</div>

			<div class="form-actions">
				<a href="javascript:;" class="btn btn-large btn-success"
					onclick="return $(this).phpr().post('social:on_confirm_email').send()">Submit</a>
			</div>
		</fieldset>

	<?= form_close() ?>
</div>