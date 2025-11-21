<?php
if ( ! defined('ABSPATH') ) {
	exit;
} ?>

<div class="login-section">
	<div class="ts-login-head">
		<span class="vx-step-title"><?= _x( 'Authenticator is enabled!', 'auth', 'voxel' ) ?></span>
	</div>

	<div class="ts-form-group">
		<label><?= _x( 'Two factor authentication is enabled when logging in to your account.', 'auth', 'voxel' ) ?></label>
	</div>



	<div class="ts-form-group">
		<label><?= _x( 'Trusted devices:', 'auth', 'voxel' ) ?> {{ config.twofa.trusted_devices_count }}</label>
		<label><?= _x( 'Backup codes remaining:', 'auth', 'voxel' ) ?> {{ config.twofa.backup_codes_count }}</label>
	</div>

	<div class="ts-form-group">
		<a href="#" @click.prevent="regenerateBackupCodes" class="ts-btn ts-btn-1 ts-btn-large" :class="{'vx-pending': pending}">
			<?= \Voxel\get_icon_markup( $this->get_settings_for_display('ts_cloud') ) ?: \Voxel\svg( 'cloud.svg' ) ?>
			<?= _x( 'Generate new backup codes', 'auth', 'voxel' ) ?>
		</a>
	</div>

	<div class="ts-form-group" v-if="config.twofa.trusted_devices_count > 0">
		<a href="#" @click.prevent="removeAllTrustedDevices" class="ts-btn ts-btn-1 ts-btn-large" :class="{'vx-pending': pending}">
			<?= \Voxel\get_icon_markup( $this->get_settings_for_display('ts_device') ) ?: \Voxel\svg( 'laptop-2.svg' ) ?>
			<?= _x( 'Remove all trusted devices', 'auth', 'voxel' ) ?>
		</a>
	</div>

	<div class="ts-form-group">
		<a href="#" @click.prevent="screen = 'security_2fa_disable'" class="ts-btn ts-btn-1 ts-btn-large">
			<?= \Voxel\get_icon_markup( $this->get_settings_for_display('ts_trash') ) ?: \Voxel\svg( 'trash-can.svg' ) ?>
			<?= _x( 'Disable 2FA', 'auth', 'voxel' ) ?>
		</a>
	</div>
    <div class="login-section">
	<div class="ts-form-group">
		<a href="#" @click.prevent="screen = 'security'" class="ts-btn ts-btn-1 ts-btn-large">
			<?= \Voxel\get_icon_markup( $this->get_settings_for_display('ts_chevron_left') ) ?: \Voxel\svg( 'chevron-left.svg' ) ?>
			<?= __( 'Go back', 'voxel' ) ?>
		</a>
	</div>
</div>

</div>


