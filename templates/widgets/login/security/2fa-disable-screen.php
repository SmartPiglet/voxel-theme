<?php
if ( ! defined('ABSPATH') ) {
	exit;
} ?>

<div class="login-section">
	<div class="ts-login-head">
		<span class="vx-step-title"><?= _x( 'Disable Two-Factor Authentication', 'auth', 'voxel' ) ?></span>
	</div>


	<div class="ts-form-group">
		<label><?= _x( 'Confirm your password', 'auth', 'voxel' ) ?></label>
		<div class="ts-input-icon flexify">
			<?= \Voxel\get_icon_markup( $this->get_settings_for_display('auth_pass_ico') ) ?: \Voxel\svg( 'lock-alt.svg' ) ?>
			<input class="ts-filter" type="password" v-model="twofa.disable_password" placeholder="<?= esc_attr_x( 'Enter your password', 'auth', 'voxel' ) ?>" required>
		</div>
	</div>

	<div class="ts-form-group">
		<a href="#" @click.prevent="disable2fa" class="ts-btn ts-btn-2 ts-btn-large" :class="{'vx-pending': pending}" >
			<?= \Voxel\get_icon_markup( $this->get_settings_for_display('ts_trash') ) ?: \Voxel\svg( 'trash.svg' ) ?>
			<?= _x( 'Disable 2FA', 'auth', 'voxel' ) ?>
		</a>
	</div>
    <div class="ts-form-group">
		<a href="#" @click.prevent="screen = 'security_2fa_manage'" class="ts-btn ts-btn-1 ts-btn-large">
			<?= \Voxel\get_icon_markup( $this->get_settings_for_display('ts_chevron_left') ) ?: \Voxel\svg( 'chevron-left.svg' ) ?>
			<?= __( 'Go back', 'voxel' ) ?>
		</a>
	</div>
</div>



