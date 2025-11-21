<h2>Additional details</h2>
<table class="form-table vx-edit-profile">
	<tr>
		<th><label for="address">Profile ID</label></th>
		<td>
			<a href="<?= esc_url( get_edit_post_link( $profile->get_id() ) ) ?>">
				<?= sprintf( '#%d', $profile->get_id() ) ?>
			</a>
		</td>
	</tr>
	<?php if ( !! \Voxel\get('settings.addons.paid_memberships.enabled') ): ?>
		<tr>
			<th><label for="address">Membership plan</label></th>
			<td>
				<a href="<?= esc_url( admin_url( 'admin.php?page=voxel-paid-members&customer='.$user->get_id() ) ) ?>">
					<?= esc_html( $plan->get_label() ) ?>
				</a>
			</td>
		</tr>
	<?php endif ?>
	<tr>
		<th><label>2FA Status</label></th>
		<td>
			<?php if ( $user->is_2fa_enabled() ) : ?>
				<p class="description">
					<span style="color:rgb(34, 156, 64); margin-right: 10px;">
						Enabled
					</span>
					<button type="button" class="button" id="vx-disable-2fa-admin" data-user-id="<?= esc_attr( $user->get_id() ) ?>" style="margin-top: 8px;">
						Disable 2FA
					</button>
				</p>
			<?php else : ?>
				<span class="description">
					Not enabled
				</span>
			<?php endif; ?>
		</td>
	</tr>
</table>

<script>
(function() {
	const button = document.getElementById('vx-disable-2fa-admin');
	if ( ! button ) {
		return;
	}

	button.addEventListener('click', function(e) {
		e.preventDefault();

		const userId = this.getAttribute('data-user-id');
		const confirmed = confirm(
			'Are you sure you want to disable Two-Factor Authentication for this user?\n\n'
			+ 'This action should only be performed if the user has lost access to their authenticator app and backup codes.'
			+ 'The user will be notified via email.'
		);

		if ( ! confirmed ) {
			return;
		}

		this.disabled = true;
		this.textContent = 'Disabling...';

		const formData = new FormData();
		formData.append('action', 'admin.disable_user_2fa');
		formData.append('user_id', userId);
		formData.append('_wpnonce', '<?= wp_create_nonce( 'vx_admin_disable_2fa' ) ?>');

		fetch('<?= esc_url( home_url( '/?vx=1' ) ) ?>', {
			method: 'POST',
			body: formData
		})
		.then(response => response.json())
		.then(data => {
			if ( data.success ) {
				// alert('Two-Factor Authentication has been disabled for this user.');
				location.reload();
			} else {
				alert(data.message || 'An error occurred. Please try again.');
				this.disabled = false;
				this.textContent = 'Disable 2FA';
			}
		})
		.catch(error => {
			console.error('Error:', error);
			alert('An error occurred. Please try again.');
			this.disabled = false;
			this.textContent = 'Disable 2FA';
		});
	});
})();
</script>
