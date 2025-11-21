<?php
if ( ! defined('ABSPATH') ) {
	exit;
} ?>

<div class="login-section">
	<div class="ts-login-head">
		<span class="vx-step-title"><?= _x( 'Enable Two-Factor Authentication', 'auth', 'voxel' ) ?></span>
	</div>

	<div class="ts-form-group">
		<label><?= _x( '1. Scan the QR code below with your preferred authenticator app to set up two-factor authentication.', 'auth', 'voxel' ) ?></label>
	</div>

	<div v-if="twofa.qr_code" class="ts-form-group qr-code-image">
		<img :src="twofa.qr_code" alt="<?= esc_attr_x( 'QR Code', 'auth', 'voxel' ) ?>" >
	</div>

	<!-- <div v-if="twofa.secret" class="ts-form-group">
		<label><?= _x( 'Or manually add this code in your authenticator app.', 'auth', 'voxel' ) ?></label>
		<div class="ts-input-icon flexify">
			<?= \Voxel\get_icon_markup( $this->get_settings_for_display('shield_ico') ) ?: \Voxel\svg( 'shield.svg' ) ?>
			<input class="ts-filter" type="text" :value="twofa.secret" readonly @click="$event.target.select()">
		</div>
	</div> -->

	<div class="ts-form-group">
		<label><?= _x( '2. Enter the 6-digit code from your authenticator app to verify the setup.', 'auth', 'voxel' ) ?></label>
		<div class="ts-input-icon flexify">
			<?= \Voxel\get_icon_markup( $this->get_settings_for_display('auth_pass_ico') ) ?: \Voxel\svg( 'lock-alt.svg' ) ?>
			<input class="ts-filter" type="text" v-model="twofa.verify_code" placeholder="<?= esc_attr_x( 'Enter 6-digit code', 'auth', 'voxel' ) ?>" pattern="[0-9]{6}" maxlength="6" required @keyup.enter="submit2faSetup">
		</div>
	</div>

	<div class="ts-form-group">
		<button type="button" @click="submit2faSetup" class="ts-btn ts-btn-2 ts-btn-large" :class="{'vx-pending': pending}">
			<?= \Voxel\get_icon_markup( $this->get_settings_for_display('ts_privacy') ) ?: \Voxel\svg( 'shield.svg' ) ?>
			<?= _x( 'Enable 2FA', 'auth', 'voxel' ) ?>
		</button>
	</div>
</div>

<div class="login-section">
	<div class="ts-form-group">
		<a href="#" @click.prevent="screen = 'security'" class="ts-btn ts-btn-1 ts-btn-large">
			<?= \Voxel\get_icon_markup( $this->get_settings_for_display('ts_chevron_left') ) ?: \Voxel\svg( 'chevron-left.svg' ) ?>
			<?= __( 'Go back', 'voxel' ) ?>
		</a>
	</div>
</div>

