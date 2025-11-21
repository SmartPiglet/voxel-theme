<?php
if ( ! defined('ABSPATH') ) {
	exit;
} ?>

<div class="login-section">
	<div class="ts-login-head">
		<span class="vx-step-title"><?= _x( 'Two-Factor Authentication', 'auth', 'voxel' ) ?></span>
	</div>

	<div class="ts-form-group">
		<label><?= _x( 'Enter the verification code from your authenticator app.', 'auth', 'voxel' ) ?></label>
	</div>

	<div class="ts-form-group">
		<label>{{ login2fa.use_backup ? '<?= esc_js( _x( 'Backup Code', 'auth', 'voxel' ) ) ?>' : '<?= esc_js( _x( 'Verification Code', 'auth', 'voxel' ) ) ?>' }}</label>
		<div class="ts-input-icon flexify">
			<?= \Voxel\get_icon_markup( $this->get_settings_for_display('auth_pass_ico') ) ?: \Voxel\svg( 'lock-alt.svg' ) ?>
			<input 
				class="ts-filter autofocus" 
				:type="login2fa.use_backup ? 'text' : 'text'" 
				v-model="login2fa.code" 
				:placeholder="login2fa.use_backup ? '<?= esc_attr_x( 'Enter backup code', 'auth', 'voxel' ) ?>' : '<?= esc_attr_x( 'Enter 6-digit code', 'auth', 'voxel' ) ?>'" 
				:pattern="login2fa.use_backup ? '[A-Z0-9]{8}' : '[0-9]{6}'" 
				:maxlength="login2fa.use_backup ? 8 : 6" 
				required
				autocomplete="off"
				@keyup.enter="submit2faVerification"
				@input="((login2fa.use_backup && login2fa.code.length === 8) || (!login2fa.use_backup && login2fa.code.length === 6)) && submit2faVerification()"
			>
		</div>
	</div>

	<div class="ts-form-group tos-group">
		<div class="ts-checkbox-container">
			<label class="container-checkbox">
				<input type="checkbox" v-model="login2fa.trust_device">
				<span class="checkmark"></span>
			</label>
		</div>
		<p class="field-info" @click.prevent="login2fa.trust_device = !login2fa.trust_device">
			<?= _x( 'Trust this device for 30 days', 'auth', 'voxel' ) ?>
		</p>
	</div>

	<div class="ts-form-group">
		<a href="#" @click.prevent="submit2faVerification" class="ts-btn ts-btn-2 ts-btn-large" :class="{'vx-pending': pending}">
			<?= \Voxel\get_icon_markup( $this->get_settings_for_display('auth_user_ico') ) ?: \Voxel\svg( 'shield-check.svg' ) ?>
			<?= _x( 'Verify', 'auth', 'voxel' ) ?>
		</a>
	</div>

	<div class="ts-form-group">
		<p class="field-info">
			<a class="ts-btn ts-btn-1 ts-btn-large" href="#" @click.prevent="login2fa.use_backup = !login2fa.use_backup; login2fa.code = '';">
				{{ login2fa.use_backup ? '<?= esc_js( _x( 'Use authenticator code', 'auth', 'voxel' ) ) ?>' : '<?= esc_js( _x( 'Use backup code instead', 'auth', 'voxel' ) ) ?>' }}
			</a>
		</p>
	</div>
</div>

